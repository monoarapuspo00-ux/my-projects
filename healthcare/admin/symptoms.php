<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
$page_title='লক্ষণ ব্যবস্থাপনা';
$msg=$err='';
if(isset($_GET['action'],$_GET['id'])&&$_GET['action']==='delete'){$pdo->prepare("DELETE FROM symptoms WHERE id=?")->execute([(int)$_GET['id']]);$msg='মুছে ফেলা হয়েছে।';}
if($_SERVER['REQUEST_METHOD']==='POST'){
  $n=trim($_POST['name']??'');$bn=trim($_POST['name_bn']??'');$d=trim($_POST['description']??'');
  $deps=array_map('intval',$_POST['departments']??[]);$tsts=array_map('intval',$_POST['tests']??[]);$id=(int)($_POST['edit_id']??0);
  if(!$n||!$bn){$err='নাম আবশ্যক।';}
  else{
    if($id){$pdo->prepare("UPDATE symptoms SET name=?,name_bn=?,description=? WHERE id=?")->execute([$n,$bn,$d,$id]);$pdo->prepare("DELETE FROM symptom_department WHERE symptom_id=?")->execute([$id]);$pdo->prepare("DELETE FROM symptom_tests WHERE symptom_id=?")->execute([$id]);$msg='আপডেট হয়েছে।';}
    else{$pdo->prepare("INSERT INTO symptoms(name,name_bn,description) VALUES(?,?,?)")->execute([$n,$bn,$d]);$id=$pdo->lastInsertId();$msg='যোগ হয়েছে।';}
    foreach($deps as $did){$pdo->prepare("INSERT IGNORE INTO symptom_department(symptom_id,department_id) VALUES(?,?)")->execute([$id,$did]);}
    foreach($tsts as $tid){$pdo->prepare("INSERT IGNORE INTO symptom_tests(symptom_id,test_id) VALUES(?,?)")->execute([$id,$tid]);}
  }
}
$edit=null;$ed=[];$et=[];
if(isset($_GET['action'],$_GET['id'])&&$_GET['action']==='edit'){
  $s=$pdo->prepare("SELECT * FROM symptoms WHERE id=?");$s->execute([(int)$_GET['id']]);$edit=$s->fetch();
  $ed=array_column($pdo->query("SELECT department_id FROM symptom_department WHERE symptom_id=".(int)$_GET['id'])->fetchAll(),'department_id');
  $et=array_column($pdo->query("SELECT test_id FROM symptom_tests WHERE symptom_id=".(int)$_GET['id'])->fetchAll(),'test_id');
}
$depts=$pdo->query("SELECT id,name_bn FROM departments ORDER BY name_bn")->fetchAll();
$mtests=$pdo->query("SELECT id,name FROM medical_tests ORDER BY name")->fetchAll();
$rows=$pdo->query("SELECT * FROM symptoms ORDER BY name_bn")->fetchAll();
include __DIR__ . '/layout.php';
?>
<?php if($msg):?><div class="alert alert-success alert-dismissible fade show mb-3"><i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($msg)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>
<?php if($err):?><div class="alert alert-danger alert-dismissible fade show mb-3"><?=htmlspecialchars($err)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>
<div class="row g-4">
  <div class="col-lg-5"><div class="section-card">
    <div class="card-head"><h5><i class="fas fa-<?=$edit?'pen':'plus'?> text-danger me-2"></i><?=$edit?'সম্পাদনা':'নতুন লক্ষণ'?></h5><?php if($edit):?><a href="symptoms.php" class="btn btn-sm btn-outline-secondary rounded-pill">বাতিল</a><?php endif;?></div>
    <div class="card-body-p"><form method="POST">
      <?php if($edit):?><input type="hidden" name="edit_id" value="<?=$edit['id']?>"><?php endif;?>
      <div class="mb-3"><label class="form-label fw-semibold">ইংরেজি নাম <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required value="<?=htmlspecialchars($edit['name']??'')?>"></div>
      <div class="mb-3"><label class="form-label fw-semibold">বাংলা নাম <span class="text-danger">*</span></label><input type="text" name="name_bn" class="form-control" required value="<?=htmlspecialchars($edit['name_bn']??'')?>"></div>
      <div class="mb-3"><label class="form-label fw-semibold">বিবরণ</label><textarea name="description" class="form-control" rows="2"><?=htmlspecialchars($edit['description']??'')?></textarea></div>
      <div class="mb-3"><label class="form-label fw-semibold">সংশ্লিষ্ট বিভাগ</label><div class="border rounded p-2" style="max-height:120px;overflow-y:auto"><?php foreach($depts as $d):?><div class="form-check"><input class="form-check-input" type="checkbox" name="departments[]" value="<?=$d['id']?>" id="dd<?=$d['id']?>" <?=in_array($d['id'],$ed)?'checked':''?>><label class="form-check-label small" for="dd<?=$d['id']?>"><?=htmlspecialchars($d['name_bn'])?></label></div><?php endforeach;?></div></div>
      <div class="mb-3"><label class="form-label fw-semibold">সংশ্লিষ্ট পরীক্ষা</label><div class="border rounded p-2" style="max-height:120px;overflow-y:auto"><?php foreach($mtests as $t):?><div class="form-check"><input class="form-check-input" type="checkbox" name="tests[]" value="<?=$t['id']?>" id="tt<?=$t['id']?>" <?=in_array($t['id'],$et)?'checked':''?>><label class="form-check-label small" for="tt<?=$t['id']?>"><?=htmlspecialchars($t['name'])?></label></div><?php endforeach;?></div></div>
      <button type="submit" class="btn btn-danger w-100"><i class="fas fa-save me-2"></i><?=$edit?'আপডেট':'যোগ করুন'?></button>
    </form></div>
  </div></div>
  <div class="col-lg-7"><div class="section-card">
    <div class="card-head"><h5><i class="fas fa-virus text-danger me-2"></i>লক্ষণ (<?=count($rows)?>)</h5><input type="text" id="s" class="form-control form-control-sm" style="width:180px" placeholder="খুঁজুন..."></div>
    <div class="table-responsive"><table class="table tbl table-hover mb-0" id="t"><thead><tr><th>#</th><th>বাংলা</th><th>English</th><th>অ্যাকশন</th></tr></thead><tbody><?php foreach($rows as $i=>$r):?><tr><td><?=$i+1?></td><td class="fw-semibold"><?=htmlspecialchars($r['name_bn'])?></td><td><small><?=htmlspecialchars($r['name'])?></small></td><td><a href="?action=edit&id=<?=$r['id']?>" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-pen"></i></a><a href="?action=delete&id=<?=$r['id']?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('মুছে ফেলবেন?')"><i class="fas fa-trash"></i></a></td></tr><?php endforeach;?></tbody></table></div>
  </div></div>
</div>
<script>document.getElementById('s').addEventListener('input',function(){const q=this.value.toLowerCase();document.querySelectorAll('#t tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});});</script>
<?php include __DIR__ . '/layout_end.php'; ?>
