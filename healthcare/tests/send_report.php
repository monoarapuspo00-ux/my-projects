<?php
ob_start();
session_name('HEALTHCARE_ADMIN');
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'Invalid method']); exit;
}
if (!isset($_SESSION['admin_id'])) {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'Admin session নেই — পুনরায় লগইন করুন']); exit;
}

require_once __DIR__ . '/../config/database.php';

// ── Helper: send mail ─────────────────────────────────────────────
function doSendMail(string $to, string $name, string $subject, string $html): bool {
    $mail_file = __DIR__ . '/../config/mail.php';
    if (!file_exists($mail_file)) return false;
    require_once $mail_file;
    if (!function_exists('sendMail')) return false;
    try { return sendMail($to, $name, $subject, $html); }
    catch (Throwable $e) { error_log('Mail error: '.$e->getMessage()); return false; }
}

// ── Helper: build HTML email ──────────────────────────────────────
function buildHtml(string $name, string $test, string $hospital, string $result_text): string {
    return "<!DOCTYPE html><html><head><meta charset='UTF-8'>
<style>
body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:0}
.w{max-width:600px;margin:30px auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.1)}
.h{background:linear-gradient(135deg,#1565C0,#0D47A1);color:#fff;padding:24px 32px}
.h h2{margin:0;font-size:1.3rem}
.h p{margin:5px 0 0;opacity:.82;font-size:.84rem}
.b{padding:28px 32px}
.r{display:flex;justify-content:space-between;border-bottom:1px solid #f0f0f0;padding:10px 0}
.l{color:#78909C;font-size:.85rem}
.v{font-weight:600;font-size:.88rem}
.rb{background:#E3F2FD;border-left:4px solid #1565C0;padding:14px 16px;border-radius:6px;margin:18px 0}
.ok{display:inline-block;background:#E8F5E9;color:#2E7D32;border-radius:20px;padding:3px 12px;font-size:.78rem;font-weight:700}
.warn{color:#90A4AE;font-size:.82rem;margin-top:14px}
.f{background:#f8f9fa;padding:14px 32px;text-align:center;font-size:.78rem;color:#90A4AE}
</style></head><body>
<div class='w'>
<div class='h'><h2>🏥 মেডিকেল রিপোর্ট</h2><p>Healthcare Portal — পরীক্ষার ফলাফল</p></div>
<div class='b'>
<p>প্রিয় <strong>$name</strong>,</p>
<p>আপনার মেডিকেল পরীক্ষার ফলাফল প্রস্তুত হয়েছে।</p>
<div class='r'><span class='l'>পরীক্ষার নাম</span><span class='v'>$test</span></div>
<div class='r'><span class='l'>হাসপাতাল</span><span class='v'>$hospital</span></div>
<div class='r'><span class='l'>রিপোর্ট তারিখ</span><span class='v'>".date('d M Y, h:i A')."</span></div>
<div class='r'><span class='l'>স্ট্যাটাস</span><span class='v'><span class='ok'>✓ সম্পন্ন</span></span></div>"
.($result_text ? "<div class='rb'><strong>ফলাফলের সারসংক্ষেপ:</strong><p style='margin:.5rem 0 0'>".nl2br(htmlspecialchars($result_text))."</p></div>" : '')
."<p class='warn'>⚠️ বিশেষজ্ঞ ডাক্তারের পরামর্শ নিন।</p>
</div><div class='f'>Healthcare Portal &copy; ".date('Y')." — আপনার স্বাস্থ্য, আমাদের দায়িত্ব</div>
</div></body></html>";
}

// ── Diagnose mail failure ─────────────────────────────────────────
function mailDiag(): string {
    if (!file_exists(__DIR__ . '/../config/mail.php'))
        return 'config/mail.php পাওয়া যায়নি';
    $mc = file_get_contents(__DIR__ . '/../config/mail.php');
    if (strpos($mc, 'your_gmail@gmail.com') !== false)
        return 'config/mail.php এ আপনার Gmail ও App Password বসান';
    if (!file_exists(__DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php')
        && !file_exists(__DIR__ . '/../vendor/autoload.php'))
        return 'PHPMailer install করুন (vendor/ folder এ রাখুন)';
    $log = __DIR__ . '/../logs/mail.log';
    if (file_exists($log)) {
        $lines = array_filter(explode("\n", file_get_contents($log)));
        $last  = end($lines);
        if ($last) return 'শেষ error: ' . htmlspecialchars($last);
    }
    return 'logs/mail.log দেখুন অথবা SMTP credentials চেক করুন';
}

// =================================================================
// MODE 1 — Direct Email (admin নিজে লিখে পাঠান)
// =================================================================
if (!empty($_POST['direct_email'])) {
    $to_email = trim($_POST['to_email'] ?? '');
    $to_name  = trim($_POST['to_name']  ?? 'রোগী');
    $subject  = trim($_POST['subject']  ?? 'Healthcare Portal — বার্তা');
    $body_txt = trim($_POST['body']     ?? '');

    if (!$to_email || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        ob_end_clean();
        echo json_encode(['success'=>false,'message'=>'সঠিক ইমেইল দিন']);
        exit;
    }

    $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'>
<style>body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:0}
.w{max-width:600px;margin:30px auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.1)}
.h{background:linear-gradient(135deg,#1565C0,#0D47A1);color:#fff;padding:24px 32px}
.h h2{margin:0;font-size:1.2rem}.b{padding:28px 32px;color:#334155;line-height:1.7}
.f{background:#f8f9fa;padding:14px 32px;text-align:center;font-size:.78rem;color:#90A4AE}</style>
</head><body><div class='w'>
<div class='h'><h2>🏥 Healthcare Portal — বার্তা</h2></div>
<div class='b'><p>প্রিয় <strong>$to_name</strong>,</p>
".nl2br(htmlspecialchars($body_txt))."
<hr style='border:none;border-top:1px solid #f0f0f0;margin:1.5rem 0'>
<p style='color:#90A4AE;font-size:.82rem'>Healthcare Portal Admin Team</p>
</div><div class='f'>Healthcare Portal &copy; ".date('Y')."</div></div></body></html>";

    $sent = doSendMail($to_email, $to_name, $subject, $html);
    ob_end_clean();
    if ($sent) {
        echo json_encode(['success'=>true, 'message'=>"✅ Email সফলভাবে পাঠানো হয়েছে ($to_email)"]);
    } else {
        echo json_encode(['success'=>false, 'message'=>'❌ Email পাঠানো ব্যর্থ — '.mailDiag()]);
    }
    exit;
}

// =================================================================
// MODE 2 — Booking Report Email
// =================================================================
$booking_id  = (int)($_POST['booking_id'] ?? 0);
$result_text = trim($_POST['result_text'] ?? '');

if (!$booking_id) {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'Booking ID missing']); exit;
}

// test_bookings table check
try { $pdo->query("SELECT 1 FROM test_bookings LIMIT 1"); }
catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'test_bookings table নেই। Admin panel এ গিয়ে table তৈরি হবে।']); exit;
}

// Booking fetch
try {
    $stmt = $pdo->prepare("
        SELECT tb.*, u.name AS user_name, u.email AS user_email,
               h.name AS hospital_name, mt.name AS test_name
        FROM test_bookings tb
        JOIN users u          ON tb.user_id     = u.id
        JOIN hospitals h      ON tb.hospital_id = h.id
        JOIN medical_tests mt ON tb.test_id     = mt.id
        WHERE tb.id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'DB error: '.$e->getMessage()]); exit;
}

if (!$booking) {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>"Booking #$booking_id পাওয়া যায়নি"]); exit;
}
if (empty($booking['user_email'])) {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'রোগীর ইমেইল নেই']); exit;
}

// File upload
$report_path = '';
if (!empty($_FILES['report_file']['tmp_name']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/../uploads/reports/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $ext = strtolower(pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf','jpg','jpeg','png'])) {
        ob_end_clean();
        echo json_encode(['success'=>false,'message'=>'শুধু PDF, JPG বা PNG আপলোড করুন']); exit;
    }
    $fn = 'report_'.$booking_id.'_'.time().'.'.$ext;
    move_uploaded_file($_FILES['report_file']['tmp_name'], $upload_dir.$fn);
    $report_path = $fn;
}

// DB update
if ($report_path || $result_text) {
    try {
        $pdo->prepare("UPDATE test_bookings
            SET result_text = ?,
                report_file = CASE WHEN ? != '' THEN ? ELSE report_file END,
                status = 'completed'
            WHERE id = ?")->execute([$result_text, $report_path, $report_path, $booking_id]);
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode(['success'=>false,'message'=>'DB update error: '.$e->getMessage()]); exit;
    }
}

// Build & send email
$html  = buildHtml($booking['user_name'], $booking['test_name'], $booking['hospital_name'], $result_text);
$sent  = doSendMail($booking['user_email'], $booking['user_name'],
    "আপনার {$booking['test_name']} রিপোর্ট প্রস্তুত — Healthcare Portal", $html);

ob_end_clean();
if ($sent) {
    echo json_encode(['success'=>true,'mail_sent'=>true,
        'message'=>"✅ রিপোর্ট সফলভাবে পাঠানো হয়েছে ({$booking['user_email']})"]);
} else {
    echo json_encode(['success'=>false,'mail_sent'=>false,'db_updated'=>($report_path||$result_text),
        'message'=>'⚠️ DB আপডেট হয়েছে, কিন্তু email যায়নি — '.mailDiag()]);
}
