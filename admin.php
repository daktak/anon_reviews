<?php
require 'config.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($user === $ADMIN_USER && $pass === $ADMIN_PASS) {
        $_SESSION['is_admin'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid login";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5" style="max-width: 400px;">

    <h3 class="mb-3">Admin Login</h3>

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
