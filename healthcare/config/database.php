<?php
$host     = 'localhost';
$dbname   = 'healthcare_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;padding:2rem;color:#c62828;background:#ffebee;border-radius:8px;margin:2rem">
        <h3>ডেটাবেস সংযোগ ব্যর্থ</h3>
        <p>' . htmlspecialchars($e->getMessage()) . '</p>
        <p>config/database.php ফাইলে username/password ঠিক করুন।</p>
    </div>');
}
