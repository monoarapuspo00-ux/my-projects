<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$base_url = '/healthcare';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Healthcare Portal</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --pri:#1565C0; --pri-d:#0D47A1; --suc:#2E7D32;
    --warn:#E65100; --bg:#F0F4F8;
}
body { font-family:'Hind Siliguri',sans-serif; background:var(--bg); }
.navbar-main {
    background:linear-gradient(135deg,#0D47A1,#1565C0);
    box-shadow:0 2px 10px rgba(0,0,0,.2);
}
.navbar-brand { font-weight:700; font-size:1.2rem; color:#fff !important; }
.navbar-brand i { color:#90CAF9; }
.nav-link { color:rgba(255,255,255,.85) !important; font-size:.9rem; transition:color .2s; }
.nav-link:hover, .nav-link.active { color:#fff !important; }
.nav-link i { margin-right:4px; }
.navbar-toggler { border-color:rgba(255,255,255,.3); }
.navbar-toggler-icon { filter:invert(1); }
.btn-custom {
    background:linear-gradient(135deg,var(--pri),var(--pri-d));
    color:#fff; border:none; border-radius:50px;
    padding:.5rem 1.5rem; transition:all .2s;
}
.btn-custom:hover { transform:translateY(-2px); box-shadow:0 4px 15px rgba(21,101,192,.4); color:#fff; }
.custom-card {
    background:#fff; border-radius:14px;
    box-shadow:0 4px 20px rgba(0,0,0,.07);
    padding:1.5rem; transition:transform .2s;
}
.custom-card:hover { transform:translateY(-3px); }
.hero-section {
    background:linear-gradient(135deg,#0D47A1,#1565C0,#1976D2);
    color:#fff; padding:3rem 1rem 2.5rem; text-align:center;
    border-radius:0 0 2rem 2rem; margin-bottom:2rem;
}
.hero-section h1 { font-size:2rem; font-weight:700; }
.feature-card {
    background:#fff; border-radius:14px;
    box-shadow:0 4px 20px rgba(0,0,0,.07);
    padding:1.8rem 1.5rem; text-align:center;
    transition:all .25s; height:100%;
}
.feature-card:hover { transform:translateY(-4px); box-shadow:0 8px 30px rgba(0,0,0,.12); }
.feature-card .icon { font-size:2.5rem; margin-bottom:1rem; }
.feature-card h5 { font-weight:700; color:#1a1a2e; }
.table-custom thead { background:#1565C0; color:#fff; }
.table-custom th,.table-custom td { vertical-align:middle; }
.table-custom tbody tr:hover { background:#E3F2FD; }
.result-card {
    background:#fff; border-radius:12px;
    box-shadow:0 4px 16px rgba(0,0,0,.07);
    padding:1.3rem; border-left:5px solid #1565C0;
    transition:transform .2s; height:100%;
}
.result-card:hover { transform:translateY(-3px); }
.price-lowest { color:#2E7D32; font-weight:700; }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-main sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?= $base_url ?>/index.php">
            <i class="fas fa-heartbeat  me-2"></i>Healthcare
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= in_array($current_page,['checker.php'])? 'active':'' ?>" href="<?= $base_url ?>/symptom/checker.php">
                        <i class="fas fa-stethoscope"></i>রোগ নির্ণয়
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page==='nearby.php'? 'active':'' ?>" href="<?= $base_url ?>/hospital/nearby.php">
                        <i class="fas fa-hospital"></i>হাসপাতাল
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page==='book.php'&&strpos($_SERVER['PHP_SELF'],'appointment')!==false? 'active':'' ?>" href="<?= $base_url ?>/appointment/book.php">
                        <i class="fas fa-calendar-check"></i>অ্যাপয়েন্টমেন্ট
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page==='search.php'? 'active':'' ?>" href="<?= $base_url ?>/tests/search.php">
                        <i class="fas fa-flask"></i>টেস্ট মূল্য
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page==='book.php'&&strpos($_SERVER['PHP_SELF'],'transport')!==false? 'active':'' ?>" href="<?= $base_url ?>/transport/book.php">
                        <i class="fas fa-ambulance"></i>পরিবহন
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page==='index.php'&&strpos($_SERVER['PHP_SELF'],'firstaid')!==false? 'active':'' ?>" href="<?= $base_url ?>/firstaid/index.php">
                        <i class="fas fa-kit-medical"></i>প্রাথমিক চিকিৎসা
                    </a>
                </li>
                 <li class="nav-item">
                <a class="nav-link" href="<?= $base_url ?>/pharmacy/index.php">
                        <i class="fas fa-pills"></i>ফার্মেসি
                </a>
                </li>
            </ul>
            <ul class="navbar-nav gap-1">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <span class="nav-link"><i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_url ?>/auth/logout.php"><i class="fas fa-sign-out-alt me-1"></i>লগআউট</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_url ?>/auth/login.php"><i class="fas fa-sign-in-alt me-1"></i>লগইন</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-custom btn-sm ms-2" href="<?= $base_url ?>/auth/register.php">নিবন্ধন</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
