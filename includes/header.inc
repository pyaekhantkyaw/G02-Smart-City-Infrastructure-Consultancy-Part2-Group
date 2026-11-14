<?php
// The page that includes this header should start its session before any HTML output.
require_once __DIR__ . '/../manager_functions.php';
$isManagerLoggedIn = isset($_SESSION['manager_user_id'])
    && is_int($_SESSION['manager_user_id']);
?>
<header>
    <nav aria-label="Main navigation">
        <a href="index.php">
            <img src="images/logo.png" alt="UrbanSync Logo"
                 style="height: 80px; width: auto; margin-right: 10px;">
        </a>
        <a href="index.php">Home</a>
        <a href="jobs.php">Jobs</a>
        <a href="explore.php">Explore</a>
        <a href="about.php">About</a>
        <?php if ($isManagerLoggedIn): ?>
            <a href="manage.php">Manage</a>
            <form action="logout.php" method="post" class="nav-logout-form">
                <input type="hidden" name="csrf_token"
                       value="<?= managerEscape(managerCsrfToken()) ?>">
                <button type="submit" class="nav-logout-button">Logout</button>
            </form>
        <?php else: ?>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </nav>
</header>
