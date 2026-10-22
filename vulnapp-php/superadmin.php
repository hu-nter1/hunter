<?php
require __DIR__ . '/config.php';
if (getenv('SUPERADMIN_KEY') !== 'secret-key-123') { die("Access Denied."); }
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_balance'])) {
        $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$_POST['amount'], $_POST['user_id']]);
    } elseif (isset($_POST['delete_user'])) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$_POST['user_id']]);
    } elseif (isset($_POST['promote_admin'])) {
        $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id = ?")->execute([$_POST['user_id']]);
    } elseif (isset($_POST['add_product'])) {
        $image_url = $_POST['image_url'] ?? '';
        if (!empty($_FILES['product_image']['name'])) {
            $img_name = bin2hex(random_bytes(8)) . '_' . basename($_FILES['product_image']['name']);
            move_uploaded_file($_FILES['product_image']['tmp_name'], __DIR__ . '/uploads/' . $img_name);
            $image_url = '/uploads/' . $img_name;
        }
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image_url) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['name'], $_POST['description'], $_POST['price'], $image_url]);
    } elseif (isset($_POST['edit_product'])) {
        $image_url = $_POST['image_url'] ?? '';
        if (!empty($_FILES['product_image']['name'])) {
            $img_name = bin2hex(random_bytes(8)) . '_' . basename($_FILES['product_image']['name']);
            move_uploaded_file($_FILES['product_image']['tmp_name'], __DIR__ . '/uploads/' . $img_name);
            $image_url = '/uploads/' . $img_name;
        }
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, image_url = ? WHERE id = ?");
        $stmt->execute([$_POST['name'], $_POST['description'], $_POST['price'], $image_url, $_POST['product_id']]);
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
    <form method="POST" enctype="multipart/form-data" style="margin-bottom:20px; padding:10px; background:#f9f9f9; border-radius:5px;">
        <input type="text" name="name" placeholder="Name" required>
        <textarea name="description" placeholder="Description" required></textarea>
        <input type="number" step="0.01" name="price" placeholder="Price" required>
        <input type="text" name="image_url" placeholder="Image URL">
        <input type="file" name="product_image">
        <button type="submit" name="add_product" class="btn">Add New Product</button>
    </form>
    <table><tr><th>Name</th><th>Desc</th><th>Price</th><th>Action</th></tr>
    <?php foreach($products as $p): ?>
    <tr><form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
        <td><input type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>"></td>
        <td><textarea name="description"><?= htmlspecialchars($p['description']) ?></textarea></td>
        <td><input type="number" step="0.01" name="price" value="<?= (float)$p['price'] ?>"></td>
        <td><button type="submit" name="edit_product" class="btn">Save</button>
        <button type="submit" name="delete_product" class="btn btn-red">Delete</button></td>
    </form></tr>
    <?php endforeach; ?></table>
</div>
<div class="card"><h3>Hall of Heroes</h3>
    <table><tr><th>User</th><th>Email</th><th>Vuln</th><th>Time</th></tr>
    <?php foreach($heroes as $h): ?>
    <tr><td><?= htmlspecialchars($h['username']) ?></td><td><?= htmlspecialchars($h['email'] ?? '') ?></td><td><?= htmlspecialchars($h['vuln_id']) ?></td><td><?= $h['timestamp'] ?></td></tr>
    <?php endforeach; ?></table>
</div>
</body></html>
