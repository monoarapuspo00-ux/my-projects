<?php
$base_url = '/healthcare';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

$pharmacies = $pdo->query("
    SELECT * FROM pharmacies ORDER BY is_24_7 DESC, name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$now        = date('H:i:s');
$is_night   = ($now >= '22:00:00' || $now < '06:00:00');
?>

<style>
.pharma-hero {
    background: linear-gradient(135deg, #065F46, #059669);
    color: #fff; padding: 2.5rem 1rem 2rem;
    text-align: center; border-radius: 0 0 2rem 2rem; margin-bottom: 2rem;
}
.pharma-hero h2 { font-size: 1.9rem; font-weight: 700; }

/* Filter tabs */
.filter-row { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1.2rem; }
.f-btn {
    border: 2px solid #e2e8f0; background: #fff; border-radius: 20px;
    padding: .3rem .9rem; font-size: .82rem; cursor: pointer; transition: all .15s;
}
.f-btn:hover, .f-btn.active { background: #059669; color: #fff; border-color: #059669; }
.f-btn.active-24 { background: #DC2626; border-color: #DC2626; color: #fff; }

/* Search */
.s-wrap { position: relative; margin-bottom: 1rem; }
.s-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
.s-wrap input { padding-left: 38px; border-radius: 10px; border: 2px solid #e2e8f0; }
.s-wrap input:focus { border-color: #059669; box-shadow: 0 0 0 3px rgba(5,150,105,.15); outline: none; }

/* Map */
#pharmaMap { height: 460px; border-radius: 14px; border: 2px solid #e2e8f0; }

/* Pharmacy cards */
.p-card {
    background: #fff; border: 2px solid #e2e8f0; border-radius: 12px;
    padding: .9rem 1rem; margin-bottom: .6rem; cursor: pointer; transition: all .18s;
}
.p-card:hover, .p-card.active { border-color: #059669; background: #F0FDF4; transform: translateX(3px); }
.p-card.is24 { border-left: 4px solid #DC2626; }
.p-name { font-weight: 700; font-size: .93rem; color: #0f172a; margin-bottom: .2rem; }
.p-addr { font-size: .78rem; color: #64748b; margin-bottom: .3rem; }
.badge-247 {
    background: #DC2626; color: #fff; border-radius: 20px;
    padding: .12rem .65rem; font-size: .7rem; font-weight: 700;
}
.badge-open {
    background: #15803d; color: #fff; border-radius: 20px;
    padding: .12rem .65rem; font-size: .7rem; font-weight: 700;
}
.badge-closed {
    background: #64748b; color: #fff; border-radius: 20px;
    padding: .12rem .65rem; font-size: .7rem; font-weight: 700;
}
.p-dist { background: #F0FDF4; color: #059669; border-radius: 20px; padding: .12rem .65rem; font-size: .7rem; font-weight: 700; }
#pharmList { max-height: 460px; overflow-y: auto; scrollbar-width: thin; }
#pharmList::-webkit-scrollbar { width: 4px; }
#pharmList::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }
.no-result { text-align: center; color: #94a3b8; padding: 2rem; font-size: .88rem; }
/* Leaflet popup */
.lf-p-title { font-weight: 700; font-size: .9rem; margin-bottom: .25rem; }
.lf-p-addr  { font-size: .76rem; color: #64748b; margin-bottom: .4rem; }
.lf-p-phone { font-size: .78rem; color: #059669; }
</style>

<div class="pharma-hero">
    <h2><i class="fas fa-pills me-2"></i>নিকটস্থ ফার্মেসি</h2>
    <p class="mt-1" style="opacity:.88">২৪/৭ খোলা ফার্মেসি সহ কাছের সব ফার্মেসি খুঁজুন</p>
    <?php if ($is_night): ?>
    <div class="mt-2 d-inline-block bg-danger px-3 py-1 rounded-pill" style="font-size:.85rem">
        <i class="fas fa-moon me-1"></i>রাত্রিকালীন সেবা — শুধু ২৪/৭ ফার্মেসি খোলা
    </div>
    <?php endif; ?>
</div>

<div class="container pb-5">
    <!-- GPS & Search Row -->
    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <div class="s-wrap">
                <i class="fas fa-search"></i>
                <input type="text" id="pharmSearch" class="form-control" placeholder="ফার্মেসির নাম বা এলাকা খুঁজুন...">
            </div>
        </div>
        <div class="col-md-4">
            <button onclick="getGPS()" id="gpsBtn" class="btn btn-success w-100">
                <i class="fas fa-crosshairs me-1"></i>আমার অবস্থান
            </button>
        </div>
    </div>

    <!-- Filter -->
    <div class="filter-row">
        <button class="f-btn active" onclick="setFilter('all',this)">সব ফার্মেসি</button>
        <button class="f-btn active-24" onclick="setFilter('247',this)">
            <i class="fas fa-clock me-1"></i>২৪/৭ খোলা
        </button>
        <button class="f-btn" onclick="setFilter('open',this)">এখন খোলা</button>
        <button class="f-btn" onclick="setFilter('nearby',this)">কাছের আগে</button>
    </div>

    <div class="row g-4">
        <!-- Map -->
        <div class="col-lg-7">
            <div id="pharmaMap"></div>
        </div>

        <!-- List -->
        <div class="col-lg-5">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="fw-bold mb-0"><i class="fas fa-list text-success me-2"></i>ফার্মেসি তালিকা</h5>
                <span class="badge bg-success" id="pharmCount"><?= count($pharmacies) ?> টি</span>
            </div>
            <div id="pharmList">
                <?php foreach ($pharmacies as $p):
                    $isOpen = $p['is_24_7'] || ($now >= $p['open_time'] && $now <= $p['close_time']);
                ?>
                <div class="p-card <?= $p['is_24_7'] ? 'is24' : '' ?>"
                     data-id="<?= $p['id'] ?>"
                     data-lat="<?= $p['latitude'] ?>"
                     data-lng="<?= $p['longitude'] ?>"
                     data-name="<?= htmlspecialchars($p['name']) ?>"
                     data-247="<?= $p['is_24_7'] ?>"
                     data-open="<?= $isOpen ? '1' : '0' ?>"
                     onclick="selectPharma(this)">
                    <div class="d-flex justify-content-between align-items-start gap-1 flex-wrap">
                        <div class="p-name"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="d-flex gap-1 flex-wrap">
                            <?php if ($p['is_24_7']): ?>
                            <span class="badge-247"><i class="fas fa-clock me-1"></i>২৪/৭</span>
                            <?php elseif ($isOpen): ?>
                            <span class="badge-open">খোলা</span>
                            <?php else: ?>
                            <span class="badge-closed">বন্ধ</span>
                            <?php endif; ?>
                            <span class="p-dist" id="pdist_<?= $p['id'] ?>">— কিমি</span>
                        </div>
                    </div>
                    <div class="p-addr"><i class="fas fa-location-dot me-1"></i><?= htmlspecialchars($p['address']) ?></div>
                    <?php if ($p['phone']): ?>
                    <div style="font-size:.76rem;color:#059669">
                        <i class="fas fa-phone me-1"></i><?= htmlspecialchars($p['phone']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!$p['is_24_7']): ?>
                    <div style="font-size:.74rem;color:#94a3b8;margin-top:.2rem">
                        <i class="fas fa-business-time me-1"></i>
                        <?= date('h:i A', strtotime($p['open_time'])) ?> – <?= date('h:i A', strtotime($p['close_time'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <div class="no-result d-none" id="noPharm">
                    <i class="fas fa-search fa-2x mb-2 d-block"></i>কোনো ফার্মেসি পাওয়া যায়নি।
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const PHARMAS = <?= json_encode($pharmacies) ?>;
let map, userMarker, userLat=null, userLng=null, markers={}, activeFilter='all';

// Map init
map = L.map('pharmaMap').setView([23.8103,90.4125],12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap',maxZoom:19}).addTo(map);

// Icons
const icon247 = L.divIcon({
    className:'',
    html:`<div style="background:#DC2626;color:#fff;border:2.5px solid #fff;border-radius:50%;
          width:34px;height:34px;display:flex;align-items:center;justify-content:center;
          font-size:11px;font-weight:800;box-shadow:0 3px 10px rgba(0,0,0,.3)">Rx</div>`,
    iconSize:[34,34],iconAnchor:[17,17]
});
const iconNorm = L.divIcon({
    className:'',
    html:`<div style="background:#059669;color:#fff;border:2.5px solid #fff;border-radius:50%;
          width:30px;height:30px;display:flex;align-items:center;justify-content:center;
          font-size:10px;font-weight:800;box-shadow:0 3px 8px rgba(0,0,0,.2)">Rx</div>`,
    iconSize:[30,30],iconAnchor:[15,15]
});

PHARMAS.forEach(p=>{
    if(!p.latitude||!p.longitude) return;
    const popup=`<div style="min-width:190px;font-family:'Segoe UI',sans-serif">
        <div class="lf-p-title">${p.name} ${p.is_24_7?'<span style="background:#DC2626;color:#fff;border-radius:10px;padding:1px 7px;font-size:.68rem">২৪/৭</span>':''}</div>
        <div class="lf-p-addr">${p.address}</div>
        ${p.phone?`<div class="lf-p-phone"><i class="fas fa-phone"></i> ${p.phone}</div>`:''}
        ${!p.is_24_7?`<div style="font-size:.72rem;color:#94a3b8;margin-top:.3rem">সময়: ${p.open_time.slice(0,5)} – ${p.close_time.slice(0,5)}</div>`:''}
    </div>`;
    const m=L.marker([parseFloat(p.latitude),parseFloat(p.longitude)],{icon:p.is_24_7?icon247:iconNorm})
             .addTo(map).bindPopup(popup);
    m.on('click',()=>highlightPharma(p.id));
    markers[p.id]=m;
});

// GPS
function getGPS(){
    const btn=document.getElementById('gpsBtn');
    if(!navigator.geolocation){alert('GPS সাপোর্ট নেই।');return;}
    btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>খোঁজা হচ্ছে...';
    btn.disabled=true;
    navigator.geolocation.getCurrentPosition(pos=>{
        setPos(pos.coords.latitude,pos.coords.longitude);
        btn.innerHTML='<i class="fas fa-crosshairs me-1"></i>আমার অবস্থান';
        btn.disabled=false;
    },()=>{
        alert('অবস্থান পাওয়া যায়নি।');
        btn.innerHTML='<i class="fas fa-crosshairs me-1"></i>আমার অবস্থান';
        btn.disabled=false;
    });
}

function setPos(lat,lng){
    userLat=lat;userLng=lng;
    if(userMarker) map.removeLayer(userMarker);
    const youIcon=L.divIcon({className:'',
        html:`<div style="background:#DC2626;border:3px solid #fff;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 12px rgba(220,38,38,.45)"><div style="background:#fff;border-radius:50%;width:11px;height:11px"></div></div>`,
        iconSize:[36,36],iconAnchor:[18,18]
    });
    userMarker=L.marker([lat,lng],{icon:youIcon,zIndexOffset:1000}).addTo(map).bindPopup('<b style="color:#DC2626">📍 আপনার অবস্থান</b>');
    map.setView([lat,lng],14);
    calcDists(lat,lng);
}

function haversine(a,b,c,d){
    const R=6371,dL=(c-a)*Math.PI/180,dN=(d-b)*Math.PI/180;
    const x=Math.sin(dL/2)**2+Math.cos(a*Math.PI/180)*Math.cos(c*Math.PI/180)*Math.sin(dN/2)**2;
    return R*2*Math.atan2(Math.sqrt(x),Math.sqrt(1-x));
}

function calcDists(lat,lng){
    PHARMAS.forEach(p=>{
        if(!p.latitude||!p.longitude) return;
        const d=haversine(lat,lng,parseFloat(p.latitude),parseFloat(p.longitude));
        const el=document.getElementById('pdist_'+p.id);
        if(el) el.textContent=d<1?(d*1000).toFixed(0)+'m':d.toFixed(1)+' কিমি';
    });
    if(activeFilter==='nearby') sortByDist();
}

function parseDist(id){
    const el=document.getElementById('pdist_'+id);
    if(!el||el.textContent.includes('—')) return 99999;
    const t=el.textContent,n=parseFloat(t.replace(/[^\d.]/g,''));
    return t.includes('m')&&!t.includes('কিমি')?n/1000:n;
}

function sortByDist(){
    const list=document.getElementById('pharmList');
    const cards=[...list.querySelectorAll('.p-card')];
    cards.sort((a,b)=>parseDist(a.dataset.id)-parseDist(b.dataset.id));
    cards.forEach(c=>list.appendChild(c));
}

function setFilter(f,btn){
    activeFilter=f;
    document.querySelectorAll('.f-btn').forEach(b=>b.classList.remove('active','active-24'));
    btn.classList.add(f==='247'?'active-24':'active');
    applyFilter();
    if(f==='nearby') sortByDist();
}

function applyFilter(){
    const q=document.getElementById('pharmSearch').value.toLowerCase().trim();
    let vis=0;
    document.querySelectorAll('.p-card').forEach(card=>{
        const n=card.dataset.name.toLowerCase();
        const is247=card.dataset['247']==='1';
        const isOpen=card.dataset.open==='1';
        const matchQ=!q||n.includes(q)||card.querySelector('.p-addr').textContent.toLowerCase().includes(q);
        let matchF=true;
        if(activeFilter==='247') matchF=is247;
        else if(activeFilter==='open') matchF=isOpen;
        const show=matchQ&&matchF;
        card.style.display=show?'':'none';
        const m=markers[card.dataset.id];
        if(m) m.setOpacity(show?1:.15);
        if(show) vis++;
    });
    document.getElementById('pharmCount').textContent=vis+' টি';
    document.getElementById('noPharm').classList.toggle('d-none',vis>0);
}

document.getElementById('pharmSearch').addEventListener('input',applyFilter);

function selectPharma(card){
    const lat=parseFloat(card.dataset.lat),lng=parseFloat(card.dataset.lng),id=card.dataset.id;
    document.querySelectorAll('.p-card').forEach(c=>c.classList.remove('active'));
    card.classList.add('active');
    if(lat&&lng){map.setView([lat,lng],16);markers[id]?.openPopup();}
}

function highlightPharma(id){
    const c=document.querySelector(`.p-card[data-id="${id}"]`);
    if(!c) return;
    document.querySelectorAll('.p-card').forEach(x=>x.classList.remove('active'));
    c.classList.add('active');
    c.scrollIntoView({behavior:'smooth',block:'nearest'});
}

// Auto GPS
window.addEventListener('load',()=>{
    if(navigator.geolocation)
        navigator.geolocation.getCurrentPosition(p=>setPos(p.coords.latitude,p.coords.longitude),()=>{},{timeout:5000});
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
