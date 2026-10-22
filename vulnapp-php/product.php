<?php
require __DIR__ . '/auth_gate.php';
require __DIR__ . '/config.php';
// Check login for product details
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
require __DIR__ . '/includes/session_check.php';
$user = current_user();

$id = (int)($_GET['id'] ?? 0);
$stmt = get_db()->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

$page_title = $product['name'] ?? 'Product';
require __DIR__ . '/includes/header.php';
?>
<?php if ($product): ?>
<div class="card">
  <?php if (!empty($product['image_url'])): ?>
    <img src="<?= htmlspecialchars($product['image_url']) ?>" style="max-width: 100%; height: auto; border-radius: 8px; margin-bottom: 15px;">
  <?php endif; ?>
  <h2><?= htmlspecialchars($product['name']) ?></h2>
  <p><?= htmlspecialchars($product['description']) ?></p>
  <p>Price: $<?= htmlspecialchars((float)$product['price']) ?></p>
  <?php if (!$user || !$user['is_admin']): ?>
      <form method="POST" action="/cart.php?action=add&id=<?= (int)$id ?>">
        <input type="number" name="quantity" value="1" min="1">
        <button type="submit">Add to Cart</button>
      </form>
  <?php else: ?>
      <p><em>Admins cannot make purchases.</em></p>
  <?php endif; ?>
</div>
<?php else: ?>
  <p>Product not found.</p>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
