<?php
require 'auth.php';
require '../config.php';

$msg = "";

// --- 1. ADICIONAR NOVO ADMIN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        // Verifica se o email já existe
        $check = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
        $check->execute([$email]);
        
        if ($check->rowCount() > 0) {
            $msg = "❌ Erro: Esse e-mail já está cadastrado.";
        } else {
            // Criptografia segura da senha
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO admins (email, password) VALUES (?, ?)");
                $stmt->execute([$email, $hash]);
                $msg = "✅ Novo administrador adicionado!";
            } catch (Exception $e) {
                $msg = "❌ Erro ao salvar: " . $e->getMessage();
            }
        }
    } else {
        $msg = "⚠️ Preencha email e senha.";
    }
}

// --- 2. EXCLUIR ADMIN ---
if (isset($_GET['delete'])) {
    $id_to_delete = $_GET['delete'];
    
    // Trava de segurança: Não pode excluir a si mesmo
    if ($id_to_delete == $_SESSION['admin_id']) {
        $msg = "🚫 Você não pode excluir sua própria conta enquanto está logado.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->execute([$id_to_delete]);
        header("Location: users.php"); // Recarrega para limpar URL
        exit;
    }
}

// --- 3. LISTAR ---
$admins = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Usuários</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen pb-10">

    <nav class="bg-white shadow p-4 mb-8 flex justify-between items-center">
        <h1 class="text-xl font-bold text-gray-800">👥 Gerenciar Acessos</h1>
        <a href="index.php" class="text-blue-600 hover:underline">← Voltar ao Painel</a>
    </nav>

    <div class="container mx-auto p-4 max-w-4xl">
        
        <?php if($msg): ?>
            <div class="bg-blue-100 text-blue-800 p-4 mb-4 rounded border border-blue-200 font-bold">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded shadow mb-8 border-l-4 border-green-500">
            <h2 class="font-bold mb-4 text-lg text-gray-700">Cadastrar Novo Admin</h2>
            
            <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <input type="hidden" name="action" value="create">
                
                <div>
                    <label class="block text-sm font-bold text-gray-600 mb-1">E-mail de Acesso</label>
                    <input type="email" name="email" placeholder="usuario@asventura.com.br" required 
                           class="w-full p-2 border rounded focus:ring-2 focus:ring-green-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-600 mb-1">Senha</label>
                    <input type="password" name="password" placeholder="******" required 
                           class="w-full p-2 border rounded focus:ring-2 focus:ring-green-500 outline-none">
                </div>

                <div>
                    <button type="submit" class="w-full bg-green-600 text-white font-bold py-2 px-4 rounded hover:bg-green-700 transition">
                        + Adicionar
                    </button>
                </div>
            </form>
        </div>

        <h2 class="font-bold text-lg mb-4 text-gray-700">Administradores Ativos</h2>
        
        <div class="bg-white rounded shadow overflow-hidden">
            <table class="min-w-full text-left">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="p-4">ID</th>
                        <th class="p-4">E-mail</th>
                        <th class="p-4">Criado em</th>
                        <th class="p-4 text-center">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($admins as $admin): ?>
                    <tr class="border-b hover:bg-gray-50 <?php echo $admin['id'] == $_SESSION['admin_id'] ? 'bg-blue-50' : ''; ?>">
                        <td class="p-4 text-gray-500 font-mono">#<?php echo $admin['id']; ?></td>
                        <td class="p-4 font-bold text-gray-800">
                            <?php echo htmlspecialchars($admin['email']); ?>
                            <?php if($admin['id'] == $_SESSION['admin_id']) echo " <span class='text-xs bg-blue-200 text-blue-800 px-2 py-1 rounded ml-2'>(Você)</span>"; ?>
                        </td>
                        <td class="p-4 text-sm text-gray-500"><?php echo date('d/m/Y H:i', strtotime($admin['created_at'])); ?></td>
                        <td class="p-4 text-center">
                            <?php if($admin['id'] != $_SESSION['admin_id']): ?>
                                <a href="?delete=<?php echo $admin['id']; ?>" 
                                   onclick="return confirm('Tem certeza que deseja remover o acesso de <?php echo $admin['email']; ?>?')"
                                   class="text-red-500 hover:text-red-700 font-bold text-sm bg-red-50 px-3 py-1 rounded border border-red-100 hover:bg-red-100 transition">
                                   Remover
                                </a>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs italic">Bloqueado</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>