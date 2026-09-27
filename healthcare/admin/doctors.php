<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
$page_title = 'ডাক্তার ব্যবস্থাপনা';
$msg=$err='';
if(isset($_GET['action'],$_GET['id'])&&$_GET['action']==='delete'){$pdo->prepare("DELETE FROM doctors WHERE id=?")->execute([(int)$_GET['id']]);$msg='মুছে ফেলা হয়েছে।';}
if($_SERVER['REQUEST_METHOD']==='POST'){
  $n=trim($_POST['name']??'');$sp=trim($_POST['specialization']??'');$ph=trim($_POST['phone']??'');
  $sc=trim($_POST['schedule']??'');$fee=(float)($_POST['fee']??0);
  $h=(int)($_POST['hospital_id']??0);$d=(int)($_POST['department_id']??0);$id=(int)($_POST['edit_id']??0);
  if(!$n||!$h||!$d){$err='নাম, হাসপাতাল ও বিভাগ আবশ্যক।';}
  else{
    if($id){$pdo->prepare("UPDATE doctors SET name=?,specialization=?,phone=?,schedule=?,fee=?,hospital_id=?,department_id=? WHERE id=?")->execute([$n,$sp,$ph,$sc,$fee,$h,$d,$id]);$msg='আপডেট হয়েছে।';}
    else{$pdo->prepare("INSERT INTO doctors(name,specialization,phone,schedule,fee,hospital_id,department_id) VALUES(?,?,?,?,?,?,?)")->execute([$n,$sp,$ph,$sc,$fee,$h,$d]);$msg='যোগ হয়েছে।';}
  }
}
$edit=null;
if(isset($_GET['action'],$_GET['id'])&&$_GET['action']==='edit'){$s=$pdo->prepare("SELECT * FROM doctors WHERE id=?");$s->execute([(int)$_GET['id']]);$edit=$s->fetch();}
$hosps=$pdo->query("SELECT id,name FROM hospitals ORDER BY name")->fetchAll();
$depts=$pdo->query("SELECT id,name_bn FROM departments ORDER BY name_bn")->fetchAll();
$rows=$pdo->query("SELECT doc.*,h.name AS hn,dep.name_bn AS dn FROM doctors doc JOIN hospitals h ON doc.hospital_id=h.id JOIN departments dep ON doc.department_id=dep.id ORDER BY dep.name_bn,doc.name")->fetchAll();
include __DIR__ . '/layout.php';
?>
<?php if($msg):?><div class="alert alert-success alert-dismissible fade show mb-3"><i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($msg)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>
<?php if($err):?><div class="alert alert-danger alert-dismissible fade show mb-3"><?=htmlspecialchars($err)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>
<div class="row g-4">
  <div class="col-lg-4"><div class="section-card">
    <div class="card-head"><h5><i class="fas fa-<?=$edit?'pen':'plus'?> text-success me-2"></i><?=$edit?'সম্পাদনা':'নতুন ডাক্তার'?></h5><?php if($edit):?><a href="doctors.php" class="btn btn-sm btn-outline-secondary rounded-pill">বাতিল</a><?php endif;?></div>
    <div class="card-body-p"><form method="POST">
      <?php if($edit):?><input type="hidden" name="edit_id" value="<?=$edit['id']?>"><?php endif;?>
      <div class="mb-3"><label class="form-label fw-semibold">নাম <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required value="<?=htmlspecialchars($edit['name']??'')?>"></div>
      <div class="mb-3"><label class="form-label fw-semibold">বিশেষজ্ঞতা</label><input type="text" name="specialization" class="form-control" value="<?=htmlspecialchars($edit['specialization']??'')?>"></div>
      <div class="mb-3"><label class="form-label fw-semibold">হাসপাতাল <span class="text-danger">*</span></label>
        <select name="hospital_id" class="form-select" required><option value="">— সিলেক্ট করুন —</option>
        <?php foreach($hosps as $h):?><option value="<?=$h['id']?>" <?=($edit['hospital_id']??0)==$h['id']?'selected':''?>><?=htmlspecialchars($h['name'])?></option><?php endforeach;?></select></div>
      <div class="mb-3"><label class="form-label fw-semibold">বিভাগ <span class="text-danger">*</span></label>
        <select name="department_id" class="form-select" required><option value="">— সিলেক্ট করুন —</option>
        <?php foreach($depts as $d):?><option value="<?=$d['id']?>" <?=($edit['department_id']??0)==$d['id']?'selected':''?>><?=htmlspecialchars($d['name_bn'])?></option><?php endforeach;?></select></div>
      <div class="mb-3"><label class="form-label fw-semibold">ফোন</label><input type="text" name="phone" class="form-control" value="<?=htmlspecialchars($edit['phone']??'')?>"></div>
      <div class="mb-3"><label class="form-label fw-semibold">সময়সূচী</label><input type="text" name="schedule" class="form-control" value="<?=htmlspecialchars($edit['schedule']??'')?>"></div>
      <div class="mb-3"><label class="form-label fw-semibold">ফি (৳)</label><input type="number" step="0.01" name="fee" class="form-control" value="<?=$edit['fee']??0?>"></div>
      <button type="submit" class="btn btn-success w-100"><i class="fas fa-save me-2"></i><?=$edit?'আপডেট':'যোগ করুন'?></button>
    </form></div>
  </div></div>
  <div class="col-lg-8"><div class="section-card">
    <div class="card-head"><h5><i class="fas fa-user-doctor text-success me-2"></i>ডাক্তার (<?=count($rows)?>)</h5><input type="text" id="s" class="form-control form-control-sm" style="width:180px" placeholder="খুঁজুন..."></div>
    <div class="table-responsive"><table class="table tbl table-hover mb-0" id="t">
      <thead><tr><th>#</th><th>নাম</th><th>বিভাগ</th><th>হাসপাতাল</th><th>ফি</th><th>অ্যাকশন</th></tr></thead>
      <tbody><?php foreach($rows as $i=>$r):?><tr><td><?=$i+1?></td><td><div class="fw-semibold"><?=htmlspecialchars($r['name'])?></div><small class="text-muted"><?=htmlspecialchars($r['specialization']??'')?></small></td><td><span class="badge bg-primary"><?=htmlspecialchars($r['dn'])?></span></td><td><small><?=htmlspecialchars($r['hn'])?></small></td><td><strong class="text-primary">৳<?=number_format($r['fee'])?></strong></td><td><a href="?action=edit&id=<?=$r['id']?>" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-pen"></i></a><a href="?action=delete&id=<?=$r['id']?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('মুছে ফেলবেন?')"><i class="fas fa-trash"></i></a></td></tr><?php endforeach;?></tbody>
    </table></div>
  </div></div>
</div>
<script>document.getElementById('s').addEventListener('input',function(){const q=this.value.toLowerCase();document.querySelectorAll('#t tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});});</script>
<?php include __DIR__ . '/layout_end.php'; ?>
