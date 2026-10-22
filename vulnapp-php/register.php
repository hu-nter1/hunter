<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';
$pdo = get_db();
$page_title = 'Create Account';
$registered = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
    $stmt->execute([$username, $password, $email]);
    $registered = true;
}
require __DIR__ . '/includes/header.php';
?>
<section class="card" style="max-width:520px;margin:32px auto;">
    <h1 style="margin-top:0;">Create your ShopEase account</h1>
    <p class="muted">Join the shop to explore products, manage your cart, and track your lab achievements.</p>
    <?php if ($registered): ?>
        <p class="success" role="status">Your account has been created. You can now <a href="/login.php">sign in</a>.</p>
    <?php else: ?>
        <form method="POST">
            <label for="register-username">Username</label>
            <input id="register-username" type="text" name="username" autocomplete="username" required>
            <label for="register-email">Email address</label>
            <input id="register-email" type="email" name="email" autocomplete="email" required>
            <label for="register-password">Password</label>
            <input id="register-password" type="password" name="password" autocomplete="new-password" required>
            <button type="submit">Create account</button>
        </form>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
