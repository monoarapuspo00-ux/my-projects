<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
$page_title='ব্যবহারকারী ব্যবস্থাপনা';
$msg=$err='';
if(isset($_GET['action'],$_GET['id'])&&$_GET['action']==='delete'){
  if((int)$_GET['id']!==(int)$_SESSION['admin_id']){$pdo->prepare("DELETE FROM users WHERE id=?")->execute([(int)$_GET['id']]);$msg='মুছে ফেলা হয়েছে।';}
  else{$err='নিজেকে মুছতে পারবেন না।';}
}
if($_SERVER['REQUEST_METHOD']==='POST'){
  $n=trim($_POST['name']??'');$e=trim($_POST['email']??'');$ph=trim($_POST['phone']??'');$p=$_POST['password']??'';$r=in_array($_POST['role']??'',['user','admin'])?$_POST['role']:'user';
  if(!$n||!$e||!$p){$err='নাম, ইমেইল ও পাসওয়ার্ড আবশ্যক।';}
  else{$c=$pdo->prepare("SELECT id FROM users WHERE email=?");$c->execute([$e]);if($c->fetch()){$err='এই ইমেইল ইতিমধ্যে আছে।';}
  else{$pdo->prepare("INSERT INTO users(name,email,phone,password,role) VALUES(?,?,?,?,?)")->execute([$n,$e,$ph,password_hash($p,PASSWORD_DEFAULT),$r]);$msg='যোগ হয়েছে।';}}
}
$rows=$pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
include __DIR__ . '/layout.php';
?>
<?php if($msg):?><div class="alert alert-success alert-dismissible fade show mb-3"><i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($msg)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>
<?php if($err):?><div class="alert alert-danger alert-dismissible fade show mb-3"><?=htmlspecialchars($err)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>
<div class="row g-4">
  <div class="col-lg-4"><div class="section-card"><div class="card-head"><h5><i class="fas fa-user-plus text-info me-2"></i>নতুন ব্যবহারকারী</h5></div><div class="card-body-p"><form method="POST">
    <div class="mb-3"><label class="form-label fw-semibold">নাম <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label fw-semibold">ইমেইল <span class="text-danger">*</span></label><input type="email" name="email" class="form-control" required></div>
    <div class="mb-3"><label class="form-label fw-semibold">ফোন</label><input type="text" name="phone" class="form-control"></div>
    <div class="mb-3"><label class="form-label fw-semibold">পাসওয়ার্ড <span class="text-danger">*</span></label><input type="password" name="password" class="form-control" required></div>
    <div class="mb-3"><label class="form-label fw-semibold">ভূমিকা</label><select name="role" class="form-select"><option value="user">User</option><option value="admin">Admin</option></select></div>
    <button type="submit" class="btn btn-info w-100 text-white"><i class="fas fa-user-plus me-2"></i>যোগ করুন</button>
  </form></div></div></div>
  <div class="col-lg-8"><div class="section-card">
    <div class="card-head"><h5><i class="fas fa-users text-info me-2"></i>ব্যবহারকারী (<?=count($rows)?>)</h5><input type="text" id="s" class="form-control form-control-sm" style="width:180px" placeholder="খুঁজুন..."></div>
    <div class="table-responsive"><table class="table tbl table-hover mb-0" id="t"><thead><tr><th>#</th><th>নাম</th><th>ইমেইল</th><th>ফোন</th><th>ভূমিকা</th><th>যোগদান</th><th>অ্যাকশন</th></tr></thead><tbody><?php foreach($rows as $i=>$r):?><tr><td><?=$i+1?></td><td class="fw-semibold"><?=htmlspecialchars($r['name'])?></td><td><small><?=htmlspecialchars($r['email'])?></small></td><td><small><?=htmlspecialchars($r['phone'])?></small></td><td><?=$r['role']==='admin'?"<span class='badge bg-danger'>Admin</span>":"<span class='badge bg-secondary'>User</span>"?></td><td><small><?=date('d M Y',strtotime($r['created_at']))?></small></td><td><?php if($r['id']!==(int)$_SESSION['admin_id']):?><a href="?action=delete&id=<?=$r['id']?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('মুছে ফেলবেন?')"><i class="fas fa-trash"></i></a><?php else:?><span class="badge bg-primary">আপনি</span><?php endif;?></td></tr><?php endforeach;?></tbody></table></div>
  </div></div>
</div>
<script>document.getElementById('s').addEventListener('input',function(){const q=this.value.toLowerCase();document.querySelectorAll('#t tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});});</script>
<?php include __DIR__ . '/layout_end.php'; ?>
