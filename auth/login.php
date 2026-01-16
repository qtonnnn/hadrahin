<?php
require_once '../config/config.php';

// kalau sudah login, lempar ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/dashboard');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>

<h2>Login</h2>

<form action="proses_login.php" method="post">
    <label>Username</label><br>
    <input type="text" name="username" required><br><br>

    <label>Password</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Login</button>
</form>

</body>
</html>
