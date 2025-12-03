<?php
require 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $university_id = $_POST['university_id'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE university_id = ?");
    $stmt->execute([$university_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Demo only: plain password (in production, use password_hash)
    if ($user && $password === $user['password']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = $user['role'];

        if ($user['role'] === 'manager') {
            header("Location: manager_dashboard.php");
        } else {
            header("Location: student_home.php");
        }
        exit;
    } else {
        $error = 'Invalid ID or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - University Restaurant</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
    
</head>
<body>
<div class="login-wrapper">
    <div class="login-box">
<p class="login-subtitle">University Restaurant Table Reservation</p>
        <div class="login-title">Login</div>
        <?php if ($error): ?>
            <div class="login-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
	     	
            <label for="university_id">University ID</label>
            <input type="text" name="university_id" id="university_id" required autofocus autocomplete="username">

            <label for="password">Password</label>
            <input type="password" name="password" id="password" required autocomplete="current-password">

            <button type="submit">Login</button>
        </form>
    </div>
</div>
</body>
</html>
