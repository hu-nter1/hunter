<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = get_db()->prepare("SELECT id, username, email, ssn, is_admin, balance FROM users WHERE id = ?");
$stmt->execute([$id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user's exploited flags
$flags = [];
if ($profile) {
    $stmt = get_db()->prepare("SELECT vuln_id FROM hall_of_fame WHERE username = ?");
    $stmt->execute([$profile['username']]);
    $flags = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = 'Profile';
require __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:480px;">
  <h2 style="margin-top:0;">User Profile</h2>
  <?php if ($profile): ?>
    <p><strong>Username:</strong> <?= htmlspecialchars($profile['username']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($profile['email']) ?></p>
    <p><strong>Balance:</strong> $<?= htmlspecialchars((float)$profile['balance']) ?></p>
    
    <h3>Flags Earned</h3>
    <ul>
        <?php foreach ($flags as $f): ?>
            <li><?= htmlspecialchars($f['vuln_id']) ?></li>
        <?php endforeach; ?>
    </ul>
    
    <button onclick="window.print()">Download Profile as PDF</button>
  <?php else: ?>
    <p class="muted">User not found.</p>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
