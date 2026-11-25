<?php
// backend/behavioral_scan.php
// Receives behavioral biometric data (keystroke timings, typing rhythm)
// Computes a behavioral hash and stores/compares alongside device fingerprint

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once 'config.php';
$conn = getDBConnection();

// Ensure behavioral columns exist in realtime_sessions
$conn->query("ALTER TABLE realtime_sessions ADD COLUMN IF NOT EXISTS scammer_behavioral_hash VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE realtime_sessions ADD COLUMN IF NOT EXISTS suspect_behavioral_hash  VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE realtime_sessions ADD COLUMN IF NOT EXISTS scammer_typing_profile   TEXT DEFAULT NULL");
$conn->query("ALTER TABLE realtime_sessions ADD COLUMN IF NOT EXISTS suspect_typing_profile    TEXT DEFAULT NULL");
$conn->query("ALTER TABLE realtime_sessions ADD COLUMN IF NOT EXISTS behavioral_match_score    FLOAT DEFAULT NULL");
$conn->query("ALTER TABLE realtime_sessions ADD COLUMN IF NOT EXISTS behavioral_verdict        VARCHAR(50) DEFAULT NULL");

// ─── GET: retrieve behavioral comparison ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $report_id = intval($_GET['report_id'] ?? 0);
    $stmt = $conn->prepare("SELECT * FROM realtime_sessions WHERE report_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('i', $report_id);
    $stmt->execute();
    $s = $stmt->get_result()->fetch_assoc();

    if (!$s) {
        echo json_encode(['status' => 'waiting', 'message' => 'No session yet']);
        $conn->close(); exit;
    }

    $resp = [
        'status'                    => $s['status'],
        'scammer_behavioral_scanned'=> !empty($s['scammer_behavioral_hash']),
        'suspect_behavioral_scanned'=> !empty($s['suspect_behavioral_hash']),
        'behavioral_match_score'    => $s['behavioral_match_score'],
        'behavioral_verdict'        => $s['behavioral_verdict'],
    ];

    if (!empty($s['scammer_typing_profile'])) {
        $tp = json_decode($s['scammer_typing_profile'], true);
        $resp['scammer_profile_summary'] = [
            'avg_hold_ms'   => $tp['avg_hold_ms']   ?? null,
            'avg_flight_ms' => $tp['avg_flight_ms']  ?? null,
            'typing_speed'  => $tp['typing_speed']   ?? null,
            'backspace_rate'=> $tp['backspace_rate']  ?? null,
            'rhythm_cv'     => $tp['rhythm_cv']       ?? null,
        ];
    }
    if (!empty($s['suspect_typing_profile'])) {
        $tp = json_decode($s['suspect_typing_profile'], true);
        $resp['suspect_profile_summary'] = [
            'avg_hold_ms'   => $tp['avg_hold_ms']   ?? null,
            'avg_flight_ms' => $tp['avg_flight_ms']  ?? null,
            'typing_speed'  => $tp['typing_speed']   ?? null,
            'backspace_rate'=> $tp['backspace_rate']  ?? null,
            'rhythm_cv'     => $tp['rhythm_cv']       ?? null,
        ];
    }

    echo json_encode($resp);
    $conn->close(); exit;
}

// ─── POST: receive behavioral data ─────────────────────────────────────────
$input     = json_decode(file_get_contents('php://input'), true);
$report_id = intval($input['report_id'] ?? 0);
$role      = in_array($input['role'] ?? '', ['scammer','suspect']) ? $input['role'] : 'scammer';
$bdata     = $input['behavioral'] ?? [];

if (empty($bdata)) {
    echo json_encode(['success' => false, 'message' => 'No behavioral data']);
    $conn->close(); exit;
}

// ── Compute behavioral signature ────────────────────────────────────────────
// Extracts statistical features from raw keystroke events
// Each feature is bucketed into bins so minor natural variation doesn't break matching
// but hardware changes (different keyboard, phone vs PC) are clearly detected

$hold_times   = array_filter(array_map('floatval', $bdata['hold_times']   ?? []), fn($v) => $v > 0 && $v < 2000);
$flight_times = array_filter(array_map('floatval', $bdata['flight_times'] ?? []), fn($v) => $v > 0 && $v < 3000);
$key_sequence = $bdata['key_sequence'] ?? [];   // anonymised key categories not actual letters
$total_keys   = intval($bdata['total_keys']   ?? 0);
$backspaces   = intval($bdata['backspaces']   ?? 0);
$total_time_ms= floatval($bdata['total_time_ms'] ?? 1);
$field_order  = $bdata['field_order'] ?? [];    // which fields typed in which order

function stats(array $arr): array {
    if (empty($arr)) return ['mean'=>0,'std'=>0,'cv'=>0,'med'=>0];
    $arr   = array_values($arr);
    $n     = count($arr);
    $mean  = array_sum($arr) / $n;
    $var   = array_sum(array_map(fn($x) => ($x-$mean)**2, $arr)) / max($n,1);
    $std   = sqrt($var);
    sort($arr);
    $med   = $n % 2 === 0 ? ($arr[$n/2-1]+$arr[$n/2])/2 : $arr[floor($n/2)];
    $cv    = $mean > 0 ? round($std/$mean, 4) : 0;
    return ['mean'=>round($mean,1),'std'=>round($std,1),'cv'=>$cv,'med'=>round($med,1)];
}

$hold_stats   = stats(array_values($hold_times));
$flight_stats = stats(array_values($flight_times));
$typing_speed = $total_time_ms > 0 ? round($total_keys / ($total_time_ms / 60000), 1) : 0; // WPM approx
$backspace_rate = $total_keys > 0 ? round($backspaces / $total_keys, 3) : 0;

// Build profile for storage
$profile = [
    'avg_hold_ms'   => $hold_stats['mean'],
    'std_hold_ms'   => $hold_stats['std'],
    'avg_flight_ms' => $flight_stats['mean'],
    'std_flight_ms' => $flight_stats['std'],
    'typing_speed'  => $typing_speed,
    'backspace_rate'=> $backspace_rate,
    'rhythm_cv'     => $flight_stats['cv'],         // coefficient of variation = typing regularity
    'total_keys'    => $total_keys,
    'field_order'   => implode(',', array_slice($field_order, 0, 5)),
];

// ── Behavioral hash: bin features so small natural variation doesn't matter ─
// Each metric is rounded to the nearest step before hashing.
// A person typing 80ms hold avg vs 83ms avg → same bin (step=10ms).
// A different person or different device will fall in a clearly different bin.
function bin(float $val, float $step): int {
    return (int) round($val / $step);
}

$hash_features = [
    'hold_bin'       => bin($hold_stats['mean'],   10),   // ±10ms tolerance
    'flight_bin'     => bin($flight_stats['mean'],  15),  // ±15ms tolerance
    'rhythm_bin'     => bin($flight_stats['cv'],   0.05), // ±0.05 CV tolerance
    'speed_bin'      => bin($typing_speed,          5),   // ±5 WPM tolerance
    'backspace_bin'  => bin($backspace_rate,        0.05),
];

$behavioral_hash = hash('sha256', json_encode($hash_features));

// ─── Store into DB ──────────────────────────────────────────────────────────
$profile_json = json_encode($profile);

$stmt = $conn->prepare("SELECT * FROM realtime_sessions WHERE report_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param('i', $report_id);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();

if (!$session) {
    $stmt2 = $conn->prepare("INSERT INTO realtime_sessions (report_id, status) VALUES (?, 'waiting')");
    $stmt2->bind_param('i', $report_id);
    $stmt2->execute();
    $session_id = $conn->insert_id;
    $session    = ['id' => $session_id];
} else {
    $session_id = $session['id'];
}

$behavioral_verdict = null;
$match_score        = null;

if ($role === 'scammer') {
    $st = $conn->prepare("UPDATE realtime_sessions SET scammer_behavioral_hash=?, scammer_typing_profile=? WHERE id=?");
    $st->bind_param('ssi', $behavioral_hash, $profile_json, $session_id);
    $st->execute();

} elseif ($role === 'suspect') {
    $st = $conn->prepare("UPDATE realtime_sessions SET suspect_behavioral_hash=?, suspect_typing_profile=? WHERE id=?");
    $st->bind_param('ssi', $behavioral_hash, $profile_json, $session_id);
    $st->execute();

    // Re-fetch session to get latest scammer profile (it may have arrived before us)
    $stmt_refetch = $conn->prepare("SELECT * FROM realtime_sessions WHERE id=?");
    $stmt_refetch->bind_param('i', $session_id);
    $stmt_refetch->execute();
    $session = $stmt_refetch->get_result()->fetch_assoc();

    // Compute similarity if scammer profile exists
    if (!empty($session['scammer_typing_profile'])) {
        $sp = json_decode($session['scammer_typing_profile'], true);

        function feature_sim(float $a, float $b, float $tolerance): float {
            if ($tolerance == 0) return $a == $b ? 1.0 : 0.0;
            $diff = abs($a - $b);
            return max(0.0, 1.0 - ($diff / ($tolerance * 4)));
        }

        $scores = [
            feature_sim((float)($sp['avg_hold_ms']    ?? 0), (float)$profile['avg_hold_ms'],    20)  * 0.25,
            feature_sim((float)($sp['avg_flight_ms']  ?? 0), (float)$profile['avg_flight_ms'],  30)  * 0.25,
            feature_sim((float)($sp['rhythm_cv']      ?? 0), (float)$profile['rhythm_cv'],      0.1) * 0.20,
            feature_sim((float)($sp['typing_speed']   ?? 0), (float)$profile['typing_speed'],   10)  * 0.15,
            feature_sim((float)($sp['backspace_rate'] ?? 0), (float)$profile['backspace_rate'], 0.1) * 0.15,
        ];

        $match_score = round(array_sum($scores) * 100, 1);

        if      ($match_score >= 80) $behavioral_verdict = 'HIGH_MATCH';
        else if ($match_score >= 55) $behavioral_verdict = 'PROBABLE_MATCH';
        else if ($match_score >= 35) $behavioral_verdict = 'UNCERTAIN';
        else                         $behavioral_verdict = 'NO_MATCH';

        $st2 = $conn->prepare("UPDATE realtime_sessions SET behavioral_match_score=?, behavioral_verdict=? WHERE id=?");
        $st2->bind_param('dsi', $match_score, $behavioral_verdict, $session_id);
        $st2->execute();
    }
}

echo json_encode([
    'success'           => true,
    'role'              => $role,
    'behavioral_hash'   => $behavioral_hash,
    'profile'           => $profile,
    'match_score'       => $match_score,
    'behavioral_verdict'=> $behavioral_verdict,
    'report_id'         => $report_id,
]);
$conn->close();
?>
