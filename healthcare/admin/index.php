<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
$page_title = 'ড্যাশবোর্ড';

$stats = [
    'hospitals'   => $pdo->query("SELECT COUNT(*) FROM hospitals")->fetchColumn(),
    'doctors'     => $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn(),
    'departments' => $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn(),
    'users'       => $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
    'appt_total'  => $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn(),
    'appt_pending'=> $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='pending'")->fetchColumn(),
    'trans_pending'=> $pdo->query("SELECT COUNT(*) FROM transport_bookings WHERE status='pending'")->fetchColumn(),
];

$recent_appts = $pdo->query("
    SELECT a.*, u.name AS uname, d.name AS dname, h.name AS hname
    FROM appointments a
    JOIN users u ON a.user_id=u.id
    JOIN doctors d ON a.doctor_id=d.id
    JOIN hospitals h ON a.hospital_id=h.id
    ORDER BY a.created_at DESC LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

$recent_trans = $pdo->query("
    SELECT tb.*, u.name AS uname, h.name AS hname
    FROM transport_bookings tb
    JOIN users u ON tb.user_id=u.id
    JOIN hospitals h ON tb.hospital_id=h.id
    ORDER BY tb.created_at DESC LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/layout.php';
?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
<?php
$cards = [
    ['fas fa-hospital',       '#eff6ff','#1d4ed8', $stats['hospitals'],    'হাসপাতাল'],
    ['fas fa-user-doctor',    '#f0fdf4','#15803d', $stats['doctors'],      'ডাক্তার'],
    ['fas fa-building-columns','#faf5ff','#7c3aed',$stats['departments'],  'বিভাগ'],
    ['fas fa-users',          '#f0fdfa','#0f766e', $stats['users'],        'ব্যবহারকারী'],
    ['fas fa-calendar-check', '#fff7ed','#c2410c', $stats['appt_total'],   'মোট অ্যাপয়েন্টমেন্ট'],
    ['fas fa-clock',          '#fef2f2','#dc2626', $stats['appt_pending'], 'পেন্ডিং অ্যাপয়েন্টমেন্ট'],
    ['fas fa-ambulance',      '#fdf4ff','#9333ea', $stats['trans_pending'],'পেন্ডিং পরিবহন'],
];
foreach ($cards as [$icon,$bg,$color,$num,$label]): ?>
<div class="col-xl-3 col-md-4 col-sm-6">
  <div class="stat-card">
    <div class="stat-icon" style="background:<?=$bg?>;color:<?=$color?>"><i class="<?=$icon?>"></i></div>
    <div><div class="stat-num"><?=$num?></div><div class="stat-label"><?=$label?></div></div>
  </div>
</div>
<?php endforeach; ?>
</div>

<!-- Recent Appointments -->
<div class="section-card mb-4">
  <div class="card-head">
    <h5><i class="fas fa-calendar-check text-primary me-2"></i>সাম্প্রতিক অ্যাপয়েন্টমেন্ট</h5>
    <a href="/healthcare/admin/appointments.php" class="btn btn-sm btn-outline-primary rounded-pill" style="font-size:.78rem">সব দেখুন</a>
  </div>
  <div class="table-responsive">
    <table class="table tbl table-hover mb-0">
      <thead><tr><th>রোগী</th><th>ডাক্তার</th><th>হাসপাতাল</th><th>তারিখ</th><th>স্ট্যাটাস</th></tr></thead>
      <tbody>
      <?php foreach($recent_appts as $a):
        $sc=['pending'=>'warning','confirmed'=>'success','cancelled'=>'danger','completed'=>'secondary'];
        $sl=['pending'=>'পেন্ডিং','confirmed'=>'নিশ্চিত','cancelled'=>'বাতিল','completed'=>'সম্পন্ন'];
      ?>
      <tr>
        <td class="fw-semibold"><?=htmlspecialchars($a['uname'])?></td>
        <td><?=htmlspecialchars($a['dname'])?></td>
        <td><small><?=htmlspecialchars($a['hname'])?></small></td>
        <td><small><?=$a['appointment_date']?></small></td>
        <td><span class="badge bg-<?=$sc[$a['status']]?>"><?=$sl[$a['status']]?></span></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Recent Transport -->
<div class="section-card">
  <div class="card-head">
    <h5><i class="fas fa-ambulance text-danger me-2"></i>সাম্প্রতিক পরিবহন বুকিং</h5>
    <a href="/healthcare/admin/transport.php" class="btn btn-sm btn-outline-danger rounded-pill" style="font-size:.78rem">সব দেখুন</a>
  </div>
  <div class="table-responsive">
    <table class="table tbl table-hover mb-0">
      <thead><tr><th>ব্যবহারকারী</th><th>হাসপাতাল</th><th>যানবাহন</th><th>তারিখ</th><th>স্ট্যাটাস</th></tr></thead>
      <tbody>
      <?php
      $vt=['ambulance'=>'অ্যাম্বুলেন্স','car'=>'গাড়ি','microbus'=>'মাইক্রোবাস'];
      foreach($recent_trans as $t):
        $sc=['pending'=>'warning','confirmed'=>'success','cancelled'=>'danger','completed'=>'secondary'];
        $sl=['pending'=>'পেন্ডিং','confirmed'=>'নিশ্চিত','cancelled'=>'বাতিল','completed'=>'সম্পন্ন'];
      ?>
      <tr>
        <td class="fw-semibold"><?=htmlspecialchars($t['uname'])?></td>
        <td><small><?=htmlspecialchars($t['hname'])?></small></td>
        <td><span class="badge bg-info text-dark"><?=$vt[$t['vehicle_type']]??$t['vehicle_type']?></span></td>
        <td><small><?=$t['booking_date']?></small></td>
        <td><span class="badge bg-<?=$sc[$t['status']]?>"><?=$sl[$t['status']]?></span></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/layout_end.php'; ?>
