<?php
// backend/admin/index.php

session_start();
require_once __DIR__ . '/../config.php';

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

// Pega o nome do usuário baseado no email (apenas visual)
$user_email = $_SESSION['admin_email'];
$user_name = explode('@', $user_email)[0];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Painel Administrativo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-slate-50 min-h-screen font-sans text-slate-800">

    <!-- Navbar -->
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-30" x-data="{ open: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center gap-2">
                    <div class="bg-gradient-to-br from-blue-600 to-indigo-600 text-white p-2 rounded-lg shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                    </div>
                    <span class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-slate-700 to-slate-900 tracking-tight">
                        Painel <span class="text-blue-600">Administrativo</span>
                    </span>
                </div>

                <!-- User Menu -->
                <div class="flex items-center">
                    <div class="relative">
                        <button @click="open = !open" @click.outside="open = false" class="flex items-center gap-3 focus:outline-none group">
                            <div class="text-right hidden sm:block">
                                <p class="text-xs text-slate-400 font-bold uppercase">Logado como</p>
                                <p class="text-sm font-bold text-slate-700 group-hover:text-blue-600 transition"><?php echo htmlspecialchars($user_email); ?></p>
                            </div>
                            <img src="https://ui-avatars.com/api/?name=<?php echo $user_email; ?>&background=eff6ff&color=2563eb" class="h-10 w-10 rounded-full border-2 border-white shadow-sm group-hover:border-blue-200 transition">
                        </button>

                        <!-- Dropdown -->
                        <div x-show="open" x-cloak x-transition.origin.top.right class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-slate-100 py-1 z-50">
                            <div class="px-4 py-3 border-b border-slate-50">
                                <p class="text-xs text-slate-500">Olá, <span class="font-bold text-slate-800 capitalize"><?php echo $user_name; ?></span>!</p>
                            </div>
                            <a href="users.php" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition">Gerenciar Admins</a>
                            <a href="settings.php" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-purple-600 transition">Configurações</a>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition font-bold">Sair do Sistema</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        
        <!-- Welcome Section -->
        <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">Visão Geral</h1>
                <p class="text-slate-500 mt-1">Resumo das atividades do seu portfólio.</p>
            </div>
            <div class="text-sm font-mono text-slate-400 bg-white px-3 py-1 rounded border">
                <?php echo date('d/m/Y'); ?> • v2.0
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            
            <!-- Projetos -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-md transition">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition transform group-hover:scale-110">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                </div>
                <div class="relative z-10">
                    <div class="text-blue-600 bg-blue-50 w-10 h-10 rounded-lg flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                    </div>
                    <dt class="text-sm font-medium text-slate-500 truncate">Projetos Ativos</dt>
                    <dd class="text-3xl font-bold text-slate-800"><?php echo $total_projects; ?></dd>
                </div>
            </div>

            <!-- Formulários -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-md transition">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition transform group-hover:scale-110">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <div class="relative z-10">
                    <div class="text-indigo-600 bg-indigo-50 w-10 h-10 rounded-lg flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    </div>
                    <dt class="text-sm font-medium text-slate-500 truncate">Formulários</dt>
                    <dd class="text-3xl font-bold text-slate-800"><?php echo $total_forms; ?></dd>
                </div>
            </div>

            <!-- Mensagens -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-md transition">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition transform group-hover:scale-110">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                </div>
                <div class="relative z-10">
                    <div class="text-emerald-600 bg-emerald-50 w-10 h-10 rounded-lg flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                    </div>
                    <dt class="text-sm font-medium text-slate-500 truncate">Mensagens Totais</dt>
                    <dd class="text-3xl font-bold text-slate-800"><?php echo $total_messages; ?></dd>
                </div>
            </div>

             <!-- Equipe -->
             <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-md transition">
                <div class="relative z-10">
                    <div class="text-orange-600 bg-orange-50 w-10 h-10 rounded-lg flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    </div>
                    <dt class="text-sm font-medium text-slate-500 truncate">Admin Team</dt>
                    <dd class="text-3xl font-bold text-slate-800"><?php echo $total_admins; ?></dd>
                </div>
            </div>
        </div>

        <!-- Shortcuts Grid -->
        <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-1 h-6 bg-blue-600 rounded"></span> Acesso Rápido
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <a href="projects.php" class="group bg-white p-5 rounded-xl shadow-sm border border-slate-200 hover:border-blue-400 hover:ring-2 hover:ring-blue-100 transition cursor-pointer flex flex-col items-center text-center">
                <div class="bg-blue-100 text-blue-600 p-3 rounded-full mb-3 group-hover:scale-110 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                </div>
                <h3 class="font-bold text-slate-800 group-hover:text-blue-600">Projetos</h3>
                <p class="text-xs text-slate-500 mt-1">Gerenciar Portfolio</p>
            </a>

            <a href="forms.php" class="group bg-white p-5 rounded-xl shadow-sm border border-slate-200 hover:border-indigo-400 hover:ring-2 hover:ring-indigo-100 transition cursor-pointer flex flex-col items-center text-center">
                <div class="bg-indigo-100 text-indigo-600 p-3 rounded-full mb-3 group-hover:scale-110 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <h3 class="font-bold text-slate-800 group-hover:text-indigo-600">Formulários</h3>
                <p class="text-xs text-slate-500 mt-1">Campos & Envios</p>
            </a>

            <a href="users.php" class="group bg-white p-5 rounded-xl shadow-sm border border-slate-200 hover:border-emerald-400 hover:ring-2 hover:ring-emerald-100 transition cursor-pointer flex flex-col items-center text-center">
                <div class="bg-emerald-100 text-emerald-600 p-3 rounded-full mb