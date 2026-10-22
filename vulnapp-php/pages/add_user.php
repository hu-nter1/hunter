<?php
require_once __DIR__ . '/../config.php';
$pdo = get_db();
$current_user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    
    // Non-superadmins cannot create admins.
    $is_admin = 0;
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, is_admin) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $password, $email, $is_admin]);
    header('Location: /admin.php?page=users.php');
    exit;
}
?>
<h3>Register New Learner</h3>
<form method="POST" action="/admin.php?page=add_user.php">
    <label>Username</label>
    <input type="text" name="username" required>
    <label>Password</label>
    <input type="password" name="password" required>
    <label>Email</label>
    <input type="email" name="email" required>
    <button type="submit">Register Learner</button>
</form>
