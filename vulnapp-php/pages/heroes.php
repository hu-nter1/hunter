<?php
require_once __DIR__ . '/../config.php';
$pdo = get_db();

$heroes = $pdo->query("SELECT * FROM hall_of_fame ORDER BY timestamp DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<h3>Hall of Heroes</h3>
<table border="1">
    <tr><th>Username</th><th>Vulnerability Exploited</th><th>Time</th></tr>
    <?php foreach ($heroes as $h): ?>
    <tr>
        <td><?= htmlspecialchars($h['username']) ?></td>
        <td><?= htmlspecialchars($h['vuln_id']) ?></td>
        <td><?= htmlspecialchars($h['timestamp']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
