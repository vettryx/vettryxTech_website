<?php
require 'auth.php';
require '../config.php';

$msg = "";

// --- 1. CRIAR NOVO FORMULÁRIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = $_POST['title'];
    $slug = $_POST['slug']; // Ex: contact-home
    $email = $_POST['recipient_email'];

    if (!empty($title) && !empty($slug) && !empty($email)) {
        try {
            // O Slug deve ser único no banco
            $stmt = $pdo->prepare("INSERT INTO forms (title, slug, recipient_email) VALUES (?, ?, ?)");
            $stmt->execute([$title, $slug, $email]);
            $msg = "✅ Formulário criado!";
        } catch (Exception $e) {
            $msg = "❌ Erro: Esse Slug já existe ou dados inválidos.";
        }
    } else {
        $msg = "⚠️ Preencha todos os campos.";
    }
}

// --- 2. LISTAR FORMULÁRIOS EXISTENTES ---
$forms = $pdo->query("SELECT * FROM forms ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Formulários</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen pb-10">

    <nav class="bg-white shadow p-4 mb-8 flex justify-between items-center">
        <h1 class="text-xl font-bold text-gray-800">📝 Meus Formulários</h1>
        <a href="index.php" class="text-blue-600 hover:underline">← Voltar ao Dashboard</a>
    </nav>

    <div class="container mx-auto p-4 max-w-4xl">
        
        <?php if($msg): ?>
            <div class="bg-blue-100 text-blue-700 p-4 mb-4 rounded border border-blue-200">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded shadow mb-8">
            <h2 class="font-bold mb-4 text-lg text-gray-700 border-b pb-2">Novo Formulário</h2>
            
            <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="hidden" name="action" value="create">
                
                <div>
                    <label class="block text-sm font-bold text-gray-600 mb-1">Nome</label>
                    <input type="text" name="title" placeholder="Ex: Contato Home" required 
                           class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-600 mb-1">Slug (ID Único)</label>
                    <input type="text" name="slug" placeholder="Ex: contact-home" required 
                           class="w-full p-2 border rounded bg-gray-50 font-mono text-sm focus:ring-2 focus:ring-blue-500">
                    <small class="text-xs text-gray-400">Sem espaços. Use hífens.</small>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-600 mb-1">Enviar para:</label>
                    <input type="email" name="recipient_email" placeholder="seu@email.com" required 
                           class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="md:col-span-3 text-right">
                    <button type="submit" class="bg-green-600 text-white font-bold py-2 px-6 rounded hover:bg-green-700 transition">
                        + Criar
                    </button>
                </div>
            </form>
        </div>

        <h2 class="font-bold text-lg mb-4 text-gray-700">Formulários Ativos</h2>
        
        <div class="bg-white rounded shadow overflow-hidden">
            <?php if(count($forms) > 0): ?>
                <table class="min-w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="p-4 text-left text-sm text-gray-500">Nome</th>
                            <th class="p-4 text-left text-sm text-gray-500">Slug</th>
                            <th class="p-4 text-left text-sm text-gray-500">Destino</th>
                            <th class="p-4 text-center text-sm text-gray-500">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($forms as $f): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-4 font-bold text-gray-800"><?php echo htmlspecialchars($f['title']); ?></td>
                            <td class="p-4 font-mono text-blue-600 text-sm"><?php echo htmlspecialchars($f['slug']); ?></td>
                            <td class="p-4 text-gray-600 text-sm"><?php echo htmlspecialchars($f['recipient_email']); ?></td>
                            <td class="p-4 text-center flex justify-center gap-2">
                                <a href="fields.php?form_id=<?php echo $f['id']; ?>" 
                                   class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded text-xs font-bold hover:bg-yellow-200 border border-yellow-300">
                                   ⚙️ Campos
                                </a>
                                <a href="submissions.php?form_id=<?php echo $f['id']; ?>" 
                                   class="bg-blue-100 text-blue-700 px-3 py-1 rounded text-xs font-bold hover:bg-blue-200 border border-blue-300">
                                   ✉️ Ver Envios
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="p-8 text-center text-gray-500">Nenhum formulário criado ainda.</p>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>