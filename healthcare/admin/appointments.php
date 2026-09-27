<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
$page_title='অ্যাপয়েন্টমেন্ট ব্যবস্থাপনা';
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['update_status'])){$pdo->prepare("UPDATE appointments SET status=? WHERE id=?")->execute([trim($_POST['status']),(int)$_POST['appt_id']]);$msg='স্ট্যাটাস আপডেট হয়েছে।';}
if(isset($_GET['action'],$_GET['id'])&&$_GET['action']==='delete'){$pdo->prepare("DELETE FROM appointments WHERE id=?")->execute([(int)$_GET['id']]);$msg='মুছে ফেলা হয়েছে।';}
$f=$_GET['status']??'all';
$w=$f!=='all'?"WHERE a.status='$f'":'';
$rows=$pdo->query("SELECT a.*,u.name AS un,u.phone AS up,d.name AS dn,h.name AS hn FROM appointments a JOIN users u ON a.user_id=u.id JOIN doctors d ON a.doctor_id=d.id JOIN hospitals h ON a.hospital_id=h.id $w ORDER BY a.appointment_date DESC,a.appointment_time DESC")->fetchAll();
include __DIR__ . '/layout.php';
$sc=['pending'=>'warning','confirmed'=>'success','cancelled'=>'danger','completed'=>'secondary'];
$sl=['pending'=>'পেন্ডিং','confirmed'=>'নিশ্চিত','cancelled'=>'বাতিল','completed'=>'সম্পন্ন'];
?>
<?php if($msg):?><div class="alert alert-success alert-dismissible fade show mb-3"><i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($msg)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>
<div class="section-card">
  <div class="card-head flex-wrap gap-2">
    <h5><i class="fas fa-calendar-check text-primary me-2"></i>অ্যাপয়েন্টমেন্ট (<?=count($rows)?>)</h5>
    <div class="d-flex gap-2 flex-wrap">
      <?php foreach(['all'=>'সব','pending'=>'পেন্ডিং','confirmed'=>'নিশ্চিত','cancelled'=>'বাতিল','completed'=>'সম্পন্ন'] as $k=>$v):?><a href="?status=<?=$k?>" class="btn btn-sm <?=$f===$k?'btn-primary':'btn-outline-secondary'?> rounded-pill"><?=$v?></a><?php endforeach;?>
      <input type="text" id="s" class="form-control form-control-sm" style="width:160px" placeholder="খুঁজুন...">
    </div>
  </div>
  <div class="table-responsive"><table class="table tbl table-hover mb-0" id="t">
    <thead><tr><th>#</th><th>রোগী</th><th>ডাক্তার</th><th>হাসপাতাল</th><th>তারিখ</th><th>সময়</th><th>স্ট্যাটাস</th><th>মুছুন</th></tr></thead>
    <tbody><?php foreach($rows as $i=>$r):?><tr>
      <td><?=$i+1?></td>
      <td><div class="fw-semibold"><?=htmlspecialchars($r['un'])?></div><small class="text-muted"><?=htmlspecialchars($r['up'])?></small></td>
      <td><?=htmlspecialchars($r['dn'])?></td><td><small><?=htmlspecialchars($r['hn'])?></small></td>
      <td><?=$r['appointment_date']?></td><td><?=$r['appointment_time']?></td>
      <td><form method="POST" class="d-flex gap-1"><input type="hidden" name="appt_id" value="<?=$r['id']?>"><select name="status" class="form-select form-select-sm" style="width:110px"><?php foreach($sl as $k=>$v):?><option value="<?=$k?>" <?=$r['status']===$k?'selected':''?>><?=$v?></option><?php endforeach;?></select><button type="submit" name="update_status" class="btn btn-sm btn-primary"><i class="fas fa-check"></i></button></form></td>
      <td><a href="?action=delete&id=<?=$r['id']?>&status=<?=$f?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('মুছে ফেলবেন?')"><i class="fas fa-trash"></i></a></td>
    </tr><?php endforeach;?></tbody>
  </table></div>
</div>
<script>document.getElementById('s').addEventListener('input',function(){const q=this.value.toLowerCase();document.querySelectorAll('#t tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});});</script>
<?php include __DIR__ . '/layout_end.php'; ?>
