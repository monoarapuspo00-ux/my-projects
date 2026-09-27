<?php
session_name('HEALTHCARE_ADMIN');
session_start();

if (isset($_SESSION['admin_id'])) {
    header('Location: /healthcare/admin/index.php'); exit;
}

require_once __DIR__ . '/../config/database.php';

$error = '';
$login_ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if (!$email || !$pass) {
        $error = 'সব তথ্য পূরণ করুন।';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['admin_id']   = $user['id'];
            $_SESSION['admin_name'] = $user['name'];
            $login_ok = true;
        } else {
            $error = 'ইমেইল বা পাসওয়ার্ড ভুল।';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login — Healthcare</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{min-height:100vh;background:#0f172a;display:flex;align-items:center;justify-content:center;font-family:'Segoe UI',sans-serif;padding:1rem}
  .login-wrap{width:100%;max-width:420px}
  .login-card{background:#1e293b;border-radius:16px;padding:2.5rem 2rem;box-shadow:0 25px 50px rgba(0,0,0,.5)}
  .logo-circle{width:72px;height:72px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-size:2rem;color:#fff;box-shadow:0 8px 24px rgba(59,130,246,.4)}
  h2{color:#f1f5f9;text-align:center;font-size:1.5rem;font-weight:700;margin-bottom:.25rem}
  .subtitle{color:#94a3b8;text-align:center;font-size:.88rem;margin-bottom:2rem}
  .form-label{color:#cbd5e1;font-size:.85rem;font-weight:600;margin-bottom:.4rem;display:block}
  .input-group-text{background:#0f172a;border:1px solid #334155;border-right:none;color:#64748b}
  .form-control{background:#0f172a;border:1px solid #334155;border-left:none;color:#f1f5f9;padding:.65rem 1rem}
  .form-control:focus{background:#0f172a;border-color:#3b82f6;color:#f1f5f9;box-shadow:0 0 0 3px rgba(59,130,246,.15)}
  .form-control::placeholder{color:#475569}
  .btn-login{width:100%;padding:.8rem;background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:#fff;border:none;border-radius:10px;font-size:1rem;font-weight:600;cursor:pointer;transition:all .2s;margin-top:.5rem}
  .btn-login:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(59,130,246,.4)}
  .alert-error{background:#7f1d1d;border:1px solid #991b1b;color:#fca5a5;border-radius:8px;padding:.75rem 1rem;margin-bottom:1.2rem;font-size:.88rem;display:flex;align-items:center;gap:.5rem}
  .divider{color:#334155;text-align:center;margin:1.5rem 0;font-size:.82rem;position:relative}
  .divider::before,.divider::after{content:'';position:absolute;top:50%;width:40%;height:1px;background:#334155}
  .divider::before{left:0}.divider::after{right:0}
  .back-link{display:block;text-align:center;color:#64748b;text-decoration:none;font-size:.83rem;margin-top:1.2rem;transition:color .2s}
  .back-link:hover{color:#94a3b8}
  .eye-btn{background:#0f172a;border:1px solid #334155;border-left:none;color:#64748b;cursor:pointer;padding:.65rem .85rem}
  .eye-btn:hover{color:#94a3b8}
  /* Credential hint box */
  .cred-box{margin-top:1.5rem;background:#0f172a;border:1px solid #334155;border-radius:10px;padding:1rem 1.1rem}
  .cred-title{color:#64748b;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;margin-bottom:.75rem;display:flex;align-items:center;gap:.4rem}
  .cred-row{display:flex;align-items:center;justify-content:space-between;padding:.35rem 0;border-bottom:1px solid #1e293b}
  .cred-row:last-of-type{border-bottom:none;margin-bottom:.75rem}
  .cred-label{color:#475569;font-size:.8rem}
  .cred-value{color:#94a3b8;font-size:.82rem;font-family:monospace;cursor:pointer;display:flex;align-items:center;gap:.4rem;padding:.2rem .4rem;border-radius:4px;transition:all .15s}
  .cred-value:hover{background:#1e293b;color:#e2e8f0}
  .cred-value i{font-size:.72rem;color:#475569}
  .cred-value.copied{color:#4ade80}
  .auto-fill-btn{width:100%;padding:.55rem;background:linear-gradient(135deg,#1d4ed8,#1e40af);color:#fff;border:none;border-radius:7px;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .2s}
  .auto-fill-btn:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(29,78,216,.35)}
</style>
</head>
<body>
<?php if(!empty($login_ok)):?><script>window.location.href="/healthcare/admin/index.php";</script><?php endif;?>
<div class="login-wrap">
  <div class="login-card">
    <div class="logo-circle"><i class="fas fa-shield-halved"></i></div>
    <h2>Admin Panel</h2>
    <p class="subtitle">Healthcare Management System</p>

    <?php if ($error): ?>
    <div class="alert-error"><i class="fas fa-circle-exclamation"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <div class="mb-3">
        <label class="form-label">ইমেইল অ্যাড্রেস</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-envelope fa-sm"></i></span>
          <input type="email" name="email" class="form-control" placeholder="admin@healthcare.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label">পাসওয়ার্ড</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-lock fa-sm"></i></span>
          <input type="password" name="password" id="pwdField" class="form-control" placeholder="••••••••" required>
          <button type="button" class="eye-btn" onclick="togglePwd()"><i class="fas fa-eye fa-sm" id="eyeIcon"></i></button>
        </div>
      </div>
      <button type="submit" class="btn-login"><i class="fas fa-right-to-bracket me-2"></i>লগইন করুন</button>
    </form>

    <a href="/healthcare/index.php" class="back-link"><i class="fas fa-arrow-left me-1"></i>মূল সাইটে ফিরুন</a>

    <!-- Login credentials hint -->
    <div class="cred-box">
      <div class="cred-title"><i class="fas fa-circle-info"></i> লগইন তথ্য</div>
      <div class="cred-row">
        <span class="cred-label">ইমেইল</span>
        <span class="cred-value" onclick="copyFill('email','admin@healthcare.com',this)">
          admin@healthcare.com <i class="fas fa-copy"></i>
        </span>
      </div>
      <div class="cred-row">
        <span class="cred-label">পাসওয়ার্ড</span>
        <span class="cred-value" id="pwdHint" onclick="toggleCredPwd()">
          <span id="pwdDots">••••••••</span>
          <span id="pwdText" style="display:none">password</span>
          <i class="fas fa-eye" id="credEye"></i>
        </span>
      </div>
      <button type="button" class="auto-fill-btn" onclick="autoFill()">
        <i class="fas fa-wand-magic-sparkles me-1"></i>স্বয়ংক্রিয় পূরণ করুন
      </button>
    </div>
  </div>
</div>
<script>
function togglePwd(){
  const f=document.getElementById('pwdField'),i=document.getElementById('eyeIcon');
  f.type=f.type==='password'?'text':'password';
  i.classList.toggle('fa-eye'); i.classList.toggle('fa-eye-slash');
}
function toggleCredPwd(){
  const dots=document.getElementById('pwdDots');
  const text=document.getElementById('pwdText');
  const eye=document.getElementById('credEye');
  const hidden=dots.style.display==='none';
  dots.style.display=hidden?'inline':'none';
  text.style.display=hidden?'none':'inline';
  eye.className=hidden?'fas fa-eye':'fas fa-eye-slash';
}
function copyFill(field, val, el){
  // form এ fill করো
  document.querySelector('input[name="'+field+'"]').value=val;
  // copied feedback
  const icon=el.querySelector('i');
  const orig=el.className;
  el.classList.add('copied');
  icon.className='fas fa-check';
  setTimeout(()=>{ el.className=orig; icon.className='fas fa-copy'; },1500);
}
function autoFill(){
  document.querySelector('input[name="email"]').value='admin@healthcare.com';
  document.querySelector('input[name="password"]').value='password';
  // password field দেখাও যাতে বুঝতে পারে
  document.getElementById('pwdField').type='text';
  document.getElementById('eyeIcon').className='fas fa-eye-slash fa-sm';
  setTimeout(()=>{
    document.getElementById('pwdField').type='password';
    document.getElementById('eyeIcon').className='fas fa-eye fa-sm';
  }, 2000);
}
</script>
</body>
</html>
