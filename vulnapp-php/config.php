<?php
/**
 * VULNAPP (PHP edition) — config + seed data.
 */

// All Flags Must Be Defined First
define('VULN_SQLI_PRODUCT_SEARCH', true);
define('VULN_IDOR_ORDER_VIEW', true);
define('VULN_PATH_TRAVERSAL_INCLUDE', true);
define('VULN_COMMAND_INJECTION', false);
define('VULN_BROKEN_ACCESS_CONTROL', true);
define('VULN_HEADER_SQLI', true);
define('VULN_SQLI_LOGIN', true);
define('VULN_WEAK_PASSWORD_HASHING', true);
define('VULN_NO_RATE_LIMITING', true);
define('VULN_PREDICTABLE_SESSIONS', true);
define('VULN_SESSION_FIXATION', true);
define('VULN_USERNAME_ENUMERATION', true);
define('VULN_REFLECTED_XSS', true);
define('VULN_STORED_XSS', true);
define('VULN_CSRF', true);
define('VULN_OPEN_REDIRECT', true);
define('VULN_IDOR_PROFILE_EDIT', true);
define('VULN_PRICE_QUANTITY_TAMPERING', true);
define('VULN_MASS_ASSIGNMENT_CHECKOUT', true);
define('VULN_UNRESTRICTED_FILE_UPLOAD', true);
define('VULN_PATH_TRAVERSAL_DOWNLOAD', true);
define('VULN_SSRF', true);
define('VULN_INSECURE_DESERIALIZATION', true);
define('VULN_WEAK_REMEMBER_ME', true);
define('VULN_PREDICTABLE_RESET_TOKEN', true);
define('VULN_RACE_CONDITION', true);
define('VULN_RACE_MULTI_ENDPOINT', true);
define('VULN_RACE_SINGLE_PACKET', true);
define('VULN_RACE_DOUBLE_SPEND', true);
define('VULN_RACE_VOUCHER_OVERRUN', true);
define('VULN_CLICKJACKING_DRAG', true);
define('VULN_CSRF_METHOD_OVERRIDE', true);
define('VULN_CSRF_TOKEN_LEAK', true);
define('VULN_CSRF_FAIL_OPEN', true);
define('VULN_EXPOSED_SOURCE_MAP', true);
define('VULN_COOKIE_DEBUG_MODE', true);
define('VULN_AUTOCOMPLETE_ENABLED', true);
define('VULN_MISSING_CACHE_CONTROL', true);
define('VULN_VERBOSE_API_JSON', true);
define('VULN_VERBOSE_ERRORS', true);
define('VULN_EXPOSED_BACKUPS', true);
define('VULN_HARDCODED_CREDS', true);
define('VULN_DEBUG_ENDPOINTS', true);
define('VULN_MISSING_HEADERS', true);
define('VULN_WORKFLOW_STEP_SKIP', true);
define('VULN_ROUNDING_ERROR', true);
define('VULN_INTEGER_OVERFLOW', true);
define('VULN_NEGATIVE_REFUND', true);
define('VULN_LOYALTY_ABUSE', true);
define('VULN_HORIZONTAL_PRIV_ESC', true);
define('VULN_VERTICAL_PRIV_ESC', true);
define('VULN_HTTP_METHOD_SWAP', true);
define('VULN_URL_QUIRKS', true);
define('VULN_SPOOFABLE_ACCESS_CONTROL', true);
define('VULN_DOM_CLOBBERING', true);
define('VULN_CLIENT_PROTOTYPE_POLLUTION', true);
define('VULN_POSTMESSAGE_NO_ORIGIN', true);
define('VULN_UNSAFE_EVAL', true);
define('VULN_CLIENT_SIDE_VAL_TRUST', true);
define('VULN_HTTP_VERB_TAMPERING', true);
define('VULN_UNICODE_NORMALIZATION', true);
define('VULN_NULL_BYTE_INJECTION', true);
define('VULN_CASE_SENSITIVITY_BYPASS', true);
define('VULN_TRAILING_DOT_SLASH', true);
define('VULN_SQL_COMMENT_FILTER_BYPASS', true);

session_start();

if (VULN_VERBOSE_ERRORS) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

define('DB_PATH', __DIR__ . '/vulnapp.db');
define('UPLOAD_DIR', __DIR__ . '/uploads/');

function get_db() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $pdo;
}

function seed_db() {
    $pdo = get_db();
    $pdo->exec("DROP TABLE IF EXISTS users");
    $pdo->exec("DROP TABLE IF EXISTS products");
    $pdo->exec("DROP TABLE IF EXISTS comments");
    $pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT, email TEXT, is_admin INTEGER DEFAULT 0, ssn TEXT, balance REAL DEFAULT 0.0)");
    $pdo->exec("CREATE TABLE products (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, description TEXT, price REAL)");
    $pdo->exec("CREATE TABLE comments (id INTEGER PRIMARY KEY AUTOINCREMENT, product_id INTEGER, author TEXT, body TEXT)");
    $pdo->exec("CREATE TABLE orders (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, total REAL, shipping_address TEXT, status TEXT DEFAULT 'pending', created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE order_items (id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER, product_id INTEGER, quantity INTEGER)");
    $pdo->exec("CREATE TABLE reviews (id INTEGER PRIMARY KEY AUTOINCREMENT, product_id INTEGER, user_id INTEGER, comment TEXT)");
    $pdo->exec("CREATE TABLE access_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, ua TEXT)");
    $pdo->exec("CREATE TABLE remember_tokens (id INTEGER PRIMARY KEY, user_id INTEGER, token TEXT)");

    $pdo->exec("INSERT INTO users (username, password, email, is_admin, ssn) VALUES
        ('alice','alice123','alice@example.com',0,'123-45-6789'),
        ('bob','bobpass','bob@example.com',0,'987-65-4321'),
        ('admin','admin123','admin@example.com',1,'000-00-0000')");

    $pdo->exec("INSERT INTO products (name, description, price) VALUES
        ('Wireless Mouse','Ergonomic 2.4GHz wireless mouse',19.99),
        ('Mechanical Keyboard','RGB backlit mechanical keyboard',59.99),
        ('USB-C Hub','7-in-1 USB-C hub with HDMI',29.99),
        ('Webcam 1080p','Full HD webcam with autofocus',34.99),
        ('Laptop Stand','Aluminum adjustable laptop stand',24.99)");
}

if (!file_exists(DB_PATH)) {
    seed_db();
}

class Logger {
    public $logFile = 'app.log';
    public $logMsg = 'default log entry';

    public function __destruct() {
        @file_put_contents($this->logFile, $this->logMsg, FILE_APPEND);
    }
}
