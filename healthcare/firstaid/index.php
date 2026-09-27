<?php
$base_url = '/healthcare';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
?>
<style>
.firstaid-hero{background:linear-gradient(135deg,#B71C1C,#E53935);color:#fff;padding:2.5rem 1rem 2rem;text-align:center;border-radius:0 0 2rem 2rem;margin-bottom:2rem}
.category-btn{border:2px solid #e0e0e0;border-radius:12px;padding:.7rem 1rem;cursor:pointer;transition:all .2s;background:#fff;display:flex;align-items:center;gap:.75rem;width:100%;text-align:left;font-weight:500;font-size:.9rem}
.category-btn:hover,.category-btn.active{border-color:#C62828;background:#FFEBEE;color:#C62828}
.category-btn i{font-size:1.1rem;width:20px;text-align:center}
.aid-card{background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.07);padding:1.5rem;margin-bottom:1rem;border-left:5px solid #C62828;display:none}
.aid-card.show{display:block;animation:fadeIn .3s ease}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
.step-item{display:flex;gap:.75rem;align-items:flex-start;padding:.55rem 0;border-bottom:1px solid #f5f5f5}
.step-item:last-child{border-bottom:none}
.step-num{background:#C62828;color:#fff;border-radius:50%;width:26px;height:26px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:700}
.badge-emergency{background:#FFEBEE;color:#C62828;border-radius:20px;padding:.3rem 1rem;font-size:.78rem;font-weight:700;display:inline-block;margin-bottom:.75rem}
.badge-home{background:#E8F5E9;color:#2E7D32;border-radius:20px;padding:.3rem 1rem;font-size:.78rem;font-weight:700;display:inline-block;margin-bottom:.75rem}
</style>

<div class="firstaid-hero">
    <h2><i class="fas fa-kit-medical me-2"></i>২৪/৭ প্রাথমিক চিকিৎসা গাইড</h2>
    <p>জরুরি পরিস্থিতিতে বাসায় কী প্রাথমিক চিকিৎসা দেবেন তা জানুন</p>
</div>

<div class="container pb-5">
    <div class="alert alert-danger d-flex align-items-center gap-3 mb-4 rounded-3">
        <i class="fas fa-phone-volume fa-2x flex-shrink-0"></i>
        <div><strong>জরুরি নম্বর:</strong> <span class="ms-3">🚑 জাতীয় জরুরি: <strong>999</strong></span> <span class="ms-3">🏥 অ্যাম্বুলেন্স: <strong>16430</strong></span> <span class="ms-3">🔥 ফায়ার: <strong>102</strong></span></div>
    </div>

    <div class="input-group mb-4">
        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
        <input type="text" class="form-control" id="aidSearch" placeholder="লক্ষণ খুঁজুন... যেমন: হার্ট অ্যাটাক, পোড়া, সাপে কামড়">
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-list me-2 text-danger"></i>ক্যাটাগরি বেছে নিন</h5>
            <div class="d-flex flex-column gap-2" id="catList">
                <?php
                $cats = [
                    ['heart',   'fas fa-heart',          '#C62828', 'হার্ট অ্যাটাক'],
                    ['stroke',  'fas fa-brain',           '#6A1B9A', 'স্ট্রোক'],
                    ['choke',   'fas fa-lungs',           '#1565C0', 'গলায় আটকানো'],
                    ['burn',    'fas fa-fire',            '#E65100', 'পোড়া'],
                    ['cut',     'fas fa-droplet',         '#AD1457', 'কাটা ও রক্তপাত'],
                    ['fracture','fas fa-bone',            '#4527A0', 'হাড় ভাঙা'],
                    ['poison',  'fas fa-skull-crossbones','#2E7D32', 'বিষক্রিয়া'],
                    ['fever',   'fas fa-thermometer',     '#F57F17', 'তীব্র জ্বর'],
                    ['diabetic','fas fa-syringe',         '#00695C', 'ডায়াবেটিক শক'],
                    ['drown',   'fas fa-water',           '#01579B', 'পানিতে ডোবা'],
                    ['snake',   'fas fa-worm',            '#33691E', 'সাপে কামড়'],
                    ['shock',   'fas fa-bolt',            '#F9A825', 'বৈদ্যুতিক শক'],
                    ['breath',  'fas fa-wind',            '#0277BD', 'শ্বাসকষ্ট'],
                    ['faint',   'fas fa-person-falling',  '#558B2F', 'অজ্ঞান হওয়া'],
                    ['eye',     'fas fa-eye',             '#00838F', 'চোখে কিছু পড়া'],
                ];
                foreach ($cats as [$id,$icon,$color,$label]): ?>
                <button class="category-btn" onclick="showAid('<?= $id ?>',this)" data-label="<?= $label ?>">
                    <i class="<?= $icon ?>" style="color:<?= $color ?>"></i><?= $label ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-lg-8" id="aidContent">
            <div class="text-center text-muted py-5"><i class="fas fa-hand-pointer fa-3x d-block mb-3" style="color:#ddd"></i>বাম পাশ থেকে একটি ক্যাটাগরি সিলেক্ট করুন</div>

            <?php
            $aids = [
                'heart' => ['emergency', 'হার্ট অ্যাটাক', 'fas fa-heart', '#C62828',
                    'বুকে তীব্র ব্যথা, বাম হাতে ব্যথা, ঘাম, শ্বাসকষ্ট, বমিভাব',
                    ['রোগীকে শুইয়ে দিন — মাথা ও কাঁধ সামান্য উঁচু রাখুন','আঁটসাঁট পোশাক ঢিলা করুন','রোগী সচেতন থাকলে ১টি Aspirin (৩২৫mg) চুষে খেতে দিন (অ্যালার্জি না থাকলে)','রোগীকে একা রাখবেন না — পাশে থাকুন','শ্বাস বন্ধ হলে CPR: ৩০ বার বুকে চাপ + ২ বার মুখে শ্বাস','অ্যাম্বুলেন্স না আসা পর্যন্ত CPR চালিয়ে যান'],
                    'রোগীকে খাবার বা পানি দেবেন না। একা ছেড়ে যাবেন না।'],
                'stroke' => ['emergency', 'স্ট্রোক', 'fas fa-brain', '#6A1B9A',
                    'মুখ বাঁকা, হাত তুলতে না পারা, কথা জড়ানো — F.A.S.T. পদ্ধতিতে চিনুন',
                    ['রোগীকে নিরাপদ স্থানে শুইয়ে দিন','কিছু খাওয়াবেন না','কখন শুরু হয়েছে তা নোট করুন','জামাকাপড় ঢিলা করুন','বমি হলে পাশে কাত করুন','দ্রুত হাসপাতালে নিন'],
                    'রোগীকে ঘুমাতে দেবেন না। Aspirin দেবেন না।'],
                'choke' => ['home', 'গলায় কিছু আটকানো', 'fas fa-lungs', '#1565C0',
                    'কাশি, শ্বাস নিতে না পারা, মুখ নীল হওয়া',
                    ['পিছন থেকে দাঁড়িয়ে রোগীর কোমর পেঁচিয়ে ধরুন','মুষ্টি করে নাভির ২ আঙুল উপরে রাখুন','অন্য হাত দিয়ে মুষ্টি ধরে ভেতরে ও উপরে জোরে চাপ দিন (Heimlich)','৫ বার করুন, কাজ না হলে ৫ বার পিঠে চাপড় দিন','শিশুর ক্ষেত্রে উপুড় করে পিঠে চাপ দিন','৯৯৯ কল করুন'],
                    ''],
                'burn' => ['home', 'পোড়া', 'fas fa-fire', '#E65100',
                    'চামড়া লাল, ফোসকা, জ্বালাপোড়া',
                    ['২০ মিনিট ঠান্ডা (বরফ নয়) পানি ঢালুন','পোশাক সরান (আটকে থাকলে কাটুন)','পরিষ্কার কাপড় দিয়ে ঢেকে রাখুন','ব্যথায় Paracetamol দিন','বড় পোড়া (২০%+) হলে হাসপাতালে যান','মুখে পোড়া হলে ৯৯৯ কল করুন'],
                    'টুথপেস্ট, ডিম, মাখন লাগাবেন না। বরফ দেবেন না।'],
                'cut' => ['home', 'কাটা ও রক্তপাত', 'fas fa-droplet', '#AD1457',
                    'রক্তপাত, ব্যথা, ক্ষত',
                    ['পরিষ্কার কাপড় দিয়ে জোরে চাপ দিন','কাটা অংশ হৃদয়ের উপরে রাখুন','১০-১৫ মিনিট চাপ ধরে রাখুন','রক্ত বন্ধে পানি দিয়ে ধুয়ে ব্যান্ডেজ করুন','গভীর কাটা হলে সেলাই লাগতে পারে — হাসপাতালে যান'],
                    'আঙুল দিয়ে ক্ষত পরীক্ষা করবেন না।'],
                'fracture' => ['emergency', 'হাড় ভাঙা', 'fas fa-bone', '#4527A0',
                    'ব্যথা, ফোলা, অস্বাভাবিক আকার',
                    ['ভাঙা অঙ্গ নাড়াবেন না','কাঠ বা পত্রিকা দিয়ে splint বানান','কাপড় দিয়ে আলতো বেঁধে স্থির রাখুন','বরফ কাপড়ে মুড়িয়ে ব্যথায় দিন','দ্রুত হাসপাতালে নিন'],
                    'হাড় সোজা করার চেষ্টা করবেন না।'],
                'poison' => ['emergency', 'বিষক্রিয়া', 'fas fa-skull-crossbones', '#2E7D32',
                    'বমি, জ্বালাপোড়া, অজ্ঞান, খিঁচুনি',
                    ['কী খেয়েছে জানার চেষ্টা করুন','বমি করাবেন না (বিশেষত কেরোসিন, এসিড হলে)','মুখ পানি দিয়ে ধুয়ে দিন','সচেতন থাকলে পানি দিন','বিষের প্যাকেট সাথে নিয়ে হাসপাতালে যান'],
                    'বমি করানো সব সময় নিরাপদ নয়।'],
                'fever' => ['home', 'তীব্র জ্বর (১০৩°F+)', 'fas fa-thermometer', '#F57F17',
                    'শরীর গরম, মাথাব্যথা, কাঁপুনি',
                    ['Paracetamol (500mg) দিন','ঠান্ডা পানিতে কাপড় ভিজিয়ে কপালে রাখুন','প্রচুর পানি ও তরল খাওয়ান','আঁটসাঁট পোশাক খুলে হালকা কাপড় পরান','১০৫°F উপরে হলে হাসপাতালে যান','জ্বরের সাথে খিঁচুনি হলে ৯৯৯ কল করুন'],
                    ''],
                'diabetic' => ['home', 'ডায়াবেটিক শক', 'fas fa-syringe', '#00695C',
                    'কাঁপুনি, ঘাম, বিভ্রান্তি, মাথা ঘোরা (Low Sugar)',
                    ['৪-৫টি গ্লুকোজ ট্যাবলেট বা আধা গ্লাস ফলের রস দিন','১৫ মিনিট পর রক্তের সুগার পরীক্ষা করুন','স্বাভাবিক না হলে আবার চিনি দিন','অজ্ঞান হলে মুখে কিছু দেবেন না','৯৯৯ কল করুন'],
                    'অজ্ঞান রোগীর মুখে কিছু দেবেন না।'],
                'drown' => ['emergency', 'পানিতে ডোবা', 'fas fa-water', '#01579B',
                    'শ্বাস বন্ধ, অজ্ঞান, নীলাভ',
                    ['নিরাপদে পানি থেকে তুলুন','শ্বাস না থাকলে CPR শুরু করুন','মাথা পেছনে কাত করে শ্বাসনালী খুলুন','৩০ বার বুকে চাপ + ২ বার মুখে শ্বাস','জ্ঞান ফিরলে পাশে কাত করে রাখুন','দ্রুত হাসপাতালে নিন'],
                    ''],
                'snake' => ['emergency', 'সাপে কামড়', 'fas fa-worm', '#33691E',
                    'কামড়ের দাগ, ব্যথা, ফোলা, অসাড়তা',
                    ['রোগীকে শান্ত ও স্থির রাখুন','কামড়ের জায়গা হৃদয়ের নিচে রাখুন','আংটি, ঘড়ি, টাইট পোশাক সরান','সাপের ছবি তুলুন (নিরাপদে)','দ্রুত হাসপাতালে নিন — antivenom লাগতে পারে'],
                    'কাটবেন না, চুষবেন না, ট্যুর্নিকেট দেবেন না।'],
                'shock' => ['emergency', 'বৈদ্যুতিক শক', 'fas fa-bolt', '#F9A825',
                    'জ্ঞান হারানো, পোড়া, হৃদস্পন্দন বন্ধ',
                    ['বিদ্যুৎ সংযোগ বন্ধ করুন (সরাসরি স্পর্শ নয়)','শুকনো কাঠ দিয়ে রোগীকে তার থেকে সরান','শ্বাস না থাকলে CPR শুরু করুন','পোড়া জায়গায় ঠান্ডা পানি দিন','দ্রুত হাসপাতালে নিন'],
                    ''],
                'breath' => ['emergency', 'শ্বাসকষ্ট', 'fas fa-wind', '#0277BD',
                    'শ্বাস নিতে কষ্ট, বুকে চাপ, ঘ্যাঁ ঘ্যাঁ শব্দ',
                    ['রোগীকে সোজা বসান — শুইয়ে দেবেন না','আঁটসাঁট পোশাক ঢিলা করুন','Inhaler থাকলে ব্যবহার করান (২-৪ পাফ)','তাজা বাতাসের ব্যবস্থা করুন','৫ মিনিটে উন্নতি না হলে ৯৯৯ কল করুন'],
                    ''],
                'faint' => ['home', 'অজ্ঞান হওয়া', 'fas fa-person-falling', '#558B2F',
                    'হঠাৎ পড়ে যাওয়া, জ্ঞান হারানো',
                    ['পড়ে গিয়ে আঘাত না পান সে চেষ্টা করুন','শুইয়ে দিন, পা উঁচু করুন','আঁটসাঁট পোশাক ঢিলা করুন','তাজা বাতাসের ব্যবস্থা করুন','১ মিনিটে জ্ঞান না ফিরলে ৯৯৯ কল করুন','জ্ঞান ফিরলে পানি দিন, ধীরে উঠতে দিন'],
                    ''],
                'eye' => ['home', 'চোখে কিছু পড়া', 'fas fa-eye', '#00838F',
                    'জ্বালাপোড়া, লালভাব, চোখ বন্ধ করতে না পারা',
                    ['চোখ ডলবেন না','পরিষ্কার পানি দিয়ে ১৫ মিনিট ধুয়ে দিন','রাসায়নিক পড়লে ক্রমাগত পানি দিন','ধূলা বা ছোট কিছু হলে পাপড়ি দিয়ে বের করুন','লাল বা ব্যথা থাকলে চক্ষু ডাক্তার দেখান'],
                    'চোখ ডলবেন না — আরও ক্ষতি হতে পারে।'],
            ];
            foreach ($aids as $id => [$type,$title,$icon,$color,$symptoms,$steps,$warning]): ?>
            <div class="aid-card" id="aid-<?= $id ?>">
                <?php if ($type==='emergency'): ?>
                <span class="badge-emergency"><i class="fas fa-ambulance me-1"></i>জরুরি — ৯৯৯ কল করুন</span>
                <?php else: ?>
                <span class="badge-home"><i class="fas fa-house me-1"></i>বাসায় প্রাথমিক চিকিৎসা সম্ভব</span>
                <?php endif; ?>
                <h4 class="fw-bold" style="color:<?= $color ?>"><i class="<?= $icon ?> me-2"></i><?= $title ?></h4>
                <?php if ($symptoms): ?><p class="text-muted mb-3"><strong>লক্ষণ:</strong> <?= $symptoms ?></p><?php endif; ?>
                <h6 class="fw-bold mb-2">বাসায় যা করবেন:</h6>
                <?php foreach ($steps as $i => $step): ?>
                <div class="step-item"><div class="step-num"><?= $i+1 ?></div><div><?= $step ?></div></div>
                <?php endforeach; ?>
                <?php if ($warning): ?><div class="alert alert-warning mt-3 py-2"><i class="fas fa-ban me-2"></i><strong>করবেন না:</strong> <?= $warning ?></div><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function showAid(id,btn){
    document.querySelectorAll('.aid-card').forEach(c=>c.classList.remove('show'));
    document.querySelectorAll('.category-btn').forEach(b=>b.classList.remove('active'));
    document.getElementById('aid-'+id)?.classList.add('show');
    if(btn)btn.classList.add('active');
}
document.getElementById('aidSearch').addEventListener('input',function(){
    const q=this.value.toLowerCase().trim();
    if(!q)return;
    document.querySelectorAll('.category-btn').forEach(btn=>{
        if(btn.dataset.label.toLowerCase().includes(q))btn.click();
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
