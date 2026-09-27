<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
$page_title='মেডিকেল টেস্ট ব্যবস্থাপনা';
$msg=$err='';
if(isset($_GET['action'],$_GET['id'])&&$_GET['action']==='delete'){$pdo->prepare("DELETE FROM medical_tests WHERE id=?")->execute([(int)$_GET['id']]);$msg='মুছে ফেলা হয়েছে।';}
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['save_test'])){
  $n=trim($_POST['name']??'');$d=trim($_POST['description']??'');$dep=(int)($_POST['department_id']??0)?:null;$id=(int)($_POST['edit_id']??0);
  if(!$n){$err='নাম আবশ্যক।';}
  else{if($id){$pdo->prepare("UPDATE medical_tests SET name=?,description=?,department_id=? WHERE id=?")->execute([$n,$d,$dep,$id]);$msg='আপডেট হয়েছে।';}
  else{$pdo->prepare("INSERT INTO medical_tests(name,description,department_id) VALUES(?,?,?)")->execute([$n,$d,$dep]);$msg='যোগ হয়েছে।';}}
}
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['save_price'])){
  $h=(int)$_POST['hospital_id'];$t=(int)$_POST['test_id'];$p=(float)$_POST['price'];
  $ex=$pdo->prepare("SELECT id FROM hospital_tests WHERE hospital_id=? AND test_id=?");$ex->execute([$h,$t]);
  if($ex->fetch()){$pdo->prepare("UPDATE hospital_tests SET price=? WHERE hospital_id=? AND test_id=?")->execute([$p,$h,$t]);}
  else{$pdo->prepare("INSERT INTO hospital_tests(hospital_id,test_id,price) VALUES(?,?,?)")->execute([$h,$t,$p]);}
  $msg='মূল্য সংরক্ষিত।';
}
$edit=null;
if(isset($_GET['action'],$_GET['id'])&&$_GET['action']==='edit'){$s=$pdo->prepare("SELECT * FROM medical_tests WHERE id=?");$s->execute([(int)$_GET['id']]);$edit=$s->fetch();}
$depts=$pdo->query("SELECT id,name_bn FROM departments ORDER BY name_bn")->fetchAll();
$hosps=$pdo->query("SELECT id,name FROM hospitals ORDER BY name")->fetchAll();
$rows=$pdo->query("SELECT mt.*,dep.name_bn AS dn FROM medical_tests mt LEFT JOIN departments dep ON mt.department_id=dep.id ORDER BY mt.name")->fetchAll();
$prices=$pdo->query("SELECT ht.*,h.name AS hn,mt.name AS tn FROM hospital_tests ht JOIN hospitals h ON ht.hospital_id=h.id JOIN medical_tests mt ON ht.test_id=mt.id ORDER BY mt.name,ht.price")->fetchAll();
include __DIR__ . '/layout.php';
?>
<?php if($msg):?><div class="alert alert-success alert-dismissible fade show mb-3"><i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($msg)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>
<?php if($err):?><div class="alert alert-danger alert-dismissible fade show mb-3"><?=htmlspecialchars($err)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="section-card mb-4">
      <div class="card-head"><h5><i class="fas fa-<?=$edit?'pen':'plus'?> text-warning me-2"></i><?=$edit?'সম্পাদনা':'নতুন টেস্ট'?></h5><?php if($edit):?><a href="tests.php" class="btn btn-sm btn-outline-secondary rounded-pill">বাতিল</a><?php endif;?></div>
      <div class="card-body-p"><form method="POST"><input type="hidden" name="save_test" value="1">
        <?php if($edit):?><input type="hidden" name="edit_id" value="<?=$edit['id']?>"><?php endif;?>
        <div class="mb-3"><label class="form-label fw-semibold">টেস্টের নাম <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required value="<?=htmlspecialchars($edit['name']??'')?>"></div>
        <div class="mb-3"><label class="form-label fw-semibold">বিবরণ</label><textarea name="description" class="form-control" rows="2"><?=htmlspecialchars($edit['description']??'')?></textarea></div>
        <div class="mb-3"><label class="form-label fw-semibold">বিভাগ</label><select name="department_id" class="form-select"><option value="">— নেই —</option><?php foreach($depts as $d):?><option value="<?=$d['id']?>" <?=($edit['department_id']??0)==$d['id']?'selected':''?>><?=htmlspecialchars($d['name_bn'])?></option><?php endforeach;?></select></div>
        <button type="submit" class="btn btn-warning w-100 text-white"><i class="fas fa-save me-2"></i><?=$edit?'আপডেট':'যোগ করুন'?></button>
      </form></div>
    </div>
    <div class="section-card">
      <div class="card-head"><h5><i class="fas fa-bangladeshi-taka-sign text-success me-2"></i>মূল্য যোগ</h5></div>
      <div class="card-body-p"><form method="POST"><input type="hidden" name="save_price" value="1">
        <div class="mb-3"><label class="form-label fw-semibold">হাসপাতাল</label><select name="hospital_id" class="form-select" required><option value="">— সিলেক্ট —</option><?php foreach($hosps as $h):?><option value="<?=$h['id']?>"><?=htmlspecialchars($h['name'])?></option><?php endforeach;?></select></div>
        <div class="mb-3"><label class="form-label fw-semibold">টেস্ট</label><select name="test_id" class="form-select" required><option value="">— সিলেক্ট —</option><?php foreach($rows as $r):?><option value="<?=$r['id']?>"><?=htmlspecialchars($r['name'])?></option><?php endforeach;?></select></div>
        <div class="mb-3"><label class="form-label fw-semibold">মূল্য (৳)</label><input type="number" step="0.01" name="price" class="form-control" required min="0"></div>
        <button type="submit" class="btn btn-success w-100"><i class="fas fa-save me-2"></i>সংরক্ষণ</button>
      </form></div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="section-card mb-4">
      <div class="card-head"><h5><i class="fas fa-flask text-warning me-2"></i>টেস্ট তালিকা (<?=count($rows)?>)</h5><input type="text" id="s" class="form-control form-control-sm" style="width:180px" placeholder="খুঁজুন..."></div>
      <div class="table-responsive"><table class="table tbl table-hover mb-0" id="t"><thead><tr><th>#</th><th>নাম</th><th>বিভাগ</th><th>অ্যাকশন</th></tr></thead><tbody><?php foreach($rows as $i=>$r):?><tr><td><?=$i+1?></td><td><div class="fw-semibold"><?=htmlspecialchars($r['name'])?></div><small class="text-muted"><?=htmlspecialchars($r['description']??'')?></small></td><td><?=$r['dn']?"<span class='badge bg-warning text-dark'>".htmlspecialchars($r['dn'])."</span>":'<span class="text-muted">—</span>'?></td><td><a href="?action=edit&id=<?=$r['id']?>" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-pen"></i></a><a href="?action=delete&id=<?=$r['id']?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('মুছে ফেলবেন?')"><i class="fas fa-trash"></i></a></td></tr><?php endforeach;?></tbody></table></div>
    </div>
    <div class="section-card">
      <div class="card-head"><h5><i class="fas fa-bangladeshi-taka-sign text-success me-2"></i>মূল্য তালিকা</h5></div>
      <div class="table-responsive"><table class="table tbl table-hover mb-0"><thead><tr><th>টেস্ট</th><th>হাসপাতাল</th><th>মূল্য</th></tr></thead><tbody><?php foreach($prices as $p):?><tr><td><?=htmlspecialchars($p['tn'])?></td><td><?=htmlspecialchars($p['hn'])?></td><td><strong class="text-primary">৳<?=number_format($p['price'])?></strong></td></tr><?php endforeach;?></tbody></table></div>
    </div>
  </div>
</div>
<script>document.getElementById('s').addEventListener('input',function(){const q=this.value.toLowerCase();document.querySelectorAll('#t tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});});</script>
<?php include __DIR__ . '/layout_end.php'; ?>
