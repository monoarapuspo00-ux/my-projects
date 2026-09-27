<?php
session_start();
$base_url = '/healthcare';
require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['user_id'])) { header("Location: $base_url/index.php"); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($email && $pass) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_phone'] = $user['phone'];
            $_SESSION['user_role']  = $user['role'];
            header("Location: $base_url/index.php"); exit;
        } else { $error = 'ইমেইল বা পাসওয়ার্ড ভুল।'; }
    } else { $error = 'সব তথ্য পূরণ করুন।'; }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>লগইন — Healthcare</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body{min-height:100vh;background:linear-gradient(135deg,#0D47A1,#1565C0,#1976D2);display:flex;align-items:center;justify-content:center;font-family:'Segoe UI',sans-serif}
.card{border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.25);border:none;width:100%;max-width:420px}
.logo{width:68px;height:68px;background:linear-gradient(135deg,#1565C0,#0D47A1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.8rem;color:#fff}
.btn-login{background:linear-gradient(135deg,#1565C0,#0D47A1);border:none;width:100%;padding:.75rem;border-radius:10px;font-size:1rem;font-weight:600;transition:all .2s}
.btn-login:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(21,101,192,.4)}
.input-group-text{background:#f8f9fa;border-right:none}.form-control{border-left:none}
.form-control:focus{border-color:#1565C0;box-shadow:0 0 0 .2rem rgba(21,101,192,.2)}
</style>
</head>
<body>
<div class="card p-4">
    <div class="logo"><i class="fas fa-heartbeat"></i></div>
    <h4 class="text-center fw-bold mb-1">Healthcare Portal</h4>
    <p class="text-center text-muted mb-4" style="font-size:.9rem">আপনার অ্যাকাউন্টে লগইন করুন</p>
    <?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-semibold">ইমেইল</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                <input type="email" name="email" class="form-control" placeholder="your@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label fw-semibold">পাসওয়ার্ড</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                <input type="password" name="password" id="pwd" class="form-control" placeholder="••••••••" required>
                <button type="button" class="btn btn-outline-secondary" onclick="t=document.getElementById('pwd');t.type=t.type==='password'?'text':'password'"><i class="fas fa-eye"></i></button>
            </div>
        </div>
        <button type="submit" class="btn-login btn text-white"><i class="fas fa-sign-in-alt me-2"></i>লগইন করুন</button>
    </form>
    <p class="text-center mt-3 mb-0" style="font-size:.88rem">অ্যাকাউন্ট নেই? <a href="<?= $base_url ?>/auth/register.php" class="text-primary fw-semibold">নিবন্ধন করুন</a></p>
    <p class="text-center mt-1 mb-0"><a href="<?= $base_url ?>/index.php" class="text-muted" style="font-size:.82rem"><i class="fas fa-arrow-left me-1"></i>হোমে ফিরুন</a></p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
