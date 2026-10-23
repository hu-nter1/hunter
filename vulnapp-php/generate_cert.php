<?php
require __DIR__ . '/auth_gate.php';
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';

$user = current_user();
if (!$user) {
    header('Location: /login.php');
    exit;
}

$pdo = get_db();
$awardCode = is_string($_GET['award_code'] ?? null) ? trim($_GET['award_code']) : '';
if ($awardCode !== '') {
    $stmt = $pdo->prepare(
        'SELECT f.vuln_id, f.vulnerability_name, h.timestamp, h.learner_name, h.learner_email, h.award_code
         FROM flags f
         JOIN hall_of_fame h ON h.flag_id = f.id
         WHERE h.award_code = ? AND h.user_id = ?
         LIMIT 1'
    );
    $stmt->execute([$awardCode, $user['id']]);
} else {
    $flagId = (int)($_GET['flag_id'] ?? 0);
    $stmt = $pdo->prepare(
        'SELECT f.vuln_id, f.vulnerability_name, h.timestamp, h.learner_name, h.learner_email, h.award_code
         FROM flags f
         JOIN hall_of_fame h ON h.flag_id = f.id
         WHERE f.id = ? AND h.user_id = ?
         ORDER BY h.timestamp DESC
         LIMIT 1'
    );
    $stmt->execute([$flagId, $user['id']]);
}
$award = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$award) {
    http_response_code(404);
    exit('Achievement not found.');
}

require __DIR__ . '/includes/certificate_pdf.php';
send_certificate_pdf(
    $user['username'],
    $award['vulnerability_name'] ?: $award['vuln_id'],
    $award['timestamp'] . ' UTC',
    $award['learner_name'] ?? '',
    $award['learner_email'] ?? '',
    $award['award_code'] ?? ''
);
