<?php
session_start();

$host = 'localhost';
$db   = 'uni_restaurant';
$user = 'root';
$pass = ''; // change if needed

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB connection failed");
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_student() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

function is_manager() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'manager';
}
?>
