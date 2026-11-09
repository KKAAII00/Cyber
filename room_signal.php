<?php
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

function room_signal_reply(int $status, array $data): never {
    http_response_code($status);
    echo json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$user = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    room_signal_reply(405, ['error' => 'Use POST for team room signaling.']);
}
verify_csrf();

$roomId = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT) ?: 0;
$member = $pdo->prepare('SELECT 1 FROM room_players WHERE room_id = ? AND user_id = ?');
$member->execute([$roomId, $user['id']]);
if (!$member->fetchColumn()) {
    room_signal_reply(403, ['error' => 'You are not a member of this team room.']);
}

$actionValue = $_POST['action'] ?? '';
$tokenValue = $_POST['client_token'] ?? '';
$action = is_string($actionValue) ? $actionValue : '';
$token = is_string($tokenValue) ? $tokenValue : '';
if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
    room_signal_reply(400, ['error' => 'Invalid room session token.']);
}

if ($action === 'leave') {
    $leave = $pdo->prepare('DELETE FROM room_presence WHERE room_id = ? AND user_id = ? AND client_token = ?');
    $leave->execute([$roomId, $user['id'], $token]);
    room_signal_reply(200, ['ok' => true]);
}

$cameraEnabled = ($_POST['camera_enabled'] ?? '0') === '1' ? 1 : 0;
$presence = $pdo->prepare(
    'INSERT INTO room_presence (room_id,user_id,client_token,camera_enabled,last_seen_at) VALUES (?,?,?,?,CURRENT_TIMESTAMP)
     ON DUPLICATE KEY UPDATE client_token=VALUES(client_token),camera_enabled=VALUES(camera_enabled),last_seen_at=CURRENT_TIMESTAMP'
);
$presence->execute([$roomId, $user['id'], $token, $cameraEnabled]);

if ($action === 'signal') {
    $recipientId = filter_input(INPUT_POST, 'recipient_id', FILTER_VALIDATE_INT) ?: 0;
    $typeValue = $_POST['signal_type'] ?? '';
    $payloadValue = $_POST['payload'] ?? '';
    $type = is_string($typeValue) ? $typeValue : '';
    $payload = is_string($payloadValue) ? $payloadValue : '';
    if (!in_array($type, ['offer', 'answer', 'candidate'], true) || strlen($payload) > 20000 || json_decode($payload, true) === null) {
        room_signal_reply(400, ['error' => 'Invalid or oversized video call signal.']);
    }
    $recipient = $pdo->prepare('SELECT 1 FROM room_players WHERE room_id = ? AND user_id = ?');
    $recipient->execute([$roomId, $recipientId]);
    if (!$recipient->fetchColumn() || $recipientId === (int)$user['id']) {
        room_signal_reply(403, ['error' => 'That participant is not available in this room.']);
    }
    $insert = $pdo->prepare('INSERT INTO room_signals (room_id,sender_id,sender_token,recipient_id,signal_type,payload) VALUES (?,?,?,?,?,?)');
    $insert->execute([$roomId, $user['id'], $token, $recipientId, $type, $payload]);
    room_signal_reply(200, ['ok' => true]);
}

if ($action !== 'sync') {
    room_signal_reply(400, ['error' => 'Unknown team room action.']);
}

$cursor = filter_input(INPUT_POST, 'cursor', FILTER_VALIDATE_INT);
$cursor = max(0, (int)$cursor);
$playersQuery = $pdo->prepare(
    'SELECT p.user_id,u.username,p.client_token,p.camera_enabled,p.last_seen_at
     FROM room_presence p JOIN users u ON u.id=p.user_id
     WHERE p.room_id=? AND p.last_seen_at >= (CURRENT_TIMESTAMP - INTERVAL 20 SECOND)
     ORDER BY p.last_seen_at DESC'
);
$playersQuery->execute([$roomId]);
$players = $playersQuery->fetchAll();
$signalsQuery = $pdo->prepare(
    'SELECT s.id,s.sender_id,u.username,s.signal_type,s.payload
     FROM room_signals s
     JOIN room_presence p ON p.room_id=s.room_id AND p.user_id=s.sender_id AND p.client_token=s.sender_token
     JOIN users u ON u.id=s.sender_id
     WHERE s.room_id=? AND s.recipient_id=? AND s.id>? AND p.last_seen_at >= (CURRENT_TIMESTAMP - INTERVAL 20 SECOND)
     ORDER BY s.id ASC LIMIT 100'
);
$signalsQuery->execute([$roomId, $user['id'], $cursor]);
$signals = $signalsQuery->fetchAll();
if ($signals) {
    $cursor = max($cursor, (int)end($signals)['id']);
}
$cleanup = $pdo->prepare('DELETE FROM room_signals WHERE room_id = ? AND created_at < (CURRENT_TIMESTAMP - INTERVAL 1 DAY)');
$cleanup->execute([$roomId]);
room_signal_reply(200, ['players' => $players, 'signals' => $signals, 'cursor' => $cursor]);
