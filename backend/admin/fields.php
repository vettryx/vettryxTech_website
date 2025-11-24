<?php
// Ativa erros para não ficar tela branca
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'auth.php';
require '../config.php';

// DEBUG: Se não chegar ID, avisa em vez de redirecionar
$form_id = $_GET['form_id'] ?? null;
if (!$form_id) {
    die("ERRO: Nenhum ID de formulário foi recebido na URL. Verifique o link na página anterior.");
}

// Busca o formulário
$stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ?");
$stmt->execute([$form_id]);
$form = $stmt->fetch();

if (!$form) {
    die("ERRO: Formulário ID $form_id não encontrado no banco.");
}

// ... (Resto do código igual, lógica de adicionar campo)
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_field') {
    $label = $_POST['label'];
    $name = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $label)));
    $type = $_POST['type'];
    $options = $_POST['options'];
    $required = isset($_POST['required']) ? 1 : 0;

    if (!empty($label)) {
        $sql = "INSERT INTO form_fields (form_id, label, name, type, options, is_required) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$form_id, $label, $name, $type, $options, $required]);
        $msg = "✅ Campo adicionado!";
    }
}

if (isset($_GET['delete_field'])) {
    $field_id = $_GET['delete_field'];
    $stmt = $pdo->prepare("DELETE FROM form_fields WHERE id = ? AND form_id = ?");
    $stmt->execute([$field_id, $form_id]);
    header("Location: fields.php?form_id=$form_id");
    exit;
}

// Lista campos
$stmt = $pdo->prepare("SELECT * FROM form_fields WHERE form_id = ? ORDER BY id ASC");
$stmt->execute([$form_id]);
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Campos - <?php echo $form['title']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen pb-10">
    <nav class="bg-white shadow p-4 mb-8 flex justify-between items-center">
        <h1 class="text-xl font-bold text-gray-800">Editando: <?php echo $form['title']; ?></h1>
        <a href="forms.php" class="text-blue-600 underline">Voltar</a>
    </nav>

    <div class="container mx-auto p-4 max-w-5xl grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1 bg-white p-6 rounded shadow">
            <h2 class="font-bold mb-4">Novo Campo</h2>
            <?php if($msg): ?><p class="text-green-600 mb-4"><?php echo $msg; ?></p><?php endif; ?>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_field">
                <input type="text" name="label" placeholder="Nome (Label)" required class="w-full border p-2 rounded">
                <select name="type" class="w-full border p-2 rounded">
                    <option value="text">Texto</option>
                    <option value="email">Email</option>
                    <option value="textarea">Mensagem (Longo)</option>
                    <option value="select">Lista</option>
                </select>
                <input type="text" name="options" placeholder="Opções (se for lista)" class="w-full border p-2 rounded">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="required" value="1" checked> Obrigatório
                </label>
                <button class="w-full bg-blue-600 text-white font-bold py-2 rounded">Adicionar</button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white p-6 rounded shadow">
            <h2 class="font-bold mb-4">Campos Criados</h2>
            <?php foreach($fields as $field): ?>
                <div class="border p-3 mb-2 flex justify-between rounded">
                    <span>
                        <strong><?php echo $field['label']; ?></strong> 
                        <small>(<?php echo $field['type']; ?>)</small>
                    </span>
                    <a href="?form_id=<?php echo $form_id; ?>&delete_field=<?php echo $field['id']; ?>" class="text-red-500 text-sm">Excluir</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>