<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: /cart.php');
    exit;
}

$user = current_user();
if (!$user) {
    header('Location: /login.php');
    exit;
}
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $fullname = $_POST['fullname'];
    $contact = $_POST['contact'];
    $district = $_POST['district'];
    $address = $_POST['address'];
    $full_shipping = "$address, $district, Contact: $contact";
    
    $total = 0;
    foreach ($_SESSION['cart'] as $item_id => $qty) {
        $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$item_id]);
        $p = $stmt->fetch();
        $total += ($p['price'] * $qty);
    }
    
    if ($user['balance'] < $total) {
        die("Insufficient balance.");
    }
    
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?")->execute([$total, $user['id']]);

    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, shipping_address, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$user['id'], $total, $full_shipping]);
    $orderId = $pdo->lastInsertId();

    $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity) VALUES (?, ?, ?)");
    foreach ($_SESSION['cart'] as $itemId => $quantity) {
        $itemStmt->execute([$orderId, $itemId, $quantity]);
    }
    $pdo->commit();
    
    $_SESSION['cart'] = [];
    header("Location: /order_success.php?id=" . $orderId);
    exit;
}
?>
<h3>Checkout</h3>
<form method="POST">
    <label>Full Name</label>
    <input type="text" name="fullname" required>
    <label>Contact</label>
    <input type="text" name="contact" required>
    <label>District</label>
    <input type="text" name="district" required>
    <label>Address</label>
    <textarea name="address" required></textarea>
    <button type="submit" name="confirm">Confirm Order</button>
</form>
