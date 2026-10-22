<?php
$validUser = getenv('LAB_AUTH_USER');
$validPass = getenv('LAB_AUTH_PASS');

if (!$validUser || !$validPass ||
    !isset($_SERVER['PHP_AUTH_USER']) ||
    !hash_equals($validUser, $_SERVER['PHP_AUTH_USER']) ||
    !hash_equals($validPass, $_SERVER['PHP_AUTH_PW'] ?? '')) {
    header('WWW-Authenticate: Basic realm="vulnapp-php lab access"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Access denied.';
    exit;
}