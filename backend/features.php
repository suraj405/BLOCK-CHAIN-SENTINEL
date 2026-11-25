<?php
// backend/features.php — All 6 new features API
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$conn   = getDBConnection();

// ─────────────────────────────────────────────────────────────────────
// 1. FRAUD RISK SCORE
// ─────────────────────────────────────────────────────────────────────
if ($action === 'risk_score') {
    $report_id = intval($_GET['report_id'] ?? 0);
    $stmt = $conn->prepare("SELECT * FROM reports WHERE id=?");
    $stmt->bind_param('i', $report_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if (!$r) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }

    $score = 0;
    $factors = [];

    // Platform risk
    $platRisk = ['WhatsApp'=>20,'Telegram'=>22,'Instagram'=>18,'Facebook'=>15,'LinkedIn'=>12,'Twitter'=>14,'TikTok'=>10,'YouTube'=>8,'Snapchat'=>10];
    $pScore = $platRisk[$r['platform']] ?? 10;
    $score += $pScore;
    $factors[] = ['label'=>'Platform Risk ('.$r['platform'].')','score'=>$pScore,'max'=>22];

    // Division risk
    $divRisk = ['Crypto Scam'=>25,'Identity Fraud'=>24,'Investment Scam'=>22,'Money Scam'=>20,'Digital Scam'=>18];
    $dScore = $divRisk[$r['division']] ?? 15;
    $score += $dScore;
    $factors[] = ['label'=>'Scam Type ('.$r['division'].')','score'=>$dScore,'max'=>25];

    // Description keyword risk
    $highRisk = ['bitcoin','crypto','wallet','seed phrase','bank','otp','kyc','invest','guarantee','profit','urgent','emergency','transfer','wire'];
    $desc = strtolower($r['description']);
    $kwHits = 0;
    foreach ($highRisk as $kw) { if (strpos($desc,$kw)!==false) $kwHits++; }
    $kwScore = min($kwHits * 4, 20);
    $score += $kwScore;
    $factors[] = ['label'=>'High-Risk Keywords ('.$kwHits.' found)','score'=>$kwScore,'max'=>20];

    // Suspect identified
    if ($r['suspect_username']) { $score += 15; $factors[] = ['label'=>'Suspect Identified','score'=>15,'max'=>15]; }
    else { $factors[] = ['label'=>'No Suspect — Unknown Actor','score'=>0,'max'=>15]; }

    // Blockchain recorded
    if ($r['blockchain_tx']) { $score += 8; $factors[] = ['label'=>'Blockchain Evidence','score'=>8,'max'=>8]; }

    // Repeat offender check
    $hashCheck = $conn->prepare("SELECT COUNT(*) as cnt FROM fingerprint_hashes WHERE hash=?");
    $hashCheck->bind_param('s', $r['fingerprint_hash'] ?? '');
    $hashCheck->execute();
    $hRow = $hashCheck->get_result()->fetch_assoc();
    if ($hRow['cnt'] > 0) { $score += 10; $factors[] = ['label'=>'Known Hash in Database','score'=>10,'max'=>10]; }

    $score = min($score, 100);
    $level = $score >= 80 ? 'CRITICAL' : ($score >= 60 ? 'HIGH' : ($score >= 40 ? 'MEDIUM' : 'LOW'));
    $color = $score >= 80 ? '#ff006e' : ($score >= 60 ? '#ff6b00' : ($score >= 40 ? '#ffe600' : '#39ff14'));

    echo json_encode(['success'=>true,'score'=>$score,'level'=>$level,'color'=>$color,'factors'=>$factors]);

// ─────────────────────────────────────────────────────────────────────
// 2. REPEAT OFFENDER CHECK
// ─────────────────────────────────────────────────────────────────────
} elseif ($action === 'repeat_check') {
    $report_id = intval($_GET['report_id'] ?? 0);
    $stmt = $conn->prepare("SELECT * FROM reports WHERE id=?");
    $stmt->bind_param('i', $report_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if (!$r) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }

    $matches = [];

    // Check by fingerprint hash
    if ($r['fingerprint_hash']) {
        $stmt2 = $conn->prepare("SELECT id,scam_username,platform,division,created_at FROM reports WHERE fingerprint_hash=? AND id!=?");
        $stmt2->bind_param('si', $r['fingerprint_hash'], $report_id);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        while ($row = $res2->fetch_assoc()) {
            $matches[] = array_merge($row, ['match_type'=>'FINGERPRINT MATCH']);
        }
    }

    // Check by scam_username across platforms
    if ($r['scam_username']) {
        $stmt3 = $conn->prepare("SELECT id,scam_username,platform,division,created_at FROM reports WHERE scam_username=? AND id!=?");
        $stmt3->bind_param('si', $r['scam_username'], $report_id);
        $stmt3->execute();
        $res3 = $stmt3->get_result();
        while ($row = $res3->fetch_assoc()) {
            $matches[] = array_merge($row, ['match_type'=>'USERNAME MATCH']);
        }
    }

    // Check fingerprint_hashes table
    $knownHash = false;
    if ($r['fingerprint_hash']) {
        $stmt4 = $conn->prepare("SELECT label,blockchain_tx,created_at FROM fingerprint_hashes WHERE hash=? LIMIT 1");
        $stmt4->bind_param('s', $r['fingerprint_hash']);
        $stmt4->execute();
        $hRow = $stmt4->get_result()->fetch_assoc();
        if ($hRow) { $knownHash = $hRow; }
    }

    echo json_encode([
        'success'    => true,
        'is_repeat'  => count($matches) > 0 || $knownHash !== false,
        'matches'    => $matches,
        'known_hash' => $knownHash,
        'total'      => count($matches),
    ]);

// ─────────────────────────────────────────────────────────────────────
// 3. EVIDENCE PDF DATA (returns structured data; PDF generated client-side)
// ─────────────────────────────────────────────────────────────────────
} elseif ($action === 'case_data') {
    $report_id = intval($_GET['report_id'] ?? 0);
    $stmt = $conn->prepare("SELECT * FROM reports WHERE id=?");
    $stmt->bind_param('i', $report_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if (!$r) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }

    // Get scan session
    $stmt2 = $conn->prepare("SELECT * FROM realtime_sessions WHERE report_id=? ORDER BY id DESC LIMIT 1");
    $stmt2->bind_param('i', $report_id);
    $stmt2->execute();
    $session = $stmt2->get_result()->fetch_assoc();

    echo json_encode(['success'=>true,'report'=>$r,'session'=>$session,'generated'=>date('Y-m-d H:i:s')]);

// ─────────────────────────────────────────────────────────────────────
// 4. GEO INTELLIGENCE
// ─────────────────────────────────────────────────────────────────────
} elseif ($action === 'geo_intel') {
    $report_id = intval($_GET['report_id'] ?? 0);
    $stmt = $conn->prepare("SELECT * FROM realtime_sessions WHERE report_id=? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('i', $report_id);
    $stmt->execute();
    $s = $stmt->get_result()->fetch_assoc();

    // Also get report
    $stmt2 = $conn->prepare("SELECT * FROM reports WHERE id=?");
    $stmt2->bind_param('i', $report_id);
    $stmt2->execute();
    $r = $stmt2->get_result()->fetch_assoc();

    // Parse timezone from scammer UA / session meta if stored
    // We decode stored fingerprint data if available
    $geoData = [
        'scammer' => ['timezone'=>'Unknown','region'=>'Unknown','country'=>'Unknown','lat'=>null,'lng'=>null,'language'=>'Unknown','device'=>'Unknown'],
        'suspect' => ['timezone'=>'Unknown','region'=>'Unknown','country'=>'Unknown','lat'=>null,'lng'=>null,'language'=>'Unknown','device'=>'Unknown'],
    ];

    // Map timezones to regions/countries
    $tzMap = [
        'Asia/Kolkata'        => ['India','IN',20.59,78.96],
        'Asia/Dubai'          => ['UAE','AE',23.42,53.85],
        'Asia/Kuala_Lumpur'   => ['Malaysia','MY',4.21,101.97],
        'Asia/Singapore'      => ['Singapore','SG',1.35,103.82],
        'Asia/Jakarta'        => ['Indonesia','ID',-0.79,113.92],
        'Asia/Karachi'        => ['Pakistan','PK',30.38,69.35],
        'Asia/Dhaka'          => ['Bangladesh','BD',23.68,90.36],
        'Asia/Manila'         => ['Philippines','PH',12.88,121.77],
        'Europe/London'       => ['United Kingdom','GB',55.38,-3.44],
        'Europe/Paris'        => ['France','FR',46.23,2.21],
        'Europe/Berlin'       => ['Germany','DE',51.17,10.45],
        'America/New_York'    => ['USA (East)','US',37.09,-95.71],
        'America/Chicago'     => ['USA (Central)','US',37.09,-95.71],
        'America/Los_Angeles' => ['USA (West)','US',37.09,-95.71],
        'Africa/Lagos'        => ['Nigeria','NG',9.08,8.68],
        'Africa/Nairobi'      => ['Kenya','KE',0.02,37.91],
        'Australia/Sydney'    => ['Australia','AU',-25.27,133.78],
    ];

    foreach (['scammer','suspect'] as $role) {
        $uaField = $role.'_ua';
        $ua = $s[$uaField] ?? '';
        if ($ua) {
            // Try to extract timezone from stored UA JSON
            $decoded = json_decode($ua, true);
            if ($decoded && isset($decoded['timezone'])) {
                $tz = $decoded['timezone'];
                if (isset($tzMap[$tz])) {
                    [$country,$code,$lat,$lng] = $tzMap[$tz];
                    $geoData[$role] = [
                        'timezone' => $tz,
                        'country'  => $country,
                        'code'     => $code,
                        'lat'      => $lat,
                        'lng'      => $lng,
                        'language' => $decoded['language'] ?? 'Unknown',
                        'device'   => $decoded['browser'] ? substr($decoded['browser'],0,60) : 'Unknown',
                        'screen'   => $decoded['screen_resolution'] ?? 'Unknown',
                        'memory'   => $decoded['device_memory'] ?? 'Unknown',
                    ];
                } else {
                    $geoData[$role]['timezone'] = $tz;
                    $geoData[$role]['country']  = 'Unknown ('.$tz.')';
                }
            }
        }
    }

    echo json_encode(['success'=>true,'geo'=>$geoData,'report_id'=>$report_id,'has_session'=>!empty($s)]);

// ─────────────────────────────────────────────────────────────────────
// 5. VICTIM ALERT CARD DATA
// ─────────────────────────────────────────────────────────────────────
} elseif ($action === 'alert_card') {
    $report_id = intval($_GET['report_id'] ?? 0);
    $stmt = $conn->prepare("SELECT * FROM reports WHERE id=?");
    $stmt->bind_param('i', $report_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if (!$r) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }

    $caseId = 'BS-'.str_pad($r['id'],6,'0',STR_PAD_LEFT);
    $alert = [
        'case_id'     => $caseId,
        'username'    => $r['scam_username'],
        'platform'    => $r['platform'],
        'division'    => $r['division'],
        'description' => substr($r['description'],0,120).'...',
        'hash_preview'=> $r['fingerprint_hash'] ? substr($r['fingerprint_hash'],0,16).'...' : 'N/A',
        'blockchain'  => $r['blockchain_tx'] ?? 'N/A',
        'date'        => $r['created_at'],
        'message_wa'  => '⚠ *SCAM ALERT — Case '.$caseId.'*'."\n\n"
                        .'🚨 Username: @'.$r['scam_username']."\n"
                        .'📱 Platform: '.$r['platform']."\n"
                        .'⚠ Type: '.$r['division']."\n\n"
                        .'Description: '.substr($r['description'],0,100)."\n\n"
                        .'🔗 Blockchain TX: '.($r['blockchain_tx']??'pending')."\n"
                        .'🛡 Do NOT engage with this account.',
        'message_text'=> 'SCAM ALERT ['.$caseId.'] @'.$r['scam_username'].' on '.$r['platform'].' — '.$r['division'].'. '.$r['blockchain_tx'],
    ];
    echo json_encode(['success'=>true,'alert'=>$alert]);

// ─────────────────────────────────────────────────────────────────────
// 6. CROSS-PLATFORM USERNAME SEARCH
// ─────────────────────────────────────────────────────────────────────
} elseif ($action === 'username_search') {
    $username = $conn->real_escape_string(trim($_GET['username'] ?? ''));
    if (!$username) { echo json_encode(['success'=>false,'message'=>'No username']); exit; }

    // Search in reports
    $stmt = $conn->prepare("SELECT id,scam_username,suspect_username,platform,division,created_at,fingerprint_hash FROM reports WHERE scam_username LIKE ? OR suspect_username LIKE ? ORDER BY created_at DESC");
    $like = '%'.$username.'%';
    $stmt->bind_param('ss',$like,$like);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Also search fingerprint_hashes
    $stmt2 = $conn->prepare("SELECT hash,label,blockchain_tx,created_at FROM fingerprint_hashes WHERE label LIKE ? LIMIT 5");
    $stmt2->bind_param('s',$like);
    $stmt2->execute();
    $hashes = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

    // Build platform profile URLs
    $platforms = ['Telegram','Instagram','LinkedIn','Twitter','Facebook','TikTok','YouTube','Snapchat'];
    $platUrls = [
        'Telegram'  => 'https://t.me/'.urlencode($username),
        'Instagram' => 'https://instagram.com/'.urlencode($username),
        'LinkedIn'  => 'https://linkedin.com/in/'.urlencode($username),
        'Twitter'   => 'https://twitter.com/'.urlencode($username),
        'Facebook'  => 'https://facebook.com/'.urlencode($username),
        'TikTok'    => 'https://tiktok.com/@'.urlencode($username),
        'YouTube'   => 'https://youtube.com/@'.urlencode($username),
        'Snapchat'  => 'https://snapchat.com/add/'.urlencode($username),
    ];

    echo json_encode([
        'success'      => true,
        'username'     => $username,
        'reports'      => $rows,
        'hashes'       => $hashes,
        'platform_urls'=> $platUrls,
        'total_reports'=> count($rows),
    ]);

// ─────────────────────────────────────────────────────────────────────
// 7. ANALYTICS DATA
// ─────────────────────────────────────────────────────────────────────
} elseif ($action === 'analytics') {
    // Platform breakdown
    $platRes = $conn->query("SELECT platform, COUNT(*) as count FROM reports GROUP BY platform ORDER BY count DESC");
    $platforms = $platRes->fetch_all(MYSQLI_ASSOC);

    // Division breakdown
    $divRes = $conn->query("SELECT division, COUNT(*) as count FROM reports GROUP BY division ORDER BY count DESC");
    $divisions = $divRes->fetch_all(MYSQLI_ASSOC);

    // Monthly trend
    $trendRes = $conn->query("SELECT DATE_FORMAT(created_at,'%Y-%m') as month, COUNT(*) as count FROM reports GROUP BY month ORDER BY month DESC LIMIT 6");
    $trend = array_reverse($trendRes->fetch_all(MYSQLI_ASSOC));

    // Scan completions
    $scanRes = $conn->query("SELECT match_result, COUNT(*) as count FROM realtime_sessions WHERE status='complete' GROUP BY match_result");
    $scans = $scanRes->fetch_all(MYSQLI_ASSOC);

    // Total amounts (parsed from descriptions — keyword heuristic)
    $amtRes = $conn->query("SELECT description FROM reports");
    $totalAmt = 0; $amtCount = 0;
    while ($row = $amtRes->fetch_assoc()) {
        preg_match_all('/\$([0-9,]+)/', $row['description'], $m);
        foreach ($m[1] as $a) { $totalAmt += intval(str_replace(',','',$a)); $amtCount++; }
    }

    echo json_encode([
        'success'     => true,
        'platforms'   => $platforms,
        'divisions'   => $divisions,
        'trend'       => $trend,
        'scans'       => $scans,
        'total_fraud' => $totalAmt,
        'total_reports'=> $conn->query("SELECT COUNT(*) as c FROM reports")->fetch_assoc()['c'],
        'total_hashes' => $conn->query("SELECT COUNT(*) as c FROM fingerprint_hashes")->fetch_assoc()['c'],
    ]);

} else {
    echo json_encode(['success'=>false,'message'=>'Unknown action']);
}

$conn->close();
?>
