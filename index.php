<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {  // Corrigido: era "istoggedIn()"
    header('Location: dashboard.php');
    exit;
} else {
    header('Location: login.php');
    exit;
}
?>