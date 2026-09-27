<?php
session_name('HEALTHCARE_ADMIN');
session_start();
session_destroy();
header('Location: /healthcare/admin/login.php'); exit;
