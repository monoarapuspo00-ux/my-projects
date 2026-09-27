<?php
$base_url = '/healthcare';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

try {
    $symptoms = $pdo->query("SELECT * FROM symptoms ORDER BY name_bn")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $symptoms = []; }

$results             = null;
$selected_symptoms   = [];
$error_message       = '';
$show_medicine_alert = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['symptoms'])) {
        $error_message = 'অনুগ্রহ করে কমপক্ষে একটি লক্ষণ সিলেক্ট করুন।';
    } else {
        $selected_symptoms   = array_map('intval', $_POST['symptoms']);
        $count               = count($selected_symptoms);
        $show_medicine_alert = ($count >= 3);
        $ph                  = implode(',', array_fill(0, $count, '?'));

        try {
            // Departments
            $s = $pdo->prepare("
                SELECT d.id, d.name, d.name_bn, d.description,
                       COUNT(sd.symptom_id) AS match_count
                FROM departments d
                INNER JOIN symptom_department sd ON d.id = sd.department_id
                WHERE sd.symptom_id IN ($ph)
                GROUP BY d.id, d.name, d.name_bn, d.description
                ORDER BY match_count DESC
            ");
            $s->execute($selected_symptoms);
            $departments = $s->fetchAll(PDO::FETCH_ASSOC);

            // Tests
            $s = $pdo->prepare("
                SELECT DISTINCT mt.id, mt.name, mt.description
                FROM medical_tests mt
                INNER JOIN symptom_tests st ON mt.id = st.test_id
                WHERE st.symptom_id IN ($ph)
                ORDER BY mt.name
            ");
            $s->execute($selected_symptoms);
            $tests = $s->fetchAll(PDO::FETCH_ASSOC);

            // Doctors
            $doctors  = [];
            $dept_ids = array_column($departments, 'id');
            if (!empty($dept_ids)) {
                $dp = implode(',', array_fill(0, count($dept_ids), '?'));
                $s  = $pdo->prepare("
                    SELECT doc.id, doc.name, doc.specialization, doc.schedule, doc.fee,
                           h.name AS hospital_name, dep.name_bn AS dept_name
                    FROM doctors doc
                    INNER JOIN hospitals h    ON doc.hospital_id   = h.id
                    INNER JOIN departments dep ON doc.department_id = dep.id
                    WHERE doc.department_id IN ($dp)
                    ORDER BY dep.name_bn, h.name
                ");
                $s->execute($dept_ids);
                $doctors = $s->fetchAll(PDO::FETCH_ASSOC);
            }

            // Medicine specialist — সব সম্ভাব্য নামে খোঁজো
            $medicine_doctors = [];
            try {
                $ms = $pdo->query("
                    SELECT doc.id, doc.name, doc.specialization, doc.schedule, doc.fee,
                           h.name AS hospital_name, dep.name_bn AS dept_name
                    FROM doctors doc
                    INNER JOIN hospitals h     ON doc.hospital_id   = h.id
                    INNER JOIN departments dep  ON doc.department_id = dep.id
                    WHERE dep.name     LIKE '%Medicine%'
                       OR dep.name     LIKE '%medicine%'
                       OR dep.name     LIKE '%General%'
                       OR dep.name     LIKE '%Internal%'
                       OR dep.name     LIKE '%Physician%'
                       OR dep.name_bn  LIKE '%মেডিসিন%'
                       OR dep.name_bn  LIKE '%জেনারেল%'
                       OR dep.name_bn  LIKE '%চিকিৎসা%'
                       OR doc.specialization LIKE '%Medicine%'
                       OR doc.specialization LIKE '%Physician%'
                    ORDER BY doc.fee ASC
                    LIMIT 4
                ");
                $medicine_doctors = $ms->fetchAll(PDO::FETCH_ASSOC);

                // যদি medicine dept না থাকে — সব dept এর সবচেয়ে কম ফি এর ডাক্তার
                if (empty($medicine_doctors)) {
                    $ms = $pdo->query("
                        SELECT doc.id, doc.name, doc.specialization, doc.schedule, doc.fee,
                               h.name AS hospital_name, dep.name_bn AS dept_name
                        FROM doctors doc
                        INNER JOIN hospitals h    ON doc.hospital_id   = h.id
                        INNER JOIN departments dep ON doc.department_id = dep.id
                        ORDER BY doc.fee ASC
                        LIMIT 4
                    ");
                    $medicine_doctors = $ms->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (Exception $e) {}

            // Test prices
            $test_prices = [];
            if (!empty($tests)) {
                $test_ids = array_column($tests, 'id');
                $tp       = implode(',', array_fill(0, count($test_ids), '?'));
                $s        = $pdo->prepare("
                    SELECT ht.test_id, ht.price, mt.name AS test_name, h.name AS hospital_name
                    FROM hospital_tests ht
                    INNER JOIN medical_tests mt ON ht.test_id     = mt.id
                    INNER JOIN hospitals h      ON ht.hospital_id = h.id
                    WHERE ht.test_id IN ($tp)
                    ORDER BY mt.name, ht.price ASC
                ");
                $s->execute($test_ids);
                $test_prices = $s->fetchAll(PDO::FETCH_ASSOC);
            }

            $results = [
                'departments'      => $departments,
                'tests'            => $tests,
                'doctors'          => $doctors,
                'medicine_doctors' => $medicine_doctors,
                'test_prices'      => $test_prices,
            ];
        } catch (Exception $e) {
            $error_message = 'ডেটাবেস ত্রুটি: ' . $e->getMessage();
        }
    }
}
?>

<style>
/* ══ HERO ══ */
.checker-hero{
    background:linear-gradient(135deg,#0D47A1 0%,#1565C0 50%,#1976D2 100%);
    padding:3rem 1rem 2.5rem;text-align:center;
    border-radius:0 0 3rem 3rem;margin-bottom:2.5rem;
    position:relative;overflow:hidden;
}
.checker-hero::before{
    content:'';position:absolute;top:-40%;right:-15%;
    width:420px;height:420px;background:rgba(255,255,255,.06);
    border-radius:50%;
}
.checker-hero::after{
    content:'';position:absolute;bottom:-55%;left:-8%;
    width:320px;height:320px;background:rgba(255,255,255,.04);
    border-radius:50%;
}
.checker-hero h1{
    font-size:2.3rem;font-weight:800;color:#fff;margin-bottom:.5rem;
    text-shadow:0 2px 12px rgba(0,0,0,.2);position:relative;z-index:1;
}
.checker-hero p{
    color:rgba(255,255,255,.87);font-size:1.05rem;
    margin-bottom:1.5rem;position:relative;z-index:1;
}
.hero-badges{display:flex;justify-content:center;gap:.6rem;flex-wrap:wrap;position:relative;z-index:1}
.hero-badge{
    background:rgba(255,255,255,.15);backdrop-filter:blur(8px);
    border:1px solid rgba(255,255,255,.28);color:#fff;
    border-radius:50px;padding:.35rem 1rem;font-size:.82rem;font-weight:600;
}

/* ══ SEARCH CARD ══ */
.search-card{
    background:#fff;border-radius:22px;
    box-shadow:0 10px 48px rgba(21,101,192,.13);
    padding:2rem;border:1px solid rgba(21,101,192,.07);
}
.search-card-title{
    font-size:1.1rem;font-weight:700;color:#0D47A1;
    margin-bottom:1.25rem;display:flex;align-items:center;gap:.5rem;
}
.count-badge{
    background:linear-gradient(135deg,#1565C0,#0D47A1);color:#fff;
    border-radius:50px;padding:.3rem 1rem;font-size:.82rem;font-weight:700;
    min-width:90px;text-align:center;transition:all .3s;
}
.count-badge.has-items{
    background:linear-gradient(135deg,#15803d,#166534);
    box-shadow:0 4px 14px rgba(21,128,61,.35);
}
.count-badge.warning-items{
    background:linear-gradient(135deg,#D97706,#B45309);
    box-shadow:0 4px 14px rgba(217,119,6,.35);
}

/* Search input */
.sym-search-wrap{position:relative;max-width:420px}
.sym-search-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8}
.sym-search-wrap input{
    padding-left:42px;border-radius:50px;
    border:2px solid #e2e8f0;transition:all .2s;
}
.sym-search-wrap input:focus{
    border-color:#1565C0;box-shadow:0 0 0 3px rgba(21,101,192,.12);outline:none;
}

/* ══ SYMPTOM CHIPS ══ */
.symptom-check{
    background:#F8FAFF;border:2px solid #E2EAF8;
    border-radius:14px;padding:.65rem .95rem;
    cursor:pointer;transition:all .2s;
    display:flex;align-items:flex-start;gap:.55rem;height:100%;
}
.symptom-check:hover{
    border-color:#1565C0;background:#EFF6FF;
    transform:translateY(-3px);
    box-shadow:0 6px 18px rgba(21,101,192,.14);
}
.symptom-check.active{
    border-color:#1565C0;
    background:linear-gradient(135deg,#EFF6FF,#DBEAFE);
    box-shadow:0 6px 20px rgba(21,101,192,.2);
}
.symptom-check input{
    margin-top:3px;accent-color:#1565C0;flex-shrink:0;width:16px;height:16px;
}
.symptom-check label{
    cursor:pointer;margin:0;font-weight:600;font-size:.88rem;
    line-height:1.35;color:#1e293b;
}
.symptom-check label small{color:#94a3b8;font-size:.73rem;display:block;font-weight:400}
.symptom-check.active label{color:#1565C0}

/* Hint bar */
.hint-bar{
    background:linear-gradient(135deg,#F0F9FF,#E0F2FE);
    border:1px solid #BAE6FD;border-radius:12px;
    padding:.65rem 1rem;font-size:.84rem;color:#0369A1;
    display:flex;align-items:center;gap:.5rem;transition:all .3s;
}
.hint-bar.warn{
    background:linear-gradient(135deg,#FFFBEB,#FEF3C7);
    border-color:#FDE68A;color:#92400E;
}
.hint-bar.success{
    background:linear-gradient(135deg,#F0FDF4,#DCFCE7);
    border-color:#86EFAC;color:#15803d;
}

/* Buttons */
.btn-diagnose{
    background:linear-gradient(135deg,#1565C0,#0D47A1);
    color:#fff;border:none;border-radius:50px;
    padding:.7rem 2.2rem;font-size:1rem;font-weight:700;
    transition:all .25s;box-shadow:0 4px 18px rgba(21,101,192,.38);
}
.btn-diagnose:hover:not(:disabled){
    transform:translateY(-3px);
    box-shadow:0 10px 28px rgba(21,101,192,.48);color:#fff;
}
.btn-diagnose:disabled{opacity:.5;cursor:not-allowed}

/* ══ MEDICINE ALERT ══ */
.medicine-alert{
    background:linear-gradient(135deg,#FFFBEB,#FEF3C7);
    border:2px solid #FCD34D;border-radius:22px;
    padding:1.6rem 1.8rem;margin-bottom:2.5rem;
    position:relative;overflow:hidden;
}
.medicine-alert::after{
    content:'💊';position:absolute;right:2rem;top:50%;
    transform:translateY(-50%);font-size:4rem;opacity:.15;pointer-events:none;
}
.med-alert-icon{
    width:56px;height:56px;flex-shrink:0;
    background:linear-gradient(135deg,#F59E0B,#D97706);
    border-radius:16px;display:flex;align-items:center;
    justify-content:center;font-size:1.5rem;color:#fff;
    box-shadow:0 6px 20px rgba(245,158,11,.42);
}
.medicine-alert h5{color:#78350F;font-weight:800;font-size:1.05rem;margin-bottom:.35rem}
.medicine-alert p{color:#92400E;font-size:.88rem;margin-bottom:.75rem;line-height:1.6}

/* Doctor mini cards */
.doc-mini-card{
    background:rgba(255,255,255,.75);border-radius:14px;
    padding:.75rem 1rem;display:flex;align-items:center;gap:.75rem;
    border:1.5px solid rgba(245,158,11,.22);transition:all .22s;
    text-decoration:none;color:inherit;
}
.doc-mini-card:hover{
    background:#fff;transform:translateY(-3px);
    box-shadow:0 8px 24px rgba(0,0,0,.1);color:inherit;
    border-color:rgba(245,158,11,.45);
}
.doc-avatar{
    width:42px;height:42px;background:linear-gradient(135deg,#F59E0B,#D97706);
    border-radius:12px;display:flex;align-items:center;
    justify-content:center;color:#fff;font-size:1rem;flex-shrink:0;
}

/* Advice box */
.advice-box{
    background:linear-gradient(135deg,#ECFDF5,#D1FAE5);
    border:1.5px solid #6EE7B7;border-radius:14px;
    padding:1.1rem 1.25rem;margin-top:.85rem;
    display:flex;gap:.75rem;align-items:flex-start;
}
.advice-box i{color:#059669;flex-shrink:0;font-size:1.1rem;margin-top:2px}
.advice-box p{color:#064E3B;font-size:.87rem;margin:0;line-height:1.7}
.advice-box strong{color:#065F46}

/* ══ SECTION TITLES ══ */
.section-title{
    font-size:1.2rem;font-weight:800;padding-bottom:.5rem;
    border-bottom:3px solid;margin-bottom:1.5rem;
    display:flex;align-items:center;gap:.6rem;
}
.section-title.dept  {border-color:#1565C0;color:#1565C0}
.section-title.doctor{border-color:#15803d;color:#15803d}
.section-title.test  {border-color:#EA580C;color:#EA580C}

/* ══ RESULT CARDS ══ */
.result-card{
    background:#fff;border-radius:16px;
    box-shadow:0 4px 24px rgba(0,0,0,.07);
    padding:1.4rem;height:100%;border-left:5px solid #1565C0;
    transition:transform .22s,box-shadow .22s;
}
.result-card:hover{transform:translateY(-5px);box-shadow:0 12px 40px rgba(0,0,0,.1)}
.result-card.test-card{border-left-color:#EA580C}
.result-card.dept-card{border-left-color:#1565C0}

/* ══ DOCTOR TABLE ══ */
.doc-table thead{
    background:linear-gradient(135deg,#1565C0,#0D47A1);color:#fff;
}
.doc-table thead th{font-weight:600;font-size:.83rem;padding:.8rem 1rem;border:none}
.doc-table tbody td{padding:.75rem 1rem;vertical-align:middle;font-size:.875rem;border-color:#f1f5f9}
.doc-table tbody tr:hover{background:#F0F9FF}
.fee-badge{
    background:linear-gradient(135deg,#EFF6FF,#DBEAFE);
    color:#1565C0;border-radius:20px;padding:.22rem .8rem;
    font-weight:700;font-size:.84rem;white-space:nowrap;
}
.btn-book{
    background:linear-gradient(135deg,#15803d,#166534);
    color:#fff;border:none;border-radius:50px;
    padding:.32rem 1rem;font-size:.78rem;font-weight:600;
    transition:all .2s;text-decoration:none;display:inline-block;
}
.btn-book:hover{color:#fff;transform:translateY(-2px);box-shadow:0 5px 14px rgba(21,128,61,.38)}

/* Price table */
.price-lowest{color:#15803d;font-weight:700}

/* Animations */
.fade-in{animation:fadeUp .45s ease both}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}

@media(max-width:767px){
    .checker-hero h1{font-size:1.6rem}
    .search-card{padding:1.25rem}
    .medicine-alert{padding:1.1rem 1.1rem}
    .medicine-alert::after{display:none}
}
</style>

<!-- HERO -->
<div class="checker-hero">
    <h1><i class="fas fa-stethoscope me-2"></i>রোগ নির্ণয় সহকারী</h1>
    <p>আপনার লক্ষণ সিলেক্ট করুন — কোন বিভাগে যাবেন ও কী পরীক্ষা করাবেন জানুন</p>
    <div class="hero-badges">
        <span class="hero-badge"><i class="fas fa-shield-halved me-1"></i>নির্ভরযোগ্য</span>
        <span class="hero-badge"><i class="fas fa-bolt me-1"></i>তাৎক্ষণিক ফলাফল</span>
        <span class="hero-badge"><i class="fas fa-user-doctor me-1"></i>Medicine বিশেষজ্ঞ পরামর্শ</span>
        <span class="hero-badge"><i class="fas fa-bangladeshi-taka-sign me-1"></i>মূল্য তুলনা</span>
    </div>
</div>

<div class="container pb-5">
    <?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4">
        <i class="fas fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- FORM -->
    <div class="search-card mb-5">
        <form method="POST" id="diagnosisForm">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div class="search-card-title">
                    <i class="fas fa-list-check text-primary"></i>
                    আপনার লক্ষণ সিলেক্ট করুন
                </div>
                <span class="count-badge" id="countBadge">
                    <span id="countNum">0</span> টি সিলেক্ট
                </span>
            </div>

            <div class="sym-search-wrap mb-3">
                <i class="fas fa-search"></i>
                <input type="text" id="symptomSearch" class="form-control"
                       placeholder="লক্ষণ খুঁজুন... যেমন: জ্বর, মাথাব্যথা">
            </div>

            <div class="row g-2 mb-3" id="symptomList">
                <?php foreach ($symptoms as $sym): ?>
                <div class="col-xl-3 col-lg-4 col-md-6 symptom-col"
                     data-bn="<?= mb_strtolower($sym['name_bn']) ?>"
                     data-en="<?= strtolower($sym['name']) ?>">
                    <div class="symptom-check <?= in_array((int)$sym['id'],$selected_symptoms)?'active':'' ?>">
                        <input class="symptom-cb" type="checkbox"
                               name="symptoms[]" value="<?= (int)$sym['id'] ?>"
                               id="sym_<?= (int)$sym['id'] ?>"
                               <?= in_array((int)$sym['id'],$selected_symptoms)?'checked':'' ?>>
                        <label for="sym_<?= (int)$sym['id'] ?>">
                            <?= htmlspecialchars($sym['name_bn']) ?>
                            <small><?= htmlspecialchars($sym['name']) ?></small>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <p id="noResult" class="text-muted d-none">
                <i class="fas fa-search me-1"></i>কোনো লক্ষণ পাওয়া যায়নি।
            </p>

            <div class="hint-bar mb-4" id="hintBar">
                <i class="fas fa-circle-info"></i>
                <span id="hintText">৩ বা তার বেশি লক্ষণ সিলেক্ট করলে Medicine বিশেষজ্ঞের পরামর্শ পাবেন।</span>
            </div>

            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <button type="button" id="clearBtn" class="btn btn-outline-secondary px-4 rounded-pill">
                    <i class="fas fa-rotate-left me-2"></i>পরিষ্কার করুন
                </button>
                <button type="submit" id="submitBtn" class="btn-diagnose" disabled>
                    <i class="fas fa-magnifying-glass-plus me-2"></i>নির্ণয় করুন
                </button>
            </div>
        </form>
    </div>

    <?php if ($results !== null): ?>
        <?php if (empty($results['departments']) && empty($results['doctors']) && empty($results['tests'])): ?>
        <div class="alert alert-info text-center rounded-3 fade-in py-4">
            <i class="fas fa-circle-info fa-2x d-block mb-2"></i>
            <strong>কোনো ফলাফল পাওয়া যায়নি।</strong><br>
            <small class="text-muted">নির্বাচিত লক্ষণের জন্য ডেটাবেসে ম্যাপিং নেই।</small>
        </div>
        <?php else: ?>

        <!-- ══ MEDICINE SPECIALIST ALERT ══ -->
        <?php if ($show_medicine_alert): ?>
        <div class="medicine-alert fade-in">
            <div class="d-flex gap-3 align-items-start">
                <div class="med-alert-icon">
                    <i class="fas fa-user-doctor"></i>
                </div>
                <div class="flex-grow-1">
                    <h5>
                        <i class="fas fa-circle-exclamation me-2"></i>
                        Medicine বিশেষজ্ঞ দেখানো দরকার!
                    </h5>
                    <p>
                        আপনি <strong><?= count($selected_symptoms) ?> টি</strong> লক্ষণ সিলেক্ট করেছেন।
                        একাধিক লক্ষণ একসাথে থাকলে প্রথমে একজন <strong>Medicine বিশেষজ্ঞ</strong> দেখানো উচিত।
                        তিনি সামগ্রিক অবস্থা মূল্যায়ন করে সঠিক বিভাগে রেফার করবেন।
                    </p>

                    <?php if (!empty($results['medicine_doctors'])): ?>
                    <p style="font-size:.82rem;color:#92400E;font-weight:700;margin-bottom:.6rem">
                        <i class="fas fa-stethoscope me-1"></i>
                        Medicine বিশেষজ্ঞ ডাক্তার — এখনই অ্যাপয়েন্টমেন্ট নিন:
                    </p>
                    <div class="row g-2 mb-3">
                        <?php foreach ($results['medicine_doctors'] as $md): ?>
                        <div class="col-md-6">
                            <a href="<?= $base_url ?>/appointment/book.php?doctor_id=<?= $md['id'] ?>"
                               class="doc-mini-card">
                                <div class="doc-avatar">
                                    <i class="fas fa-user-doctor"></i>
                                </div>
                                <div>
                                    <div style="font-weight:700;font-size:.87rem;color:#1e293b">
                                        <?= htmlspecialchars($md['name']) ?>
                                    </div>
                                    <div style="font-size:.76rem;color:#64748b">
                                        <?= htmlspecialchars($md['dept_name']) ?>
                                        — <?= htmlspecialchars($md['hospital_name']) ?>
                                    </div>
                                    <div style="font-size:.8rem;color:#D97706;font-weight:700">
                                        ৳<?= number_format((float)$md['fee']) ?> ভিজিট ফি
                                    </div>
                                </div>
                                <i class="fas fa-arrow-right ms-auto text-muted" style="font-size:.8rem"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="advice-box">
                        <i class="fas fa-lightbulb"></i>
                        <p>
                            <strong>আমাদের পরামর্শ:</strong> প্রথমে একজন
                            <strong>Medicine বিশেষজ্ঞ (General Physician)</strong> দেখান।
                            তিনি আপনার সব লক্ষণ পরীক্ষা করে বলবেন — কোন বিভাগে
                            (হৃদরোগ, নিউরোলজি, গ্যাস্ট্রোএন্টারোলজি ইত্যাদি) যেতে হবে
                            এবং কোন কোন টেস্ট করাতে হবে।
                            <strong>বিশেষজ্ঞের পরামর্শ ছাড়া ওষুধ খাবেন না।</strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- DEPARTMENTS -->
        <div class="mb-5 fade-in">
            <div class="section-title dept">
                <i class="fas fa-building-columns"></i>প্রস্তাবিত বিভাগ
                <span class="badge bg-primary ms-auto"><?= count($results['departments']) ?> টি</span>
            </div>
            <?php if (empty($results['departments'])): ?>
            <div class="alert alert-info rounded-3">কোনো বিভাগ পাওয়া যায়নি।</div>
            <?php else: ?>
            <div class="row g-3">
                <?php foreach ($results['departments'] as $dept): ?>
                <div class="col-md-6">
                    <div class="result-card dept-card">
                        <h5 class="text-primary fw-bold mb-1">
                            <i class="fas fa-hospital-user me-2"></i><?= htmlspecialchars($dept['name_bn']) ?>
                        </h5>
                        <p class="text-muted mb-1" style="font-size:.82rem"><?= htmlspecialchars($dept['name']) ?></p>
                        <p class="mb-2" style="font-size:.86rem;color:#334155"><?= htmlspecialchars($dept['description']) ?></p>
                        <span class="badge" style="background:#EFF6FF;color:#1565C0;font-size:.75rem">
                            <i class="fas fa-check me-1"></i><?= (int)$dept['match_count'] ?> টি লক্ষণ ম্যাচ
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- DOCTORS -->
        <div class="mb-5 fade-in" style="animation-delay:.1s">
            <div class="section-title doctor">
                <i class="fas fa-user-doctor"></i>ডাক্তার তালিকা
                <span class="badge bg-success ms-auto"><?= count($results['doctors']) ?> জন</span>
            </div>
            <?php if (empty($results['doctors'])): ?>
            <div class="alert alert-info rounded-3">এই বিভাগে কোনো ডাক্তার নেই।</div>
            <?php else: ?>
            <div class="table-responsive rounded-3 shadow-sm">
                <table class="table doc-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ডাক্তার</th><th>বিশেষজ্ঞতা</th>
                            <th>বিভাগ</th><th>হাসপাতাল</th>
                            <th>সময়সূচী</th><th>ফি</th><th>বুকিং</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($results['doctors'] as $doc): ?>
                    <tr>
                        <td><div class="fw-bold"><?= htmlspecialchars($doc['name']) ?></div></td>
                        <td><small class="text-muted"><?= htmlspecialchars($doc['specialization']??'') ?></small></td>
                        <td><?= htmlspecialchars($doc['dept_name']) ?></td>
                        <td><small><?= htmlspecialchars($doc['hospital_name']) ?></small></td>
                        <td><small class="text-muted"><?= htmlspecialchars($doc['schedule']) ?></small></td>
                        <td><span class="fee-badge">৳<?= number_format((float)$doc['fee']) ?></span></td>
                        <td>
                            <a href="<?= $base_url ?>/appointment/book.php?doctor_id=<?= (int)$doc['id'] ?>"
                               class="btn-book">
                                <i class="fas fa-calendar-plus me-1"></i>বুক করুন
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- TESTS -->
        <div class="mb-5 fade-in" style="animation-delay:.2s">
            <div class="section-title test">
                <i class="fas fa-flask"></i>প্রস্তাবিত পরীক্ষা ও মূল্য
                <span class="badge bg-warning text-dark ms-auto"><?= count($results['tests']) ?> টি</span>
            </div>
            <?php if (empty($results['tests'])): ?>
            <div class="alert alert-info rounded-3">কোনো পরীক্ষা পাওয়া যায়নি।</div>
            <?php else:
                $grouped = [];
                foreach ($results['test_prices'] as $tp) { $grouped[$tp['test_name']][] = $tp; }
            ?>
            <div class="row g-3">
                <?php foreach ($results['tests'] as $test): ?>
                <div class="col-md-6">
                    <div class="result-card test-card">
                        <h5 class="fw-bold mb-1" style="color:#EA580C">
                            <i class="fas fa-vial me-2"></i><?= htmlspecialchars($test['name']) ?>
                        </h5>
                        <p class="text-muted mb-2" style="font-size:.84rem"><?= htmlspecialchars($test['description']) ?></p>
                        <?php if (!empty($grouped[$test['name']])): ?>
                            <?php $prices=$grouped[$test['name']]; $min=min(array_column($prices,'price')); ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0" style="font-size:.82rem">
                                    <thead class="table-light"><tr><th>হাসপাতাল</th><th>মূল্য</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($prices as $p): ?>
                                    <tr <?= $p['price']==$min?'class="table-success"':'' ?>>
                                        <td><?= htmlspecialchars($p['hospital_name']) ?></td>
                                        <td>
                                            <?php if ($p['price']==$min): ?>
                                            <span class="price-lowest">
                                                ৳<?= number_format((float)$p['price']) ?>
                                                <i class="fas fa-arrow-trend-down ms-1"></i>
                                                <small>সবচেয়ে কম</small>
                                            </span>
                                            <?php else: ?>
                                            ৳<?= number_format((float)$p['price']) ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0"><small>মূল্য তথ্য পাওয়া যায়নি।</small></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
(function(){
    const cbs       = document.querySelectorAll('.symptom-cb');
    const countEl   = document.getElementById('countNum');
    const countBadge= document.getElementById('countBadge');
    const submitBtn = document.getElementById('submitBtn');
    const clearBtn  = document.getElementById('clearBtn');
    const searchBox = document.getElementById('symptomSearch');
    const cols      = document.querySelectorAll('.symptom-col');
    const noResult  = document.getElementById('noResult');
    const hintBar   = document.getElementById('hintBar');
    const hintText  = document.getElementById('hintText');
    const form      = document.getElementById('diagnosisForm');

    function updateCount(){
        const n = document.querySelectorAll('.symptom-cb:checked').length;
        countEl.textContent = n;
        submitBtn.disabled  = (n === 0);

        // Badge color
        if(n === 0)         countBadge.className = 'count-badge';
        else if(n >= 3)     countBadge.className = 'count-badge warning-items';
        else                countBadge.className = 'count-badge has-items';

        // Hint bar
        if(n >= 3){
            hintBar.className = 'hint-bar success mb-4';
            hintText.innerHTML = '<strong>✓ Medicine বিশেষজ্ঞের পরামর্শ পাবেন!</strong> এখন "নির্ণয় করুন" বাটন চাপুন।';
        } else if(n > 0){
            hintBar.className = 'hint-bar warn mb-4';
            hintText.textContent = `আরো ${3-n} টি সিলেক্ট করলে Medicine বিশেষজ্ঞের পরামর্শ পাবেন।`;
        } else {
            hintBar.className = 'hint-bar mb-4';
            hintText.textContent = '৩ বা তার বেশি লক্ষণ সিলেক্ট করলে Medicine বিশেষজ্ঞের পরামর্শ পাবেন।';
        }
    }

    cbs.forEach(cb => cb.addEventListener('change', function(){
        this.closest('.symptom-check').classList.toggle('active', this.checked);
        updateCount();
    }));

    clearBtn.addEventListener('click', function(){
        cbs.forEach(cb => { cb.checked=false; cb.closest('.symptom-check').classList.remove('active'); });
        if(searchBox){ searchBox.value=''; filterSymptoms(''); }
        updateCount();
    });

    function filterSymptoms(q){
        let v=0;
        cols.forEach(col => {
            const m = col.dataset.bn.includes(q) || col.dataset.en.includes(q);
            col.style.display = m ? '' : 'none';
            if(m) v++;
        });
        if(noResult) noResult.classList.toggle('d-none', v>0);
    }

    if(searchBox) searchBox.addEventListener('input', function(){ filterSymptoms(this.value.toLowerCase().trim()); });

    if(form) form.addEventListener('submit', function(){
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>প্রক্রিয়া চলছে...';
    });

    const firstEl = document.querySelector('.medicine-alert, .section-title');
    if(firstEl) setTimeout(() => firstEl.scrollIntoView({behavior:'smooth',block:'start'}), 350);

    updateCount();
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
