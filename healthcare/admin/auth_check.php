<?php
// auth_check.php — সব admin page এর শুরুতে include করতে হবে
// এটা আলাদা session name ব্যবহার করে তাই user session এর সাথে conflict নেই

session_name('HEALTHCARE_ADMIN');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: /healthcare/admin/login.php'); exit;
}
