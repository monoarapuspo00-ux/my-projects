<?php
$base_url = '/healthcare';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$hospital_count = $pdo->query("SELECT COUNT(*) FROM hospitals")->fetchColumn();
$doctor_count   = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
$dept_count     = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
?>

<section class="hero-section">
    <div class="container">
        <h1><i class="fas fa-heartbeat me-2"></i>স্বাস্থ্যসেবা পোর্টাল</h1>
        <p class="mt-2 mb-4" style="opacity:.85;font-size:1.05rem">আপনার স্বাস্থ্য, আমাদের দায়িত্ব — রোগের লক্ষণ থেকে চিকিৎসা পর্যন্ত সব এক জায়গায়</p>
        <a href="<?= $base_url ?>/symptom/checker.php" class="btn btn-light btn-lg px-4 me-2">
            <i class="fas fa-stethoscope me-2"></i>রোগ নির্ণয় করুন
        </a>
        <a href="<?= $base_url ?>/firstaid/index.php" class="btn btn-outline-light btn-lg px-4">
            <i class="fas fa-kit-medical me-2"></i>প্রাথমিক চিকিৎসা
        </a>
    </div>
</section>

<div class="container mt-2 pb-5">
    <!-- Stats -->
    <div class="row mb-5 text-center g-3">
        <div class="col-md-4">
            <div class="custom-card p-4">
                <h2 class="text-primary fw-bold"><?= $hospital_count ?>+</h2>
                <p class="text-muted mb-0"><i class="fas fa-hospital me-2"></i>হাসপাতাল</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="custom-card p-4">
                <h2 class="text-success fw-bold"><?= $doctor_count ?>+</h2>
                <p class="text-muted mb-0"><i class="fas fa-user-doctor me-2"></i>ডাক্তার</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="custom-card p-4">
                <h2 class="text-warning fw-bold"><?= $dept_count ?>+</h2>
                <p class="text-muted mb-0"><i class="fas fa-building-columns me-2"></i>বিভাগ</p>
            </div>
        </div>
    </div>

    <!-- Features -->
    <h3 class="text-center fw-bold mb-4">আমাদের সেবাসমূহ</h3>
    <div class="row g-4 mb-5">
        <?php
        $features = [
            ['/symptom/checker.php','fas fa-stethoscope','text-primary','রোগ নির্ণয়','লক্ষণ সিলেক্ট করুন — কোন বিভাগে যাবেন ও কী পরীক্ষা করাবেন জানুন।'],
            ['/hospital/nearby.php','fas fa-hospital','text-success','নিকটস্থ হাসপাতাল','আপনার কাছাকাছি হাসপাতাল ও বিভাগ খুঁজুন।'],
            ['/appointment/book.php','fas fa-calendar-check','text-info','অ্যাপয়েন্টমেন্ট','অনলাইনে ডাক্তারের অ্যাপয়েন্টমেন্ট বুক করুন।'],
            ['/tests/search.php','fas fa-flask','text-warning','টেস্ট ও মূল্য','বিভিন্ন হাসপাতালে টেস্টের দাম তুলনা করুন।'],
            ['/transport/book.php','fas fa-ambulance','text-danger','পরিবহন বুকিং','হাসপাতালে যাওয়ার জন্য গাড়ি বুক করুন।'],
            ['/firstaid/index.php','fas fa-kit-medical','text-secondary','২৪/৭ প্রাথমিক চিকিৎসা','জরুরি পরিস্থিতিতে বাসায় কী করবেন জানুন।'],
        ];
        foreach ($features as [$url,$icon,$color,$title,$desc]): ?>
        <div class="col-md-4 col-sm-6">
            <a href="<?= $base_url . $url ?>" class="text-decoration-none">
                <div class="feature-card">
                    <div class="icon <?= $color ?>"><i class="<?= $icon ?>"></i></div>
                    <h5><?= $title ?></h5>
                    <p class="text-muted mb-0" style="font-size:.9rem"><?= $desc ?></p>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Emergency Banner -->
    <div class="alert alert-danger d-flex align-items-center gap-3 rounded-3">
        <i class="fas fa-phone-volume fa-2x flex-shrink-0"></i>
        <div>
            <strong>জরুরি নম্বর:</strong>
            <span class="ms-3">🚑 জাতীয় জরুরি: <strong>999</strong></span>
            <span class="ms-3">🏥 অ্যাম্বুলেন্স: <strong>16430</strong></span>
            <span class="ms-3">🔥 ফায়ার সার্ভিস: <strong>102</strong></span>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
