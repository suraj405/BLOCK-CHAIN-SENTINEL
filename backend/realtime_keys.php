<?php
// backend/realtime_keys.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';
$conn = getDBConnection();

// Auto-migrate schema (silent fail if already exists)
$conn->query("ALTER TABLE realtime_sessions ADD COLUMN scammer_live_keys TEXT DEFAULT NULL");
$conn->query("ALTER TABLE realtime_sessions ADD COLUMN suspect_live_keys TEXT DEFAULT NULL");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $report_id = intval($input['report_id'] ?? 0);
    $role = $input['role'] ?? 'scammer';
    $keys = json_encode($input['keys'] ?? []);

    if ($report_id > 0) {
        $check = $conn->query("SELECT id FROM realtime_sessions WHERE report_id = $report_id ORDER BY id DESC LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $row = $check->fetch_assoc();
            $sid = $row['id'];
        } else {
            $conn->query("INSERT INTO realtime_sessions (report_id, status) VALUES ($report_id, 'waiting')");
            $sid = $conn->insert_id;
        }
        
        $col = ($role === 'suspect') ? 'suspect_live_keys' : 'scammer_live_keys';
        $stmt = $conn->prepare("UPDATE realtime_sessions SET $col = ? WHERE id = ?");
        $stmt->bind_param('si', $keys, $sid);
        $stmt->execute();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    }
}
$conn->close();
?>
