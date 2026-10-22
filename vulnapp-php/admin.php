<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';

// VULNERABLE: URL quirks (trailing slash, etc)
$request_uri = $_SERVER['REQUEST_URI'];
if (VULN_URL_QUIRKS) {
    // bypass check if trailing slash is added
    if (strpos($request_uri, '/admin.php/') !== false) {
        // bypass
    } else {
        check_admin();
    }
} else {
    check_admin();
}

function check_admin() {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        http_response_code(403);
        die('Forbidden');
    }
}

$page_title = 'Admin';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h2>Admin Panel</h2>
  <?php
    $page = $_GET['page'] ?? 'dashboard.php';
    $target = 'pages/' . $page;
    
    // Debugging
    if (file_exists($target)) {
        include $target;
    } else {
        echo "<p style='color:red;'>Debug: Cannot find page: " . htmlspecialchars($target) . "</p>";
    }
  ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
