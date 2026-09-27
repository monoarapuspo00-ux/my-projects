<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
$page_title = 'বিভাগ ব্যবস্থাপনা';
$msg=$err='';
if(isset($_GET['action'],$_GET['id'])&&$_GET['action']==='delete'){$pdo->prepare("DELETE FROM departments WHERE id=?")->execute([(int)$_GET['id']]);$msg='মুছে ফেলা হয়েছে।';}
if($_SERVER['REQUEST_METHOD']==='POST'){
  $n=trim($_POST['name']??'');$bn=trim($_POST['name_bn']??'');$d=trim($_POST['description']??'');$id=(int)($_POST['edit_id']??0);
  if(!$n||!$bn){$err='নাম আবশ্যক।';}
  else{if($id){$pdo->prepare("UPDATE departments SET name=?,name_bn=?,description=? WHERE id=?")->execute([$n,$bn,$d,$id]);$msg='আপডেট হয়েছে।';}
  else{$pdo->prepare("INSERT INTO departments(name,name_bn,description) VALUES(?,?,?)")->execute([$n,$bn,$d]);$msg='যোগ হয়েছে।';}}
}
$edit=null;
if(isset($_GET['action'],$_GET['id'])&&$_GET['action']==='edit'){$s=$pdo->prepare("SELECT * FROM departments WHERE id=?");$s->execute([(int)$_GET['id']]);$edit=$s->fetch();}
$rows=$pdo->query("SELECT * FROM departments ORDER BY name_bn")->fetchAll();
include __DIR__ . '/layout.php';
?>
<?php if($msg):?><div class="alert alert-success alert-dismissible fade show mb-3"><i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($msg)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>
<?php if($err):?><div class="alert alert-danger alert-dismissible fade show mb-3"><?=htmlspecialchars($err)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>
<div class="row g-4">
  <div class="col-lg-4"><div class="section-card">
    <div class="card-head"><h5><i class="fas fa-<?=$edit?'pen':'plus'?> me-2" style="color:#7c3aed"></i><?=$edit?'সম্পাদনা':'নতুন বিভাগ'?></h5><?php if($edit):?><a href="departments.php" class="btn btn-sm btn-outline-secondary rounded-pill">বাতিল</a><?php endif;?></div>
    <div class="card-body-p"><form method="POST">
      <?php if($edit):?><input type="hidden" name="edit_id" value="<?=$edit['id']?>"><?php endif;?>
      <div class="mb-3"><label class="form-label fw-semibold">ইংরেজি নাম <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required value="<?=htmlspecialchars($edit['name']??'')?>"></div>
      <div class="mb-3"><label class="form-label fw-semibold">বাংলা নাম <span class="text-danger">*</span></label><input type="text" name="name_bn" class="form-control" required value="<?=htmlspecialchars($edit['name_bn']??'')?>"></div>
      <div class="mb-3"><label class="form-label fw-semibold">বিবরণ</label><textarea name="description" class="form-control" rows="3"><?=htmlspecialchars($edit['description']??'')?></textarea></div>
      <button type="submit" class="btn w-100 text-white" style="background:#7c3aed"><i class="fas fa-save me-2"></i><?=$edit?'আপডেট':'যোগ করুন'?></button>
    </form></div>
  </div></div>
  <div class="col-lg-8"><div class="section-card">
    <div class="card-head"><h5><i class="fas fa-building-columns me-2" style="color:#7c3aed"></i>বিভাগ (<?=count($rows)?>)</h5><input type="text" id="s" class="form-control form-control-sm" style="width:180px" placeholder="খুঁজুন..."></div>
    <div class="table-responsive"><table class="table tbl table-hover mb-0" id="t">
      <thead><tr><th>#</th><th>বাংলা</th><th>English</th><th>বিবরণ</th><th>অ্যাকশন</th></tr></thead>
      <tbody><?php foreach($rows as $i=>$r):?><tr><td><?=$i+1?></td><td class="fw-semibold"><?=htmlspecialchars($r['name_bn'])?></td><td><?=htmlspecialchars($r['name'])?></td><td><small class="text-muted"><?=htmlspecialchars($r['description'])?></small></td><td><a href="?action=edit&id=<?=$r['id']?>" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-pen"></i></a><a href="?action=delete&id=<?=$r['id']?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('মুছে ফেলবেন?')"><i class="fas fa-trash"></i></a></td></tr><?php endforeach;?></tbody>
    </table></div>
  </div></div>
</div>
<script>document.getElementById('s').addEventListener('input',function(){const q=this.value.toLowerCase();document.querySelectorAll('#t tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});});</script>
<?php include __DIR__ . '/layout_end.php'; ?>
