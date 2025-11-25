<?php
session_start();
require_once __DIR__ . '/../config.php';

// Verifica se está logado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Dados do Dashboard
try {
    // Contagem de Projetos
    $total_projects = $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
    
    // Contagem de Formulários Criados
    $total_forms = $pdo->query('SELECT COUNT(*) FROM forms')->fetchColumn();
    
    // Contagem de Mensagens (Total de todas as submissões)
    $total_messages = $pdo->query('SELECT COUNT(*) FROM form_submissions')->fetchColumn();

} catch (Exception $e) {
    $total_projects = 0;
    $total_forms = 0;
    $total_messages = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Painel Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen font-sans">

    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-2xl font-bold text-gray-800 tracking-tight">
                        Painel<span class="text-blue-600">Admin</span>
                    </span>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm text-gray-500">Logado como</p>
                        <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['admin_email']); ?></p>
                    </div>
                    <a href="logout.php" class="bg-red-50 text-red-600 hover:bg-red-100 px-4 py-2 rounded-md text-sm font-medium transition">
                        Sair
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Visão Geral</h1>
            <p class="text-gray-500 mt-1">Bem-vindo de volta! Aqui está o resumo do seu site.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            
            <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-blue-500">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                            <span class="text-2xl">📂</span>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Projetos no Portfólio</dt>
                                <dd class="text-3xl font-bold text-gray-900"><?php echo $total_projects; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-purple-500">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-100 rounded-md p-3">
                            <span class="text-2xl">📝</span>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Formulários Ativos</dt>
                                <dd class="text-3xl font-bold text-gray-900"><?php echo $total_forms; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-green-500">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                            <span class="text-2xl">✉️</span>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Mensagens Recebidas</dt>
                                <dd class="text-3xl font-bold text-gray-900"><?php echo $total_messages; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="text-xl font-bold text-gray-900 mb-4">Gerenciamento</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <a href="projects.php" class="group bg-white p-6 rounded-lg shadow hover:shadow-md transition border border-gray-200 hover:border-blue-400 cursor-pointer">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600 transition">Meus Projetos</h3>
                        <p class="text-sm text-gray-500 mt-1">Adicionar, editar ou remover projetos do site.</p>
                    </div>
                    <span class="text-gray-300 group-hover:text-blue-500 text-2xl transition">→</span>
                </div>
            </a>

            <a href="forms.php" class="group bg-white p-6 rounded-lg shadow hover:shadow-md transition border border-gray-200 hover:border-purple-400 cursor-pointer">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 group-hover:text-purple-600 transition">Formulários & Mensagens</h3>
                        <p class="text-sm text-gray-500 mt-1">Criar campos e ver contatos recebidos.</p>
                    </div>
                    <span class="text-gray-300 group-hover:text-purple-500 text-2xl transition">→</span>
                </div>
            </a>

            <a href="users.php" class="group bg-white p-6 rounded-lg shadow hover:shadow-md transition border border-gray-200 hover:border-orange-400 cursor-pointer">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 group-hover:text-orange-600 transition">Usuários Admin</h3>
                        <p class="text-sm text-gray-500 mt-1">Gerenciar quem tem acesso a este painel.</p>
                    </div>
                    <span class="text-gray-300 group-hover:text-orange-500 text-2xl transition">→</span>
                </div>
            </a>

        </div>

    </main>

</body>
</html>