<?php
session_start();
$base_url = '/healthcare';
require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['user_id'])) { header("Location: $base_url/index.php"); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (!$name || !$email || !$phone || !$pass) { $error = 'সব তথ্য পূরণ করুন।'; }
    elseif ($pass !== $pass2)  { $error = 'পাসওয়ার্ড মিলছে না।'; }
    elseif (strlen($pass) < 6) { $error = 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।'; }
    else {
        $c = $pdo->prepare("SELECT id FROM users WHERE email=?"); $c->execute([$email]);
        if ($c->fetch()) { $error = 'এই ইমেইল ইতিমধ্যে নিবন্ধিত।'; }
        else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users(name,email,phone,password,role) VALUES(?,?,?,?,'user')")->execute([$name,$email,$phone,$hash]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $name; $_SESSION['user_phone'] = $phone;
            header("Location: $base_url/index.php"); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>নিবন্ধন — Healthcare</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<style>
body{min-height:100vh;background:linear-gradient(135deg,#0D47A1,#1565C0,#1976D2);display:flex;align-items:center;justify-content:center;font-family:'Segoe UI',sans-serif;padding:1.5rem}
.card{border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.25);border:none;width:100%;max-width:480px}
.logo{width:64px;height:64px;background:linear-gradient(135deg,#1565C0,#0D47A1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.6rem;color:#fff}
.btn-reg{background:linear-gradient(135deg,#1565C0,#0D47A1);border:none;width:100%;padding:.75rem;border-radius:10px;font-size:1rem;font-weight:600;transition:all .2s}
.btn-reg:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(21,101,192,.4)}
.input-group-text{background:#f8f9fa;border-right:none}.form-control{border-left:none}
.form-control:focus{border-color:#1565C0;box-shadow:0 0 0 .2rem rgba(21,101,192,.2)}
#nameFeedback{font-size:.8rem;margin-top:.3rem;min-height:1.1rem}
.name-ok{color:#2E7D32}.name-taken{color:#C62828}.name-loading{color:#1565C0}.name-short{color:#E65100}
.strength-bar{height:5px;border-radius:3px;transition:all .3s;margin-top:.3rem;width:0}
</style>
</head>
<body>
<div class="card p-4">
    <div class="logo"><i class="fas fa-user-plus"></i></div>
    <h4 class="text-center fw-bold mb-1">নিবন্ধন করুন</h4>
    <p class="text-center text-muted mb-3" style="font-size:.88rem">Healthcare Portal এ স্বাগতম</p>
    <?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-semibold">পূর্ণ নাম <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user text-muted"></i></span>
                <input type="text" name="name" id="nameInput" class="form-control" placeholder="আপনার নাম" autocomplete="off" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                <span class="input-group-text bg-white border-start-0" id="nameSpinner" style="display:none"><span class="spinner-border spinner-border-sm text-primary"></span></span>
            </div>
            <div id="nameFeedback"></div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">ইমেইল <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                <input type="email" name="email" class="form-control" placeholder="your@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">ফোন <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-phone text-muted"></i></span>
                <input type="tel" name="phone" class="form-control" placeholder="01XXXXXXXXX" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">পাসওয়ার্ড <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                <input type="password" name="password" id="pwd" class="form-control" placeholder="কমপক্ষে ৬ অক্ষর" required>
                <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('pwd','e1')"><i class="fas fa-eye" id="e1"></i></button>
            </div>
            <div class="strength-bar" id="sbar"></div>
            <small id="stext" class="text-muted"></small>
        </div>
        <div class="mb-4">
            <label class="form-label fw-semibold">পাসওয়ার্ড নিশ্চিত করুন <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                <input type="password" name="password2" id="pwd2" class="form-control" placeholder="পাসওয়ার্ড আবার লিখুন" required>
                <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('pwd2','e2')"><i class="fas fa-eye" id="e2"></i></button>
            </div>
            <div id="pwdMatch" style="font-size:.8rem;margin-top:.3rem;min-height:1.1rem"></div>
        </div>
        <button type="submit" class="btn-reg btn text-white"><i class="fas fa-user-plus me-2"></i>নিবন্ধন করুন</button>
    </form>
    <p class="text-center mt-3 mb-0" style="font-size:.88rem">ইতিমধ্যে অ্যাকাউন্ট আছে? <a href="<?= $base_url ?>/auth/login.php" class="text-primary fw-semibold">লগইন করুন</a></p>
</div>

<script>
let nameTimer;
$('#nameInput').on('input',function(){
    const v=$(this).val().trim();
    clearTimeout(nameTimer);
    $('#nameFeedback').text('').removeClass('name-ok name-taken name-loading name-short');
    if(!v)return;
    if(v.length<3){$('#nameFeedback').text('কমপক্ষে ৩ অক্ষর লিখুন').addClass('name-short');return;}
    nameTimer=setTimeout(()=>{
        $('#nameFeedback').text('চেক করা হচ্ছে...').addClass('name-loading');
        $('#nameSpinner').show();
        $.post('/healthcare/auth/check_name.php',{name:v},function(r){
            $('#nameSpinner').hide();
            $('#nameFeedback').removeClass('name-loading');
            if(r.exists) $('#nameFeedback').text('⚠️ এই নামে ইতিমধ্যে নিবন্ধন আছে।').addClass('name-taken');
            else $('#nameFeedback').text('✓ এই নামটি ব্যবহারযোগ্য').addClass('name-ok');
        },'json');
    },400);
});

$('#pwd').on('input',function(){
    const v=$(this).val();
    let s=0;
    if(v.length>=6)s++;if(v.length>=10)s++;
    if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;
    const c=['','#ef5350','#FF9800','#FDD835','#66BB6A','#2E7D32'];
    const l=['','খুবই দুর্বল','দুর্বল','মোটামুটি','শক্তিশালী','খুবই শক্তিশালী'];
    $('#sbar').css({width:(s*20)+'%',background:c[s]});
    $('#stext').text(s?l[s]:'').css('color',c[s]);
    checkMatch();
});
$('#pwd2').on('input',checkMatch);
function checkMatch(){
    const p1=$('#pwd').val(),p2=$('#pwd2').val();
    if(!p2){$('#pwdMatch').text('');return;}
    if(p1===p2)$('#pwdMatch').text('✓ পাসওয়ার্ড মিলেছে').css('color','#2E7D32');
    else $('#pwdMatch').text('✗ পাসওয়ার্ড মিলছে না').css('color','#C62828');
}
function togglePwd(i,e){
    const el=document.getElementById(i),ic=document.getElementById(e);
    el.type=el.type==='password'?'text':'password';
    ic.classList.toggle('fa-eye');ic.classList.toggle('fa-eye-slash');
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
