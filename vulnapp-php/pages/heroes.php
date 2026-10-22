<?php
require_once __DIR__ . '/../config.php';
$pdo = get_db();

$heroes = $pdo->query(
    "SELECT h.username, COALESCE(h.email, u.email, '') AS email, h.vuln_id,
            COALESCE(f.vulnerability_name, h.vuln_id) AS vulnerability_name,
            f.flag, h.timestamp
     FROM hall_of_fame h
     LEFT JOIN users u ON h.user_id = u.id OR (h.user_id IS NULL AND h.username = u.username)
     LEFT JOIN flags f ON h.flag_id = f.id
     ORDER BY h.timestamp DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<h3>Hall of Heroes</h3>
<table border="1">
    <tr><th>Username</th><th>Email</th><th>Flag</th><th>Vulnerability Type</th><th>Time (UTC)</th></tr>
    <?php if (empty($heroes)): ?>
    <tr><td colspan="5">No discoveries have been submitted yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($heroes as $h): ?>
    <tr>
        <td><?= htmlspecialchars($h['username']) ?></td>
        <td><?= htmlspecialchars($h['email']) ?></td>
        <td><code><?= htmlspecialchars($h['flag'] ?? 'Legacy entry') ?></code></td>
        <td><?= htmlspecialchars($h['vulnerability_name']) ?> (<?= htmlspecialchars($h['vuln_id']) ?>)</td>
        <td><?= htmlspecialchars($h['timestamp']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
