<?php
session_start();
require_once __DIR__ . '/../config.php';

// Verifica se está logado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Lógica para obter dados do dashboard
// Usa try/catch para evitar que a página quebre se o banco estiver vazio ou com erro
try {
    $total_projects = $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
    $new_contacts = $pdo->query("SELECT COUNT(*) FROM contacts WHERE status = 'Novo'")->fetchColumn();
} catch (Exception $e) {
    $total_projects = 0;
    $new_contacts = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Painel Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f4f4; }
        .header { background-color: #333; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
        .header a { color: white; text-decoration: none; margin-left: 20px; }
        .header a:hover { text-decoration: underline; }
        .container { padding: 20px; }
        .stats { display: flex; gap: 20px; margin-bottom: 30px; }
        .stat-box { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); flex: 1; }
        .stat-box h3 { margin-top: 0; color: #007bff; }
        .stat-box p { font-size: 2em; margin: 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Painel Administrativo</h1>
        <div>
            <a href="projects.php">Projetos (<?php echo $total_projects; ?>)</a>
            <a href="contacts.php">Contatos (<?php echo $new_contacts; ?> Novos)</a>
            <a href="logout.php">Sair</a>
        </div>
    </div>
    <div class="container">
        <h2>Bem-vindo, <?php echo htmlspecialchars($_SESSION['admin_email']); ?>!</h2>
        
        <div class="stats">
            <div class="stat-box">
                <h3>Total de Projetos</h3>
                <p><?php echo $total_projects; ?></p>
            </div>
            <div class="stat-box">
                <h3>Contatos Novos</h3>
                <p><?php echo $new_contacts; ?></p>
            </div>
        </div>
        <p>Use os links acima para gerenciar o conteúdo do seu site.</p>
    </div>
</body>
</html>