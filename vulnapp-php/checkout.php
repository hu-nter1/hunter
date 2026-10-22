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
$checkoutError = null;
$fullname = trim($_POST['fullname'] ?? '');
$contact = trim($_POST['contact'] ?? '');
$district = trim($_POST['district'] ?? '');
$address = trim($_POST['address'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $full_shipping = "$address, $district, Contact: $contact";
    
    $total = 0;
    $cartItems = [];
    foreach ($_SESSION['cart'] as $item_id => $qty) {
        $itemId = (int)$item_id;
        $quantity = filter_var($qty, FILTER_VALIDATE_INT);
        if ($itemId < 1 || $quantity === false || $quantity < 1) {
            $checkoutError = 'Please check your cart and make sure every quantity is at least 1.';
            break;
        }
        $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$itemId]);
        $p = $stmt->fetch();
        if (!$p) {
            $checkoutError = 'A product in your cart is no longer available. Please review your cart.';
            break;
        }
        $total += $p['price'] * $quantity;
        $cartItems[] = ['id' => $itemId, 'quantity' => $quantity];
    }
    
    if ($checkoutError === null) {
        $pdo->beginTransaction();
        $balanceStmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $balanceStmt->execute([$user['id']]);
        $balance = $balanceStmt->fetchColumn();
        if ($balance === false || (float)$balance < $total) {
            $pdo->rollBack();
            $checkoutError = 'Insufficient funds. Add money to your wallet before confirming this order.';
        } else {
            $deductStmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?");
            $deductStmt->execute([$total, $user['id'], $total]);
            if ($deductStmt->rowCount() === 0) {
                $pdo->rollBack();
                $checkoutError = 'Insufficient funds. Add money to your wallet before confirming this order.';
            } else {
                $orderId = insert_and_get_id(
                    $pdo,
                    "INSERT INTO orders (user_id, total, shipping_address, status) VALUES (?, ?, ?, 'pending')",
                    [$user['id'], $total, $full_shipping]
                );

                $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity) VALUES (?, ?, ?)");
                foreach ($cartItems as $item) {
                    $itemStmt->execute([$orderId, $item['id'], $item['quantity']]);
                }
                $pdo->commit();

                $_SESSION['cart'] = [];
                header("Location: /order_success.php?id=" . $orderId);
                exit;
            }
        }
    }
}
?>
<h3>Checkout</h3>
<?php if ($checkoutError !== null): ?>
    <div class="error" role="alert">
        <p><?= htmlspecialchars($checkoutError) ?></p>
        <?php if (str_starts_with($checkoutError, 'Insufficient funds.')): ?>
            <a href="/profile.php?id=<?= (int)$user['id'] ?>">Add funds to your wallet</a>
        <?php else: ?>
            <a href="/cart.php">Review your cart</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
<form method="POST">
    <label>Full Name</label>
    <input type="text" name="fullname" value="<?= htmlspecialchars($fullname) ?>" required>
    <label>Contact</label>
    <input type="text" name="contact" value="<?= htmlspecialchars($contact) ?>" required>
    <label>District</label>
    <input type="text" name="district" value="<?= htmlspecialchars($district) ?>" required>
    <label>Address</label>
    <textarea name="address" required><?= htmlspecialchars($address) ?></textarea>
    <button type="submit" name="confirm">Confirm Order</button>
</form>
