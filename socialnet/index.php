<?php
// /socialnet/index.php — Home Page with friend-connection feature
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /socialnet/signin.php');
    exit;
}

require_once __DIR__ . '/../db_config.php';

$db = get_db();
$me = (int) $_SESSION['user_id'];

// ── 1. All accepted friends (either side) ────────────────────────────────
$sql_friends = '
    SELECT a.id, a.username, a.fullname
    FROM   account a
    JOIN   friendship f
           ON  f.status = "accepted"
           AND ((f.requester_id = ? AND f.receiver_id = a.id)
             OR (f.receiver_id  = ? AND f.requester_id = a.id))
    ORDER  BY a.fullname ASC
';
$stmt = $db->prepare($sql_friends);
$stmt->bind_param('ii', $me, $me);
$stmt->execute();
$friends = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$friend_ids = array_column($friends, 'id');

// ── 2. Incoming pending requests (others added ME) ───────────────────────
$sql_incoming = '
    SELECT a.id, a.username, a.fullname
    FROM   account a
    JOIN   friendship f ON f.requester_id = a.id AND f.receiver_id = ? AND f.status = "pending"
    ORDER  BY f.created_at ASC
';
$stmt = $db->prepare($sql_incoming);
$stmt->bind_param('i', $me);
$stmt->execute();
$incoming = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$incoming_ids = array_column($incoming, 'id');

// ── 3. Outgoing pending requests (I added others, not yet accepted) ──────
$sql_outgoing = '
    SELECT receiver_id FROM friendship WHERE requester_id = ? AND status = "pending"
';
$stmt = $db->prepare($sql_outgoing);
$stmt->bind_param('i', $me);
$stmt->execute();
$outgoing_rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$outgoing_ids = array_column($outgoing_rows, 'receiver_id');

// ── 4. Strangers — everyone else ─────────────────────────────────────────
$exclude = array_merge([$me], $friend_ids, $incoming_ids, $outgoing_ids);
$placeholders = implode(',', array_fill(0, count($exclude), '?'));
$types = str_repeat('i', count($exclude));

$sql_strangers = "
    SELECT id, username, fullname
    FROM   account
    WHERE  id NOT IN ($placeholders)
    ORDER  BY fullname ASC
";
$stmt = $db->prepare($sql_strangers);
$stmt->bind_param($types, ...$exclude);
$stmt->execute();
$strangers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── 5. People I sent a request to (shown separately in strangers list) ───
$pending_sent = [];
if (!empty($outgoing_ids)) {
    $ph2    = implode(',', array_fill(0, count($outgoing_ids), '?'));
    $types2 = str_repeat('i', count($outgoing_ids));
    $stmt   = $db->prepare("SELECT id, username, fullname FROM account WHERE id IN ($ph2) ORDER BY fullname ASC");
    $stmt->bind_param($types2, ...$outgoing_ids);
    $stmt->execute();
    $pending_sent = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$db->close();

// Helper: render an action form (POST)
function action_form(int $other_id, string $action, string $label, string $btn_class = 'btn-secondary'): string {
    return '<form method="POST" action="/socialnet/friend_action.php" style="display:inline;">'
         . '<input type="hidden" name="action"   value="' . htmlspecialchars($action) . '">'
         . '<input type="hidden" name="other_id" value="' . $other_id . '">'
         . '<button type="submit" class="btn ' . $btn_class . '" style="font-size:.85rem; padding:.38rem .9rem;">'
         . htmlspecialchars($label) . '</button></form>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | SocialNet</title>
    <link rel="stylesheet" href="/style.css">
    <style>
        /* ── extra styles for friend sections ── */
        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: #444;
            margin: 0 0 .75rem;
            display: flex;
            align-items: center;
            gap: .45rem;
        }
        .badge {
            background: #1877f2;
            color: #fff;
            border-radius: 12px;
            font-size: .72rem;
            font-weight: 700;
            padding: .1rem .5rem;
            min-width: 1.4rem;
            text-align: center;
        }
        .badge-green { background: #23983e; }
        .badge-orange { background: #e67e22; }
        .empty-note {
            color: #aaa;
            font-size: .88rem;
            font-style: italic;
            padding: .3rem 0;
        }
        .user-list li .actions { display: flex; gap: .4rem; align-items: center; flex-wrap: wrap; }
        .btn-danger { background: #e74c3c; color: #fff; }
        .btn-danger:hover { background: #c0392b; }
        .btn-success { background: #23983e; color: #fff; }
        .btn-success:hover { background: #1a7a30; }
        .btn-muted { background: #e4e6eb; color: #65676b; }
        .btn-muted:hover { background: #d0d2d8; color: #050505; }
        /* pending badge inline */
        .tag-pending {
            font-size: .72rem;
            background: #fff3e0;
            color: #e67e22;
            border: 1px solid #f0c080;
            border-radius: 4px;
            padding: .1rem .45rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../menubar.php'; ?>

<div class="page-container">

    <!-- ── Welcome card ─────────────────────────────────────────────────── -->
    <div class="card">
        <h2>&#127968; Welcome back!</h2>
        <div class="profile-header">
            <div class="profile-avatar-lg"><?= htmlspecialchars(substr($_SESSION['fullname'], 0, 1)) ?></div>
            <div class="profile-meta">
                <h2><?= htmlspecialchars($_SESSION['fullname']) ?></h2>
                <p class="username-tag">@<?= htmlspecialchars($_SESSION['username']) ?></p>
            </div>
        </div>
        <a href="/socialnet/profile.php" class="btn btn-secondary">View My Profile</a>
    </div>

    <!-- ── Strangers (People you may know) ──────────────────────────────── -->
    <div class="card">
        <h2>&#128101; People You May Know</h2>

        <!-- Strangers (no connection at all) -->
        <?php if (!empty($strangers)): ?>
            <p class="section-title">
                <span>&#128268; Strangers</span>
                <span class="badge"><?= count($strangers) ?></span>
            </p>
            <ul class="user-list" style="margin-bottom:1.2rem;">
                <?php foreach ($strangers as $u): ?>
                <li>
                    <div class="user-avatar"><?= htmlspecialchars(mb_substr($u['fullname'], 0, 1)) ?></div>
                    <div class="user-info-block">
                        <div class="uname"><?= htmlspecialchars($u['fullname']) ?></div>
                        <div class="fname">@<?= htmlspecialchars($u['username']) ?></div>
                    </div>
                    <div class="actions">
                        <?= action_form((int)$u['id'], 'add', '+ Add Friend', 'btn') ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <!-- Requests you already sent (pending) -->
        <?php if (!empty($pending_sent)): ?>
            <p class="section-title">
                <span>&#128228; Requests Sent</span>
                <span class="badge badge-orange"><?= count($pending_sent) ?></span>
            </p>
            <ul class="user-list" style="margin-bottom:1.2rem;">
                <?php foreach ($pending_sent as $u): ?>
                <li>
                    <div class="user-avatar"><?= htmlspecialchars(mb_substr($u['fullname'], 0, 1)) ?></div>
                    <div class="user-info-block">
                        <div class="uname"><?= htmlspecialchars($u['fullname']) ?></div>
                        <div class="fname">@<?= htmlspecialchars($u['username']) ?></div>
                    </div>
                    <div class="actions">
                        <span class="tag-pending">Pending…</span>
                        <?= action_form((int)$u['id'], 'cancel', 'Cancel', 'btn btn-muted') ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (empty($strangers) && empty($pending_sent)): ?>
            <p class="empty-note">You're connected with everyone on SocialNet!</p>
        <?php endif; ?>
    </div>

    <!-- ── Incoming Friend Requests ─────────────────────────────────────── -->
    <div class="card">
        <h2>&#128239; Friend Requests
            <?php if (!empty($incoming)): ?>
                <span class="badge badge-orange" style="font-size:.75rem; vertical-align: middle;"><?= count($incoming) ?></span>
            <?php endif; ?>
        </h2>
        <?php if (empty($incoming)): ?>
            <p class="empty-note">No pending friend requests.</p>
        <?php else: ?>
            <ul class="user-list">
                <?php foreach ($incoming as $u): ?>
                <li>
                    <div class="user-avatar"><?= htmlspecialchars(mb_substr($u['fullname'], 0, 1)) ?></div>
                    <div class="user-info-block">
                        <div class="uname"><?= htmlspecialchars($u['fullname']) ?></div>
                        <div class="fname">@<?= htmlspecialchars($u['username']) ?></div>
                    </div>
                    <div class="actions">
                        <?= action_form((int)$u['id'], 'accept', '✓ Accept', 'btn btn-success') ?>
                        <?= action_form((int)$u['id'], 'reject', '✗ Reject', 'btn btn-danger') ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- ── Friends List ──────────────────────────────────────────────────── -->
    <div class="card">
        <h2>&#128149; Friends
            <?php if (!empty($friends)): ?>
                <span class="badge badge-green" style="font-size:.75rem; vertical-align: middle;"><?= count($friends) ?></span>
            <?php endif; ?>
        </h2>
        <?php if (empty($friends)): ?>
            <p class="empty-note">No friends yet — send some requests above!</p>
        <?php else: ?>
            <ul class="user-list">
                <?php foreach ($friends as $u): ?>
                <li>
                    <div class="user-avatar"><?= htmlspecialchars(mb_substr($u['fullname'], 0, 1)) ?></div>
                    <div class="user-info-block">
                        <div class="uname"><?= htmlspecialchars($u['fullname']) ?></div>
                        <div class="fname">@<?= htmlspecialchars($u['username']) ?></div>
                    </div>
                    <div class="actions">
                        <a href="/socialnet/profile.php?owner=<?= urlencode($u['username']) ?>"
                           class="btn btn-secondary" style="font-size:.85rem; padding:.38rem .9rem;">
                            View Profile
                        </a>
                        <?= action_form((int)$u['id'], 'unfriend', 'Unfriend', 'btn btn-danger') ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
