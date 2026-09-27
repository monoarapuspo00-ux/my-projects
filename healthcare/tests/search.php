<?php
$base_url = '/healthcare';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

$tests     = $pdo->query("SELECT * FROM medical_tests ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$hospitals = $pdo->query("SELECT * FROM hospitals ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// checkbox + text search উভয় handle
$selected_test  = (int)($_GET['test_id'] ?? 0);
$search_query   = trim($_GET['q'] ?? '');
$prices = [];
$search_results = []; // text search results

if ($selected_test) {
    $stmt = $pdo->prepare("
        SELECT ht.*, h.name AS hosp_name, h.address, mt.name AS test_name, mt.description AS test_desc
        FROM hospital_tests ht
        JOIN hospitals h ON ht.hospital_id = h.id
        JOIN medical_tests mt ON ht.test_id = mt.id
        WHERE ht.test_id = ?
        ORDER BY ht.price ASC
    ");
    $stmt->execute([$selected_test]);
    $prices = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($search_query) {
    $stmt = $pdo->prepare("
        SELECT ht.*, h.name AS hosp_name, h.address, mt.name AS test_name, mt.description AS test_desc
        FROM hospital_tests ht
        JOIN hospitals h ON ht.hospital_id = h.id
        JOIN medical_tests mt ON ht.test_id = mt.id
        WHERE mt.name LIKE ? OR mt.description LIKE ?
        ORDER BY mt.name, ht.price ASC
    ");
    $like = '%' . $search_query . '%';
    $stmt->execute([$like, $like]);
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Test page এর নিজস্ব sliders — appointment_sliders এর সাথে mix নেই
$sliders = [];
try {
    $sliders = $pdo->query("
        SELECT * FROM test_sliders WHERE is_active=1 ORDER BY sort_order ASC LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) { $sliders = []; }

// Fallback sliders — pathological instrument images
if (empty($sliders)) {
    $sliders = [
        [
            'title'     => 'সঠিক মূল্যে সেরা স্বাস্থ্যসেবা',
            'subtitle'  => 'বিভিন্ন হাসপাতালের টেস্ট মূল্য তুলনা করুন এবং সাশ্রয়ী বিকল্প বেছে নিন',
            // Blood sample collection tubes — colorful pathology specimens
            'image_url' => 'https://images.unsplash.com/photo-1579165466741-7f35e4755660?w=1200&q=80',
            'btn_text'  => 'এখনই খুঁজুন',
            'btn_url'   => '#search-section',
        ],
        [
            'title'     => 'উন্নত প্রযুক্তিতে সঠিক রোগ নির্ণয়',
            'subtitle'  => 'আধুনিক মাইক্রোস্কোপ ও যন্ত্রপাতিতে নির্ভুল পরীক্ষা-নিরীক্ষা',
            // Laboratory microscope — pathology instrument
            'image_url' => 'https://images.unsplash.com/photo-1507413245164-6160d8298b31?w=1200&q=80',
            'btn_text'  => 'টেস্ট খুঁজুন',
            'btn_url'   => '#search-section',
        ],
        [
            'title'     => 'দ্রুত ও নির্ভরযোগ্য টেস্ট রিপোর্ট',
            'subtitle'  => 'CBC, ECG, MRI সহ সকল পরীক্ষার ফলাফল সরাসরি ইমেইলে পান',
            // Test tubes rack in lab — pathology specimens
            'image_url' => 'https://images.unsplash.com/photo-1576086213369-97a306d36557?w=1200&q=80',
            'btn_text'  => 'অ্যাপয়েন্টমেন্ট করুন',
            'btn_url'   => '/healthcare/appointment/book.php',
        ],
    ];
}
?>

<style>
/* ── Slider ── */
.test-slider{position:relative;overflow:hidden;border-radius:0 0 1.5rem 1.5rem;margin-bottom:0}
.test-slider .carousel-item{height:380px}
.test-slider .carousel-item img{width:100%;height:380px;object-fit:cover;filter:brightness(.55)}
.test-slider .carousel-caption{bottom:auto;top:50%;transform:translateY(-50%);text-align:left;left:8%;right:35%}
.test-slider .carousel-caption h2{font-size:1.9rem;font-weight:800;margin-bottom:.6rem;text-shadow:0 2px 8px rgba(0,0,0,.4)}
.test-slider .carousel-caption p{font-size:.95rem;opacity:.9;margin-bottom:1.1rem}
.test-slider .carousel-caption .sl-btn{
    display:inline-block;background:linear-gradient(135deg,#F59E0B,#D97706);
    color:#fff;border-radius:50px;padding:.6rem 1.8rem;
    font-size:.9rem;font-weight:700;text-decoration:none;
    box-shadow:0 4px 16px rgba(245,158,11,.4);transition:transform .2s;
}
.test-slider .carousel-caption .sl-btn:hover{transform:translateY(-2px);color:#fff}
.test-slider .carousel-indicators button{width:10px;height:10px;border-radius:50%;border:none}

/* Quick stats on slider right */
.slider-stats{
    position:absolute;right:4%;top:50%;transform:translateY(-50%);
    display:flex;flex-direction:column;gap:.6rem;z-index:10;
}
.slider-stat{
    background:rgba(255,255,255,.18);backdrop-filter:blur(8px);
    border:1px solid rgba(255,255,255,.3);border-radius:12px;
    padding:.7rem 1rem;color:#fff;text-align:center;min-width:110px;
}
.slider-stat .sn{font-size:1.4rem;font-weight:800;line-height:1}
.slider-stat .sl{font-size:.72rem;opacity:.88;margin-top:.15rem}

/* ── Search Section ── */
.search-section{background:#F0F4F8;padding:2rem 0}
.search-card-main{background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);padding:1.75rem 2rem}
.search-card-main h4{color:#0D47A1;font-weight:700;margin-bottom:1.25rem}

/* Tabs */
.search-tabs{display:flex;gap:.5rem;margin-bottom:1.25rem;border-bottom:2px solid #f1f5f9;padding-bottom:.5rem}
.s-tab{background:none;border:none;padding:.5rem 1.2rem;border-radius:8px 8px 0 0;font-size:.9rem;font-weight:600;color:#64748b;cursor:pointer;transition:all .15s;border-bottom:3px solid transparent}
.s-tab.active{color:#1565C0;border-bottom-color:#1565C0;background:#EFF6FF}

/* Text search input */
.big-search{position:relative}
.big-search i{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:1.1rem}
.big-search input{padding-left:48px;padding-right:140px;border-radius:50px;border:2px solid #e2e8f0;font-size:1rem;height:54px;transition:border-color .2s}
.big-search input:focus{border-color:#F59E0B;box-shadow:0 0 0 3px rgba(245,158,11,.15);outline:none}
.big-search .s-btn{position:absolute;right:6px;top:6px;border-radius:50px;background:linear-gradient(135deg,#F59E0B,#D97706);border:none;color:#fff;padding:.5rem 1.4rem;font-weight:700}

/* Autocomplete suggestions */
#testSuggestions{position:absolute;top:100%;left:0;right:0;z-index:999;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.1);margin-top:4px;display:none;max-height:240px;overflow-y:auto}
.sug-item{padding:.6rem 1rem;cursor:pointer;display:flex;align-items:center;gap:.6rem;font-size:.88rem;border-bottom:1px solid #f8f9fa;transition:background .12s}
.sug-item:last-child{border-bottom:none}
.sug-item:hover{background:#FEF9EC}
.sug-item i{color:#F59E0B;flex-shrink:0}

/* Result cards */
.result-card{background:#fff;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,.07);padding:1.3rem;height:100%;border-left:5px solid #1565C0;transition:transform .2s}
.result-card:hover{transform:translateY(-3px)}
.result-card.best{border-left-color:#15803d}
.price-min{color:#15803d;font-weight:700}

/* Search results grouped */
.test-group{background:#fff;border-radius:14px;box-shadow:0 4px 16px rgba(0,0,0,.07);margin-bottom:1.2rem;overflow:hidden}
.test-group-head{background:linear-gradient(135deg,#0D47A1,#1565C0);color:#fff;padding:.9rem 1.25rem;font-weight:700;font-size:.95rem}
.test-group-body{padding:1rem 1.25rem}
</style>

<!-- ── Slider ── -->
<?php if (!empty($sliders)): ?>
<div class="test-slider">
    <div id="testCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="4500">
        <div class="carousel-indicators">
            <?php foreach ($sliders as $i => $_s): ?>
            <button type="button" data-bs-target="#testCarousel" data-bs-slide-to="<?= $i ?>" class="<?= $i===0?'active':'' ?>"></button>
            <?php endforeach; ?>
        </div>
        <div class="carousel-inner">
            <?php foreach ($sliders as $i => $sl): ?>
            <div class="carousel-item <?= $i===0?'active':'' ?>">
                <img src="<?= htmlspecialchars($sl['image_url']) ?>" alt="">
                <div class="carousel-caption">
                    <h2><?= htmlspecialchars($sl['title']) ?></h2>
                    <p><?= htmlspecialchars($sl['subtitle']) ?></p>
                    <a href="<?= htmlspecialchars($sl['btn_url']) ?>" class="sl-btn">
                        <?= htmlspecialchars($sl['btn_text']) ?> <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#testCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
        <button class="carousel-control-next" type="button" data-bs-target="#testCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
    </div>
    <!-- Quick stats -->
    <div class="slider-stats d-none d-md-flex">
        <div class="slider-stat"><div class="sn"><?= count($tests) ?>+</div><div class="sl">টেস্ট</div></div>
        <div class="slider-stat"><div class="sn"><?= count($hospitals) ?>+</div><div class="sl">হাসপাতাল</div></div>
        <div class="slider-stat"><div class="sn">৩০%</div><div class="sl">সাশ্রয়</div></div>
    </div>
</div>
<?php endif; ?>

<!-- ── Search Section ── -->
<div class="search-section" id="search-section">
    <div class="container">
        <div class="search-card-main">
            <h4><i class="fas fa-flask me-2"></i>মেডিকেল টেস্ট মূল্য তুলনা</h4>

            <!-- Tabs -->
            <div class="search-tabs">
                <button class="s-tab <?= !$search_query?'active':'' ?>" onclick="switchTab('checkbox')">
                    <i class="fas fa-list-check me-1"></i>তালিকা থেকে বেছে নিন
                </button>
                <button class="s-tab <?= $search_query?'active':'' ?>" onclick="switchTab('text')">
                    <i class="fas fa-keyboard me-1"></i>লিখে খুঁজুন
                </button>
            </div>

            <!-- Tab 1: Checkbox/Select -->
            <div id="tabCheckbox" style="display:<?= $search_query?'none':'block' ?>">
                <form method="GET">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">টেস্ট সিলেক্ট করুন</label>
                            <select name="test_id" class="form-select form-select-lg" required>
                                <option value="">— টেস্ট বেছে নিন —</option>
                                <?php foreach ($tests as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $t['id']==$selected_test?'selected':'' ?>>
                                    <?= htmlspecialchars($t['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold">
                                <i class="fas fa-search me-2"></i>মূল্য দেখুন
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tab 2: Text search -->
            <div id="tabText" style="display:<?= $search_query?'block':'none' ?>">
                <form method="GET">
                    <div class="big-search mb-2">
                        <i class="fas fa-search"></i>
                        <input type="text" name="q" id="testSearchInput" class="form-control"
                               placeholder="টেস্টের নাম লিখুন, যেমন: CBC, ECG, MRI, X-Ray..."
                               value="<?= htmlspecialchars($search_query) ?>"
                               autocomplete="off">
                        <button type="submit" class="s-btn btn">
                            <i class="fas fa-search me-1"></i>খুঁজুন
                        </button>
                        <div id="testSuggestions"></div>
                    </div>
                    <small class="text-muted">উদাহরণ: CBC, ECG, X-Ray, MRI, Blood Sugar, Liver Function</small>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ── Results ── -->
<div class="container py-4 pb-5">

    <!-- Checkbox results -->
    <?php if ($selected_test && empty($prices)): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>এই টেস্টের কোনো মূল্য তথ্য পাওয়া যায়নি।</div>
    <?php elseif (!empty($prices)): ?>
    <?php $min_price = min(array_column($prices,'price')); ?>
    <h5 class="fw-bold mb-3">
        <i class="fas fa-flask me-2 text-warning"></i>
        <?= htmlspecialchars($prices[0]['test_name']) ?> — হাসপাতাল ভেদে মূল্য
        <span class="badge bg-warning text-dark ms-2"><?= count($prices) ?> টি হাসপাতাল</span>
    </h5>
    <?php if (!empty($prices[0]['test_desc'])): ?>
    <p class="text-muted mb-3"><?= htmlspecialchars($prices[0]['test_desc']) ?></p>
    <?php endif; ?>
    <div class="row g-3">
        <?php foreach ($prices as $p): ?>
        <div class="col-md-6">
            <div class="result-card <?= $p['price']==$min_price?'best':'' ?>">
                <?php if ($p['price']==$min_price): ?>
                <span class="badge bg-success mb-2"><i class="fas fa-crown me-1"></i>সবচেয়ে কম মূল্য</span>
                <?php endif; ?>
                <h5 class="fw-bold mb-1"><?= htmlspecialchars($p['hosp_name']) ?></h5>
                <p class="text-muted mb-2" style="font-size:.85rem">
                    <i class="fas fa-location-dot me-1"></i><?= htmlspecialchars($p['address']) ?>
                </p>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="fs-3 fw-bold <?= $p['price']==$min_price?'price-min':'text-primary' ?>">
                        ৳<?= number_format((float)$p['price']) ?>
                    </span>
                    <div class="d-flex gap-2">
                        <a href="<?= $base_url ?>/appointment/book.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-calendar me-1"></i>বুকিং
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Text search results -->
    <?php if ($search_query && !empty($search_results)): ?>
    <?php
    // Group by test name
    $grouped = [];
    foreach ($search_results as $r) {
        $grouped[$r['test_name']][] = $r;
    }
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">
            <i class="fas fa-search me-2 text-warning"></i>
            "<?= htmlspecialchars($search_query) ?>" এর ফলাফল
        </h5>
        <span class="badge bg-warning text-dark"><?= count($grouped) ?> টি টেস্ট পাওয়া গেছে</span>
    </div>
    <?php foreach ($grouped as $tname => $tprices): ?>
    <?php $min = min(array_column($tprices, 'price')); ?>
    <div class="test-group">
        <div class="test-group-head">
            <i class="fas fa-vial me-2"></i><?= htmlspecialchars($tname) ?>
            <span class="badge bg-warning text-dark ms-2" style="font-size:.72rem">
                সর্বনিম্ন ৳<?= number_format($min) ?>
            </span>
        </div>
        <div class="test-group-body">
            <?php if (!empty($tprices[0]['test_desc'])): ?>
            <p class="text-muted mb-3" style="font-size:.85rem"><?= htmlspecialchars($tprices[0]['test_desc']) ?></p>
            <?php endif; ?>
            <div class="row g-3">
                <?php foreach ($tprices as $p): ?>
                <div class="col-md-6">
                    <div class="result-card <?= $p['price']==$min?'best':'' ?>" style="padding:1rem">
                        <?php if ($p['price']==$min): ?>
                        <span class="badge bg-success mb-1" style="font-size:.7rem"><i class="fas fa-crown me-1"></i>সবচেয়ে কম</span>
                        <?php endif; ?>
                        <div class="fw-bold mb-1" style="font-size:.9rem"><?= htmlspecialchars($p['hosp_name']) ?></div>
                        <div class="text-muted mb-2" style="font-size:.76rem"><?= htmlspecialchars($p['address']) ?></div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="fw-bold fs-5 <?= $p['price']==$min?'price-min':'text-primary' ?>">৳<?= number_format((float)$p['price']) ?></span>
                            <a href="<?= $base_url ?>/appointment/book.php" class="btn btn-sm btn-primary" style="font-size:.76rem">বুকিং</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php elseif ($search_query && empty($search_results)): ?>
    <div class="alert alert-info text-center">
        <i class="fas fa-search fa-2x d-block mb-2"></i>
        "<strong><?= htmlspecialchars($search_query) ?></strong>" নামে কোনো টেস্ট পাওয়া যায়নি।<br>
        <small class="text-muted">অন্য নামে চেষ্টা করুন, যেমন: CBC, ECG, X-Ray</small>
    </div>
    <?php endif; ?>

</div>

<script>
function switchTab(tab){
    document.getElementById('tabCheckbox').style.display=tab==='checkbox'?'block':'none';
    document.getElementById('tabText').style.display=tab==='text'?'block':'none';
    document.querySelectorAll('.s-tab').forEach((t,i)=>{
        t.classList.toggle('active',(tab==='checkbox'&&i===0)||(tab==='text'&&i===1));
    });
    if(tab==='text') document.getElementById('testSearchInput').focus();
}

// Live suggestions
const TESTS = <?= json_encode(array_map(fn($t)=>['id'=>$t['id'],'name'=>$t['name']], $tests)) ?>;
const inp = document.getElementById('testSearchInput');
const sug = document.getElementById('testSuggestions');

if(inp){
    inp.addEventListener('input',function(){
        const q=this.value.trim().toLowerCase();
        if(q.length<2){sug.style.display='none';return;}
        const matches=TESTS.filter(t=>t.name.toLowerCase().includes(q)).slice(0,8);
        if(!matches.length){sug.style.display='none';return;}
        sug.innerHTML=matches.map(t=>`
            <div class="sug-item" onclick="fillSearch('${t.name.replace(/'/g,"\\'")}')">
                <i class="fas fa-vial"></i><span>${t.name}</span>
            </div>`).join('');
        sug.style.display='block';
    });
}
function fillSearch(name){
    if(inp) inp.value=name;
    sug.style.display='none';
    inp.closest('form').submit();
}
document.addEventListener('click',e=>{if(!e.target.closest('.big-search')) sug.style.display='none';});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
