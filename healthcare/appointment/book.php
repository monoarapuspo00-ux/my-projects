<?php
session_start();
$base_url = '/healthcare';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../includes/header.php';

$msg = $err = '';
$test_msg = $test_err = '';
$doctor_id  = (int)($_GET['doctor_id']  ?? 0);
$active_tab = $_GET['tab'] ?? 'doctor';

// ── Email HTML builder ───────────────────────────────────────────
function buildApptEmail(string $uname, string $dname, string $hname,
                         string $dept, string $date, string $time,
                         string $fee, string $notes): string {
    return "<!DOCTYPE html><html><head><meta charset='UTF-8'>
<style>body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:0}
.w{max-width:600px;margin:30px auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.1)}
.h{background:linear-gradient(135deg,#1565C0,#0D47A1);color:#fff;padding:24px 32px}
.h h2{margin:0;font-size:1.2rem}.h p{margin:4px 0 0;opacity:.82;font-size:.83rem}
.b{padding:28px 32px}
.row{display:flex;justify-content:space-between;border-bottom:1px solid #f0f0f0;padding:10px 0}
.l{color:#78909C;font-size:.85rem}.v{font-weight:600;font-size:.88rem;color:#1e293b}
.ok{display:inline-block;background:#E8F5E9;color:#2E7D32;border-radius:20px;padding:3px 14px;font-size:.78rem;font-weight:700}
.note{background:#E3F2FD;border-left:4px solid #1565C0;padding:12px 16px;border-radius:6px;margin:16px 0;font-size:.86rem;color:#1e40af}
.warn{color:#90A4AE;font-size:.8rem;margin-top:14px}
.f{background:#f8f9fa;padding:14px 32px;text-align:center;font-size:.78rem;color:#90A4AE}</style>
</head><body><div class='w'>
<div class='h'><h2>✅ অ্যাপয়েন্টমেন্ট নিশ্চিত হয়েছে</h2><p>Healthcare Portal — বুকিং বিজ্ঞপ্তি</p></div>
<div class='b'>
<p>প্রিয় <strong>$uname</strong>,</p>
<p>আপনার অ্যাপয়েন্টমেন্ট সফলভাবে নিশ্চিত হয়েছে।</p>
<div class='row'><span class='l'>ডাক্তার</span><span class='v'>$dname</span></div>
<div class='row'><span class='l'>বিশেষজ্ঞতা</span><span class='v'>$dept</span></div>
<div class='row'><span class='l'>হাসপাতাল</span><span class='v'>$hname</span></div>
<div class='row'><span class='l'>তারিখ</span><span class='v'>$date</span></div>
<div class='row'><span class='l'>সময়</span><span class='v'>$time</span></div>
<div class='row'><span class='l'>ভিজিট ফি</span><span class='v'>$fee</span></div>
<div class='row'><span class='l'>স্ট্যাটাস</span><span class='v'><span class='ok'>✓ নিশ্চিত</span></span></div>
" . ($notes ? "<div class='note'><strong>আপনার নোট:</strong> $notes</div>" : "") . "
<p class='warn'>⚠️ নির্ধারিত সময়ের কমপক্ষে ১৫ মিনিট আগে হাসপাতালে পৌঁছান।</p>
</div><div class='f'>Healthcare Portal &copy; " . date('Y') . " — আপনার স্বাস্থ্য, আমাদের দায়িত্ব</div>
</div></body></html>";
}

function buildTestEmail(string $uname, string $tname, string $hname,
                         string $date, string $price, string $notes): string {
    return "<!DOCTYPE html><html><head><meta charset='UTF-8'>
<style>body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:0}
.w{max-width:600px;margin:30px auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.1)}
.h{background:linear-gradient(135deg,#EA580C,#C2410C);color:#fff;padding:24px 32px}
.h h2{margin:0;font-size:1.2rem}.h p{margin:4px 0 0;opacity:.82;font-size:.83rem}
.b{padding:28px 32px}
.row{display:flex;justify-content:space-between;border-bottom:1px solid #f0f0f0;padding:10px 0}
.l{color:#78909C;font-size:.85rem}.v{font-weight:600;font-size:.88rem;color:#1e293b}
.ok{display:inline-block;background:#FFF7ED;color:#C2410C;border-radius:20px;padding:3px 14px;font-size:.78rem;font-weight:700}
.note{background:#FFF7ED;border-left:4px solid #EA580C;padding:12px 16px;border-radius:6px;margin:16px 0;font-size:.86rem;color:#9A3412}
.warn{color:#90A4AE;font-size:.8rem;margin-top:14px}
.f{background:#f8f9fa;padding:14px 32px;text-align:center;font-size:.78rem;color:#90A4AE}</style>
</head><body><div class='w'>
<div class='h'><h2>🔬 টেস্ট বুকিং নিশ্চিত হয়েছে</h2><p>Healthcare Portal — বুকিং বিজ্ঞপ্তি</p></div>
<div class='b'>
<p>প্রিয় <strong>$uname</strong>,</p>
<p>আপনার মেডিকেল টেস্ট বুকিং সফলভাবে সম্পন্ন হয়েছে।</p>
<div class='row'><span class='l'>পরীক্ষার নাম</span><span class='v'>$tname</span></div>
<div class='row'><span class='l'>হাসপাতাল</span><span class='v'>$hname</span></div>
<div class='row'><span class='l'>পছন্দের তারিখ</span><span class='v'>$date</span></div>
<div class='row'><span class='l'>পরীক্ষার ফি</span><span class='v'>$price</span></div>
<div class='row'><span class='l'>স্ট্যাটাস</span><span class='v'><span class='ok'>⏳ অপেক্ষমাণ</span></span></div>
" . ($notes ? "<div class='note'><strong>বিশেষ নির্দেশনা:</strong> $notes</div>" : "") . "
<p class='warn'>📋 রিপোর্ট প্রস্তুত হলে আপনার ইমেইলে পাঠানো হবে। পরীক্ষার আগে হাসপাতালে confirm করুন।</p>
</div><div class='f'>Healthcare Portal &copy; " . date('Y') . " — আপনার স্বাস্থ্য, আমাদের দায়িত্ব</div>
</div></body></html>";
}

// ── Doctor Appointment ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['book_type'] ?? '') === 'doctor') {
    if (!isset($_SESSION['user_id'])) { header("Location: $base_url/auth/login.php"); exit; }
    $d_id  = (int)($_POST['doctor_id']        ?? 0);
    $h_id  = (int)($_POST['hospital_id']      ?? 0);
    $date  = trim($_POST['appointment_date']   ?? '');
    $time  = trim($_POST['appointment_time']   ?? '');
    $notes = trim($_POST['notes']              ?? '');

    if (!$d_id || !$h_id || !$date || !$time) {
        $err = 'সব তথ্য পূরণ করুন।';
    } else {
        $pdo->prepare("INSERT INTO appointments(user_id,doctor_id,hospital_id,appointment_date,appointment_time,notes) VALUES(?,?,?,?,?,?)")
            ->execute([$_SESSION['user_id'],$d_id,$h_id,$date,$time,$notes]);
        $msg = 'অ্যাপয়েন্টমেন্ট সফলভাবে বুক হয়েছে!';

        // Email পাঠানো
        try {
            $user = $pdo->prepare("SELECT name,email FROM users WHERE id=?");
            $user->execute([$_SESSION['user_id']]);
            $urow = $user->fetch(PDO::FETCH_ASSOC);

            $doc  = $pdo->prepare("SELECT doc.name,doc.fee,doc.schedule,h.name AS hname,dep.name_bn AS dname FROM doctors doc JOIN hospitals h ON doc.hospital_id=h.id JOIN departments dep ON doc.department_id=dep.id WHERE doc.id=?");
            $doc->execute([$d_id]);
            $drow = $doc->fetch(PDO::FETCH_ASSOC);

            if ($urow && !empty($urow['email']) && $drow) {
                $html = buildApptEmail(
                    $urow['name'],
                    $drow['name'],
                    $drow['hname'],
                    $drow['dname'],
                    date('d M Y', strtotime($date)),
                    $time,
                    '৳' . number_format((float)$drow['fee']),
                    htmlspecialchars($notes)
                );
                sendMail($urow['email'], $urow['name'],
                    "অ্যাপয়েন্টমেন্ট নিশ্চিত — {$drow['name']} ({$drow['hname']})", $html);
            }
        } catch (Exception $e) { /* email fail হলেও booking থাকবে */ }
    }
    $active_tab = 'doctor';
}

// ── Test Booking ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['book_type'] ?? '') === 'test') {
    if (!isset($_SESSION['user_id'])) { header("Location: $base_url/auth/login.php"); exit; }
    $test_id   = (int)($_POST['test_id']       ?? 0);
    $t_hosp_id = (int)($_POST['t_hospital_id'] ?? 0);
    $t_date    = trim($_POST['test_date']       ?? '');
    $t_notes   = trim($_POST['test_notes']      ?? '');

    if (!$test_id || !$t_hosp_id || !$t_date) {
        $test_err = 'টেস্ট, হাসপাতাল ও তারিখ অবশ্যই দিন।';
    } else {
        try {
            $pdo->prepare("
                INSERT INTO test_bookings(user_id,hospital_id,test_id,test_date,test_notes,status)
                VALUES(?,?,?,?,?,'pending')
            ")->execute([$_SESSION['user_id'], $t_hosp_id, $test_id,
                          $t_date ?: null, $t_notes ?: null]);
            $test_msg = 'টেস্ট বুকিং সফলভাবে সম্পন্ন হয়েছে!';

            // Email পাঠানো
            try {
                $user = $pdo->prepare("SELECT name,email FROM users WHERE id=?");
                $user->execute([$_SESSION['user_id']]);
                $urow = $user->fetch(PDO::FETCH_ASSOC);

                $trow = $pdo->prepare("SELECT mt.name AS tname, h.name AS hname, ht.price FROM medical_tests mt JOIN hospitals h ON h.id=? LEFT JOIN hospital_tests ht ON ht.test_id=mt.id AND ht.hospital_id=? WHERE mt.id=?");
                $trow->execute([$t_hosp_id, $t_hosp_id, $test_id]);
                $tdata = $trow->fetch(PDO::FETCH_ASSOC);

                if ($urow && !empty($urow['email']) && $tdata) {
                    $price_str = $tdata['price'] ? '৳'.number_format((float)$tdata['price']) : 'তথ্য নেই';
                    $html = buildTestEmail(
                        $urow['name'],
                        $tdata['tname'],
                        $tdata['hname'],
                        date('d M Y', strtotime($t_date)),
                        $price_str,
                        htmlspecialchars($t_notes)
                    );
                    sendMail($urow['email'], $urow['name'],
                        "টেস্ট বুকিং নিশ্চিত — {$tdata['tname']} ({$tdata['hname']})", $html);
                }
            } catch (Exception $e) { /* email fail হলেও booking থাকবে */ }

        } catch (Exception $e) {
            $test_err = 'বুকিং সমস্যা: test_bookings table নেই — SQL run করুন।';
        }
    }
    $active_tab = 'test';
}

$doctors = $pdo->query("
    SELECT doc.*, h.name AS hosp_name, dep.name_bn AS dept_name
    FROM doctors doc
    JOIN hospitals h   ON doc.hospital_id   = h.id
    JOIN departments dep ON doc.department_id = dep.id
    ORDER BY dep.name_bn, doc.name
")->fetchAll(PDO::FETCH_ASSOC);

// Test booking এর জন্য data
$medical_tests = $pdo->query("SELECT * FROM medical_tests ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$hospitals_all = $pdo->query("SELECT * FROM hospitals ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Test prices — কোন হাসপাতালে কত
$test_prices_all = $pdo->query("
    SELECT ht.*, h.name AS hosp_name, mt.name AS test_name
    FROM hospital_tests ht
    JOIN hospitals h      ON ht.hospital_id = h.id
    JOIN medical_tests mt ON ht.test_id     = mt.id
    ORDER BY mt.name, ht.price ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Sliders — appointment_sliders table থেকে নেবে, না থাকলে fallback
$sliders = [];
try {
    $sliders = $pdo->query("SELECT * FROM appointment_sliders WHERE is_active=1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ── Slider fallback — DMCH / PG / Salimullah ────────────────────────────────
if (empty($sliders)) {
    $sliders = [
        [
            // ঢাকা মেডিকেল কলেজ হাসপাতাল — Wikipedia আসল ছবি
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8e/Dhaka_Medical_College_Hospital.jpg/1280px-Dhaka_Medical_College_Hospital.jpg',
            'title'     => 'ঢাকা মেডিকেল কলেজ হাসপাতাল',
            'subtitle'  => 'বাংলাদেশের বৃহত্তম সরকারি হাসপাতাল — অনলাইনে অ্যাপয়েন্টমেন্ট নিন',
            'btn_text'  => 'এখনই বুক করুন',
            'btn_url'   => '#appt-form',
        ],
        [
            // Sir Salimullah Medical College (Mitford Hospital) — Wikipedia
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2b/Sir_Salimullah_Medical_College.jpg/1280px-Sir_Salimullah_Medical_College.jpg',
            'title'     => 'স্যার সলিমুল্লাহ মেডিকেল কলেজ',
            'subtitle'  => 'মিটফোর্ড হাসপাতাল — ঢাকার ঐতিহ্যবাহী চিকিৎসা কেন্দ্র',
            'btn_text'  => 'অ্যাপয়েন্টমেন্ট নিন',
            'btn_url'   => '#appt-form',
        ],
        [
            // Bangabandhu Sheikh Mujib Medical University (PG Hospital) — Wikipedia
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6e/Bangabandhu_Sheikh_Mujib_Medical_University.jpg/1280px-Bangabandhu_Sheikh_Mujib_Medical_University.jpg',
            'title'     => 'বঙ্গবন্ধু শেখ মুজিব মেডিকেল বিশ্ববিদ্যালয়',
            'subtitle'  => 'পিজি হাসপাতাল — দেশের সেরা বিশেষজ্ঞ চিকিৎসা সেবা',
            'btn_text'  => 'ডাক্তার খুঁজুন',
            'btn_url'   => '#appt-form',
        ],
        [
            // ল্যাব টেস্ট / Medical lab — Unsplash fallback
            'image_url' => 'https://images.unsplash.com/photo-1551601651-2a8555f1a136?w=1400&q=85',
            'title'     => 'ল্যাব টেস্ট বুকিং — সেরা মূল্যে',
            'subtitle'  => 'রক্ত পরীক্ষা, এক্স-রে, আল্ট্রাসনো — ঘরে বসেই বুক করুন',
            'btn_text'  => 'টেস্ট বুক করুন',
            'btn_url'   => '#appt-form',
        ],
    ];
}

$selected_doctor = null;
if ($doctor_id) {
    foreach ($doctors as $d) { if ($d['id']==$doctor_id) { $selected_doctor=$d; break; } }
}
?>

<style>
/* ── Slider ── */
.appt-slider { position:relative; overflow:hidden; border-radius:0 0 2rem 2rem; margin-bottom:0; }
.appt-slider .carousel-item { height:460px; }
.appt-slider .carousel-item img {
    width:100%; height:460px; object-fit:cover;
    filter:brightness(.50) saturate(1.15) contrast(1.05);
    object-position:center center;
    background:#0D47A1; /* fallback if image fails */
}
.appt-slider .carousel-item {
    background: linear-gradient(135deg,#0D47A1,#1565C0); /* always visible bg */
}
.appt-slider .carousel-caption {
    bottom:auto; top:50%; transform:translateY(-50%);
    text-align:left; left:7%; right:38%;
}
.appt-slider .carousel-caption h2 {
    font-size:2rem; font-weight:800; line-height:1.25;
    margin-bottom:.65rem;
    text-shadow:0 2px 12px rgba(0,0,0,.5);
}
.appt-slider .carousel-caption p {
    font-size:.95rem; opacity:.92; margin-bottom:1.3rem;
    text-shadow:0 1px 6px rgba(0,0,0,.4); line-height:1.5;
}
.sl-btn {
    display:inline-block;
    background:linear-gradient(135deg,#1565C0,#0D47A1);
    color:#fff; border-radius:50px;
    padding:.65rem 2rem; font-size:.95rem; font-weight:700;
    text-decoration:none;
    box-shadow:0 6px 20px rgba(13,71,161,.45);
    transition:transform .2s, box-shadow .2s;
    border:2px solid rgba(255,255,255,.25);
}
.sl-btn:hover { transform:translateY(-3px); box-shadow:0 10px 28px rgba(13,71,161,.5); color:#fff; }

/* Right side floating stats card */
.slider-info-card {
    position:absolute; right:5%; top:50%; transform:translateY(-50%);
    background:rgba(255,255,255,.13); backdrop-filter:blur(14px);
    border:1px solid rgba(255,255,255,.25); border-radius:18px;
    padding:1.4rem 1.6rem; color:#fff; min-width:180px;
    display:flex; flex-direction:column; gap:.85rem;
    z-index:10;
}
.info-stat { display:flex; align-items:center; gap:.8rem; }
.info-stat .icon {
    width:42px; height:42px; border-radius:12px;
    background:rgba(255,255,255,.18);
    display:flex; align-items:center; justify-content:center;
    font-size:1.1rem; flex-shrink:0;
}
.info-stat .num { font-size:1.3rem; font-weight:800; line-height:1; }
.info-stat .lbl { font-size:.72rem; opacity:.82; }

/* Carousel indicators */
.appt-slider .carousel-indicators button {
    width:10px; height:10px; border-radius:50%; border:none;
    opacity:.5; transition:all .2s;
}
.appt-slider .carousel-indicators button.active { opacity:1; transform:scale(1.3); }

/* ── Form section ── */
.form-wrap { background:#F0F4F8; padding:2.5rem 0 3rem; }
.form-card {
    background:#fff; border-radius:18px;
    box-shadow:0 8px 32px rgba(0,0,0,.09);
    padding:2rem 2.25rem; max-width:680px; margin:0 auto;
}
.form-card .section-label {
    font-size:.7rem; font-weight:700; letter-spacing:1.2px;
    text-transform:uppercase; color:#94a3b8; margin-bottom:.5rem;
}
.form-title { color:#0D47A1; font-weight:800; font-size:1.5rem; margin-bottom:.4rem; }
.form-subtitle { color:#64748b; font-size:.88rem; margin-bottom:1.75rem; }

/* Doctor info box */
.doc-box {
    background:linear-gradient(135deg,#EFF6FF,#DBEAFE);
    border:1.5px solid #BFDBFE; border-radius:14px;
    padding:1rem 1.25rem; margin-bottom:1.25rem;
    display:none;
}
.doc-box .d-label { font-size:.75rem; color:#64748b; margin-bottom:.2rem; }
.doc-box .d-val   { font-weight:700; color:#0f172a; font-size:.92rem; }
.doc-box .d-fee   { font-size:1.25rem; font-weight:800; color:#1565C0; }

/* Success banner */
.success-banner {
    background:linear-gradient(135deg,#15803d,#166534);
    color:#fff; border-radius:14px; padding:1.25rem 1.5rem;
    display:flex; align-items:center; gap:1rem; margin-bottom:1.5rem;
}
.success-banner i { font-size:2rem; flex-shrink:0; }

/* Steps */
.step-row { display:flex; justify-content:center; gap:0; margin-bottom:2rem; }
.step-item { display:flex; align-items:center; gap:.4rem; font-size:.8rem; font-weight:600; color:#94a3b8; }
.step-item.done   { color:#15803d; }
.step-item.active { color:#1565C0; }
.step-circle { width:28px; height:28px; border-radius:50%; background:#e2e8f0; color:#64748b; display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:700; flex-shrink:0; }
.step-item.active .step-circle { background:#1565C0; color:#fff; }
.step-item.done   .step-circle { background:#15803d; color:#fff; }
.step-line { width:40px; height:2px; background:#e2e8f0; margin:0 .4rem; }
.step-item.done + .step-line,
.step-item.active + .step-line { background:#1565C0; }

@media(max-width:767px){
    .appt-slider .carousel-item { height:320px; }
    .appt-slider .carousel-item img { height:320px; }
    .appt-slider .carousel-caption { left:5%; right:5%; text-align:center; }
    .appt-slider .carousel-caption h2 { font-size:1.35rem; }
    .slider-info-card { display:none !important; }
    .form-card { padding:1.25rem; }
}
</style>

<!-- ══ SLIDER ══ -->
<div class="appt-slider">
    <div id="apptCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="4500">
        <div class="carousel-indicators">
            <?php foreach ($sliders as $i => $_): ?>
            <button type="button" data-bs-target="#apptCarousel"
                    data-bs-slide-to="<?= $i ?>"
                    class="<?= $i===0?'active':'' ?>"></button>
            <?php endforeach; ?>
        </div>
        <div class="carousel-inner">
            <?php foreach ($sliders as $i => $sl): ?>
            <div class="carousel-item <?= $i===0?'active':'' ?>">
                <img src="<?= htmlspecialchars($sl['image_url']) ?>" alt="<?= htmlspecialchars($sl['title']) ?>" loading="<?= $i===0?'eager':'lazy' ?>" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=1400&q=80'">
                <div class="carousel-caption">
                    <h2><?= htmlspecialchars($sl['title']) ?></h2>
                    <p><?= htmlspecialchars($sl['subtitle']) ?></p>
                    <a href="<?= htmlspecialchars($sl['btn_url']) ?>" class="sl-btn">
                        <?= htmlspecialchars($sl['btn_text']) ?>
                        <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#apptCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#apptCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>

    <!-- Floating stats — right side -->
    <div class="slider-info-card d-none d-md-flex">
        <div class="info-stat">
            <div class="icon"><i class="fas fa-user-doctor"></i></div>
            <div>
                <div class="num"><?= count($doctors) ?>+</div>
                <div class="lbl">বিশেষজ্ঞ ডাক্তার</div>
            </div>
        </div>
        <div class="info-stat">
            <div class="icon"><i class="fas fa-hospital"></i></div>
            <div>
                <div class="num">
                    <?php
                    try { echo $pdo->query("SELECT COUNT(*) FROM hospitals")->fetchColumn(); }
                    catch(Exception $e){ echo '10'; }
                    ?>+
                </div>
                <div class="lbl">হাসপাতাল</div>
            </div>
        </div>
        <div class="info-stat">
            <div class="icon"><i class="fas fa-clock"></i></div>
            <div>
                <div class="num">২৪/৭</div>
                <div class="lbl">জরুরি সেবা</div>
            </div>
        </div>
        <div class="info-stat">
            <div class="icon"><i class="fas fa-star"></i></div>
            <div>
                <div class="num">৯৮%</div>
                <div class="lbl">সন্তুষ্ট রোগী</div>
            </div>
        </div>
    </div>
</div>


<!-- ══ FORM ══ -->
<div class="form-wrap" id="appt-form">
    <div class="container">

        <!-- ── Tab Navigation ── -->
        <div class="tab-nav">
            <button class="tab-btn <?= $active_tab==='doctor'?'active':'' ?>"
                    onclick="switchTab('doctor')">
                <i class="fas fa-user-doctor me-2"></i>ডাক্তার অ্যাপয়েন্টমেন্ট
            </button>
            <button class="tab-btn <?= $active_tab==='test'?'active':'' ?>"
                    onclick="switchTab('test')">
                <i class="fas fa-flask me-2"></i>মেডিকেল টেস্ট বুকিং
            </button>
        </div>

        <!-- ══════════════════════════════════════════ -->
        <!-- TAB 1: Doctor Appointment                  -->
        <!-- ══════════════════════════════════════════ -->
        <div id="tabDoctor" class="tab-content <?= $active_tab==='doctor'?'show':'' ?>">

            <!-- Steps -->
            <div class="step-row">
                <div class="step-item <?= !$msg?'active':'done' ?>">
                    <div class="step-circle"><?= $msg?'✓':'1' ?></div>
                    <span class="d-none d-sm-inline">ডাক্তার বেছে নিন</span>
                </div>
                <div class="step-line"></div>
                <div class="step-item <?= $msg?'active':'' ?>">
                    <div class="step-circle">2</div>
                    <span class="d-none d-sm-inline">তারিখ ও সময়</span>
                </div>
                <div class="step-line"></div>
                <div class="step-item">
                    <div class="step-circle">3</div>
                    <span class="d-none d-sm-inline">নিশ্চিতকরণ</span>
                </div>
            </div>

            <div class="form-card">
                <div class="section-label">অনলাইন বুকিং</div>
                <h3 class="form-title">ডাক্তার অ্যাপয়েন্টমেন্ট</h3>
                <p class="form-subtitle">নিচের তথ্য পূরণ করুন — ডাক্তার নির্বাচন করলে বিস্তারিত দেখাবে</p>

                <?php if ($msg): ?>
                <div class="success-banner">
                    <i class="fas fa-circle-check"></i>
                    <div>
                        <div class="fw-bold fs-5">বুকিং সফল হয়েছে!</div>
                        <div style="opacity:.88;font-size:.88rem"><?= htmlspecialchars($msg) ?></div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($err): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($err) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="book_type" value="doctor">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">ডাক্তার সিলেক্ট করুন <span class="text-danger">*</span></label>
                        <select name="doctor_id" id="doctorSel" class="form-select form-select-lg" required onchange="updateDoc(this)">
                            <option value="">— বিভাগ ও ডাক্তার বেছে নিন —</option>
                            <?php
                            $cur_dept = '';
                            foreach ($doctors as $d):
                                if ($d['dept_name'] !== $cur_dept):
                                    if ($cur_dept) echo '</optgroup>';
                                    echo '<optgroup label="' . htmlspecialchars($d['dept_name']) . '">';
                                    $cur_dept = $d['dept_name'];
                                endif;
                            ?>
                            <option value="<?= $d['id'] ?>"
                                data-hosp-id="<?= $d['hospital_id'] ?>"
                                data-hosp="<?= htmlspecialchars($d['hosp_name']) ?>"
                                data-fee="<?= $d['fee'] ?>"
                                data-sched="<?= htmlspecialchars($d['schedule']) ?>"
                                data-spec="<?= htmlspecialchars($d['specialization']??'') ?>"
                                <?= $d['id']==$doctor_id?'selected':'' ?>>
                                <?= htmlspecialchars($d['name']) ?>
                            </option>
                            <?php endforeach; if($cur_dept) echo '</optgroup>'; ?>
                        </select>
                    </div>

                    <input type="hidden" name="hospital_id" id="hospId" 
                           value="<?= $selected_doctor ? (int)$selected_doctor['hospital_id'] : '' ?>">

                    <div class="doc-box" id="docBox">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="d-label">হাসপাতাল</div>
                                <div class="d-val" id="dHosp"></div>
                            </div>
                            <div class="col-6">
                                <div class="d-label">ভিজিট ফি</div>
                                <div class="d-fee" id="dFee"></div>
                            </div>
                            <div class="col-12">
                                <div class="d-label">সময়সূচী</div>
                                <div class="d-val" id="dSched"></div>
                            </div>
                            <div class="col-12" id="dSpecRow" style="display:none">
                                <div class="d-label">বিশেষজ্ঞতা</div>
                                <div class="d-val text-success" id="dSpec"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label class="form-label fw-semibold">তারিখ <span class="text-danger">*</span></label>
                            <input type="date" name="appointment_date" class="form-control form-control-lg"
                                   required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col">
                            <label class="form-label fw-semibold">সময় <span class="text-danger">*</span></label>
                            <input type="time" name="appointment_time" class="form-control form-control-lg" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">সমস্যার বিবরণ <small class="text-muted fw-normal">(ঐচ্ছিক)</small></label>
                        <textarea name="notes" class="form-control" rows="3"
                                  placeholder="সমস্যার সংক্ষিপ্ত বিবরণ লিখুন..."></textarea>
                    </div>

                    <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        বুকিং করতে <a href="<?= $base_url ?>/auth/login.php" class="fw-bold">লগইন করুন</a>।
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary btn-lg w-100"
                            <?= !isset($_SESSION['user_id'])?'disabled':'' ?>>
                        <i class="fas fa-calendar-plus me-2"></i>বুকিং নিশ্চিত করুন
                    </button>
                </form>
            </div>
        </div>

        <!-- ══════════════════════════════════════════ -->
        <!-- TAB 2: Test Booking                        -->
        <!-- ══════════════════════════════════════════ -->
        <div id="tabTest" class="tab-content <?= $active_tab==='test'?'show':'' ?>">

            <!-- Steps -->
            <div class="step-row">
                <div class="step-item <?= !$test_msg?'active':'done' ?>">
                    <div class="step-circle"><?= $test_msg?'✓':'1' ?></div>
                    <span class="d-none d-sm-inline">টেস্ট বেছে নিন</span>
                </div>
                <div class="step-line"></div>
                <div class="step-item <?= $test_msg?'active':'' ?>">
                    <div class="step-circle">2</div>
                    <span class="d-none d-sm-inline">হাসপাতাল ও তারিখ</span>
                </div>
                <div class="step-line"></div>
                <div class="step-item">
                    <div class="step-circle">3</div>
                    <span class="d-none d-sm-inline">নিশ্চিতকরণ</span>
                </div>
            </div>

            <div class="form-card">
                <div class="section-label">মেডিকেল টেস্ট</div>
                <h3 class="form-title">টেস্ট বুকিং করুন</h3>
                <p class="form-subtitle">টেস্ট বেছে নিন — হাসপাতাল ভেদে মূল্য দেখাবে, সেরা মূল্যে বুক করুন</p>

                <?php if ($test_msg): ?>
                <div class="success-banner">
                    <i class="fas fa-circle-check"></i>
                    <div>
                        <div class="fw-bold fs-5">টেস্ট বুকিং সফল!</div>
                        <div style="opacity:.88;font-size:.88rem"><?= htmlspecialchars($test_msg) ?></div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($test_err): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($test_err) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="book_type" value="test">

                    <!-- Test select -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">টেস্ট বেছে নিন <span class="text-danger">*</span></label>
                        <select name="test_id" id="testSel" class="form-select form-select-lg" required
                                onchange="showTestPrices(this.value)">
                            <option value="">— টেস্ট বেছে নিন —</option>
                            <?php foreach ($medical_tests as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Price comparison table -->
                    <div id="priceBox" style="display:none" class="mb-3">
                        <div class="price-compare-box">
                            <div class="price-compare-title">
                                <i class="fas fa-tags me-2"></i>হাসপাতাল ভেদে মূল্য তুলনা
                                <span class="badge bg-warning text-dark ms-2" id="priceBadge"></span>
                            </div>
                            <div id="priceList" class="price-list"></div>
                        </div>
                    </div>

                    <!-- Hospital select -->
                    <script>const ALL_HOSPITALS = <?= json_encode($hospitals_all) ?>;</script>
                    <!-- Hospital select — JS দিয়ে dynamically populate হবে -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">হাসপাতাল বেছে নিন <span class="text-danger">*</span></label>
                        <select name="t_hospital_id" id="testHospSel" class="form-select form-select-lg" required>
                            <option value="">— প্রথমে টেস্ট বেছে নিন —</option>
                        </select>
                        <div id="testHospNote" class="form-text text-muted mt-1" style="display:none">
                            <i class="fas fa-info-circle me-1 text-warning"></i>এই টেস্টের মূল্য তথ্য নেই — সকল হাসপাতাল দেখানো হচ্ছে
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">পছন্দের তারিখ <span class="text-danger">*</span></label>
                        <input type="date" name="test_date" class="form-control form-control-lg"
                               required min="<?= date('Y-m-d') ?>">
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">বিশেষ নির্দেশনা <small class="text-muted fw-normal">(ঐচ্ছিক)</small></label>
                        <textarea name="test_notes" class="form-control" rows="2"
                                  placeholder="যেমন: খালি পেটে আসব, রিপোর্ট জরুরি..."></textarea>
                    </div>

                    <!-- Info note -->
                    <div class="test-info-note">
                        <i class="fas fa-circle-info me-2"></i>
                        টেস্টের রিপোর্ট সরাসরি আপনার <strong>ইমেইলে</strong> পাঠানো হবে।
                        রিপোর্ট পেতে আপনার profile এ ইমেইল যোগ করুন।
                    </div>

                    <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        বুকিং করতে <a href="<?= $base_url ?>/auth/login.php" class="fw-bold">লগইন করুন</a>।
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn-test-book mt-3 w-100"
                            <?= !isset($_SESSION['user_id'])?'disabled':'' ?>>
                        <i class="fas fa-flask me-2"></i>টেস্ট বুকিং নিশ্চিত করুন
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<style>
/* ── Tabs ── */
.tab-nav{
    display:flex;gap:.5rem;margin-bottom:2rem;
    background:#fff;border-radius:16px;
    padding:.5rem;box-shadow:0 4px 20px rgba(0,0,0,.07);
    max-width:680px;margin-left:auto;margin-right:auto;
}
.tab-btn{
    flex:1;padding:.75rem 1rem;border:none;border-radius:12px;
    font-size:.95rem;font-weight:700;cursor:pointer;
    background:transparent;color:#64748b;transition:all .22s;
}
.tab-btn.active{
    background:linear-gradient(135deg,#1565C0,#0D47A1);
    color:#fff;box-shadow:0 4px 16px rgba(21,101,192,.35);
}
.tab-btn:hover:not(.active){background:#F0F4F8;color:#1565C0}

.tab-content{display:none}
.tab-content.show{display:block;animation:fadeUp .35s ease}

/* ── Price compare ── */
.price-compare-box{
    background:linear-gradient(135deg,#FFFBEB,#FEF3C7);
    border:1.5px solid #FCD34D;border-radius:14px;overflow:hidden;
}
.price-compare-title{
    background:linear-gradient(135deg,#F59E0B,#D97706);
    color:#fff;padding:.65rem 1.1rem;font-weight:700;font-size:.88rem;
    display:flex;align-items:center;
}
.price-list{padding:.75rem 1rem}
.price-row{
    display:flex;align-items:center;justify-content:space-between;
    padding:.45rem .6rem;border-radius:8px;margin-bottom:.3rem;
    cursor:pointer;transition:all .15s;border:1.5px solid transparent;
}
.price-row:hover{background:#fff;border-color:#FCD34D}
.price-row.selected{background:#fff;border-color:#1565C0;box-shadow:0 2px 8px rgba(21,101,192,.15)}
.price-row.lowest{background:#F0FDF4;border-color:#86EFAC}
.price-row.lowest:hover{background:#DCFCE7}
.price-hosp{font-weight:600;font-size:.86rem;color:#1e293b}
.price-amount{font-weight:800;font-size:.95rem}
.price-amount.green{color:#15803d}
.price-amount.blue{color:#1565C0}
.price-tag-low{
    background:#15803d;color:#fff;border-radius:20px;
    padding:.1rem .55rem;font-size:.68rem;font-weight:700;margin-left:.4rem;
}

/* Test book button */
.btn-test-book{
    display:block;background:linear-gradient(135deg,#EA580C,#C2410C);
    color:#fff;border:none;border-radius:50px;
    padding:.75rem 2rem;font-size:1rem;font-weight:700;
    transition:all .25s;box-shadow:0 4px 18px rgba(234,88,12,.38);
    text-align:center;cursor:pointer;
}
.btn-test-book:hover:not(:disabled){
    transform:translateY(-3px);box-shadow:0 10px 28px rgba(234,88,12,.48);
}
.btn-test-book:disabled{opacity:.5;cursor:not-allowed}

/* Test info note */
.test-info-note{
    background:linear-gradient(135deg,#EFF6FF,#DBEAFE);
    border:1px solid #BFDBFE;border-radius:10px;
    padding:.7rem 1rem;font-size:.84rem;color:#1e40af;
    display:flex;align-items:center;
}
</style>

<script>
// ── Tab switch ────────────────────────────────────────────────
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('show'));
    document.getElementById('tab' + tab.charAt(0).toUpperCase() + tab.slice(1)).classList.add('show');
    event.currentTarget.classList.add('active');
}

// ── Doctor info box ───────────────────────────────────────────
function updateDoc(sel) {
    const opt = sel.options[sel.selectedIndex];
    const box = document.getElementById('docBox');
    const hospIdInput = document.getElementById('hospId');
    if (!opt.value) {
        box.style.display='none';
        hospIdInput.value='';
        return;
    }
    const hospId = opt.dataset.hospId || opt.getAttribute('data-hosp-id') || '';
    hospIdInput.value = hospId;
    document.getElementById('dHosp').textContent  = opt.dataset.hosp || opt.getAttribute('data-hosp');
    document.getElementById('dFee').textContent   = '৳' + parseInt(opt.dataset.fee || opt.getAttribute('data-fee') || 0).toLocaleString();
    document.getElementById('dSched').textContent = opt.dataset.sched || opt.getAttribute('data-sched');
    const spec = opt.dataset.spec || opt.getAttribute('data-spec');
    if (spec) {
        document.getElementById('dSpec').textContent = spec;
        document.getElementById('dSpecRow').style.display = 'block';
    } else {
        document.getElementById('dSpecRow').style.display = 'none';
    }
    box.style.display = 'block';
}

// ── Doctor form submit validation ───────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const doctorForm = document.querySelector('#tabDoctor form');
    if (doctorForm) {
        doctorForm.addEventListener('submit', function(e) {
            const hospId = document.getElementById('hospId').value;
            const doctorId = document.getElementById('doctorSel').value;
            if (!doctorId) {
                e.preventDefault();
                alert('অনুগ্রহ করে একজন ডাক্তার সিলেক্ট করুন।');
                return;
            }
            if (!hospId) {
                e.preventDefault();
                alert('ডাক্তার সিলেক্ট করলে হাসপাতাল স্বয়ংক্রিয়ভাবে নির্বাচিত হবে। পুনরায় ডাক্তার সিলেক্ট করুন।');
                document.getElementById('doctorSel').focus();
                return;
            }
        });
    }
});

// ── Test price comparison ────────────────────────────────────
const TEST_PRICES = <?= json_encode($test_prices_all) ?>;

function showTestPrices(testId) {
    const box      = document.getElementById('priceBox');
    const listEl   = document.getElementById('priceList');
    const badge    = document.getElementById('priceBadge');
    const hospSel  = document.getElementById('testHospSel');
    const hospNote = document.getElementById('testHospNote');

    // Reset dropdown
    hospSel.innerHTML = '<option value="">— হাসপাতাল বেছে নিন —</option>';
    if (hospNote) hospNote.style.display = 'none';

    if (!testId) {
        box.style.display = 'none';
        return;
    }

    const prices = TEST_PRICES.filter(p => String(p.test_id) === String(testId));

    if (!prices.length) {
        // hospital_tests এ data নেই — সব হাসপাতাল দেখাও
        box.style.display = 'none';
        ALL_HOSPITALS.forEach(h => {
            const opt = document.createElement('option');
            opt.value = h.id;
            opt.textContent = h.name;
            hospSel.appendChild(opt);
        });
        if (hospNote) hospNote.style.display = 'block';
        return;
    }

    // Sort lowest price first
    prices.sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
    const minPrice = parseFloat(prices[0].price);
    badge.textContent = prices.length + ' টি হাসপাতাল';

    // Build price comparison rows
    listEl.innerHTML = prices.map(p => {
        const isLowest = parseFloat(p.price) === minPrice;
        return `<div class="price-row ${isLowest ? 'lowest' : ''}"
                     onclick="selectHospFromPrice(${p.hospital_id}, this)">
            <div>
                <span class="price-hosp">${p.hosp_name}</span>
                ${isLowest ? '<span class="price-tag-low">সেরা দাম</span>' : ''}
            </div>
            <span class="price-amount ${isLowest ? 'green' : 'blue'}">
                ৳${parseInt(p.price).toLocaleString('bn-BD')}
            </span>
        </div>`;
    }).join('');

    box.style.display = 'block';

    // Populate dropdown with ONLY hospitals that have this test
    prices.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.hospital_id;
        opt.textContent = p.hosp_name + ' — ৳' + parseInt(p.price).toLocaleString();
        hospSel.appendChild(opt);
    });

    // Auto-select cheapest
    if (prices.length === 1) {
        hospSel.value = prices[0].hospital_id;
    }
}

function selectHospFromPrice(hospId, el) {
    document.getElementById('testHospSel').value = hospId;
    document.querySelectorAll('.price-row').forEach(r => r.classList.remove('selected'));
    if (el) el.classList.add('selected');
}

}

window.onload = () => {
    const s = document.getElementById('doctorSel');
    if (s && s.value) updateDoc(s);
    // hospital_id ensure on page load
    const hospInput = document.getElementById('hospId');
    if (s && s.value && (!hospInput.value)) {
        const opt = s.options[s.selectedIndex];
        if (opt) {
            hospInput.value = opt.dataset.hospId || opt.getAttribute('data-hosp-id') || '';
        }
    }
};
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

