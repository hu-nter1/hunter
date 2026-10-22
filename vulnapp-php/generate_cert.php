<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';

$user = current_user();
$vuln_id = $_GET['vuln_id'] ?? 'Unknown';

// Fetch details for the certificate
$pdo = get_db();
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$u_data = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <style>
        .cert { border: 5px solid #2d6cdf; padding: 40px; width: 600px; margin: auto; text-align: center; font-family: sans-serif; }
    </style>
</head>
<body onload="window.print()">
    <div class="cert">
        <h1>Exploit Certificate</h1>
        <p>This certifies that</p>
        <h2><?= htmlspecialchars($u_data['username']) ?></h2>
        <p>(<?= htmlspecialchars($u_data['email']) ?>)</p>
        <p>has successfully identified and exploited:</p>
        <h3><?= htmlspecialchars($vuln_id) ?></h3>
        <p>Date: <?= date('Y-m-d H:i:s') ?></p>
    </div>
</body>
</html>
