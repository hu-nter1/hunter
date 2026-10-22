<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (getenv('DATABASE_URL') === false || getenv('DATABASE_URL') === '') {
    fwrite(STDERR, "Refusing to continue: DATABASE_URL must point to the Neon database.\n");
    exit(1);
}

if (!in_array('--confirm', $argv, true)) {
    fwrite(STDERR, "This permanently replaces all shop data with the demo users and products. Re-run with --confirm.\n");
    exit(1);
}

require_once __DIR__ . '/../config.php';

if (!USE_POSTGRES) {
    fwrite(STDERR, "Refusing to reset data: the active database is not PostgreSQL.\n");
    exit(1);
}

$pdo = get_db();
$tables = [
    'order_items',
    'orders',
    'comments',
    'reviews',
    'access_logs',
    'remember_tokens',
    'flags',
    'hall_of_fame',
    'activity_logs',
    'users',
    'products',
];

try {
    $pdo->beginTransaction();
    $pdo->exec('TRUNCATE TABLE ' . implode(', ', $tables) . ' RESTART IDENTITY CASCADE');
    seed_db();
    $pdo->commit();

    $summary = [];
    foreach (['users', 'products', 'orders', 'order_items'] as $table) {
        $summary[$table] = (int)$pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    }
    fwrite(STDOUT, 'Neon demo data reset complete: ' . json_encode($summary) . PHP_EOL);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Neon demo data reset failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
