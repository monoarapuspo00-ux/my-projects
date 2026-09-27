<?php
// ================================================================
// config/mail.php — Healthcare Portal
// ================================================================
// PHPMailer থাকলে সেটা ব্যবহার করবে,
// না থাকলে PHP socket দিয়ে সরাসরি Gmail SMTP ব্যবহার করবে।
// ================================================================

// ╔══════════════════════════════════════════════════════════════╗
// ║           এখানে শুধু আপনার তথ্য বসান                      ║
// ╠══════════════════════════════════════════════════════════════╣
define('MAIL_FROM',     'your-email@gmail.com');  // আপনার Gmail
define('MAIL_PASSWORD', 'YOUR_APP_PASSWORD');   // 16-digit App Password
define('MAIL_NAME',     'Healthcare Portal');     // Sender নাম
// ╚══════════════════════════════════════════════════════════════╝


// ────────────────────────────────────────────────────────────────
// sendMail() — main function
// ────────────────────────────────────────────────────────────────
function sendMail(
    string $to_email,
    string $to_name,
    string $subject,
    string $html_body
): bool {

    // Log directory
    $log_dir  = __DIR__ . '/../logs/';
    $log_file = $log_dir . 'mail.log';
    if (!is_dir($log_dir)) @mkdir($log_dir, 0755, true);

    // Validate email
    if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        @file_put_contents($log_file,
            date('Y-m-d H:i:s') . " | INVALID EMAIL | $to_email\n", FILE_APPEND);
        return false;
    }

    // Gmail credentials check
    if (MAIL_FROM === 'monoarapuspo00@gmail.com' || MAIL_PASSWORD === 'rxzyanoijmocynol') {
        @file_put_contents($log_file,
            date('Y-m-d H:i:s') . " | CONFIG ERROR | config/mail.php এ Gmail ও App Password বসানো হয়নি\n",
            FILE_APPEND);
        return false;
    }

    // ── Method 1: PHPMailer (manually installed) ─────────────────
    $phpmailer_path = __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    $autoload_path  = __DIR__ . '/../vendor/autoload.php';

    if (file_exists($phpmailer_path)) {
        return _sendWithPHPMailer($to_email, $to_name, $subject, $html_body, $log_file, 'manual');
    }
    if (file_exists($autoload_path)) {
        return _sendWithPHPMailer($to_email, $to_name, $subject, $html_body, $log_file, 'composer');
    }

    // ── Method 2: PHP Socket SMTP (PHPMailer ছাড়া) ──────────────
    return _sendWithSocket($to_email, $to_name, $subject, $html_body, $log_file);
}


// ────────────────────────────────────────────────────────────────
// Method 1: PHPMailer দিয়ে পাঠানো
// ────────────────────────────────────────────────────────────────
function _sendWithPHPMailer(
    string $to_email,
    string $to_name,
    string $subject,
    string $html_body,
    string $log_file,
    string $mode
): bool {

    if ($mode === 'manual') {
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
    } else {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_FROM;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Sender & recipient
        $mail->setFrom(MAIL_FROM, MAIL_NAME);
        $mail->addAddress($to_email, $to_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html_body;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html_body));

        $mail->send();

        @file_put_contents($log_file,
            date('Y-m-d H:i:s') . " | OK | TO:$to_email | $subject | via PHPMailer($mode)\n",
            FILE_APPEND);
        return true;

    } catch (\PHPMailer\PHPMailer\Exception $e) {
        @file_put_contents($log_file,
            date('Y-m-d H:i:s') . " | ERROR | TO:$to_email | PHPMailer: {$mail->ErrorInfo}\n",
            FILE_APPEND);
        return false;
    }
}


// ────────────────────────────────────────────────────────────────
// Method 2: PHP Socket দিয়ে Gmail SMTP (PHPMailer ছাড়া)
// ────────────────────────────────────────────────────────────────
function _sendWithSocket(
    string $to_email,
    string $to_name,
    string $subject,
    string $html_body,
    string $log_file
): bool {

    $host = 'ssl://smtp.gmail.com';
    $port = 465;

    // Socket open
    $errno  = 0;
    $errstr = '';
    $socket = @fsockopen($host, $port, $errno, $errstr, 20);

    if (!$socket) {
        @file_put_contents($log_file,
            date('Y-m-d H:i:s') . " | SOCKET FAIL | $errstr ($errno) — openssl extension চালু আছে কি?\n",
            FILE_APPEND);
        return false;
    }

    // Email headers
    $encoded_name    = '=?UTF-8?B?' . base64_encode(MAIL_NAME)  . '?=';
    $encoded_to_name = '=?UTF-8?B?' . base64_encode($to_name)   . '?=';
    $encoded_subject = '=?UTF-8?B?' . base64_encode($subject)   . '?=';

    $data  = "From: $encoded_name <" . MAIL_FROM . ">\r\n";
    $data .= "To: $encoded_to_name <$to_email>\r\n";
    $data .= "Subject: $encoded_subject\r\n";
    $data .= "MIME-Version: 1.0\r\n";
    $data .= "Content-Type: text/html; charset=UTF-8\r\n";
    $data .= "Content-Transfer-Encoding: base64\r\n";
    $data .= "Date: " . date('r') . "\r\n";
    $data .= "Message-ID: <" . time() . rand(1000,9999) . "@healthcare>\r\n\r\n";
    $data .= chunk_split(base64_encode($html_body));

    // SMTP steps
    $steps = [
        null,
        "EHLO healthcare.local\r\n",
        "AUTH LOGIN\r\n",
        base64_encode(MAIL_FROM)       . "\r\n",
        base64_encode(MAIL_PASSWORD)   . "\r\n",
        "MAIL FROM:<" . MAIL_FROM . ">\r\n",
        "RCPT TO:<$to_email>\r\n",
        "DATA\r\n",
        $data . "\r\n.\r\n",
        "QUIT\r\n",
    ];
    $expected = [220, 250, 334, 334, 235, 250, 250, 354, 250, 221];

    foreach ($steps as $i => $cmd) {
        if ($cmd !== null) {
            fwrite($socket, $cmd);
        }
        $resp = '';
        while ($line = fgets($socket, 515)) {
            $resp .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }
        $code = (int)substr(trim($resp), 0, 3);
        if ($code !== $expected[$i]) {
            fclose($socket);
            $err_msg = "SMTP step $i — expected {$expected[$i]}, got $code: " . trim($resp);
            @file_put_contents($log_file,
                date('Y-m-d H:i:s') . " | ERROR | TO:$to_email | $err_msg\n",
                FILE_APPEND);
            return false;
        }
    }

    fclose($socket);
    @file_put_contents($log_file,
        date('Y-m-d H:i:s') . " | OK | TO:$to_email | $subject | via Socket\n",
        FILE_APPEND);
    return true;
}
