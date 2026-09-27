<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
$page_title = 'হাসপাতাল ব্যবস্থাপনা';
$msg = $err = '';
if (isset($_GET['action'],$_GET['id']) && $_GET['action']==='delete') {
    $pdo->prepare("DELETE FROM hospitals WHERE id=?")->execute([(int)$_GET['id']]); $msg='হাসপাতাল মুছে ফেলা হয়েছে।';
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $n=trim($_POST['name']??''); $a=trim($_POST['address']??''); $ph=trim($_POST['phone']??'');
    $em=trim($_POST['email']??''); $lat=(float)($_POST['latitude']??0); $lng=(float)($_POST['longitude']??0); $id=(int)($_POST['edit_id']??0);
    if (!$n||!$a) { $err='নাম ও ঠিকানা আবশ্যক।'; }
    else {
        if ($id) { $pdo->prepare("UPDATE hospitals SET name=?,address=?,phone=?,email=?,latitude=?,longitude=? WHERE id=?")->execute([$n,$a,$ph,$em,$lat,$lng,$id]); $msg='আপডেট হয়েছে।'; }
        else { $pdo->prepare("INSERT INTO hospitals(name,address,phone,email,latitude,longitude) VALUES(?,?,?,?,?,?)")->execute([$n,$a,$ph,$em,$lat,$lng]); $msg='যোগ হয়েছে।'; }
    }
}
$edit=null;
if (isset($_GET['action'],$_GET['id'])&&$_GET['action']==='edit') { $s=$pdo->prepare("SELECT * FROM hospitals WHERE id=?"); $s->execute([(int)$_GET['id']]); $edit=$s->fetch(); }
$rows=$pdo->query("SELECT * FROM hospitals ORDER BY name")->fetchAll();
include __DIR__ . '/layout.php';
?>
<?php if($msg):?><div class="alert alert-success alert-dismissible fade show mb-3"><i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($msg)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>
<?php if($err):?><div class="alert alert-danger alert-dismissible fade show mb-3"><i class="fas fa-exclamation-circle me-2"></i><?=htmlspecialchars($err)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="section-card">
      <div class="card-head"><h5><i class="fas fa-<?=$edit?'pen':'plus'?> text-primary me-2"></i><?=$edit?'সম্পাদনা':'নতুন হাসপাতাল'?></h5><?php if($edit):?><a href="hospitals.php" class="btn btn-sm btn-outline-secondary rounded-pill">বাতিল</a><?php endif;?></div>
      <div class="card-body-p"><form method="POST">
        <?php if($edit):?><input type="hidden" name="edit_id" value="<?=$edit['id']?>"><?php endif;?>
        <div class="mb-3"><label class="form-label fw-semibold">নাম <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required value="<?=htmlspecialchars($edit['name']??'')?>"></div>
        <div class="mb-3"><label class="form-label fw-semibold">ঠিকানা <span class="text-danger">*</span></label><textarea name="address" class="form-control" rows="2" required><?=htmlspecialchars($edit['address']??'')?></textarea></div>
        <div class="mb-3"><label class="form-label fw-semibold">ফোন</label><input type="text" name="phone" class="form-control" value="<?=htmlspecialchars($edit['phone']??'')?>"></div>
        <div class="mb-3"><label class="form-label fw-semibold">ইমেইল</label><input type="email" name="email" class="form-control" value="<?=htmlspecialchars($edit['email']??'')?>"></div>
        <div class="row g-2 mb-3"><div class="col"><label class="form-label fw-semibold">Latitude</label><input type="number" step="any" name="latitude" class="form-control" value="<?=$edit['latitude']??0?>"></div><div class="col"><label class="form-label fw-semibold">Longitude</label><input type="number" step="any" name="longitude" class="form-control" value="<?=$edit['longitude']??0?>"></div></div>
        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-2"></i><?=$edit?'আপডেট':'যোগ করুন'?></button>
      </form></div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="section-card">
      <div class="card-head"><h5><i class="fas fa-hospital text-primary me-2"></i>হাসপাতাল (<?=count($rows)?>)</h5><input type="text" id="s" class="form-control form-control-sm" style="width:180px" placeholder="খুঁজুন..."></div>
      <div class="table-responsive"><table class="table tbl table-hover mb-0" id="t">
        <thead><tr><th>#</th><th>নাম</th><th>ঠিকানা</th><th>ফোন</th><th>অ্যাকশন</th></tr></thead>
        <tbody><?php foreach($rows as $i=>$h):?><tr><td><?=$i+1?></td><td class="fw-semibold"><?=htmlspecialchars($h['name'])?></td><td><small><?=htmlspecialchars($h['address'])?></small></td><td><small><?=htmlspecialchars($h['phone'])?></small></td><td><a href="?action=edit&id=<?=$h['id']?>" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-pen"></i></a><a href="?action=delete&id=<?=$h['id']?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('মুছে ফেলবেন?')"><i class="fas fa-trash"></i></a></td></tr><?php endforeach;?></tbody>
      </table></div>
    </div>
  </div>
</div>
<script>document.getElementById('s').addEventListener('input',function(){const q=this.value.toLowerCase();document.querySelectorAll('#t tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});});</script>
<?php include __DIR__ . '/layout_end.php'; ?>
