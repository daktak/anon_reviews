<?php
require 'config.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    if (isset($ADMIN_USERS[$user]) && $ADMIN_USERS[$user] === $pass) {

        $_SESSION['is_admin'] = true;
        $_SESSION['admin_user'] = $user;

        header("Location: index.php");
        exit;

    } else {
        $error = "Invalid login";
    }
}
?>
<!DOCTYPE html>
<html data-bs-theme="<?= htmlspecialchars($_COOKIE['theme'] ?? 'light') ?>">
<head>
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
    <script src="assets/theme.js"></script>
</head>

<body>

<div class="container py-5" style="max-width: 400px;">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Admin Login</h3>
        <button class="btn btn-sm btn-outline-secondary" onclick="toggleTheme()" title="Toggle theme">🌙</button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">

        <div class="mb-2">
            <input type="text" name="username" class="form-control" placeholder="Username" required>
        </div>

        <div class="mb-3">
            <input type="password" name="password" class="form-control" placeholder="Password" required>
        </div>

        <button class="btn btn-primary w-100">Login</button>

    </form>

</div>

</body>
</html>
