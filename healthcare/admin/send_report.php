<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';
$page_title = 'রিপোর্ট ব্যবস্থাপনা';

// Table auto-create
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS test_bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL, hospital_id INT NOT NULL, test_id INT NOT NULL,
        status VARCHAR(20) DEFAULT 'pending', result_text TEXT,
        report_file VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
        FOREIGN KEY (test_id) REFERENCES medical_tests(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(Exception $e){}

$msg = $err = '';

// Add booking
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add_booking'){
    $uid=(int)($_POST['user_id']??0);$hid=(int)($_POST['hospital_id']??0);$tid=(int)($_POST['test_id']??0);
    if(!$uid||!$hid||!$tid){ $err='সব তথ্য পূরণ করুন।'; }
    else {
        $chk=$pdo->prepare("SELECT id FROM test_bookings WHERE user_id=? AND test_id=? AND status='pending'");
        $chk->execute([$uid,$tid]);
        if($chk->fetch()){ $err='এই রোগীর এই টেস্টের pending বুকিং ইতিমধ্যে আছে।'; }
        else { $pdo->prepare("INSERT INTO test_bookings(user_id,hospital_id,test_id) VALUES(?,?,?)")->execute([$uid,$hid,$tid]); $msg='বুকিং যোগ হয়েছে।'; }
    }
}
if(isset($_GET['delete'])&&(int)$_GET['delete']>0){ $pdo->prepare("DELETE FROM test_bookings WHERE id=?")->execute([(int)$_GET['delete']]); $msg='মুছে ফেলা হয়েছে।'; }

$pending=$pdo->query("SELECT tb.*,u.name AS un,u.email AS ue,u.phone AS up,h.name AS hn,mt.name AS tn FROM test_bookings tb JOIN users u ON tb.user_id=u.id JOIN hospitals h ON tb.hospital_id=h.id JOIN medical_tests mt ON tb.test_id=mt.id WHERE tb.status='pending' ORDER BY tb.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$done=$pdo->query("SELECT tb.*,u.name AS un,u.email AS ue,h.name AS hn,mt.name AS tn FROM test_bookings tb JOIN users u ON tb.user_id=u.id JOIN hospitals h ON tb.hospital_id=h.id JOIN medical_tests mt ON tb.test_id=mt.id WHERE tb.status='completed' ORDER BY tb.created_at DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
$users=$pdo->query("SELECT id,name,email,phone FROM users WHERE role='user' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$hospitals=$pdo->query("SELECT id,name FROM hospitals ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$tests=$pdo->query("SELECT id,name FROM medical_tests ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/layout.php';
?>

<style>
.tab-nav{display:flex;gap:.35rem;margin-bottom:1.5rem;flex-wrap:wrap}
.tab-btn{border:2px solid #e2e8f0;background:#fff;border-radius:10px;padding:.5rem 1.2rem;font-size:.88rem;font-weight:600;cursor:pointer;transition:all .2s;color:#64748b}
.tab-btn.active{background:#0f172a;color:#fff;border-color:#0f172a}
.tab-btn .bn{background:#e2e8f0;color:#475569;border-radius:20px;padding:.05rem .5rem;font-size:.72rem;font-weight:700;margin-left:.35rem}
.tab-btn.active .bn{background:rgba(255,255,255,.2);color:#fff}
.tab-btn.has-data .bn{background:#DC2626;color:#fff}
.tab-pane{display:none}.tab-pane.active{display:block}
.patient-card{background:linear-gradient(135deg,#EFF6FF,#DBEAFE);border:1.5px solid #BFDBFE;border-radius:14px;padding:1rem 1.2rem;margin-bottom:1rem}
.patient-card .pl{font-size:.72rem;color:#64748b;margin-bottom:.15rem}
.patient-card .pv{font-weight:700;font-size:.9rem;color:#0f172a}
.email-ok{background:#f0fdf4;border:1.5px solid #86EFAC;border-radius:8px;padding:.4rem .8rem;font-weight:700;color:#15803d;font-size:.88rem;display:flex;align-items:center;gap:.5rem}
.email-no{background:#fff1f2;border:1.5px solid #FECACA;border-radius:8px;padding:.4rem .8rem;font-weight:700;color:#DC2626;font-size:.88rem;display:flex;align-items:center;gap:.5rem}
.dark-card{background:linear-gradient(135deg,#0f172a,#1e293b);border-radius:16px;padding:1.5rem;color:#e2e8f0}
.dark-card label{color:#94a3b8;font-size:.82rem;font-weight:600}
.dark-card .form-control,.dark-card .form-select{background:#0f172a;border:1.5px solid #334155;color:#f1f5f9}
.dark-card .form-control:focus,.dark-card .form-select:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.15);background:#0f172a;color:#f1f5f9}
.dark-card .form-control::placeholder{color:#475569}
.add-card{background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.07);padding:1.5rem;border:2px dashed #e2e8f0}
</style>

<?php if($msg):?><div class="alert alert-success alert-dismissible fade show mb-3"><i class="fas fa-check-circle me-2"></i><?=htmlspecialchars($msg)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>
<?php if($err):?><div class="alert alert-danger alert-dismissible fade show mb-3"><i class="fas fa-exclamation-circle me-2"></i><?=htmlspecialchars($err)?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>

<div class="tab-nav">
    <button class="tab-btn active <?=count($pending)?'has-data':''?>" onclick="sw('pending',this)">
        <i class="fas fa-clock me-1"></i>Pending রিপোর্ট<span class="bn"><?=count($pending)?></span>
    </button>
    <button class="tab-btn" onclick="sw('add',this)">
        <i class="fas fa-plus me-1"></i>নতুন বুকিং যোগ
    </button>
    <button class="tab-btn" onclick="sw('direct',this)">
        <i class="fas fa-envelope me-1"></i>সরাসরি Email
    </button>
    <button class="tab-btn" onclick="sw('done',this)">
        <i class="fas fa-check-circle me-1"></i>সম্পন্ন<span class="bn"><?=count($done)?></span>
    </button>
</div>

<!-- TAB 1: Pending -->
<div class="tab-pane active" id="tab-pending">
<div class="section-card">
  <div class="card-head">
    <h5><i class="fas fa-paper-plane text-primary me-2"></i>Pending রিপোর্ট — Email করুন</h5>
    <input type="text" id="sp" class="form-control form-control-sm" style="width:200px" placeholder="খুঁজুন...">
  </div>
  <div class="table-responsive"><table class="table tbl table-hover mb-0" id="tp">
    <thead><tr><th>#</th><th>রোগী</th><th>পরীক্ষা</th><th>হাসপাতাল</th><th>ইমেইল</th><th>তারিখ</th><th>অ্যাকশন</th></tr></thead>
    <tbody>
    <?php if(empty($pending)):?>
    <tr><td colspan="7" class="text-center text-muted py-4">
      <i class="fas fa-inbox fa-2x d-block mb-2 opacity-50"></i>কোনো pending রিপোর্ট নেই।
      <div class="mt-2"><button class="btn btn-sm btn-primary" onclick="sw('add',document.querySelectorAll('.tab-btn')[1])"><i class="fas fa-plus me-1"></i>নতুন বুকিং যোগ</button></div>
    </td></tr>
    <?php endif;?>
    <?php foreach($pending as $i=>$r):?>
    <tr>
      <td><?=$i+1?></td>
      <td><div class="fw-semibold"><?=htmlspecialchars($r['un'])?></div><small class="text-muted"><?=htmlspecialchars($r['up']??'')?></small></td>
      <td><?=htmlspecialchars($r['tn'])?></td>
      <td><small><?=htmlspecialchars($r['hn'])?></small></td>
      <td><?php if(!empty($r['ue'])):?><small class="text-success"><i class="fas fa-envelope me-1"></i><?=htmlspecialchars($r['ue'])?></small><?php else:?><small class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>নেই</small><?php endif;?></td>
      <td><small><?=date('d M Y',strtotime($r['created_at']))?></small></td>
      <td><div class="d-flex gap-1">
        <button class="btn btn-sm btn-primary" onclick="openModal(<?=(int)$r['id']?>,'<?=htmlspecialchars(addslashes($r['un']))?>','<?=htmlspecialchars(addslashes($r['tn']))?>','<?=htmlspecialchars(addslashes($r['hn']))?>','<?=htmlspecialchars(addslashes($r['ue']??''))?>')"><i class="fas fa-paper-plane me-1"></i>পাঠান</button>
        <a href="?delete=<?=(int)$r['id']?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('মুছে ফেলবেন?')"><i class="fas fa-trash"></i></a>
      </div></td>
    </tr>
    <?php endforeach;?>
    </tbody>
  </table></div>
</div>
</div>

<!-- TAB 2: Add Booking -->
<div class="tab-pane" id="tab-add">
<div class="row g-4">
  <div class="col-lg-7">
    <div class="add-card">
      <h5 class="fw-bold mb-4"><i class="fas fa-plus-circle text-primary me-2"></i>নতুন টেস্ট বুকিং যোগ করুন</h5>
      <form method="POST">
        <input type="hidden" name="action" value="add_booking">
        <div class="mb-3">
          <label class="form-label fw-semibold">রোগী <span class="text-danger">*</span></label>
          <select name="user_id" id="uSel" class="form-select" required onchange="showUI(this)">
            <option value="">— রোগী বেছে নিন —</option>
            <?php foreach($users as $u):?><option value="<?=$u['id']?>" data-email="<?=htmlspecialchars($u['email']??'')?>" data-phone="<?=htmlspecialchars($u['phone']??'')?>"><?=htmlspecialchars($u['name'])?> <?=$u['email']?'('.$u['email'].')':'(ইমেইল নেই)'?></option><?php endforeach;?>
          </select>
          <div id="uInfo" class="mt-1 d-none" style="font-size:.82rem"></div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">হাসপাতাল <span class="text-danger">*</span></label>
          <select name="hospital_id" class="form-select" required>
            <option value="">— হাসপাতাল বেছে নিন —</option>
            <?php foreach($hospitals as $h):?><option value="<?=$h['id']?>"><?=htmlspecialchars($h['name'])?></option><?php endforeach;?>
          </select>
        </div>
        <div class="mb-4">
          <label class="form-label fw-semibold">পরীক্ষা <span class="text-danger">*</span></label>
          <select name="test_id" class="form-select" required>
            <option value="">— পরীক্ষা বেছে নিন —</option>
            <?php foreach($tests as $t):?><option value="<?=$t['id']?>"><?=htmlspecialchars($t['name'])?></option><?php endforeach;?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus me-2"></i>বুকিং যোগ করুন</button>
      </form>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="section-card">
      <div class="card-head"><h5 style="font-size:.9rem"><i class="fas fa-users text-info me-2"></i>রোগীর তালিকা</h5><input type="text" id="su" class="form-control form-control-sm" style="width:130px" placeholder="খুঁজুন..."></div>
      <div style="max-height:380px;overflow-y:auto"><table class="table tbl table-hover mb-0" id="tu">
        <thead><tr><th>নাম</th><th>ইমেইল</th></tr></thead>
        <tbody><?php foreach($users as $u):?><tr><td class="fw-semibold"><?=htmlspecialchars($u['name'])?></td><td><?php if($u['email']):?><small class="text-success"><?=htmlspecialchars($u['email'])?></small><?php else:?><small class="text-danger">নেই</small><?php endif;?></td></tr><?php endforeach;?></tbody>
      </table></div>
    </div>
  </div>
</div>
</div>

<!-- TAB 3: Direct Email -->
<div class="tab-pane" id="tab-direct">
<div class="row g-4 justify-content-center"><div class="col-lg-8">
<div class="dark-card">
  <h5 class="fw-bold mb-4" style="color:#f1f5f9"><i class="fas fa-envelope me-2" style="color:#3b82f6"></i>রোগীকে সরাসরি Email করুন</h5>
  <div class="mb-3">
    <label class="form-label">রোগী বেছে নিন</label>
    <select class="form-select mb-2" id="dSel" onchange="fillDE(this)">
      <option value="">— রোগী বেছে নিন —</option>
      <?php foreach($users as $u):?><option value="<?=htmlspecialchars($u['email']??'')?>" data-name="<?=htmlspecialchars($u['name'])?>"><?=htmlspecialchars($u['name'])?> <?=$u['email']?'— '.$u['email']:'(ইমেইল নেই)'?></option><?php endforeach;?>
    </select>
    <input type="email" id="dEmail" class="form-control" placeholder="অথবা সরাসরি ইমেইল লিখুন...">
  </div>
  <div class="mb-3"><label class="form-label">রোগীর নাম</label><input type="text" id="dName" class="form-control" placeholder="রোগীর নাম..."></div>
  <div class="mb-3"><label class="form-label">বিষয় (Subject)</label><input type="text" id="dSub" class="form-control" value="আপনার মেডিকেল রিপোর্ট — Healthcare Portal"></div>
  <div class="mb-3"><label class="form-label">বার্তা</label><textarea id="dBody" class="form-control" rows="6" placeholder="রিপোর্টের ফলাফল বা বার্তা লিখুন..."></textarea></div>
  <div class="mb-4"><label class="form-label">রিপোর্ট ফাইল (ঐচ্ছিক)</label><input type="file" id="dFile" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div>
  <div id="dRes"></div>
  <button class="btn btn-primary w-100 btn-lg mt-2" id="dBtn" onclick="sendDE()"><i class="fas fa-paper-plane me-2"></i>Email পাঠান</button>
</div>
</div></div>
</div>

<!-- TAB 4: Done -->
<div class="tab-pane" id="tab-done">
<div class="section-card">
  <div class="card-head"><h5><i class="fas fa-check-circle text-success me-2"></i>সম্পন্ন রিপোর্ট</h5></div>
  <div class="table-responsive"><table class="table tbl table-hover mb-0">
    <thead><tr><th>#</th><th>রোগী</th><th>পরীক্ষা</th><th>হাসপাতাল</th><th>ফলাফল</th><th>তারিখ</th></tr></thead>
    <tbody>
    <?php if(empty($done)):?><tr><td colspan="6" class="text-center text-muted py-3">কোনো সম্পন্ন রিপোর্ট নেই।</td></tr><?php endif;?>
    <?php foreach($done as $i=>$r):?><tr><td><?=$i+1?></td><td><div class="fw-semibold"><?=htmlspecialchars($r['un'])?></div><small class="text-success"><?=htmlspecialchars($r['ue']??'')?></small></td><td><?=htmlspecialchars($r['tn'])?></td><td><small><?=htmlspecialchars($r['hn'])?></small></td><td><small class="text-muted"><?=htmlspecialchars(mb_substr($r['result_text']??'',0,60)).(strlen($r['result_text']??'')>60?'...':'')?></small></td><td><small><?=date('d M Y',strtotime($r['created_at']))?></small></td></tr><?php endforeach;?>
    </tbody>
  </table></div>
</div>
</div>

<!-- MODAL -->
<div class="modal fade" id="rModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold"><i class="fas fa-file-medical text-primary me-2"></i>রিপোর্ট পাঠান</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form id="rForm" enctype="multipart/form-data"><div class="modal-body pt-2">
    <input type="hidden" name="booking_id" id="mId">
    <div class="patient-card mb-3">
      <div class="row g-2">
        <div class="col-6"><div class="pl">রোগী</div><div class="pv" id="mP"></div></div>
        <div class="col-6"><div class="pl">পরীক্ষা</div><div class="pv" id="mT" style="color:#D97706"></div></div>
        <div class="col-12"><div class="pl">হাসপাতাল</div><div class="pv" id="mH" style="font-size:.84rem"></div></div>
        <div class="col-12 mt-1">
          <div class="pl mb-1"><i class="fas fa-envelope me-1"></i>ইমেইল পাঠানো হবে</div>
          <div id="mEB"><span id="mE"></span></div>
          <div class="alert alert-danger py-2 mt-1 d-none" id="mNE" style="font-size:.8rem"><i class="fas fa-exclamation-triangle me-1"></i>ইমেইল নেই — পাঠানো সম্ভব হবে না।</div>
        </div>
      </div>
    </div>
    <div class="mb-3"><label class="form-label fw-semibold">রিপোর্ট ফাইল <small class="text-muted fw-normal">(PDF/ছবি — ঐচ্ছিক)</small></label><input type="file" name="report_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div>
    <div class="mb-3"><label class="form-label fw-semibold">ফলাফলের সারসংক্ষেপ</label><textarea name="result_text" class="form-control" rows="4" placeholder="রিপোর্টের সংক্ষিপ্ত ফলাফল লিখুন..."></textarea></div>
    <div id="mRes"></div>
  </div>
  <div class="modal-footer border-0 pt-0">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
    <button type="submit" class="btn btn-primary" id="mBtn"><i class="fas fa-paper-plane me-2"></i>পাঠান</button>
  </div></form>
</div></div></div>

<script>
// Tab
function sw(id,btn){
    document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    document.getElementById('tab-'+id).classList.add('active');
    btn.classList.add('active');
}

// User info
function showUI(sel){
    const o=sel.options[sel.selectedIndex],b=document.getElementById('uInfo');
    if(!o.value){b.classList.add('d-none');return;}
    b.innerHTML=(o.dataset.email?'<span class="text-success"><i class="fas fa-envelope me-1"></i>'+o.dataset.email+'</span>':'<span class="text-danger">ইমেইল নেই</span>')+(o.dataset.phone?' &nbsp; <span class="text-muted"><i class="fas fa-phone me-1"></i>'+o.dataset.phone+'</span>':'');
    b.classList.remove('d-none');
}

// Modal
const rm=new bootstrap.Modal(document.getElementById('rModal'));
function openModal(id,patient,test,hospital,email){
    document.getElementById('rForm').reset();
    document.getElementById('mRes').innerHTML='';
    document.getElementById('mId').value=id;
    document.getElementById('mP').textContent=patient;
    document.getElementById('mT').textContent=test;
    document.getElementById('mH').textContent=hospital;
    const eb=document.getElementById('mEB'),es=document.getElementById('mE'),ne=document.getElementById('mNE'),btn=document.getElementById('mBtn');
    if(email&&email.trim()){
        es.textContent=email;eb.className='email-ok';
        eb.innerHTML='<i class="fas fa-envelope"></i><span>'+email+'</span>';
        ne.classList.add('d-none');btn.disabled=false;
    } else {
        eb.className='email-no';
        eb.innerHTML='<i class="fas fa-exclamation-circle"></i><span>ইমেইল পাওয়া যায়নি</span>';
        ne.classList.remove('d-none');btn.disabled=true;
    }
    rm.show();
}

document.getElementById('rForm').addEventListener('submit',async function(e){
    e.preventDefault();
    const btn=document.getElementById('mBtn'),res=document.getElementById('mRes');
    btn.disabled=true;btn.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>পাঠানো হচ্ছে...';res.innerHTML='';
    try{
        const r=await fetch('/healthcare/tests/send_report.php',{method:'POST',body:new FormData(this)});
        const t=await r.text();let d;
        try{d=JSON.parse(t);}catch(pe){res.innerHTML=`<div class="alert alert-danger mt-2 py-2" style="font-size:.8rem"><strong>Server error:</strong><br><code>${t.substring(0,400)}</code></div>`;btn.disabled=false;btn.innerHTML='<i class="fas fa-paper-plane me-2"></i>পাঠান';return;}
        res.innerHTML=`<div class="alert alert-${d.success?'success':'warning'} mt-2 py-2" style="font-size:.82rem">${d.message}${d.db_updated?'<br><small class="text-muted">✓ DB সংরক্ষিত</small>':''}</div>`;
        if(d.success){btn.innerHTML='<i class="fas fa-check me-2"></i>পাঠানো হয়েছে';setTimeout(()=>{rm.hide();location.reload();},2000);}
        else{btn.disabled=false;btn.innerHTML='<i class="fas fa-paper-plane me-2"></i>আবার চেষ্টা করুন';}
    }catch(ne){res.innerHTML=`<div class="alert alert-danger mt-2 py-2">নেটওয়ার্ক সমস্যা: ${ne.message}</div>`;btn.disabled=false;btn.innerHTML='<i class="fas fa-paper-plane me-2"></i>পাঠান';}
});

// Direct email
function fillDE(sel){const o=sel.options[sel.selectedIndex];document.getElementById('dEmail').value=o.value||'';document.getElementById('dName').value=o.dataset.name||'';}

async function sendDE(){
    const email=document.getElementById('dEmail').value.trim();
    const name=document.getElementById('dName').value.trim()||'রোগী';
    const sub=document.getElementById('dSub').value.trim();
    const body=document.getElementById('dBody').value.trim();
    const file=document.getElementById('dFile').files[0];
    const res=document.getElementById('dRes');
    const btn=document.getElementById('dBtn');
    if(!email){alert('ইমেইল দিন।');return;}
    if(!body&&!file){alert('বার্তা বা ফাইল দিন।');return;}
    const fd=new FormData();
    fd.append('direct_email','1');fd.append('to_email',email);fd.append('to_name',name);
    fd.append('subject',sub);fd.append('body',body);
    if(file) fd.append('report_file',file);
    btn.disabled=true;btn.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>পাঠানো হচ্ছে...';res.innerHTML='';
    try{
        const r=await fetch('/healthcare/tests/send_report.php',{method:'POST',body:fd});
        const t=await r.text();let d;
        try{d=JSON.parse(t);}catch(pe){res.innerHTML=`<div class="alert alert-danger mt-3" style="font-size:.82rem"><strong>Server error:</strong><br><code>${t.substring(0,300)}</code></div>`;btn.disabled=false;btn.innerHTML='<i class="fas fa-paper-plane me-2"></i>Email পাঠান';return;}
        res.innerHTML=`<div class="alert alert-${d.success?'success':'warning'} mt-3">${d.message}</div>`;
        btn.disabled=false;btn.innerHTML=d.success?'<i class="fas fa-check me-2"></i>পাঠানো হয়েছে':'<i class="fas fa-paper-plane me-2"></i>Email পাঠান';
    }catch(ne){res.innerHTML=`<div class="alert alert-danger mt-3">নেটওয়ার্ক সমস্যা: ${ne.message}</div>`;btn.disabled=false;btn.innerHTML='<i class="fas fa-paper-plane me-2"></i>Email পাঠান';}
}

// Search
document.getElementById('sp').addEventListener('input',function(){const q=this.value.toLowerCase();document.querySelectorAll('#tp tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});});
document.getElementById('su').addEventListener('input',function(){const q=this.value.toLowerCase();document.querySelectorAll('#tu tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});});
</script>

<?php include __DIR__ . '/layout_end.php'; ?>
