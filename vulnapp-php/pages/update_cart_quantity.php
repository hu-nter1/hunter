<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qty = (int)$_POST['quantity'];
    // VULNERABLE: Integer overflow/sign abuse
    if (VULN_INTEGER_OVERFLOW) {
        // No check if qty > 0 or too large
    } else {
        if ($qty < 1 || $qty > 99) die("Invalid qty");
    }
    $_SESSION['cart'][$_POST['product_id']] = $qty;
}
