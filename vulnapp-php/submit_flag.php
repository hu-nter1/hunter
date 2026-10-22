<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = current_user();
    $flag = $_POST['flag'];
    $pdo = get_db();
    
    $stmt = $pdo->prepare("SELECT vuln_id FROM flags WHERE flag = ?");
    $stmt->execute([$flag]);
    $result = $stmt->fetch();
    
    if ($result) {
        $vuln_id = $result['vuln_id'];
        
        // Record in Hall of Fame
        $stmt = $pdo->prepare("INSERT INTO hall_of_fame (username, vuln_id) VALUES (?, ?)");
        $stmt->execute([$user['username'], $vuln_id]);
        
        $message = "Congratulations! You are a Hero.";
    } else {
        $message = "Invalid flag.";
    }
}
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h3>Submit Flag</h3>
  <p><?= htmlspecialchars($message) ?></p>
  <form method="POST">
    <input type="text" name="flag" placeholder="FLAG{...}" required>
    <button type="submit">Submit</button>
  </form>

  <?php if (!empty($message) && $message === "Congratulations! You are a Hero."): ?>
    <div style="margin-top:20px;">
        <a href='/generate_cert.php?vuln_id=<?= urlencode($vuln_id) ?>'><button>Download Achievement Certificate</button></a>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
