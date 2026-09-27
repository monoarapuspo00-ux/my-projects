<?php
// layout.php — সব admin page এ include করা হয়
$cur = basename($_SERVER['PHP_SELF']);
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($page_title ?? 'Dashboard') ?> — Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
/* ===== RESET & BASE ===== */
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',sans-serif;background:#f1f5f9;color:#1e293b;min-height:100vh}

/* ===== SIDEBAR ===== */
#adminSidebar{
  position:fixed;top:0;left:0;width:260px;height:100vh;
  background:#0f172a;display:flex;flex-direction:column;
  z-index:1050;overflow-y:auto;transition:transform .3s ease;
  scrollbar-width:thin;scrollbar-color:#334155 #0f172a;
}
#adminSidebar::-webkit-scrollbar{width:4px}
#adminSidebar::-webkit-scrollbar-track{background:#0f172a}
#adminSidebar::-webkit-scrollbar-thumb{background:#334155;border-radius:4px}

/* Brand */
.sb-brand{padding:1.25rem 1.5rem;border-bottom:1px solid #1e293b;display:flex;align-items:center;gap:.75rem;flex-shrink:0}
.sb-logo{width:40px;height:40px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem;flex-shrink:0}
.sb-brand-text .name{color:#f1f5f9;font-weight:700;font-size:1rem;line-height:1.2}
.sb-brand-text .sub{color:#64748b;font-size:.72rem}

/* Nav sections */
.sb-section{padding:.75rem 1.25rem .25rem;font-size:.65rem;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:#475569}

/* Nav links */
.sb-nav a{
  display:flex;align-items:center;gap:.7rem;
  margin:.1rem .75rem;padding:.6rem .85rem;
  border-radius:8px;color:#94a3b8;text-decoration:none;
  font-size:.875rem;font-weight:500;transition:all .15s;
}
.sb-nav a i{width:18px;text-align:center;font-size:.9rem;flex-shrink:0}
.sb-nav a:hover{background:#1e293b;color:#e2e8f0}
.sb-nav a.active{background:#1d4ed8;color:#fff;box-shadow:0 4px 12px rgba(29,78,216,.3)}
.sb-nav a .badge-pill{margin-left:auto;background:#ef4444;color:#fff;border-radius:20px;padding:.1rem .5rem;font-size:.67rem;font-weight:700}

/* Footer */
.sb-footer{margin-top:auto;padding:1rem 1.25rem;border-top:1px solid #1e293b;display:flex;align-items:center;gap:.75rem;flex-shrink:0}
.sb-avatar{width:36px;height:36px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.9rem;flex-shrink:0}
.sb-footer .info .uname{color:#e2e8f0;font-size:.85rem;font-weight:600}
.sb-footer .info .role{color:#64748b;font-size:.72rem}
.sb-footer .logout-btn{margin-left:auto;color:#64748b;text-decoration:none;font-size:.9rem;padding:.25rem;border-radius:6px;transition:all .15s}
.sb-footer .logout-btn:hover{color:#ef4444;background:#1e293b}

/* Overlay */
#sbOverlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1040}

/* ===== TOPBAR ===== */
#adminTopbar{
  position:fixed;top:0;left:260px;right:0;height:60px;
  background:#fff;border-bottom:1px solid #e2e8f0;
  display:flex;align-items:center;padding:0 1.5rem;gap:1rem;
  z-index:1030;box-shadow:0 1px 4px rgba(0,0,0,.06);
}
.topbar-toggle{background:none;border:none;color:#64748b;font-size:1.2rem;cursor:pointer;padding:.25rem;border-radius:6px;display:none}
.topbar-toggle:hover{background:#f1f5f9;color:#1e293b}
.topbar-title{font-weight:700;font-size:1rem;color:#1e293b}
.topbar-right{margin-left:auto;display:flex;align-items:center;gap:.5rem}
.topbar-site-btn{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:8px;padding:.35rem .9rem;font-size:.82rem;font-weight:600;text-decoration:none;transition:all .15s}
.topbar-site-btn:hover{background:#dbeafe;color:#1e40af}

/* ===== MAIN CONTENT ===== */
#adminMain{margin-left:260px;padding-top:60px;min-height:100vh}
.content-wrap{padding:1.75rem}

/* ===== CARDS ===== */
.stat-card{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06);padding:1.25rem;display:flex;align-items:center;gap:1rem;transition:transform .2s,box-shadow .2s}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 4px 16px rgba(0,0,0,.1)}
.stat-icon{width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0}
.stat-num{font-size:1.75rem;font-weight:800;color:#0f172a;line-height:1}
.stat-label{font-size:.8rem;color:#64748b;margin-top:.2rem}

.section-card{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;margin-bottom:1.5rem}
.card-head{padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem}
.card-head h5{margin:0;font-weight:700;font-size:.95rem;color:#0f172a}
.card-body-p{padding:1.25rem}

/* ===== TABLE ===== */
.tbl{margin:0}
.tbl thead{background:#0f172a;color:#e2e8f0}
.tbl thead th{font-weight:600;font-size:.82rem;padding:.75rem 1rem;border:none}
.tbl tbody td{padding:.7rem 1rem;vertical-align:middle;border-color:#f1f5f9;font-size:.875rem}
.tbl tbody tr:hover{background:#f8fafc}

/* ===== FORM CONTROLS ===== */
.form-control:focus,.form-select:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.15)}
.btn-primary{background:#1d4ed8;border-color:#1d4ed8}
.btn-primary:hover{background:#1e40af;border-color:#1e40af}

/* ===== ALERTS ===== */
.alert-auto{animation:fadeOut 0s 3s forwards}
@keyframes fadeOut{to{opacity:0;pointer-events:none}}

/* ===== MOBILE ===== */
@media(max-width:991px){
  #adminSidebar{transform:translateX(-100%)}
  #adminSidebar.open{transform:translateX(0)}
  #adminMain{margin-left:0}
  #adminTopbar{left:0}
  .topbar-toggle{display:flex}
  #sbOverlay.show{display:block}
}
</style>
</head>
<body>

<div id="sbOverlay" onclick="closeSb()"></div>

<!-- ========== SIDEBAR ========== -->
<nav id="adminSidebar">
  <div class="sb-brand">
    <div class="sb-logo"><i class="fas fa-heartbeat"></i></div>
    <div class="sb-brand-text">
      <div class="name">Healthcare</div>
      <div class="sub">Admin Panel</div>
    </div>
  </div>

  <div class="sb-nav mt-1">
    <div class="sb-section">মেইন</div>
    <a href="/healthcare/admin/index.php" class="<?= $cur==='index.php'?'active':'' ?>">
      <i class="fas fa-gauge-high"></i>ড্যাশবোর্ড
    </a>

    <div class="sb-section">ব্যবস্থাপনা</div>
    <a href="/healthcare/admin/pharmacy.php" class="<?= $cur==='pharmacy.php'?'active':'' ?>">
    <i class="fas fa-pills"></i>ফার্মেসি
     </a>
    <a href="/healthcare/admin/hospitals.php" class="<?= $cur==='hospitals.php'?'active':'' ?>">
      <i class="fas fa-hospital"></i>হাসপাতাল
    </a>
    <a href="/healthcare/admin/departments.php" class="<?= $cur==='departments.php'?'active':'' ?>">
      <i class="fas fa-building-columns"></i>বিভাগ
    </a>
    <a href="/healthcare/admin/doctors.php" class="<?= $cur==='doctors.php'?'active':'' ?>">
      <i class="fas fa-user-doctor"></i>ডাক্তার
    </a>
    <a href="/healthcare/admin/symptoms.php" class="<?= $cur==='symptoms.php'?'active':'' ?>">
      <i class="fas fa-virus"></i>লক্ষণ
    </a>
    <a href="/healthcare/admin/tests.php" class="<?= $cur==='tests.php'?'active':'' ?>">
      <i class="fas fa-flask"></i>মেডিকেল টেস্ট
    </a>

    <div class="sb-section">বুকিং</div>
    <a href="/healthcare/admin/appointments.php" class="<?= $cur==='appointments.php'?'active':'' ?>">
      <i class="fas fa-calendar-check"></i>অ্যাপয়েন্টমেন্ট
    </a>
    <a href="/healthcare/admin/transport.php" class="<?= $cur==='transport.php'?'active':'' ?>">
      <i class="fas fa-ambulance"></i>পরিবহন বুকিং
    </a>
    <a href="/healthcare/admin/send_report.php" class="<?= $cur==='send_report.php'?'active':'' ?>">
      <i class="fas fa-paper-plane"></i>রিপোর্ট পাঠান
    </a>

    <div class="sb-section">সিস্টেম</div>
    <a href="/healthcare/admin/users.php" class="<?= $cur==='users.php'?'active':'' ?>">
      <i class="fas fa-users"></i>ব্যবহারকারী
    </a>
  </div>

  <div class="sb-footer">
    <div class="sb-avatar"><i class="fas fa-user"></i></div>
    <div class="info">
      <div class="uname"><?= htmlspecialchars($admin_name) ?></div>
      <div class="role">Administrator</div>
    </div>
    <a href="/healthcare/admin/logout.php" class="logout-btn" title="লগআউট">
      <i class="fas fa-right-from-bracket"></i>
    </a>
  </div>
</nav>

<!-- ========== TOPBAR ========== -->
<div id="adminTopbar">
  <button class="topbar-toggle" onclick="toggleSb()"><i class="fas fa-bars"></i></button>
  <span class="topbar-title">
    <i class="fas fa-heartbeat text-primary me-2"></i><?= htmlspecialchars($page_title ?? 'Dashboard') ?>
  </span>
  <div class="topbar-right">
    <a href="/healthcare/index.php" target="_blank" class="topbar-site-btn">
      <i class="fas fa-arrow-up-right-from-square me-1"></i>সাইট দেখুন
    </a>
  </div>
</div>

<!-- ========== MAIN ========== -->
<div id="adminMain">
<div class="content-wrap">
