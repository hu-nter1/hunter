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

$databaseUrl = getenv('DATABASE_URL');
define('USE_POSTGRES', $databaseUrl !== false && $databaseUrl !== '');

if (!USE_POSTGRES) {
    $dataDir = getenv('APP_DATA_DIR') ?: __DIR__ . '/data';
    if (!is_dir($dataDir) && !mkdir($dataDir, 0775, true) && !is_dir($dataDir)) {
        throw new RuntimeException('Unable to create the application data directory.');
    }
    define('DB_PATH', $dataDir . '/vulnapp.db');
}
define('UPLOAD_DIR', __DIR__ . '/uploads/');

function get_db() {
    static $pdo = null;
    if ($pdo === null) {
        if (USE_POSTGRES) {
            $databaseUrl = getenv('DATABASE_URL');
            $parts = parse_url($databaseUrl);
            if ($parts === false || empty($parts['host']) || empty($parts['path'])) {
                throw new RuntimeException('DATABASE_URL is not a valid PostgreSQL connection URL.');
            }

            $query = [];
            parse_str($parts['query'] ?? '', $query);
            $sslMode = $query['sslmode'] ?? 'require';
            $channelBinding = $query['channel_binding'] ?? null;
            $dsn = sprintf(
                'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
                $parts['host'],
                $parts['port'] ?? 5432,
                rawurldecode(ltrim($parts['path'], '/')),
                $sslMode
            );
            if ($channelBinding !== null) {
                $dsn .= ';channel_binding=' . $channelBinding;
            }

            $pdo = new PDO(
                $dsn,
                rawurldecode($parts['user'] ?? ''),
                rawurldecode($parts['pass'] ?? ''),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } else {
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        initialize_schema($pdo);
    }
    return $pdo;
}

function insert_and_get_id(PDO $pdo, $sql, array $params = []) {
    if (USE_POSTGRES) {
        $stmt = $pdo->prepare($sql . ' RETURNING id');
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $pdo->lastInsertId();
}

function save_product_image_upload($field = 'product_image') {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('The product image could not be uploaded.');
    }

    $image = getimagesize($file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    $mimeType = $image['mime'] ?? '';
    if ($image === false || !isset($extensions[$mimeType])) {
        throw new RuntimeException('Choose a valid JPG, PNG, GIF, or WebP image.');
    }

    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0775, true) && !is_dir(UPLOAD_DIR)) {
        throw new RuntimeException('The product image directory is unavailable.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
        throw new RuntimeException('The product image could not be saved.');
    }

    return '/uploads/' . $filename;
}

function initialize_schema(PDO $pdo) {
    $autoId = USE_POSTGRES ? 'BIGSERIAL PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $timestampType = USE_POSTGRES ? 'TIMESTAMP' : 'DATETIME';
    $tables = [
        'users' => "(id $autoId, username TEXT, password TEXT, email TEXT, is_admin INTEGER DEFAULT 0, ssn TEXT, balance REAL DEFAULT 0.0)",
        'products' => "(id $autoId, name TEXT, description TEXT, price REAL, image_url TEXT)",
        'comments' => "(id $autoId, product_id INTEGER, author TEXT, body TEXT)",
        'orders' => "(id $autoId, user_id INTEGER, total REAL, shipping_address TEXT, status TEXT DEFAULT 'pending', created_at $timestampType DEFAULT CURRENT_TIMESTAMP)",
        'order_items' => "(id $autoId, order_id INTEGER, product_id INTEGER, quantity INTEGER)",
        'reviews' => "(id $autoId, product_id INTEGER, user_id INTEGER, comment TEXT)",
        'access_logs' => "(id $autoId, ua TEXT)",
        'remember_tokens' => '(id INTEGER PRIMARY KEY, user_id INTEGER, token TEXT)',
        'flags' => '(id INTEGER PRIMARY KEY, vuln_id TEXT, flag TEXT)',
        'hall_of_fame' => "(id $autoId, username TEXT, vuln_id TEXT, timestamp $timestampType DEFAULT CURRENT_TIMESTAMP)",
        'activity_logs' => "(id $autoId, username TEXT, action TEXT, timestamp $timestampType DEFAULT CURRENT_TIMESTAMP)",
    ];
    foreach ($tables as $table => $definition) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS $table $definition");
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS app_metadata (metadata_key TEXT PRIMARY KEY, metadata_value TEXT)');

    $columns = [
        'users' => [
            'email' => 'TEXT',
            'is_admin' => 'INTEGER DEFAULT 0',
            'ssn' => 'TEXT',
            'balance' => 'REAL DEFAULT 0.0',
        ],
        'products' => ['image_url' => 'TEXT'],
        'orders' => [
            'shipping_address' => 'TEXT',
            'status' => "TEXT DEFAULT 'pending'",
            'created_at' => USE_POSTGRES ? 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP' : 'DATETIME',
        ],
        'hall_of_fame' => ['timestamp' => USE_POSTGRES ? 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP' : 'DATETIME'],
        'activity_logs' => ['timestamp' => USE_POSTGRES ? 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP' : 'DATETIME'],
    ];

    foreach ($columns as $table => $definitions) {
        if (USE_POSTGRES) {
            $stmt = $pdo->prepare('SELECT column_name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ?');
            $stmt->execute([$table]);
            $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $existingColumns = $pdo->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_COLUMN, 1);
        }
        foreach ($definitions as $column => $definition) {
            if (!in_array($column, $existingColumns, true)) {
                $pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
            }
        }
    }

    if (!USE_POSTGRES) {
        $pdo->exec("CREATE TRIGGER IF NOT EXISTS orders_created_at_default AFTER INSERT ON orders WHEN NEW.created_at IS NULL BEGIN UPDATE orders SET created_at = CURRENT_TIMESTAMP WHERE id = NEW.id; END");
        $pdo->exec("CREATE TRIGGER IF NOT EXISTS hall_of_fame_timestamp_default AFTER INSERT ON hall_of_fame WHEN NEW.timestamp IS NULL BEGIN UPDATE hall_of_fame SET timestamp = CURRENT_TIMESTAMP WHERE id = NEW.id; END");
        $pdo->exec("CREATE TRIGGER IF NOT EXISTS activity_logs_timestamp_default AFTER INSERT ON activity_logs WHEN NEW.timestamp IS NULL BEGIN UPDATE activity_logs SET timestamp = CURRENT_TIMESTAMP WHERE id = NEW.id; END");
    }
}

function seed_db() {
    $pdo = get_db();
    if ((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() === 0) {
        $result = $pdo->exec("INSERT INTO users (username, password, email, is_admin, ssn) VALUES
        ('alice','alice123','alice@example.com',0,'123-45-6789'),
        ('bob','bobpass','bob@example.com',0,'987-65-4321'),
        ('admin','admin!2024','admin@example.com',1,'000-00-0000')");
        if ($result === false) {
            throw new RuntimeException('Demo user seed failed: ' . implode(' | ', array_filter($pdo->errorInfo())));
        }
    }

    if ((int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() === 0) {
        $result = $pdo->exec("INSERT INTO products (name, description, price, image_url) VALUES
        ('Wireless Mouse','Ergonomic 2.4GHz wireless mouse',19.99,'https://images.unsplash.com/photo-1527814050087-3793815479db?auto=format&fit=crop&w=800&q=80'),
        ('Mechanical Keyboard','RGB backlit mechanical keyboard',59.99,'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=800&q=80'),
        ('USB-C Hub','7-in-1 USB-C hub with HDMI',29.99,'https://images.unsplash.com/photo-1625842268584-8f3296236761?auto=format&fit=crop&w=800&q=80'),
        ('Webcam 1080p','Full HD webcam with autofocus',34.99,'https://images.unsplash.com/photo-1598327105666-5b89351aff97?auto=format&fit=crop&w=800&q=80'),
        ('Laptop Stand','Aluminum adjustable laptop stand',24.99,'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?auto=format&fit=crop&w=800&q=80')");
        if ($result === false) {
            throw new RuntimeException('Demo product seed failed: ' . implode(' | ', array_filter($pdo->errorInfo())));
        }
    }
}

get_db();
seed_db();

class Logger {
    public $logFile = 'app.log';
    public $logMsg = 'default log entry';

    public function __destruct() {
        @file_put_contents($this->logFile, $this->logMsg, FILE_APPEND);
    }
}
