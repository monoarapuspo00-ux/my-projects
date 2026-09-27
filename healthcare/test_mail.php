<?php
// ================================================================
// test_mail.php — Email test করুন
// Browser এ যান: http://localhost/healthcare/test_mail.php
// কাজ হলে এই ফাইল DELETE করুন!
// ================================================================
require_once __DIR__ . '/config/mail.php';

$result = '';
$steps  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_to = trim($_POST['test_email'] ?? '');

    if (!$test_to) {
        $result = 'error|ইমেইল দিন';
    } else {
        // Step by step diagnosis
        global $MAIL_FROM, $MAIL_PASSWORD;

        $steps[] = ['Gmail configured', !empty($MAIL_FROM) && $MAIL_FROM !== 'YOUR_GMAIL@gmail.com', $MAIL_FROM];
        $steps[] = ['Password configured', !empty($MAIL_PASSWORD) && $MAIL_PASSWORD !== 'YOUR_APP_PASSWORD', '****'];
        $steps[] = ['openssl extension', extension_loaded('openssl'), extension_loaded('openssl') ? 'চালু আছে' : 'বন্ধ — php.ini তে চালু করুন'];
        $steps[] = ['PHPMailer (manual)', file_exists(__DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php'), ''];
        $steps[] = ['PHPMailer (composer)', file_exists(__DIR__ . '/vendor/autoload.php'), ''];

        // Try send
        $ok = sendMail($test_to, 'Test User', 'Healthcare Test Email', '
            <div style="font-family:Arial;padding:20px;background:#f4f4f4">
                <div style="max-width:500px;margin:0 auto;background:#fff;border-radius:12px;padding:24px">
                    <h2 style="color:#1565C0">✅ Email কাজ করছে!</h2>
                    <p>Healthcare Portal থেকে test email সফলভাবে পাঠানো হয়েছে।</p>
                    <p style="color:#64748b;font-size:.85rem">সময়: ' . date('d M Y, h:i A') . '</p>
                </div>
            </div>
        ');
        $result = $ok ? 'success|✅ Email সফলভাবে পাঠানো হয়েছে!' : 'error|❌ Email পাঠানো ব্যর্থ — নিচে log দেখুন';

        // Read log
        $log_file = __DIR__ . '/logs/mail.log';
        $log_content = file_exists($log_file) ? file_get_contents($log_file) : 'log file নেই';
    }
}
[$res_type, $res_msg] = isset($result) && $result ? explode('|', $result, 2) : ['', ''];
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Email Test — Healthcare</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:#f0f4f8;font-family:'Segoe UI',sans-serif} .card{border-radius:14px;border:none;box-shadow:0 4px 20px rgba(0,0,0,.08)}</style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card p-4 mb-4">
                <h3 class="text-primary fw-bold mb-1"><i class="fas fa-envelope me-2"></i>Email Test</h3>
                <p class="text-muted mb-4">config/mail.php সঠিকভাবে কাজ করছে কিনা পরীক্ষা করুন</p>

                <?php if ($res_type): ?>
                <div class="alert alert-<?= $res_type==='success'?'success':'danger' ?> mb-3">
                    <?= htmlspecialchars($res_msg) ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($steps)): ?>
                <div class="mb-3">
                    <h6 class="fw-bold">সিস্টেম চেক:</h6>
                    <?php foreach ($steps as [$label, $ok, $val]): ?>
                    <div class="d-flex align-items-center gap-2 py-1 border-bottom">
                        <span><?= $ok ? '✅' : '❌' ?></span>
                        <span class="fw-semibold" style="min-width:220px"><?= $label ?></span>
                        <span class="text-muted small"><?= htmlspecialchars($val) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">যে ইমেইলে test পাঠাবেন</label>
                        <input type="email" name="test_email" class="form-control form-control-lg"
                               placeholder="test@gmail.com" required
                               value="<?= htmlspecialchars($_POST['test_email'] ?? '') ?>">
                        <small class="text-muted">আপনার নিজের ইমেইল দিন যেটায় আসলে পাবেন</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        Test Email পাঠান
                    </button>
                </form>
            </div>

            <?php
            $log_file = __DIR__ . '/logs/mail.log';
            if (file_exists($log_file)):
                $lines = array_filter(explode("\n", file_get_contents($log_file)));
                $lines = array_reverse(array_slice(array_values($lines), -10));
            ?>
            <div class="card p-4">
                <h6 class="fw-bold mb-3">📋 শেষ ১০টি Log Entry (logs/mail.log):</h6>
                <?php foreach ($lines as $line): ?>
                <div class="small p-2 mb-1 rounded <?= str_contains($line,'OK') ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' ?>">
                    <?= htmlspecialchars($line) ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="card p-4 mt-4">
                <h6 class="fw-bold">config/mail.php এর বর্তমান Gmail:</h6>
                <?php
                global $MAIL_FROM, $MAIL_PASSWORD;
                $from_ok = !empty($MAIL_FROM) && $MAIL_FROM !== 'YOUR_GMAIL@gmail.com';
                $pass_ok = !empty($MAIL_PASSWORD) && $MAIL_PASSWORD !== 'YOUR_APP_PASSWORD';
                ?>
                <p class="mb-1"><?= $from_ok ? '✅' : '❌' ?> Gmail: <code><?= htmlspecialchars($MAIL_FROM ?? 'set করা হয়নি') ?></code></p>
                <p class="mb-0"><?= $pass_ok ? '✅' : '❌' ?> Password: <code><?= $pass_ok ? '****' . substr($MAIL_PASSWORD, -4) : 'set করা হয়নি' ?></code></p>
            </div>

            <div class="alert alert-warning mt-3">
                <strong>⚠️ গুরুত্বপূর্ণ:</strong> কাজ হলে এই ফাইলটি DELETE করুন!<br>
                <code>healthcare/test_mail.php</code> মুছে ফেলুন।
            </div>
        </div>
    </div>
</div>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
