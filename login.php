<?php
/** Manager sign-in. Requires users.sql to be imported once in phpMyAdmin. */
declare(strict_types=1);
require_once __DIR__ . '/manager_functions.php';
managerStartSession();

if (isset($_SESSION['manager_user_id'])) {
    header('Location: manage.php', true, 303);
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = $_POST['csrf_token'] ?? null;
    $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if (!managerValidCsrf($submitted)) {
        http_response_code(403);
        $error = 'This login form has expired. Reload the page and try again.';
    } elseif ($username === '' || strlen($username) > 50 || $password === '') {
        $error = 'Enter both your username and password.';
    } else {
        try {
            $pdo = managerDatabase();
            $statement = $pdo->prepare(
                'SELECT user_id, username, password_hash FROM users WHERE username = :username LIMIT 1'
            );
            $statement->execute(['username' => $username]);
            $account = $statement->fetch();

            if ($account && password_verify($password, (string) $account['password_hash'])) {
                session_regenerate_id(true); // Prevent session fixation after login.
                $_SESSION['manager_user_id'] = (int) $account['user_id'];
                $_SESSION['manager_username'] = (string) $account['username'];
                unset($_SESSION['manager_csrf_token']);
                header('Location: manage.php', true, 303);
                exit;
            }
            // Same message whether username or password is wrong.
            $error = 'Incorrect username or password.';
        } catch (PDOException $exception) {
            error_log('UrbanSync manager login database error: ' . $exception->getMessage());
            $error = 'Login is temporarily unavailable. Check your local database setup.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Manager Login | UrbanSync</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .hr-login { max-width: 460px; margin: 4rem auto; padding: 2rem; background: #fff;
            border: 1px solid #cbd5e1; border-radius: 12px; box-shadow: 0 6px 22px #17255412; }
        .hr-login h1 { margin-top: 0; }
        .hr-login label { display:block; margin-top:1rem; font-weight:600; }
        .hr-login input { display:block; box-sizing:border-box; width:100%; margin-top:.35rem;
            padding:.7rem; border:1px solid #94a3b8; border-radius:6px; }
        .hr-login button { margin-top:1.3rem; padding:.75rem 1.2rem; border:0; border-radius:6px;
            background:#174b68; color:white; font-weight:700; cursor:pointer; }
        .hr-error { padding:.8rem; background:#fff1f2; border:1px solid #be123c; color:#881337; border-radius:6px; }
    </style>
</head>
<body>
<?php if (is_file(__DIR__ . '/header.inc')) { include __DIR__ . '/header.inc'; } ?>
<main class="hr-login">
    <h1>HR Manager Login</h1>
    <p>Sign in to review job applications.</p>
    <?php if ($error !== ''): ?>
        <p class="hr-error" role="alert"><?= managerEscape($error) ?></p>
    <?php endif; ?>
    <form action="login.php" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= managerEscape(managerCsrfToken()) ?>">
        <label for="username">Username</label>
        <input id="username" name="username" maxlength="50" autocomplete="username" value="<?= managerEscape($username) ?>">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password">
        <button type="submit">Sign in</button>
    </form>
    <p><a href="index.php">Return to home</a></p>
</main>
<?php if (is_file(__DIR__ . '/footer.inc')) { include __DIR__ . '/footer.inc'; } ?>
</body>
</html>
