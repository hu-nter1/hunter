<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';
require __DIR__ . '/includes/header.php';
$pdo = get_db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
    $stmt->execute([$username, $password, $email]);
    echo "Registered.";
}
?>
<form method="POST">
    <input type="text" name="username" placeholder="Username (try SQL payload for 2nd-order)" required>
    <input type="email" name="email" placeholder="Email address" required>
    <input type="password" name="password" required>
    <button type="submit">Register</button>
</form>
