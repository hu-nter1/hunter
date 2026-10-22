<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';
require __DIR__ . '/includes/header.php';
$pdo = get_db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute([$username, $password]);
    echo "Registered.";
}
?>
<form method="POST">
    <input type="text" name="username" placeholder="Username (try SQL payload for 2nd-order)">
    <input type="password" name="password">
    <button type="submit">Register</button>
</form>
