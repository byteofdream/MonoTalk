<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

logoutUser();
header('Location: ' . BASE_URL . 'index.php');
exit;
