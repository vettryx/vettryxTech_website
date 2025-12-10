<?php
// backend/admin/index.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../config.php';
require 'includes/Layout.php';
require 'includes/Components.php';

// Verifica Login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: auth/login.php');
    exit;
}

// Dados do Dashboard
try {
    $total_projects = $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
    $total_forms = $pdo->query('SELECT COUNT(*) FROM forms')->fetchColumn();
    // Tenta contar clientes/contratos se as tabelas existirem (evita erro se rodar antes do install)
    $total_clients = 0;
    $total_contracts = 0;
    try {
        $total_clients = $pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn();
        $total_contracts = $pdo->query("SELECT COUNT(*) FROM contracts WHERE status = 'active'")->fetchColumn();
    } catch(Exception $e) {}
} catch (Exception $e) {
    $total_projects = 0; $total_forms = 0;
}

Layout::header('Dashboard');
?>

<div>
    <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-brand-dark dark:text-white">Visão Geral</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Bem-vindo ao seu painel de controle.</p>
        </div>
        <div class="text-sm font-mono text-brand-blue bg-blue-50 dark:bg-slate-800 dark:text-brand-blue px-3 py-1 rounded border border-blue-100 dark:border-slate-700">
            <?php echo date('d/m/Y'); ?> • v2.5
        </div>
    </div>

    <div class="mb-10">
        <h2 class="text-lg font-bold text-brand-dark dark:text-white mb-4 flex items-center gap-2">
            <span class="w-1 h-6 bg-blue-500 rounded"></span> Gestão Comercial
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <a href="clients.php" class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 hover:border-blue-400 hover:shadow-md transition group flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-bold mb-1">Carteira</p>
                    <h3 class="text-2xl font-bold text-brand-dark dark:text-white group-hover:text-blue-500 transition">Clientes</h3>
                    <p class="text-xs text-slate-400 mt-1"><?php echo $total_clients; ?> cadastrados</p>
                </div>
                <div class="bg-blue-50 dark:bg-slate-700 text-blue-500 p-3 rounded-lg group-hover:scale-110 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </div>
            </a>

            <a href="contracts.php" class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 hover:border-green-400 hover:shadow-md transition group flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-bold mb-1">Vigência</p>
                    <h3 class="text-2xl font-bold text-brand-dark dark:text-white group-hover:text-green-500 transition">Contratos</h3>
                    <p class="text-xs text-slate-400 mt-1"><?php echo $total_contracts; ?> ativos</p>
                </div>
                <div class="bg-green-50 dark:bg-slate-700 text-green-500 p-3 rounded-lg group-hover:scale-110 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
            </a>

            <a href="sales_config.php" class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 hover:border-purple-400 hover:shadow-md transition group flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-bold mb-1">Catálogo</p>
                    <h3 class="text-lg font-bold text-brand-dark dark:text-white group-hover:text-purple-500 transition">Config. Vendas</h3>
                    <p class="text-xs text-slate-400 mt-1">Planos, Preços e Pagamentos</p>
                </div>
                <div class="bg-purple-50 dark:bg-slate-700 text-purple-500 p-3 rounded-lg group-hover:scale-110 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </a>

        </div>
    </div>

    <div>
        <h2 class="text-lg font-bold text-brand-dark dark:text-white mb-4 flex items-center gap-2">
            <span class="w-1 h-6 bg-brand-green rounded"></span> Site & Sistema
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <a href="projects.php" class="group bg-white dark:bg-slate-800 p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 hover:border-brand-blue hover:ring-1 hover:ring-brand-blue transition cursor-pointer flex flex-col items-center text-center">
                <div class="bg-blue-50 dark:bg-slate-700 text-brand-blue p-3 rounded-full mb-3 group-hover:scale-110 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                </div>
                <h3 class="font-bold text-brand-dark dark:text-white group-hover:text-brand-blue">Projetos</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?php echo $total_projects; ?> ativos</p>
            </a>

            <a href="forms.php" class="group bg-white dark:bg-slate-800 p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 hover:border-brand-purple hover:ring-1 hover:ring-brand-purple transition cursor-pointer flex flex-col items-center text-center">
                <div class="bg-purple-50 dark:bg-slate-700 text-brand-purple p-3 rounded-full mb-3 group-hover:scale-110 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <h3 class="font-bold text-brand-dark dark:text-white group-hover:text-brand-purple">Formulários</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?php echo $total_forms; ?> ativos</p>
            </a>

            <a href="settings.php" class="group bg-white dark:bg-slate-800 p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 hover:border-brand-orange hover:ring-1 hover:ring-brand-orange transition cursor-pointer flex flex-col items-center text-center">
                <div class="bg-orange-50 dark:bg-slate-700 text-brand-orange p-3 rounded-full mb-3 group-hover:scale-110 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </div>
                <h3 class="font-bold text-brand-dark dark:text-white group-hover:text-brand-orange">Config. Geral</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">SEO e Contatos</p>
            </a>

            <a href="auth/security.php" class="group bg-white dark:bg-slate-800 p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 hover:border-green-500 hover:ring-1 hover:ring-green-500 transition cursor-pointer flex flex-col items-center text-center">
                <div class="bg-green-50 dark:bg-slate-700 text-green-500 p-3 rounded-full mb-3 group-hover:scale-110 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h3 class="font-bold text-brand-dark dark:text-white group-hover:text-green-500">Segurança</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">2FA e Senha</p>
            </a>

        </div>
    </div>
</div>

<?php Layout::footer(); ?>