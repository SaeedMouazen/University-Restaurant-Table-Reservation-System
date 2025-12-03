<?php
require 'config.php';
if (!is_logged_in() || !is_student()) {
    header('Location: login.php');
    exit;
}
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("UPDATE reservations SET status='cancelled' WHERE id = ? AND student_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
header('Location: student_home.php');
exit;
?>
