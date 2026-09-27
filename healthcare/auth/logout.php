<?php
session_start(); session_destroy();
header('Location: /healthcare/index.php'); exit;
