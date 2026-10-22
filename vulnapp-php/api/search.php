<?php
require __DIR__ . '/../config.php';
$q = $_GET['q'] ?? '';

if (VULN_SQLI_PRODUCT_SEARCH) {
    $sql = "SELECT id, name, description, price, image_url FROM products WHERE name LIKE '%$q%'";
    $items = get_db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = get_db()->prepare("SELECT id, name, description, price, image_url FROM products WHERE name LIKE ?");
    $stmt->execute(['%' . $q . '%']);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

foreach ($items as $p): ?>
    <div class="product">
      <?php if (!empty($p['image_url'])): ?>
        <img src="<?= htmlspecialchars($p['image_url']) ?>" style="width:100%; height:150px; object-fit:cover; border-radius:8px; margin-bottom:10px;">
      <?php endif; ?>
      <h3><a href="/product.php?id=<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></a></h3>
      <p class="muted"><?= htmlspecialchars($p['description']) ?></p>
      <div class="price">$<?= htmlspecialchars((float)$p['price']) ?></div>
    </div>
<?php endforeach; 
