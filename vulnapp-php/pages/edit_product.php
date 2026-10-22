<?php
require_once __DIR__ . '/../config.php';
$pdo = get_db();

$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = (float)$_POST['price'];
    $image_url = $_POST['image_url'];

    // Handle File Upload
    $image_path = null;
    if (!empty($_FILES['product_image']['name'])) {
        $image_name = bin2hex(random_bytes(8)) . '_' . basename($_FILES['product_image']['name']);
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], __DIR__ . '/../uploads/' . $image_name)) {
            $image_path = '/uploads/' . $image_name;
        }
    }

    $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, image_url = ? WHERE id = ?");
    $stmt->execute([$name, $description, $price, ($image_path ?? $image_url), $id]);
    header('Location: /admin.php?page=products.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<h3>Edit Product: <?= htmlspecialchars($product['name']) ?></h3>
<form method="POST" enctype="multipart/form-data">
    <label>Name</label>
    <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
    <label>Description</label>
    <textarea name="description"><?= htmlspecialchars($product['description']) ?></textarea>
    <label>Price</label>
    <input type="number" step="0.01" name="price" value="<?= (float)$product['price'] ?>" required>
    <label>Image URL</label>
    <input type="url" name="image_url" value="<?= htmlspecialchars($product['image_url'] ?? '') ?>">
    <label>Or Upload Image</label>
    <input type="file" name="product_image">
    <button type="submit">Update Product</button>
</form>
