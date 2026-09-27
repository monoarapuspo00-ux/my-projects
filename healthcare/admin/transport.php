<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
$page_title='পরিবহন বুকিং';
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){$pdo->prepare("UPDATE transport_bookings SET status=?,fare=? WHERE id=?")->execute([trim($_POST['status']),(float)$_POST['fare'],(int)$_POST['tb_id']]);$msg='আপডেট হয়েছে।';}
if(isset($_GET['action'],$_GET['id'])&&$_GET['action']==='delete'){$pdo->prepare("DELETE FROM transport_bookings WHERE id=?")->execute([(int)$_GET['id']]);$msg='মুছে ফেলা হয়েছে।';}
$f=$_GET['status']??'all';$w=$f!=='all'?"WHERE tb.status='$f'":'';
$rows=$pdo->query("SELECT tb.*,u.name AS un,u.phone AS up,h.name AS hn FROM transport_bookings tb JOIN users u ON tb.user_id=u.id JOIN hospitals h ON tb.hospital_id=h.id $w ORDER BY tb.created_at DESC")->fetchAll();
include __DIR__ . '/layout.php';
$vt=['ambulance'=>'অ্যাম্বুলেন্স','car'=>'গাড়ি','microbus'=>'মাইক্রোবাস'];
$sl=['pending'=>'পেন্ডিং','confirmed'=>'নিশ্চিত','cancelled'=>'বাতিল','completed'=>'সম্পন্ন'];
?>
<?php if($msg):?><div class="alert alert-success alert-dismissible fade show mb-3"><i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($msg)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>
<div class="section-card">
  <div class="card-head flex-wrap gap-2">
    <h5><i class="fas fa-ambulance text-danger me-2"></i>পরিবহন বুকিং (<?=count($rows)?>)</h5>
    <div class="d-flex gap-2 flex-wrap"><?php foreach(['all'=>'সব','pending'=>'পেন্ডিং','confirmed'=>'নিশ্চিত','cancelled'=>'বাতিল','completed'=>'সম্পন্ন'] as $k=>$v):?><a href="?status=<?=$k?>" class="btn btn-sm <?=$f===$k?'btn-danger':'btn-outline-secondary'?> rounded-pill"><?=$v?></a><?php endforeach;?></div>
  </div>
  <div class="table-responsive"><table class="table tbl table-hover mb-0">
    <thead><tr><th>#</th><th>ব্যবহারকারী</th><th>হাসপাতাল</th><th>যানবাহন</th><th>তারিখ</th><th>ভাড়া ও স্ট্যাটাস</th><th>মুছুন</th></tr></thead>
    <tbody><?php foreach($rows as $i=>$r):?><tr>
      <td><?=$i+1?></td><td><div class="fw-semibold"><?=htmlspecialchars($r['un'])?></div><small><?=htmlspecialchars($r['up']??'')?></small></td>
      <td><small><?=htmlspecialchars($r['hn'])?></small></td><td><span class="badge bg-info text-dark"><?=$vt[$r['vehicle_type']]??$r['vehicle_type']?></span></td>
      <td><small><?=$r['booking_date']?></small></td>
      <td><form method="POST" class="d-flex flex-column gap-1" style="min-width:140px"><input type="hidden" name="tb_id" value="<?=$r['id']?>"><input type="number" name="fare" class="form-control form-control-sm" value="<?=$r['fare']?>" step="0.01"><select name="status" class="form-select form-select-sm"><?php foreach($sl as $k=>$v):?><option value="<?=$k?>" <?=$r['status']===$k?'selected':''?>><?=$v?></option><?php endforeach;?></select><button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-check me-1"></i>আপডেট</button></form></td>
      <td><a href="?action=delete&id=<?=$r['id']?>&status=<?=$f?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('মুছে ফেলবেন?')"><i class="fas fa-trash"></i></a></td>
    </tr><?php endforeach;?></tbody>
  </table></div>
</div>
<?php include __DIR__ . '/layout_end.php'; ?>
