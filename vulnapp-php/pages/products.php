<?php
require_once __DIR__ . '/../config.php';
$pdo = get_db();

// Handle form submission to add product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stmt = $pdo->prepare("INSERT INTO products (name, description, price) VALUES (?, ?, ?)");
    $stmt->execute([$name, $description, $price]);
}

// Fetch products
$products = $pdo->query("SELECT * FROM products")->fetchAll(PDO::FETCH_ASSOC);
?>
<h3>Manage Products</h3>
<form method="POST" action="/admin.php?page=products.php">
    <input type="hidden" name="add_product" value="1">
    <input type="text" name="name" placeholder="Product Name" required>
    <textarea name="description" placeholder="Description"></textarea>
    <input type="number" step="0.01" name="price" placeholder="Price" required>
    <button type="submit">Add Product</button>
</form>

<table>
    <tr><th>Image</th><th>ID</th><th>Name</th><th>Price</th><th>Action</th></tr>
    <?php foreach ($products as $p): ?>
    <tr>
        <td>
            <?php if (!empty($p['image_url'])): ?>
                <img src="<?= htmlspecialchars($p['image_url']) ?>" style="width:50px; height:50px; object-fit:cover; border-radius:4px;">
            <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($p['id']) ?></td>
        <td><?= htmlspecialchars($p['name']) ?></td>
        <td>$<?= htmlspecialchars($p['price']) ?></td>
        <td><a href="/admin.php?page=edit_product.php&id=<?= (int)$p['id'] ?>">Edit</a></td>
    </tr>
    <?php endforeach; ?>
</table>
