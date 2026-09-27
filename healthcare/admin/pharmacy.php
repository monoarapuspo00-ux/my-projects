<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
$page_title = 'ফার্মেসি ব্যবস্থাপনা';
$msg=$err='';

if(isset($_GET['action'],$_GET['id'])&&$_GET['action']==='delete'){
    $pdo->prepare("DELETE FROM pharmacies WHERE id=?")->execute([(int)$_GET['id']]); $msg='মুছে ফেলা হয়েছে।';
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $n=trim($_POST['name']??'');$a=trim($_POST['address']??'');
    $lat=(float)($_POST['latitude']??0);$lng=(float)($_POST['longitude']??0);
    $ph=trim($_POST['phone']??'');
    $is247=(int)isset($_POST['is_24_7']);
    $ot=trim($_POST['open_time']??'08:00');$ct=trim($_POST['close_time']??'22:00');
    $id=(int)($_POST['edit_id']??0);
    if(!$n||!$a){$err='নাম ও ঠিকানা আবশ্যক।';}
    else{
        if($id){$pdo->prepare("UPDATE pharmacies SET name=?,address=?,latitude=?,longitude=?,phone=?,is_24_7=?,open_time=?,close_time=? WHERE id=?")->execute([$n,$a,$lat,$lng,$ph,$is247,$ot,$ct,$id]);$msg='আপডেট হয়েছে।';}
        else{$pdo->prepare("INSERT INTO pharmacies(name,address,latitude,longitude,phone,is_24_7,open_time,close_time) VALUES(?,?,?,?,?,?,?,?)")->execute([$n,$a,$lat,$lng,$ph,$is247,$ot,$ct]);$msg='যোগ হয়েছে।';}
    }
}
$edit=null;
if(isset($_GET['action'],$_GET['id'])&&$_GET['action']==='edit'){
    $s=$pdo->prepare("SELECT * FROM pharmacies WHERE id=?");$s->execute([(int)$_GET['id']]);$edit=$s->fetch();
}
$rows=$pdo->query("SELECT * FROM pharmacies ORDER BY is_24_7 DESC, name ASC")->fetchAll();
include __DIR__ . '/layout.php';
?>
<?php if($msg):?><div class="alert alert-success alert-auto alert-dismissible fade show mb-3"><i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($msg)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>
<?php if($err):?><div class="alert alert-danger alert-dismissible fade show mb-3"><?=htmlspecialchars($err)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>
<div class="row g-4">
  <div class="col-lg-4"><div class="section-card">
    <div class="card-head"><h5><i class="fas fa-<?=$edit?'pen':'plus'?> text-success me-2"></i><?=$edit?'ফার্মেসি সম্পাদনা':'নতুন ফার্মেসি'?></h5><?php if($edit):?><a href="pharmacy.php" class="btn btn-sm btn-outline-secondary rounded-pill">বাতিল</a><?php endif;?></div>
    <div class="card-body-p"><form method="POST">
      <?php if($edit):?><input type="hidden" name="edit_id" value="<?=$edit['id']?>"><?php endif;?>
      <div class="mb-3"><label class="form-label fw-semibold">নাম <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required value="<?=htmlspecialchars($edit['name']??'')?>"></div>
      <div class="mb-3"><label class="form-label fw-semibold">ঠিকানা <span class="text-danger">*</span></label><textarea name="address" class="form-control" rows="2" required><?=htmlspecialchars($edit['address']??'')?></textarea></div>
      <div class="row g-2 mb-3">
        <div class="col"><label class="form-label fw-semibold">Latitude</label><input type="number" step="any" name="latitude" class="form-control" value="<?=$edit['latitude']??0?>"></div>
        <div class="col"><label class="form-label fw-semibold">Longitude</label><input type="number" step="any" name="longitude" class="form-control" value="<?=$edit['longitude']??0?>"></div>
      </div>
      <div class="mb-3"><label class="form-label fw-semibold">ফোন</label><input type="text" name="phone" class="form-control" value="<?=htmlspecialchars($edit['phone']??'')?>"></div>
      <div class="mb-3">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_24_7" id="is247" <?=($edit['is_24_7']??0)?'checked':''?> onchange="toggle247(this)">
          <label class="form-check-label fw-semibold" for="is247"><span class="badge bg-danger">২৪/৭</span> সর্বদা খোলা</label>
        </div>
      </div>
      <div id="timeRow" class="row g-2 mb-3" style="<?=($edit['is_24_7']??0)?'display:none':''?>">
        <div class="col"><label class="form-label fw-semibold">খোলার সময়</label><input type="time" name="open_time" class="form-control" value="<?=$edit['open_time']??'08:00'?>"></div>
        <div class="col"><label class="form-label fw-semibold">বন্ধের সময়</label><input type="time" name="close_time" class="form-control" value="<?=$edit['close_time']??'22:00'?>"></div>
      </div>
      <button type="submit" class="btn btn-success w-100"><i class="fas fa-save me-2"></i><?=$edit?'আপডেট':'যোগ করুন'?></button>
    </form></div>
  </div></div>

  <div class="col-lg-8"><div class="section-card">
    <div class="card-head"><h5><i class="fas fa-pills text-success me-2"></i>ফার্মেসি তালিকা (<?=count($rows)?>)</h5><input type="text" id="s" class="form-control form-control-sm" style="width:180px" placeholder="খুঁজুন..."></div>
    <div class="table-responsive"><table class="table tbl table-hover mb-0" id="t">
      <thead><tr><th>#</th><th>নাম</th><th>ঠিকানা</th><th>সময়</th><th>২৪/৭</th><th>অ্যাকশন</th></tr></thead>
      <tbody><?php foreach($rows as $i=>$r):?>
        <tr>
          <td><?=$i+1?></td>
          <td class="fw-semibold"><?=htmlspecialchars($r['name'])?></td>
          <td><small><?=htmlspecialchars($r['address'])?></small></td>
          <td><small><?=$r['is_24_7']?'সবসময়':date('h:i A',strtotime($r['open_time'])).'–'.date('h:i A',strtotime($r['close_time']))?></small></td>
          <td><?=$r['is_24_7']?"<span class='badge bg-danger'>২৪/৭</span>":"<span class='badge bg-secondary'>না</span>"?></td>
          <td><a href="?action=edit&id=<?=$r['id']?>" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-pen"></i></a><a href="?action=delete&id=<?=$r['id']?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('মুছে ফেলবেন?')"><i class="fas fa-trash"></i></a></td>
        </tr>
      <?php endforeach;?></tbody>
    </table></div>
  </div></div>
</div>
<script>
document.getElementById('s').addEventListener('input',function(){const q=this.value.toLowerCase();document.querySelectorAll('#t tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});});
function toggle247(cb){document.getElementById('timeRow').style.display=cb.checked?'none':'flex';}
</script>
<?php include __DIR__ . '/layout_end.php'; ?>
