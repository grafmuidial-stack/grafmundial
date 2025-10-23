<?php
/**
 * Logout
 * Mundial Gráfica - Painel Administrativo
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Log da atividade
if (isset($_SESSION['admin_user'])) {
    logActivity('Logout realizado');
}

$auth = new Auth();
$auth->logout();
?>