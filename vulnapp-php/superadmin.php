<?php
require __DIR__ . '/config.php';
$superadminKey = getenv('SUPERADMIN_KEY');
if ($superadminKey === false || $superadminKey === '') {
    http_response_code(503);
    exit('Superadmin access is not configured.');
}
if (empty($_SESSION['superadmin_authenticated'])) {
    $loginError = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submittedKey = (string)($_POST['superadmin_key'] ?? '');
        if (!hash_equals($superadminKey, $submittedKey)) {
            http_response_code(403);
            $loginError = 'Invalid admin key.';
        } else {
            session_regenerate_id(true);
            $_SESSION['superadmin_authenticated'] = true;
            header('Location: /superadmin.php');
            exit;
        }
    }
    ?>
    <!doctype html><html><head><title>Superadmin Sign In</title></head><body>
    <h2>Superadmin Sign In</h2>
    <?php if ($loginError): ?><p><?= htmlspecialchars($loginError) ?></p><?php endif; ?>
    <form method="POST">
        <label>Admin key <input type="password" name="superadmin_key" required></label>
        <button type="submit">Sign In</button>
    </form>
    </body></html>
    <?php
    exit;
}
$pdo = get_db();
$adminError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_balance'])) {
        $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$_POST['amount'], $_POST['user_id']]);
    } elseif (isset($_POST['delete_user'])) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$_POST['user_id']]);
    } elseif (isset($_POST['promote_admin'])) {
        $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id = ?")->execute([$_POST['user_id']]);
    } elseif (isset($_POST['add_product'])) {
        try {
            $image_url = trim($_POST['image_url'] ?? '');
            $uploadedImage = save_product_image_upload();
            if ($uploadedImage !== null) {
                $image_url = $uploadedImage;
            }
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image_url) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['name'], $_POST['description'], $_POST['price'], $image_url]);
        } catch (RuntimeException $exception) {
            $adminError = $exception->getMessage();
        }
    } elseif (isset($_POST['edit_product'])) {
        try {
            $image_url = trim($_POST['image_url'] ?? '');
            $uploadedImage = save_product_image_upload();
            if ($uploadedImage !== null) {
                $image_url = $uploadedImage;
            }
            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, image_url = ? WHERE id = ?");
            $stmt->execute([$_POST['name'], $_POST['description'], $_POST['price'], $image_url, $_POST['product_id']]);
        } catch (RuntimeException $exception) {
            $adminError = $exception->getMessage();
        }
    } elseif (isset($_POST['delete_product'])) {
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$_POST['product_id']]);
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$heroes = $pdo->query("SELECT h.*, u.email FROM hall_of_fame h JOIN users u ON h.username = u.username ORDER BY h.timestamp DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html><html><head><title>Superadmin</title>
<style>
    body { font-family: sans-serif; background: #f0f2f5; padding: 20px; }
    .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.1); margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; vertical-align: top; }
    .btn { padding: 5px 8px; border: none; border-radius: 4px; cursor: pointer; color: white; background: #2d6cdf; }
    .btn-red { background: #dc3545; }
    textarea { width: 100%; height: 60px; }
</style></head><body>
<h2>Superadmin Control Center</h2>
<div class="card"><h3>Manage Users</h3>
    <table><tr><th>User</th><th>Balance</th><th>Action</th></tr>
    <?php foreach($users as $u): ?>
    <tr><td><?= htmlspecialchars($u['username']) ?></td><td>$<?= htmlspecialchars((float)$u['balance']) ?></td>
    <td><form method="POST"><input type="hidden" name="user_id" value="<?= $u['id'] ?>">
    <input type="number" name="amount" style="width:50px" placeholder="$">
    <button type="submit" name="add_balance" class="btn">Add</button>
    <button type="submit" name="promote_admin" class="btn">Admin</button>
    <button type="submit" name="delete_user" class="btn btn-red">Delete</button></form></td></tr>
    <?php endforeach; ?></table>
</div>
<div class="card"><h3>Manage Products</h3>
    <?php if ($adminError !== null): ?><p role="alert"><?= htmlspecialchars($adminError) ?></p><?php endif; ?>
    <form method="POST" enctype="multipart/form-data" style="margin-bottom:20px; padding:10px; background:#f9f9f9; border-radius:5px;">
        <input type="text" name="name" placeholder="Name" required>
        <textarea name="description" placeholder="Description" required></textarea>
        <input type="number" step="0.01" name="price" placeholder="Price" required>
        <label>Product image URL</label>
        <input type="url" name="image_url" placeholder="https://example.com/product.jpg">
        <label>Or upload an image from your computer</label>
        <input type="file" name="product_image" accept="image/jpeg,image/png,image/gif,image/webp">
        <button type="submit" name="add_product" class="btn">Add New Product</button>
    </form>
    <table><tr><th>Image</th><th>Name</th><th>Description</th><th>Price</th><th>Image URL / Upload</th><th>Action</th></tr>
    <?php foreach($products as $p): ?>
    <?php $productFormId = 'edit-product-' . (int)$p['id']; ?>
    <tr>
        <td>
            <form id="<?= htmlspecialchars($productFormId) ?>" method="POST" enctype="multipart/form-data"></form>
            <input type="hidden" form="<?= htmlspecialchars($productFormId) ?>" name="product_id" value="<?= (int)$p['id'] ?>">
            <?php if (!empty($p['image_url'])): ?><img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" style="width:64px;height:64px;object-fit:contain;"><?php endif; ?>
        </td>
        <td><input form="<?= htmlspecialchars($productFormId) ?>" type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>"></td>
        <td><textarea form="<?= htmlspecialchars($productFormId) ?>" name="description"><?= htmlspecialchars($p['description']) ?></textarea></td>
        <td><input form="<?= htmlspecialchars($productFormId) ?>" type="number" step="0.01" name="price" value="<?= (float)$p['price'] ?>"></td>
        <td><input form="<?= htmlspecialchars($productFormId) ?>" type="url" name="image_url" value="<?= htmlspecialchars($p['image_url'] ?? '') ?>" placeholder="https://example.com/product.jpg"><input form="<?= htmlspecialchars($productFormId) ?>" type="file" name="product_image" accept="image/jpeg,image/png,image/gif,image/webp"></td>
        <td><button form="<?= htmlspecialchars($productFormId) ?>" type="submit" name="edit_product" class="btn">Save</button>
        <button form="<?= htmlspecialchars($productFormId) ?>" type="submit" name="delete_product" class="btn btn-red">Delete</button></td>
    </tr>
    <?php endforeach; ?></table>
</div>
<div class="card"><h3>Hall of Heroes</h3>
    <table><tr><th>User</th><th>Email</th><th>Vuln</th><th>Time</th></tr>
    <?php foreach($heroes as $h): ?>
    <tr><td><?= htmlspecialchars($h['username']) ?></td><td><?= htmlspecialchars($h['email'] ?? '') ?></td><td><?= htmlspecialchars($h['vuln_id']) ?></td><td><?= $h['timestamp'] ?></td></tr>
    <?php endforeach; ?></table>
</div>
</body></html>
