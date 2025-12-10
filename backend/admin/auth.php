<?php
// backend/admin/auth.php
// Responsável por proteger páginas internas

session_start();

// Verifica sessão
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: auth/login.php");
    exit;
}
?>