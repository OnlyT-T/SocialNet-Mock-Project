<?php
// menubar.php — included in Home / Profile / Setting / About pages
// Expects session to be started before inclusion.
$current = basename($_SERVER['PHP_SELF']);
?>
<nav class="menubar">
    <div class="menubar-brand">&#127758; SocialNet</div>
    <ul class="menubar-links">
        <li><a href="/socialnet/index.php"   class="<?= $current==='index.php'   ? 'active':'' ?>">Home</a></li>
        <li><a href="/socialnet/setting.php" class="<?= $current==='setting.php' ? 'active':'' ?>">Setting</a></li>
        <li><a href="/socialnet/profile.php" class="<?= $current==='profile.php' && !isset($_GET['owner']) ? 'active':'' ?>">Profile</a></li>
        <li><a href="/socialnet/about.php"   class="<?= $current==='about.php'   ? 'active':'' ?>">About</a></li>
        <li><a href="/socialnet/signout.php" class="signout-link">Sign Out</a></li>
    </ul>
</nav>
