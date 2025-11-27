<?php
// backend/admin/index.php

session_start();

// Caminho absoluto para a raiz do sistema
$baseDir = __DIR__;

// --- AUTOMAÇÃO DE INCLUDE (Corrige Casing Windows/Linux) ---
function require_smart($path) {
    // 1. Tenta o caminho exato
    if (file_exists($path)) {
        require_once $path;
        return;
    }

    // 2. Se falhar, tenta achar o arquivo ignorando maiúsculas/minúsculas
    $dir = dirname($path);
    $filename = basename($path);
    
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if (strtolower($file) === strtolower($filename)) {
                require_once $dir . '/' . $file;
                return;
            }
        }
    }

    // 3. Se nada funcionar, erro fatal com debug
    die("<h1>ERRO CRÍTICO DE DEPLOY</h1>
         <p>O sistema não encontrou o arquivo: <strong>" . htmlspecialchars(basename($path)) . "</strong></p>
         <p>Caminho buscado: $path</p>
         <p>Verifique se a pasta <strong>includes</strong> foi enviada para o servidor.</p>");
}

// Carrega as dependências usando a função inteligente
require_once __DIR__ . '/../config.php';
require_smart(__DIR__ . '/includes/Layout.php');
require_smart(__DIR__ . '/includes/Components.php');

// Verifica se está logado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Dados do Dashboard
try {
    $total_projects = $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
    $total_forms = $pdo->query('SELECT COUNT(*) FROM forms')->fetchColumn();
    $total_messages = $pdo->query('SELECT COUNT(*) FROM form_submissions')->fetchColumn();
    $total_admins = $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
} catch (Exception $e) {
    $total_projects = 0; $total_forms = 0; $total_messages = 0; $total_admins = 0;
}

Layout::header('Dashboard');
?>

<div>
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-brand-dark dark:text-white">Visão Geral</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Resumo das atividades do seu portfólio.</p>
        </div>
        <div class="text-sm font-mono text-brand-blue bg-blue-50 dark:bg-slate-800 dark:text-brand-blue px-3 py-1 rounded border border-blue-100 dark:border-slate-700">
            <?php echo date('d/m/Y'); ?> • v2.0
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
        
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm border border-slate-100 dark:border-slate-700 relative overflow-hidden group hover:shadow-md transition">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition transform group-hover:scale-110">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-brand-blue" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
            </div>
            <div class="relative z-10">
                <div class="text-brand-blue bg-blue-50 dark:bg-slate-700 w-10 h-10 rounded-lg flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                </div>
                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate">Projetos Ativos</dt>
                <dd class="text-3xl font-bold text-brand-dark dark:text-white"><?php echo $total_projects; ?></dd>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm border border-slate-100 dark:border-slate-700 relative overflow-hidden group hover:shadow-md transition">
            <div class="relative z-10">
                <div class="text-brand-purple bg-purple-50 dark:bg-slate-700 w-10 h-10 rounded-lg flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate">Formulários</dt>
                <dd class="text-3xl font-bold text-brand-dark dark:text-white"><?php echo $total_forms; ?></dd>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm border border-slate-100 dark:border-slate-700 relative overflow-hidden group hover:shadow-md transition">
            <div class="relative z-10">
                <div class="text-brand-green bg-green-50 dark:bg-slate-700 w-10 h-10 rounded-lg flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                </div>
                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate">Mensagens</dt>
                <dd class="text-3xl font-bold text-brand-dark dark:text-white"><?php echo $total_messages; ?></dd>
            </div>
        </div>

         <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm border border-slate-100 dark:border-slate-700 relative overflow-hidden group hover:shadow-md transition">
            <div class="relative z-10">
                <div class="text-brand-orange bg-orange-50 dark:bg-slate-700 w-10 h-10 rounded-lg flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </div>
                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate">Admin Team</dt>
                <dd class="text-3xl font-bold text-brand-dark dark:text-white"><?php echo $total_admins; ?></dd>
            </div>
        </div>
    </div>

    <h2 class="text-lg font-bold text-brand-dark dark:text-white mb-4 flex items-center gap-2">
        <span class="w-1 h-6 bg-brand-green rounded"></span> Acesso Rápido
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <a href="projects.php" class="group bg-white dark:bg-slate-800 p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 hover:border-brand-blue hover:ring-1 hover:ring-brand-blue transition cursor-pointer flex flex-col items-center text-center">
            <div class="bg-blue-50 dark:bg-slate-700 text-brand-blue p-3 rounded-full mb-3 group-hover:scale-110 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
            </div>
            <h3 class="font-bold text-brand-dark dark:text-white group-hover:text-brand-blue">Projetos</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Gerenciar Portfolio</p>
        </a>

        <a href="forms.php" class="group bg-white dark:bg-slate-800 p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 hover:border-brand-purple hover:ring-1 hover:ring-brand-purple transition cursor-pointer flex flex-col items-center text-center">
            <div class="bg-purple-50 dark:bg-slate-700 text-brand-purple p-3 rounded-full mb-3 group-hover:scale-110 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            </div>
            <h3 class="font-bold text-brand-dark dark:text-white group-hover:text-brand-purple">Formulários</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Campos & Envios</p>
        </a>

        <a href="settings.php" class="group bg-white dark:bg-slate-800 p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 hover:border-brand-orange hover:ring-1 hover:ring-brand-orange transition cursor-pointer flex flex-col items-center text-center">
            <div class="bg-orange-50 dark:bg-slate-700 text-brand-orange p-3 rounded-full mb-3 group-hover:scale-110 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            </div>
            <h3 class="font-bold text-brand-dark dark:text-white group-hover:text-brand-orange">Configurações</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Geral e SMTP</p>
        </a>

    </div>
</div>

<?php Layout::footer(); ?>