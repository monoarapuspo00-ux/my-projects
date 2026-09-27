<?php
session_name('HEALTHCARE_ADMIN');
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

$result = [];

// 1. Session check
$result['session_admin_id'] = $_SESSION['admin_id'] ?? 'NOT SET';
$result['session_name'] = session_name();

// 2. test_bookings table exists?
try {
    $count = $pdo->query("SELECT COUNT(*) FROM test_bookings")->fetchColumn();
    $result['test_bookings_count'] = $count;
    $result['table_exists'] = true;
} catch (Exception $e) {
    $result['table_exists'] = false;
    $result['table_error'] = $e->getMessage();
}

// 3. Sample booking data
try {
    $rows = $pdo->query("
        SELECT tb.id, tb.status, u.name AS uname, u.email AS uemail
        FROM test_bookings tb
        JOIN users u ON tb.user_id = u.id
        LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);
    $result['sample_bookings'] = $rows;
} catch (Exception $e) {
    $result['sample_error'] = $e->getMessage();
}

// 4. mail.php loaded ok?
try {
    ob_start();
    require_once __DIR__ . '/../config/mail.php';
    ob_end_clean();
    $result['mail_php_loaded'] = true;
    $result['sendmail_exists'] = function_exists('sendMail');
} catch (Throwable $e) {
    ob_end_clean();
    $result['mail_php_error'] = $e->getMessage();
}

// 5. PHPMailer vendor exists?
$result['vendor_exists'] = file_exists(__DIR__ . '/../vendor/autoload.php');

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
