<?php
// /socialnet/friend_action.php — Handles all friendship state changes
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /socialnet/signin.php');
    exit;
}

require_once __DIR__ . '/../db_config.php';

$me     = (int) $_SESSION['user_id'];
$action = trim($_POST['action'] ?? '');
$other  = (int) ($_POST['other_id'] ?? 0);

if ($other <= 0 || $other === $me) {
    header('Location: /socialnet/index.php');
    exit;
}

$db = get_db();

switch ($action) {

    // ── Send a friend request ──────────────────────────────────────────
    case 'add':
        $stmt = $db->prepare(
            'INSERT IGNORE INTO friendship (requester_id, receiver_id, status)
             VALUES (?, ?, "pending")'
        );
        $stmt->bind_param('ii', $me, $other);
        $stmt->execute();
        $stmt->close();
        break;

    // ── Cancel a request you sent (before it is accepted) ────────────
    case 'cancel':
        $stmt = $db->prepare(
            'DELETE FROM friendship
              WHERE requester_id = ? AND receiver_id = ? AND status = "pending"'
        );
        $stmt->bind_param('ii', $me, $other);
        $stmt->execute();
        $stmt->close();
        break;

    // ── Accept an incoming request ────────────────────────────────────
    case 'accept':
        $stmt = $db->prepare(
            'UPDATE friendship SET status = "accepted"
              WHERE requester_id = ? AND receiver_id = ? AND status = "pending"'
        );
        $stmt->bind_param('ii', $other, $me);
        $stmt->execute();
        $stmt->close();
        break;

    // ── Reject (decline) an incoming request ─────────────────────────
    case 'reject':
        $stmt = $db->prepare(
            'DELETE FROM friendship
              WHERE requester_id = ? AND receiver_id = ? AND status = "pending"'
        );
        $stmt->bind_param('ii', $other, $me);
        $stmt->execute();
        $stmt->close();
        break;

    // ── Unfriend an accepted connection ──────────────────────────────
    case 'unfriend':
        $stmt = $db->prepare(
            'DELETE FROM friendship
              WHERE status = "accepted"
                AND ((requester_id = ? AND receiver_id = ?)
                  OR (requester_id = ? AND receiver_id = ?))'
        );
        $stmt->bind_param('iiii', $me, $other, $other, $me);
        $stmt->execute();
        $stmt->close();
        break;
}

$db->close();
header('Location: /socialnet/index.php');
exit;
