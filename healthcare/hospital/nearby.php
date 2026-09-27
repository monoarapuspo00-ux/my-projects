<?php
$base_url = '/healthcare';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

// হাসপাতাল + বিভাগ + ডাক্তার সংখ্যা একসাথে লোড
$hospitals = $pdo->query("
    SELECT h.*,
           COUNT(DISTINCT doc.id) AS doctor_count,
           GROUP_CONCAT(DISTINCT dep.name_bn ORDER BY dep.name_bn SEPARATOR '||') AS dept_list
    FROM hospitals h
    LEFT JOIN doctors doc ON h.id = doc.hospital_id
    LEFT JOIN departments dep ON doc.department_id = dep.id
    GROUP BY h.id
    ORDER BY h.name
")->fetchAll(PDO::FETCH_ASSOC);

// Hospital images — Bangladeshi hospital vibe (Unsplash free)
$hosp_imgs = [
    'https://images.unsplash.com/photo-1586773860418-d37222d8fce3?w=800&q=80',
    'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=800&q=80',
    'https://images.unsplash.com/photo-1538108149393-fbbd81895907?w=800&q=80',
    'https://images.unsplash.com/photo-1516549655169-df83a0774514?w=800&q=80',
    'https://images.unsplash.com/photo-1632833239869-a37e3a5806d2?w=800&q=80',
    'https://images.unsplash.com/photo-1551190822-a9333d879b1f?w=800&q=80',
];
?>

<style>
/* ── Hero ── */
.hosp-hero{background:linear-gradient(135deg,#0D47A1,#1565C0);padding:1.75rem 0 1.5rem}
.search-card{background:#fff;border-radius:16px;padding:1.25rem 1.5rem;box-shadow:0 8px 30px rgba(0,0,0,.18)}
.search-card h5{color:#0D47A1;font-weight:700;margin-bottom:.9rem}
.inp-w{position:relative}
.inp-w i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;z-index:2;pointer-events:none}
.inp-w input{padding-left:38px;border-radius:10px;border:2px solid #e2e8f0;transition:border-color .2s}
.inp-w input:focus{border-color:#1565C0;box-shadow:0 0 0 3px rgba(21,101,192,.15);outline:none}

/* Autocomplete */
.ac-list{position:absolute;top:100%;left:0;right:0;z-index:9999;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);margin-top:4px;max-height:200px;overflow-y:auto;display:none}
.ac-item{padding:.5rem 1rem;cursor:pointer;font-size:.83rem;display:flex;align-items:flex-start;gap:.5rem;border-bottom:1px solid #f8f9fa;transition:background .12s}
.ac-item:hover{background:#EFF6FF}

/* Map */
#map{height:500px;border-radius:14px;border:2px solid #e2e8f0}

/* Hospital list */
#hospList{max-height:500px;overflow-y:auto;scrollbar-width:thin;scrollbar-color:#e2e8f0 transparent}
#hospList::-webkit-scrollbar{width:4px}
#hospList::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:4px}

.h-card{background:#fff;border:2px solid #e2e8f0;border-radius:12px;padding:.85rem 1rem;margin-bottom:.5rem;cursor:pointer;transition:all .2s}
.h-card:hover,.h-card.active{border-color:#1565C0;background:#EFF6FF;transform:translateX(3px)}
.h-name{font-weight:700;font-size:.92rem;color:#0f172a;margin-bottom:.2rem}
.h-addr{font-size:.76rem;color:#64748b;margin-bottom:.25rem}
.h-dist{background:#EFF6FF;color:#1565C0;border-radius:20px;padding:.12rem .65rem;font-size:.72rem;font-weight:700}
.h-actions{margin-top:.6rem;display:flex;gap:.35rem;flex-wrap:wrap}
.h-actions .btn{font-size:.74rem;padding:.25rem .7rem}
.no-result{text-align:center;color:#94a3b8;padding:2rem}

/* Direction panel */
#dirPanel{background:#fff;border:2px solid #1565C0;border-radius:12px;padding:1rem 1.1rem;margin-top:1rem;display:none}
#dirPanel h6{color:#1565C0;font-weight:700;margin-bottom:.6rem}
.dir-summary{background:#EFF6FF;border-radius:8px;padding:.55rem .9rem;font-size:.83rem;color:#1e40af;font-weight:600;margin-bottom:.75rem}
.dir-step{display:flex;gap:.6rem;align-items:flex-start;padding:.45rem 0;border-bottom:1px solid #f1f5f9;font-size:.82rem}
.dir-step:last-child{border-bottom:none}
.step-num{background:#1565C0;color:#fff;border-radius:50%;width:22px;height:22px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:700;margin-top:2px}
.dir-dist{color:#94a3b8;font-size:.75rem;display:block;margin-top:2px}
.leaflet-routing-container{display:none!important}

/* ══ MODAL ══ */
.hosp-modal .modal-content{border:none;border-radius:20px;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.25)}
.modal-img-wrap{position:relative;height:230px;overflow:hidden}
.modal-img-wrap img{width:100%;height:230px;object-fit:cover;filter:brightness(.65)}
.modal-img-overlay{
    position:absolute;bottom:0;left:0;right:0;
    padding:1.2rem 1.5rem;
    background:linear-gradient(transparent,rgba(0,0,0,.78));
    color:#fff;
}
.modal-img-overlay h4{font-weight:800;font-size:1.25rem;margin:0 0 .2rem;text-shadow:0 2px 8px rgba(0,0,0,.4)}
.modal-img-overlay p{font-size:.8rem;opacity:.88;margin:0}
.modal-close-btn{
    position:absolute;top:.8rem;right:.8rem;
    background:rgba(0,0,0,.45);color:#fff;border:none;
    width:32px;height:32px;border-radius:50%;cursor:pointer;z-index:10;
    display:flex;align-items:center;justify-content:center;font-size:.85rem;
    transition:background .15s;
}
.modal-close-btn:hover{background:rgba(0,0,0,.72)}

.modal-stats{display:flex;gap:.75rem;padding:1rem 1.5rem;background:#f8fafc;border-bottom:1px solid #f1f5f9}
.m-stat{flex:1;text-align:center}
.m-stat .sn{font-size:1.4rem;font-weight:800;color:#1565C0;line-height:1}
.m-stat .sl{font-size:.7rem;color:#64748b;margin-top:.15rem}

.modal-body{padding:1.1rem 1.5rem}
.info-row{display:flex;align-items:flex-start;gap:.75rem;padding:.55rem 0;border-bottom:1px solid #f8f9fa}
.info-row:last-child{border-bottom:none}
.info-row i{color:#1565C0;width:18px;text-align:center;flex-shrink:0;margin-top:3px;font-size:.88rem}
.info-row .ilabel{font-size:.72rem;color:#94a3b8;margin-bottom:.1rem}
.info-row .ival{font-size:.88rem;font-weight:500;color:#0f172a}

.dept-chip{display:inline-block;background:#EFF6FF;color:#1565C0;border-radius:20px;padding:.18rem .7rem;font-size:.72rem;font-weight:600;margin:.15rem .1rem}

.modal-actions{padding:1rem 1.5rem;border-top:1px solid #f1f5f9;display:flex;flex-direction:column;gap:.5rem}
.m-btn{display:block;width:100%;border-radius:10px;padding:.6rem;font-size:.88rem;font-weight:600;text-align:center;text-decoration:none;transition:all .2s;border:2px solid transparent;cursor:pointer}
.m-btn.primary{background:#1565C0;color:#fff;border-color:#1565C0}
.m-btn.primary:hover{background:#0D47A1;color:#fff}
.m-btn-row{display:flex;gap:.5rem}
.m-btn.outline{border-color:#1565C0;color:#1565C0;background:transparent;flex:1}
.m-btn.outline:hover{background:#EFF6FF}
.m-btn.danger{border-color:#DC2626;color:#DC2626;background:transparent;flex:1}
.m-btn.danger:hover{background:#FFEBEE}
</style>

<!-- Search Hero -->
<div class="hosp-hero">
    <div class="container">
        <div class="search-card">
            <h5><i class="fas fa-hospital me-2"></i>হাসপাতাল খুঁজুন</h5>
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label fw-semibold" style="font-size:.83rem">হাসপাতালের নাম বা এলাকা</label>
                    <div class="inp-w">
                        <i class="fas fa-search"></i>
                        <input type="text" id="textSearch" class="form-control" placeholder="যেমন: স্কয়ার হাসপাতাল, গুলশান...">
                    </div>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold" style="font-size:.83rem">আপনার অবস্থান</label>
                    <div class="inp-w">
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" id="locInput" class="form-control" placeholder="ঠিকানা লিখুন...">
                        <div class="ac-list" id="acList"></div>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button onclick="getGPS()" id="gpsBtn" class="btn btn-primary w-100">
                        <i class="fas fa-crosshairs me-1"></i>GPS
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main -->
<div class="container py-4 pb-5">
    <div class="row g-4">
        <!-- Map -->
        <div class="col-lg-7">
            <div id="map"></div>
            <div id="dirPanel">
                <h6><i class="fas fa-route me-2"></i>পথনির্দেশ</h6>
                <div class="dir-summary" id="dirSummary"></div>
                <div id="dirSteps"></div>
                <button class="btn btn-sm btn-outline-secondary mt-2" onclick="clearDir()">
                    <i class="fas fa-times me-1"></i>বন্ধ করুন
                </button>
            </div>
        </div>

        <!-- List -->
        <div class="col-lg-5">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="fw-bold mb-0"><i class="fas fa-list text-primary me-2"></i>হাসপাতাল তালিকা</h5>
                <span class="badge bg-primary" id="countBadge"><?= count($hospitals) ?> টি</span>
            </div>
            <div id="hospList">
                <?php foreach ($hospitals as $idx => $h):
                    $img = $hosp_imgs[$idx % count($hosp_imgs)];
                    $depts = $h['dept_list'] ? explode('||', $h['dept_list']) : [];
                ?>
                <div class="h-card"
                     data-id="<?= $h['id'] ?>"
                     data-lat="<?= $h['latitude'] ?>"
                     data-lng="<?= $h['longitude'] ?>"
                     data-name="<?= htmlspecialchars($h['name']) ?>"
                     data-addr="<?= htmlspecialchars($h['address']) ?>"
                     data-phone="<?= htmlspecialchars($h['phone'] ?? '') ?>"
                     data-email="<?= htmlspecialchars($h['email'] ?? '') ?>"
                     data-doctors="<?= (int)$h['doctor_count'] ?>"
                     data-depts="<?= htmlspecialchars($h['dept_list'] ?? '') ?>"
                     data-img="<?= $img ?>"
                     onclick="selectCard(this)">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="h-name"><?= htmlspecialchars($h['name']) ?></div>
                        <span class="h-dist" id="dist_<?= $h['id'] ?>">— কিমি</span>
                    </div>
                    <div class="h-addr">
                        <i class="fas fa-location-dot me-1"></i><?= htmlspecialchars($h['address']) ?>
                    </div>
                    <?php if ($h['phone']): ?>
                    <div style="font-size:.75rem;color:#15803d;margin-top:.2rem">
                        <i class="fas fa-phone me-1"></i><?= htmlspecialchars($h['phone']) ?>
                    </div>
                    <?php endif; ?>
                    <div class="h-actions">
                        <button class="btn btn-primary btn-sm"
                                onclick="event.stopPropagation(); showModal(this.closest('.h-card'))">
                            <i class="fas fa-eye me-1"></i>বিস্তারিত
                        </button>
                        <button class="btn btn-outline-primary btn-sm"
                                onclick="event.stopPropagation(); getDir(
                                    <?= $h['latitude'] ?>, <?= $h['longitude'] ?>,
                                    '<?= htmlspecialchars(addslashes($h['name'])) ?>')">
                            <i class="fas fa-route me-1"></i>পথনির্দেশ
                        </button>
                        <a class="btn btn-outline-danger btn-sm"
                           href="<?= $base_url ?>/appointment/book.php"
                           onclick="event.stopPropagation()">
                            <i class="fas fa-calendar me-1"></i>বুকিং
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="no-result d-none" id="noResult">
                    <i class="fas fa-search fa-2x mb-2 d-block"></i>কোনো হাসপাতাল পাওয়া যায়নি।
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ DETAIL MODAL ══ -->
<div class="modal fade hosp-modal" id="hospModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px">
        <div class="modal-content">

            <!-- Image header -->
            <div class="modal-img-wrap">
                <img id="modalImg" src="" alt="">
                <div class="modal-img-overlay">
                    <h4 id="modalName"></h4>
                    <p id="modalAddrShort"></p>
                </div>
                <button type="button" class="modal-close-btn" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Stats bar -->
            <div class="modal-stats">
                <div class="m-stat">
                    <div class="sn" id="mDoctors">—</div>
                    <div class="sl">ডাক্তার</div>
                </div>
                <div class="m-stat">
                    <div class="sn" id="mDepts">—</div>
                    <div class="sl">বিভাগ</div>
                </div>
                <div class="m-stat">
                    <div class="sn" id="mDist">—</div>
                    <div class="sl">দূরত্ব</div>
                </div>
            </div>

            <!-- Info rows -->
            <div class="modal-body">
                <div class="info-row">
                    <i class="fas fa-location-dot"></i>
                    <div><div class="ilabel">ঠিকানা</div><div class="ival" id="mAddr"></div></div>
                </div>
                <div class="info-row" id="mPhoneRow">
                    <i class="fas fa-phone"></i>
                    <div><div class="ilabel">ফোন</div><div class="ival" id="mPhone"></div></div>
                </div>
                <div class="info-row" id="mEmailRow">
                    <i class="fas fa-envelope"></i>
                    <div><div class="ilabel">ইমেইল</div><div class="ival" id="mEmail"></div></div>
                </div>
                <div class="info-row" id="mDeptRow">
                    <i class="fas fa-stethoscope"></i>
                    <div>
                        <div class="ilabel">বিভাগসমূহ</div>
                        <div id="mDeptChips"></div>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="modal-actions">
                <a href="#" id="mApptBtn" class="m-btn primary">
                    <i class="fas fa-calendar-plus me-2"></i>অ্যাপয়েন্টমেন্ট বুক করুন
                </a>
                <div class="m-btn-row">
                    <button onclick="modalGetDir()" class="m-btn outline">
                        <i class="fas fa-route me-1"></i>পথনির্দেশ
                    </button>
                    <a href="#" id="mTransBtn" class="m-btn danger">
                        <i class="fas fa-ambulance me-1"></i>পরিবহন বুক
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js"></script>

<script>
const HOSPS = <?= json_encode($hospitals) ?>;
const BASE  = '<?= $base_url ?>';
let map, userMarker, userLat=null, userLng=null;
let leafMarkers={}, routeControl=null, acTimer=null;
let modalActiveLat=null, modalActiveLng=null, modalActiveName='';
let hospModal;

// ── Map init ──────────────────────────────────────────────────
map = L.map('map').setView([23.8103, 90.4125], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
    maxZoom: 19
}).addTo(map);

const hIcon = L.divIcon({
    className: '',
    html: `<div style="background:#1565C0;color:#fff;border:2.5px solid #fff;
           border-radius:50%;width:34px;height:34px;display:flex;align-items:center;
           justify-content:center;font-size:13px;font-weight:700;
           box-shadow:0 3px 10px rgba(0,0,0,.28)">H</div>`,
    iconSize: [34,34], iconAnchor: [17,17]
});

HOSPS.forEach(h => {
    if (!h.latitude || !h.longitude) return;
    const popup = `<div style="min-width:200px;font-family:'Segoe UI',sans-serif;padding:4px">
        <div style="font-weight:700;font-size:.92rem;margin-bottom:.3rem">${h.name}</div>
        <div style="font-size:.76rem;color:#64748b;margin-bottom:.4rem">
            <i class="fas fa-location-dot" style="color:#1565C0"></i> ${h.address}
        </div>
        ${h.phone ? `<div style="font-size:.76rem;color:#15803d;margin-bottom:.5rem">
            <i class="fas fa-phone"></i> ${h.phone}</div>` : ''}
        <button onclick="showModalById(${h.id})"
            style="background:#1565C0;color:#fff;border:none;border-radius:6px;
                   padding:.3rem .85rem;font-size:.75rem;font-weight:600;cursor:pointer;margin-right:.3rem">
            <i class="fas fa-eye"></i> বিস্তারিত
        </button>
        <button onclick="getDir(${h.latitude},${h.longitude},'${h.name.replace(/'/g,"\\'")}')"
            style="background:#15803d;color:#fff;border:none;border-radius:6px;
                   padding:.3rem .85rem;font-size:.75rem;font-weight:600;cursor:pointer">
            <i class="fas fa-route"></i> পথনির্দেশ
        </button>
    </div>`;
    const m = L.marker([parseFloat(h.latitude), parseFloat(h.longitude)], {icon: hIcon})
               .addTo(map).bindPopup(popup, {maxWidth: 260});
    m.on('click', () => highlightCard(h.id));
    leafMarkers[h.id] = m;
});

// ── GPS ──────────────────────────────────────────────────────
function getGPS() {
    const btn = document.getElementById('gpsBtn');
    if (!navigator.geolocation) { alert('GPS সাপোর্ট নেই।'); return; }
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
    btn.disabled = true;
    navigator.geolocation.getCurrentPosition(pos => {
        setPos(pos.coords.latitude, pos.coords.longitude);
        btn.innerHTML = '<i class="fas fa-crosshairs me-1"></i>GPS';
        btn.disabled = false;
        fetch(`https://nominatim.openstreetmap.org/reverse?lat=${pos.coords.latitude}&lon=${pos.coords.longitude}&format=json`)
            .then(r => r.json())
            .then(d => { document.getElementById('locInput').value = d.display_name || ''; });
    }, () => {
        btn.innerHTML = '<i class="fas fa-crosshairs me-1"></i>GPS';
        btn.disabled = false;
        alert('অবস্থান পাওয়া যায়নি।');
    });
}

function setPos(lat, lng) {
    userLat = lat; userLng = lng;
    if (userMarker) map.removeLayer(userMarker);
    const youIcon = L.divIcon({
        className: '',
        html: `<div style="background:#DC2626;border:3px solid #fff;border-radius:50%;width:38px;
               height:38px;display:flex;align-items:center;justify-content:center;
               box-shadow:0 3px 12px rgba(220,38,38,.45)">
               <div style="background:#fff;border-radius:50%;width:12px;height:12px"></div></div>`,
        iconSize: [38,38], iconAnchor: [19,19]
    });
    userMarker = L.marker([lat,lng], {icon:youIcon, zIndexOffset:1000})
                  .addTo(map).bindPopup('<b style="color:#DC2626">📍 আপনার অবস্থান</b>');
    map.setView([lat,lng], 13);
    calcDists(lat, lng);
}

// ── Distance ─────────────────────────────────────────────────
function haversine(a,b,c,d) {
    const R=6371, dL=(c-a)*Math.PI/180, dN=(d-b)*Math.PI/180;
    const x = Math.sin(dL/2)**2 + Math.cos(a*Math.PI/180)*Math.cos(c*Math.PI/180)*Math.sin(dN/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(x), Math.sqrt(1-x));
}

function calcDists(lat, lng) {
    HOSPS.forEach(h => {
        if (!h.latitude || !h.longitude) return;
        const d = haversine(lat, lng, parseFloat(h.latitude), parseFloat(h.longitude));
        const el = document.getElementById('dist_' + h.id);
        if (el) el.textContent = d < 1 ? (d*1000).toFixed(0)+'m' : d.toFixed(1)+' কিমি';
    });
    sortByDist();
}

function sortByDist() {
    const list = document.getElementById('hospList');
    const cards = [...list.querySelectorAll('.h-card')];
    cards.sort((a,b) => parseDist(a.dataset.id) - parseDist(b.dataset.id));
    cards.forEach(c => list.appendChild(c));
}

function parseDist(id) {
    const el = document.getElementById('dist_' + id);
    if (!el || el.textContent === '— কিমি') return 99999;
    const t = el.textContent, n = parseFloat(t.replace(/[^\d.]/g, ''));
    return t.includes('m') && !t.includes('কিমি') ? n/1000 : n;
}

// ── Directions ───────────────────────────────────────────────
function getDir(destLat, destLng, destName) {
    if (!userLat || !userLng) { alert('আগে GPS বাটন চাপুন।'); return; }
    if (routeControl) { map.removeControl(routeControl); routeControl = null; }

    const modal = bootstrap.Modal.getInstance(document.getElementById('hospModal'));
    if (modal) modal.hide();

    routeControl = L.Routing.control({
        waypoints: [L.latLng(userLat,userLng), L.latLng(destLat,destLng)],
        router: L.Routing.osrmv1({
            serviceUrl: 'https://router.project-osrm.org/route/v1',
            profile: 'driving'
        }),
        lineOptions: { styles: [{color:'#1565C0',weight:5,opacity:.85}] },
        createMarker: () => null,
        show: false,
        addWaypoints: false
    }).addTo(map);

    routeControl.on('routesfound', e => {
        const r = e.routes[0];
        const dist = (r.summary.totalDistance/1000).toFixed(1);
        const mins = Math.ceil(r.summary.totalTime/60);
        const time = mins >= 60 ? Math.floor(mins/60)+'ঘণ্টা '+(mins%60)+'মিনিট' : mins+'মিনিট';
        document.getElementById('dirSummary').innerHTML =
            `<i class="fas fa-car me-2"></i><strong>${dist} কিমি</strong> — <strong>${time}</strong> | গন্তব্য: <strong>${destName}</strong>`;
        document.getElementById('dirSteps').innerHTML =
            r.instructions.map((s,i) => `
                <div class="dir-step">
                    <div class="step-num">${i+1}</div>
                    <div>${s.text}<span class="dir-dist">${(s.distance/1000).toFixed(2)} কিমি</span></div>
                </div>`).join('');
        document.getElementById('dirPanel').style.display = 'block';
        document.getElementById('dirPanel').scrollIntoView({behavior:'smooth',block:'start'});
        map.fitBounds(L.latLngBounds([[userLat,userLng],[destLat,destLng]]).pad(.15));
    });
}

function clearDir() {
    if (routeControl) { map.removeControl(routeControl); routeControl = null; }
    document.getElementById('dirPanel').style.display = 'none';
}

function modalGetDir() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('hospModal'));
    if (modal) modal.hide();
    setTimeout(() => getDir(modalActiveLat, modalActiveLng, modalActiveName), 350);
}

// ── Card select ──────────────────────────────────────────────
function selectCard(card) {
    const lat = parseFloat(card.dataset.lat);
    const lng = parseFloat(card.dataset.lng);
    const id  = card.dataset.id;
    document.querySelectorAll('.h-card').forEach(c => c.classList.remove('active'));
    card.classList.add('active');
    if (!lat || !lng) return;
    map.setView([lat,lng], 15);
    leafMarkers[id]?.openPopup();
}

function highlightCard(id) {
    const card = document.querySelector(`.h-card[data-id="${id}"]`);
    if (!card) return;
    document.querySelectorAll('.h-card').forEach(c => c.classList.remove('active'));
    card.classList.add('active');
    card.scrollIntoView({behavior:'smooth', block:'nearest'});
}

// ── MODAL — "বিস্তারিত" button এ click করলে খোলে ────────────
window.addEventListener('DOMContentLoaded', () => {
    hospModal = new bootstrap.Modal(document.getElementById('hospModal'));
});

function showModal(card) {
    const d = card.dataset;

    // Store for direction use
    modalActiveLat  = parseFloat(d.lat);
    modalActiveLng  = parseFloat(d.lng);
    modalActiveName = d.name;

    // Image
    document.getElementById('modalImg').src    = d.img;
    document.getElementById('modalName').textContent = d.name;
    document.getElementById('modalAddrShort').textContent = d.addr;

    // Stats
    document.getElementById('mDoctors').textContent = (d.doctors || '0') + '+';
    const deptArr = d.depts ? d.depts.split('||').filter(Boolean) : [];
    document.getElementById('mDepts').textContent = deptArr.length || '—';
    const distEl = document.getElementById('dist_' + d.id);
    document.getElementById('mDist').textContent =
        distEl ? distEl.textContent.replace(' কিমি','') : '—';

    // Address
    document.getElementById('mAddr').textContent = d.addr;

    // Phone
    if (d.phone) {
        document.getElementById('mPhone').textContent = d.phone;
        document.getElementById('mPhoneRow').style.display = 'flex';
    } else {
        document.getElementById('mPhoneRow').style.display = 'none';
    }

    // Email
    if (d.email) {
        document.getElementById('mEmail').textContent = d.email;
        document.getElementById('mEmailRow').style.display = 'flex';
    } else {
        document.getElementById('mEmailRow').style.display = 'none';
    }

    // Department chips
    if (deptArr.length) {
        document.getElementById('mDeptChips').innerHTML =
            deptArr.map(dep => `<span class="dept-chip">${dep}</span>`).join('');
        document.getElementById('mDeptRow').style.display = 'flex';
    } else {
        document.getElementById('mDeptRow').style.display = 'none';
    }

    // Action buttons
    document.getElementById('mApptBtn').href  = BASE + '/appointment/book.php';
    document.getElementById('mTransBtn').href = BASE + '/transport/book.php';

    hospModal.show();
}

// Map popup থেকেও modal খোলার জন্য
function showModalById(id) {
    const card = document.querySelector(`.h-card[data-id="${id}"]`);
    if (card) showModal(card);
}

// ── Text search ──────────────────────────────────────────────
document.getElementById('textSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    let vis = 0;
    document.querySelectorAll('.h-card').forEach(card => {
        const show = !q || card.dataset.name.toLowerCase().includes(q)
                       || card.dataset.addr.toLowerCase().includes(q);
        card.style.display = show ? '' : 'none';
        const m = leafMarkers[card.dataset.id];
        if (m) m.setOpacity(show ? 1 : .15);
        if (show) vis++;
    });
    document.getElementById('countBadge').textContent = vis + ' টি';
    document.getElementById('noResult').classList.toggle('d-none', vis > 0);
});

// ── Nominatim autocomplete ───────────────────────────────────
const locInput = document.getElementById('locInput');
const acList   = document.getElementById('acList');
locInput.addEventListener('input', function() {
    clearTimeout(acTimer);
    const q = this.value.trim();
    if (q.length < 3) { acList.style.display = 'none'; return; }
    acTimer = setTimeout(() => {
        fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(q)}&format=json&limit=5&countrycodes=bd&accept-language=bn`)
            .then(r => r.json())
            .then(data => {
                if (!data.length) { acList.style.display = 'none'; return; }
                acList.innerHTML = data.map(d =>
                    `<div class="ac-item" onclick="pickLoc(${d.lat},${d.lon},'${d.display_name.replace(/'/g,"\\'")}')">
                        <i class="fas fa-map-marker-alt"></i><span>${d.display_name}</span>
                    </div>`).join('');
                acList.style.display = 'block';
            }).catch(() => { acList.style.display = 'none'; });
    }, 450);
});

function pickLoc(lat, lng, name) {
    locInput.value = name;
    acList.style.display = 'none';
    setPos(parseFloat(lat), parseFloat(lng));
}

document.addEventListener('click', e => {
    if (!e.target.closest('.inp-w')) acList.style.display = 'none';
});

// Auto GPS on load
window.addEventListener('load', () => {
    if (navigator.geolocation)
        navigator.geolocation.getCurrentPosition(
            p => setPos(p.coords.latitude, p.coords.longitude),
            () => {}, {timeout: 5000}
        );
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
