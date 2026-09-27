<?php
session_start();
$base_url = '/healthcare';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

$msg = $err = '';
$booking_id   = 0;
$show_payment = false;
$current_step = 1; // 1=booking form, 2=payment, 3=success

$hospitals = $pdo->query("SELECT id,name,address,latitude,longitude FROM hospitals ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// ── Step 1: Booking submit ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['step'] ?? '') === 'booking') {
    if (!isset($_SESSION['user_id'])) { header("Location: $base_url/auth/login.php"); exit; }

    $hospital_id = (int)($_POST['hospital_id'] ?? 0);
    $pickup      = trim($_POST['pickup_address'] ?? '');
    $date        = trim($_POST['booking_date'] ?? '');
    $time        = trim($_POST['booking_time'] ?? '');
    $vehicle     = $_POST['vehicle_type'] ?? 'car';
    $user_lat    = trim($_POST['user_lat'] ?? '');
    $user_lng    = trim($_POST['user_lng'] ?? '');
    $user_phone  = trim($_POST['user_phone'] ?? $_SESSION['user_phone'] ?? '');
    $notes       = trim($_POST['notes'] ?? '');

    if (!$hospital_id || !$pickup || !$date || !$time || !$user_phone) {
        $err = 'সব তথ্য পূরণ করুন।';
    } else {
        $fare_map  = ['car' => 300, 'ambulance' => 800, 'microbus' => 500];
        $base_fare = $fare_map[$vehicle] ?? 300;

        $pdo->prepare("INSERT INTO transport_bookings
            (user_id,hospital_id,pickup_address,booking_date,booking_time,vehicle_type,
             status,fare,user_lat,user_lng,user_phone,payment_status,notes)
            VALUES(?,?,?,?,?,?,'pending',?,?,?,?,'unpaid',?)")
            ->execute([$_SESSION['user_id'],$hospital_id,$pickup,$date,$time,$vehicle,
                        $base_fare,$user_lat,$user_lng,$user_phone,$notes]);
        $booking_id   = $pdo->lastInsertId();
        $show_payment = true;
        $current_step = 2;

        // Driver assign
        $driver = $pdo->query("SELECT * FROM drivers WHERE status='available' ORDER BY RAND() LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($driver) {
            $pdo->prepare("UPDATE transport_bookings SET driver_id=? WHERE id=?")->execute([$driver['id'],$booking_id]);
            $pdo->prepare("UPDATE drivers SET status='busy' WHERE id=?")->execute([$driver['id']]);
        }
    }
}

// ── Step 2: Payment submit ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['step'] ?? '') === 'payment') {
    $booking_id     = (int)($_POST['booking_id'] ?? 0);
    $pay_method     = $_POST['payment_method'] ?? 'bkash';
    $transaction_id = trim($_POST['transaction_id'] ?? '');
    $amount         = (float)($_POST['amount'] ?? 0);

    if (!$transaction_id) {
        $err          = 'Transaction ID দিন।';
        $show_payment = true;
        $current_step = 2;
    } else {
        $pdo->prepare("INSERT INTO transport_payments
            (booking_id,user_id,amount,payment_method,transaction_id,status)
            VALUES(?,?,?,?,?,'completed')")
            ->execute([$booking_id,$_SESSION['user_id'],$amount,$pay_method,$transaction_id]);

        $pdo->prepare("UPDATE transport_bookings
            SET payment_status='paid', payment_method=?, transaction_id=?, status='confirmed'
            WHERE id=?")
            ->execute([$pay_method,$transaction_id,$booking_id]);

        $msg          = "পেমেন্ট সফল! বুকিং নিশ্চিত হয়েছে। বুকিং ID: #$booking_id";
        $show_payment = false;
        $current_step = 3;
    }
}

// Get fare for payment step
$booking_fare    = 0;
$booking_vehicle = 'car';
if ($show_payment && $booking_id) {
    $b = $pdo->prepare("SELECT fare,vehicle_type FROM transport_bookings WHERE id=?");
    $b->execute([$booking_id]);
    $bdata           = $b->fetch(PDO::FETCH_ASSOC);
    $booking_fare    = $bdata['fare'] ?? 300;
    $booking_vehicle = $bdata['vehicle_type'] ?? 'car';
}
?>

<style>
/* ══ Transport page — professional Bangladesh transport theme ══ */
:root {
    --red:    #C62828;
    --red-dk: #B71C1C;
    --red-lt: #FFEBEE;
    --blue:   #1565C0;
    --green:  #15803d;
    --gold:   #D97706;
    --dark:   #0f172a;
    --card-r: 16px;
}

/* ── HERO ─────────────────────────────────────────────────────── */
.transport-hero {
    background: linear-gradient(135deg, var(--red-dk) 0%, #7F0000 100%);
    color: #fff;
    padding: 3rem 1rem 4.5rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.transport-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.transport-hero h1 {
    font-size: clamp(1.6rem, 4vw, 2.4rem);
    font-weight: 800;
    margin-bottom: .5rem;
    position: relative;
}
.transport-hero p {
    opacity: .88;
    font-size: .95rem;
    margin-bottom: 1.8rem;
    position: relative;
}
.hero-cta-row {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    position: relative;
}

/* ── HERO CTA BUTTONS ─────────────────────────────────────────── */
.btn-hero-book {
    background: #fff;
    color: var(--red);
    border: none;
    border-radius: 50px;
    padding: .9rem 2.2rem;
    font-weight: 800;
    font-size: 1rem;
    cursor: pointer;
    box-shadow: 0 8px 24px rgba(0,0,0,.25);
    transition: all .2s;
    display: inline-flex;
    align-items: center;
    gap: .6rem;
    text-decoration: none;
}
.btn-hero-book:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(0,0,0,.35);
    color: var(--red-dk);
}
.btn-hero-ambulance {
    background: transparent;
    color: #fff;
    border: 2px solid rgba(255,255,255,.6);
    border-radius: 50px;
    padding: .85rem 1.8rem;
    font-weight: 700;
    font-size: .95rem;
    cursor: pointer;
    transition: all .2s;
    display: inline-flex;
    align-items: center;
    gap: .6rem;
    text-decoration: none;
}
.btn-hero-ambulance:hover {
    background: rgba(255,255,255,.15);
    border-color: #fff;
    color: #fff;
}

/* ── VEHICLE CARDS ───────────────────────────────────────────── */
.vehicle-cards-row { display: flex; gap: .75rem; }
.vehicle-card {
    border: 2.5px solid #e2e8f0;
    border-radius: 14px;
    padding: 1rem .75rem;
    cursor: pointer;
    transition: all .25s;
    background: #fff;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .4rem;
    font-weight: 700;
    text-align: center;
    flex: 1;
    font-size: .88rem;
}
.vehicle-card:hover,
.vehicle-card.selected {
    border-color: var(--red);
    background: var(--red-lt);
    color: var(--red);
    box-shadow: 0 4px 16px rgba(198,40,40,.15);
}
.vehicle-card i { font-size: 1.7rem; }
.vehicle-card .fare { font-size: .72rem; color: #94a3b8; font-weight: 500; }
.vehicle-card.selected .fare { color: var(--red); opacity: .8; }

/* ── STEP INDICATOR ──────────────────────────────────────────── */
.step-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    margin: -1.5rem auto 2rem;
    max-width: 480px;
    position: relative;
    z-index: 10;
    padding: 0 1rem;
}
.step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .35rem;
    flex: 1;
}
.step-circle {
    width: 44px; height: 44px;
    border-radius: 50%;
    background: #e2e8f0;
    color: #94a3b8;
    font-weight: 800;
    font-size: .95rem;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 2px 8px rgba(0,0,0,.1);
    transition: all .3s;
}
.step-circle.active {
    background: var(--blue);
    color: #fff;
    box-shadow: 0 4px 16px rgba(21,101,192,.4);
}
.step-circle.done {
    background: var(--green);
    color: #fff;
}
.step-label {
    font-size: .7rem;
    font-weight: 600;
    color: #94a3b8;
    white-space: nowrap;
}
.step-label.active { color: var(--blue); }
.step-label.done   { color: var(--green); }
.step-line {
    height: 3px;
    flex: 1;
    background: #e2e8f0;
    border-radius: 3px;
    margin-bottom: 1.4rem;
    transition: background .3s;
}
.step-line.done { background: var(--green); }

/* ── MAP ─────────────────────────────────────────────────────── */
#map {
    height: 280px;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #e2e8f0;
}

/* ── CARDS ───────────────────────────────────────────────────── */
.transport-card {
    background: #fff;
    border-radius: var(--card-r);
    box-shadow: 0 4px 20px rgba(0,0,0,.07);
    padding: 1.5rem;
    border: 1px solid #f1f5f9;
}

/* ── PAYMENT SECTION ─────────────────────────────────────────── */
.payment-section {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 8px 40px rgba(0,0,0,.1);
    padding: 2rem;
    border: 1px solid #f1f5f9;
}
.payment-section h4 {
    color: var(--blue);
    font-weight: 800;
    margin-bottom: 1.5rem;
    font-size: 1.2rem;
}

/* ── PAYMENT METHOD BTNS ──────────────────────────────────────── */
.pay-method-btn {
    border: 2.5px solid #e2e8f0;
    border-radius: 12px;
    padding: .85rem 1.1rem;
    cursor: pointer;
    transition: all .2s;
    background: #fff;
    display: flex;
    align-items: center;
    gap: .85rem;
    font-weight: 700;
    font-size: .9rem;
    width: 100%;
    margin-bottom: .5rem;
}
.pay-method-btn:hover   { border-color: var(--blue); background: #EFF6FF; }
.pay-method-btn.selected{ border-color: var(--blue); background: #EFF6FF;
                           box-shadow: 0 4px 16px rgba(21,101,192,.12); }
.pay-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 800; font-size: .75rem; flex-shrink: 0;
}

/* ── PAYMENT NUMBER BOX ──────────────────────────────────────── */
.pay-number-box {
    background: #FFF0F6; border: 2px dashed #E2136E;
    border-radius: 12px; padding: 1rem 1.25rem; margin: 1rem 0;
}
.pay-number-box.nagad  { background: #FFF5F0; border-color: #F05829; }
.pay-number-box.rocket { background: #F8F0FF; border-color: #8E44AD; }
.pay-number-box.cash   { background: #F0FDF4; border-color: var(--green); }
.big-number { font-size: 1.5rem; font-weight: 900; letter-spacing: 2px; }
.copy-btn {
    background: none; border: 1px solid #e2e8f0; border-radius: 6px;
    cursor: pointer; color: #94a3b8; font-size: .78rem; padding: .2rem .6rem;
    margin-left: .75rem; transition: all .15s;
}
.copy-btn:hover { color: var(--blue); border-color: var(--blue); }

/* ── FARE SUMMARY ────────────────────────────────────────────── */
.fare-summary {
    background: #F8FAFC;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.25rem;
    border: 1px solid #e2e8f0;
}
.fare-row {
    display: flex; justify-content: space-between;
    font-size: .9rem; padding: .35rem 0;
    border-bottom: 1px solid #f1f5f9;
}
.fare-row:last-child {
    border-bottom: none;
    font-weight: 800;
    font-size: 1rem;
    color: var(--blue);
    padding-top: .55rem;
}

/* ── SUCCESS ────────────────────────────────────────────────── */
.success-box {
    text-align: center;
    padding: 3rem 2rem;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 8px 40px rgba(0,0,0,.1);
}
.success-icon {
    width: 90px; height: 90px;
    background: linear-gradient(135deg, #4ade80, #15803d);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1.25rem;
    box-shadow: 0 8px 24px rgba(21,128,61,.3);
}

/* ── QUICK INFO CHIPS ────────────────────────────────────────── */
.info-chips { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1.25rem; }
.chip {
    background: var(--red-lt); color: var(--red);
    border-radius: 20px; padding: .3rem .9rem;
    font-size: .78rem; font-weight: 700;
    display: flex; align-items: center; gap: .4rem;
}

@media(max-width:575px) {
    .vehicle-card { padding: .7rem .4rem; font-size: .8rem; }
    .vehicle-card i { font-size: 1.4rem; }
    .hero-cta-row { flex-direction: column; align-items: center; }
    .btn-hero-book, .btn-hero-ambulance { width: 100%; max-width: 280px; justify-content: center; }
}
</style>

<!-- ══ HERO ══ -->
<div class="transport-hero">
    <div class="container">
        <h1><i class="fas fa-ambulance me-2"></i>পরিবহন বুকিং</h1>
        <p>হাসপাতালে যাওয়ার জন্য গাড়ি বুক করুন — bKash · Nagad · Rocket দিয়ে পেমেন্ট করুন</p>
        <div class="hero-cta-row">
            <a href="#booking-form" class="btn-hero-book">
                <i class="fas fa-car"></i>গাড়ি বুক করুন
            </a>
            <a href="#booking-form" onclick="document.querySelector('[value=ambulance]').closest('.vehicle-card').click()" class="btn-hero-ambulance">
                <i class="fas fa-ambulance"></i>অ্যাম্বুলেন্স দরকার?
            </a>
        </div>
    </div>
</div>

<!-- ══ STEP INDICATOR ══ -->
<div class="container">
    <div class="step-bar">
        <div class="step-item">
            <div class="step-circle <?= $current_step >= 1 ? ($current_step > 1 ? 'done' : 'active') : '' ?>">
                <?= $current_step > 1 ? '<i class="fas fa-check"></i>' : '১' ?>
            </div>
            <div class="step-label <?= $current_step >= 1 ? ($current_step > 1 ? 'done' : 'active') : '' ?>">বুকিং তথ্য</div>
        </div>
        <div class="step-line <?= $current_step > 1 ? 'done' : '' ?>"></div>
        <div class="step-item">
            <div class="step-circle <?= $current_step >= 2 ? ($current_step > 2 ? 'done' : 'active') : '' ?>">
                <?= $current_step > 2 ? '<i class="fas fa-check"></i>' : '২' ?>
            </div>
            <div class="step-label <?= $current_step >= 2 ? ($current_step > 2 ? 'done' : 'active') : '' ?>">পেমেন্ট</div>
        </div>
        <div class="step-line <?= $current_step > 2 ? 'done' : '' ?>"></div>
        <div class="step-item">
            <div class="step-circle <?= $current_step >= 3 ? 'done' : '' ?>">
                <?= $current_step >= 3 ? '<i class="fas fa-check"></i>' : '৩' ?>
            </div>
            <div class="step-label <?= $current_step >= 3 ? 'done' : '' ?>">সম্পন্ন</div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($err): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-3">
        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($err) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- ══ STEP 3: SUCCESS ══ -->
    <?php if ($current_step === 3): ?>
    <div class="row justify-content-center mb-5">
        <div class="col-lg-6">
            <div class="success-box">
                <div class="success-icon">
                    <i class="fas fa-check fa-2x text-white"></i>
                </div>
                <h3 class="fw-bold text-success mb-2">বুকিং নিশ্চিত!</h3>
                <p class="text-muted mb-3"><?= htmlspecialchars($msg) ?></p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="<?= $base_url ?>/transport/book.php" class="btn btn-danger rounded-pill px-4">
                        <i class="fas fa-plus me-2"></i>নতুন বুকিং
                    </a>
                    <a href="<?= $base_url ?>/index.php" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="fas fa-home me-2"></i>হোমে যান
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ STEP 2: PAYMENT ══ -->
    <?php elseif ($show_payment): ?>
    <div class="row justify-content-center mb-5" id="booking-form">
        <div class="col-lg-7">
            <div class="payment-section">
                <h4><i class="fas fa-credit-card me-2"></i>পেমেন্ট করুন</h4>

                <!-- Fare Summary -->
                <div class="fare-summary">
                    <div class="fare-row">
                        <span>বেস ভাড়া
                            <span class="badge bg-secondary ms-1" style="font-size:.68rem">
                                <?= $booking_vehicle === 'car' ? 'গাড়ি' : ($booking_vehicle === 'ambulance' ? 'অ্যাম্বুলেন্স' : 'মাইক্রোবাস') ?>
                            </span>
                        </span>
                        <span>৳<?= number_format($booking_fare) ?></span>
                    </div>
                    <div class="fare-row">
                        <span>সার্ভিস চার্জ</span>
                        <span class="text-success fw-semibold">বিনামূল্যে</span>
                    </div>
                    <div class="fare-row">
                        <span><strong>মোট পরিশোধযোগ্য</strong></span>
                        <span><strong>৳<?= number_format($booking_fare) ?></strong></span>
                    </div>
                </div>

                <form method="POST" id="payForm">
                    <input type="hidden" name="step" value="payment">
                    <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
                    <input type="hidden" name="amount" value="<?= $booking_fare ?>">
                    <input type="hidden" name="payment_method" id="payMethodInput" value="bkash">

                    <label class="form-label fw-bold mb-2">পেমেন্ট পদ্ধতি বেছে নিন</label>

                    <!-- bKash -->
                    <div class="pay-method-btn selected" id="btn_bkash" onclick="selectPay('bkash')">
                        <div class="pay-icon" style="background:#E2136E">bK</div>
                        <div>
                            <div style="color:#E2136E" class="fw-bold">bKash</div>
                            <small class="text-muted">মোবাইল ব্যাংকিং</small>
                        </div>
                        <i class="fas fa-check-circle ms-auto text-primary d-none check-icon"></i>
                    </div>

                    <!-- Nagad -->
                    <div class="pay-method-btn" id="btn_nagad" onclick="selectPay('nagad')">
                        <div class="pay-icon" style="background:#F05829">Ng</div>
                        <div>
                            <div style="color:#F05829" class="fw-bold">Nagad</div>
                            <small class="text-muted">ডাক-বিভাগের মোবাইল ব্যাংকিং</small>
                        </div>
                        <i class="fas fa-check-circle ms-auto text-primary d-none check-icon"></i>
                    </div>

                    <!-- Rocket -->
                    <div class="pay-method-btn" id="btn_rocket" onclick="selectPay('rocket')">
                        <div class="pay-icon" style="background:#8E44AD">Rk</div>
                        <div>
                            <div style="color:#8E44AD" class="fw-bold">Rocket</div>
                            <small class="text-muted">ডাচ-বাংলা ব্যাংক মোবাইল</small>
                        </div>
                        <i class="fas fa-check-circle ms-auto text-primary d-none check-icon"></i>
                    </div>

                    <!-- Cash -->
                    <div class="pay-method-btn" id="btn_cash" onclick="selectPay('cash')">
                        <div class="pay-icon" style="background:#15803d"><i class="fas fa-money-bill"></i></div>
                        <div>
                            <div style="color:#15803d" class="fw-bold">ক্যাশ</div>
                            <small class="text-muted">চালক আসলে পরিশোধ করুন</small>
                        </div>
                        <i class="fas fa-check-circle ms-auto text-primary d-none check-icon"></i>
                    </div>

                    <!-- Payment number box -->
                    <div id="payBox" class="pay-number-box mt-3">
                        <div style="font-size:.78rem;color:#94a3b8;margin-bottom:.5rem">এই নম্বরে <strong>Send Money</strong> করুন:</div>
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <span class="big-number" id="payNumber" style="color:#E2136E">01712-345678</span>
                            <button type="button" class="copy-btn" onclick="copyNum()">
                                <i class="fas fa-copy me-1"></i>কপি
                            </button>
                        </div>
                        <div style="font-size:.8rem;color:#475569;margin-top:.6rem;line-height:1.5" id="payInstr">
                            bKash → Send Money → নম্বর দিন → টাকা ৳<?= $booking_fare ?> → Reference: <strong>HEALTH<?= $booking_id ?></strong>
                        </div>
                    </div>

                    <!-- Cash box -->
                    <div id="cashBox" style="display:none" class="pay-number-box cash mt-3">
                        <i class="fas fa-handshake me-2 text-success"></i>
                        <strong>ক্যাশ পেমেন্ট:</strong> চালক আপনার কাছে পৌঁছালে সরাসরি
                        <strong>৳<?= number_format($booking_fare) ?></strong> পরিশোধ করুন।
                        <div class="mt-2" style="font-size:.78rem;color:#475569">নিচে <code>CASH</code> লিখে কনফার্ম করুন।</div>
                    </div>

                    <!-- Transaction ID -->
                    <div class="mb-3 mt-3">
                        <label class="form-label fw-bold">
                            Transaction ID <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="transaction_id" id="txnInput"
                               class="form-control form-control-lg"
                               placeholder="যেমন: 8AB12C3D4E" required>
                        <small class="text-muted" id="txnHelp">
                            bKash পেমেন্টের পর SMS এ প্রাপ্ত Transaction ID লিখুন।
                        </small>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100 rounded-pill">
                        <i class="fas fa-check-circle me-2"></i>
                        পেমেন্ট নিশ্চিত করুন — ৳<?= number_format($booking_fare) ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ══ STEP 1: BOOKING FORM ══ -->
    <?php else: ?>
    <div class="row g-4 mb-5" id="booking-form">

        <!-- Map column -->
        <div class="col-lg-6">
            <div class="transport-card h-100">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-map-marker-alt text-danger me-2"></i>আপনার অবস্থান
                </h5>
                <div id="map"></div>
                <div class="mt-2 p-2 bg-light rounded" id="nearestInfo" style="display:none">
                    <i class="fas fa-hospital text-danger me-1"></i>নিকটতম:
                    <strong id="nearestName"></strong>
                    (<span id="nearestDist"></span> কি.মি.)
                </div>
                <!-- Info chips -->
                <div class="info-chips mt-3">
                    <span class="chip"><i class="fas fa-car"></i>গাড়ি — ৳৩০০+</span>
                    <span class="chip"><i class="fas fa-ambulance"></i>অ্যাম্বুলেন্স — ৳৮০০+</span>
                    <span class="chip"><i class="fas fa-van-shuttle"></i>মাইক্রো — ৳৫০০+</span>
                </div>
            </div>
        </div>

        <!-- Booking form column -->
        <div class="col-lg-6">
            <div class="transport-card">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-calendar-plus text-danger me-2"></i>বুকিং তথ্য দিন
                </h5>
                <form method="POST" id="bookForm">
                    <input type="hidden" name="step" value="booking">
                    <input type="hidden" name="user_lat" id="userLat">
                    <input type="hidden" name="user_lng" id="userLng">

                    <!-- Vehicle select -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">যানবাহন বেছে নিন</label>
                        <div class="vehicle-cards-row">
                            <?php
                            $vehicles = [
                                'car'       => ['fas fa-car',        'গাড়ি',         '৳৩০০+'],
                                'ambulance' => ['fas fa-ambulance',  'অ্যাম্বুলেন্স','৳৮০০+'],
                                'microbus'  => ['fas fa-van-shuttle','মাইক্রোবাস',   '৳৫০০+'],
                            ];
                            foreach ($vehicles as $val => [$ico, $lbl, $fare]):
                            ?>
                            <div class="vehicle-card <?= $val==='car'?'selected':'' ?>"
                                 onclick="selVehicle('<?= $val ?>', this)">
                                <i class="<?= $ico ?>"></i>
                                <span><?= $lbl ?></span>
                                <span class="fare"><?= $fare ?></span>
                                <input type="radio" name="vehicle_type" value="<?= $val ?>"
                                       id="v_<?= $val ?>" <?= $val==='car'?'checked':'' ?> style="display:none">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Hospital -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">গন্তব্য হাসপাতাল <span class="text-danger">*</span></label>
                        <select name="hospital_id" id="hospSel" class="form-select" required>
                            <option value="">— হাসপাতাল বেছে নিন —</option>
                            <?php foreach ($hospitals as $h): ?>
                            <option value="<?= $h['id'] ?>"
                                    data-lat="<?= $h['latitude'] ?>"
                                    data-lng="<?= $h['longitude'] ?>">
                                <?= htmlspecialchars($h['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Pickup address -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">পিকআপ ঠিকানা <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <textarea name="pickup_address" id="pickupAddr" class="form-control" rows="2"
                                      placeholder="আপনার বর্তমান ঠিকানা লিখুন বা GPS থেকে নিন" required></textarea>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger mt-1 rounded-pill"
                                onclick="getLocation()">
                            <i class="fas fa-crosshairs me-1"></i>বর্তমান অবস্থান নিন
                        </button>
                    </div>

                    <!-- Date & Time -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">তারিখ <span class="text-danger">*</span></label>
                            <input type="date" name="booking_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">সময় <span class="text-danger">*</span></label>
                            <input type="time" name="booking_time" class="form-control" required>
                        </div>
                    </div>

                    <!-- Phone -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">ফোন নম্বর <span class="text-danger">*</span></label>
                        <input type="tel" name="user_phone" class="form-control"
                               placeholder="01XXXXXXXXX" required
                               value="<?= htmlspecialchars($_SESSION['user_phone'] ?? '') ?>">
                    </div>

                    <!-- Notes (optional) -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">বিশেষ নির্দেশনা <small class="text-muted fw-normal">(ঐচ্ছিক)</small></label>
                        <input type="text" name="notes" class="form-control"
                               placeholder="যেমন: হুইলচেয়ার দরকার, রাত্রে আসবেন...">
                    </div>

                    <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="alert alert-warning py-2 rounded-pill text-center mb-3">
                        <i class="fas fa-info-circle me-2"></i>বুকিং করতে
                        <a href="<?= $base_url ?>/auth/login.php" class="fw-bold">লগইন করুন</a>।
                    </div>
                    <?php endif; ?>

                    <!-- ── MAIN BOOKING BUTTON ── -->
                    <button type="submit" class="btn btn-danger btn-lg w-100 rounded-pill"
                            <?= !isset($_SESSION['user_id']) ? 'disabled' : '' ?>>
                        <i class="fas fa-arrow-right me-2"></i>বুকিং করুন ও পেমেন্টে যান
                    </button>

                    <?php if (isset($_SESSION['user_id'])): ?>
                    <p class="text-center text-muted mt-2" style="font-size:.78rem">
                        <i class="fas fa-lock me-1"></i>নিরাপদ পেমেন্ট — bKash · Nagad · Rocket · Cash
                    </p>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ── MAP & JS ── -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const hospitals = <?= json_encode($hospitals) ?>;
let map, userMarker;

// Init map
map = L.map('map').setView([23.8103, 90.4125], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    {attribution:'© OpenStreetMap contributors'}).addTo(map);
hospitals.forEach(h => {
    if (h.latitude && h.longitude)
        L.marker([h.latitude, h.longitude]).addTo(map)
         .bindPopup('<strong>' + h.name + '</strong>');
});

// GPS location
function getLocation() {
    if (!navigator.geolocation) { alert('GPS নেই।'); return; }
    navigator.geolocation.getCurrentPosition(pos => {
        const lat = pos.coords.latitude, lng = pos.coords.longitude;
        document.getElementById('userLat').value = lat;
        document.getElementById('userLng').value = lng;
        if (userMarker) map.removeLayer(userMarker);
        userMarker = L.marker([lat, lng], {
            icon: L.icon({iconUrl:'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                iconSize:[25,41],iconAnchor:[12,41]})
        }).addTo(map).bindPopup('আপনার অবস্থান').openPopup();
        map.setView([lat, lng], 14);
        fetch('https://nominatim.openstreetmap.org/reverse?lat='+lat+'&lon='+lng+'&format=json')
            .then(r=>r.json())
            .then(d=>{ document.getElementById('pickupAddr').value = d.display_name||lat+','+lng; })
            .catch(()=>{ document.getElementById('pickupAddr').value = lat+','+lng; });
        // Find nearest hospital
        let nearest = null, minD = Infinity;
        hospitals.forEach(h => {
            if (!h.latitude || !h.longitude) return;
            const d = getDist(lat, lng, h.latitude, h.longitude);
            if (d < minD) { minD = d; nearest = h; }
        });
        if (nearest) {
            document.getElementById('nearestInfo').style.display = 'block';
            document.getElementById('nearestName').textContent = nearest.name;
            document.getElementById('nearestDist').textContent = minD.toFixed(1);
            document.getElementById('hospSel').value = nearest.id;
        }
    }, () => alert('অবস্থান পাওয়া যায়নি — GPS চালু আছে কিনা দেখুন।'));
}

// Haversine distance
function getDist(a, b, c, d) {
    const R=6371, dL=(c-a)*Math.PI/180, dN=(d-b)*Math.PI/180;
    const x = Math.sin(dL/2)**2 + Math.cos(a*Math.PI/180)*Math.cos(c*Math.PI/180)*Math.sin(dN/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(x), Math.sqrt(1-x));
}

// Vehicle select
function selVehicle(v, el) {
    document.querySelectorAll('.vehicle-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('v_'+v).checked = true;
}

// ── Payment methods ──────────────────────────────────────────────
const payData = {
    bkash:  { num:'01712-345678', color:'#E2136E', cls:'',       instr:'bKash → Send Money → Reference: HEALTH<?= $booking_id ?>' },
    nagad:  { num:'01712-345679', color:'#F05829', cls:'nagad',  instr:'Nagad → Send Money → Reference: HEALTH<?= $booking_id ?>' },
    rocket: { num:'01712-345680', color:'#8E44AD', cls:'rocket', instr:'Rocket → Send Money → Reference: HEALTH<?= $booking_id ?>' },
};

function selectPay(method) {
    document.querySelectorAll('.pay-method-btn').forEach(b => {
        b.classList.remove('selected');
        b.querySelector('.check-icon')?.classList.add('d-none');
    });
    const btn = document.getElementById('btn_'+method);
    btn.classList.add('selected');
    btn.querySelector('.check-icon')?.classList.remove('d-none');
    document.getElementById('payMethodInput').value = method;

    const payBox  = document.getElementById('payBox');
    const cashBox = document.getElementById('cashBox');

    if (method === 'cash') {
        payBox.style.display  = 'none';
        cashBox.style.display = 'block';
        document.getElementById('txnInput').placeholder = 'CASH লিখুন';
        document.getElementById('txnHelp').textContent  = 'ক্যাশ পেমেন্টের জন্য CASH লিখুন।';
    } else {
        payBox.style.display  = 'block';
        cashBox.style.display = 'none';
        const d = payData[method];
        payBox.className = 'pay-number-box mt-3 ' + d.cls;
        const numEl = document.getElementById('payNumber');
        numEl.style.color = d.color;
        numEl.textContent = d.num;
        document.getElementById('payInstr').innerHTML = d.instr;
        document.getElementById('txnInput').placeholder = 'Transaction ID লিখুন...';
        document.getElementById('txnHelp').textContent  = method + ' SMS এ প্রাপ্ত Transaction ID লিখুন।';
    }
}

function copyNum() {
    const num = document.getElementById('payNumber').textContent.replace(/-/g,'');
    navigator.clipboard.writeText(num).then(() => {
        const btn = event.currentTarget;
        btn.innerHTML = '<i class="fas fa-check text-success me-1"></i>কপি হয়েছে';
        setTimeout(() => { btn.innerHTML = '<i class="fas fa-copy me-1"></i>কপি'; }, 2000);
    });
}

// Auto-init GPS on page load
window.addEventListener('load', () => {
    if (document.getElementById('map') && navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            pos => { /* auto-fill on allowed */ },
            () => { /* silently fail */ },
            {timeout: 3000}
        );
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
