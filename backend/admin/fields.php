<?php
require 'auth.php';
require '../config.php';

$form_id = $_GET['form_id'] ?? null;
$msg = "";

if (!$form_id) {
    header("Location: forms.php");
    exit;
}

// Busca dados do formulário pai
$stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ?");
$stmt->execute([$form_id]);
$form = $stmt->fetch();

if (!$form) die("Formulário não encontrado.");

// --- 1. ADICIONAR CAMPO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_field') {
    $label = $_POST['label'];
    // Gera um nome técnico automático (ex: Seu Nome -> seu_nome)
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

// --- 2. EXCLUIR CAMPO ---
if (isset($_GET['delete_field'])) {
    $field_id = $_GET['delete_field'];
    $stmt = $pdo->prepare("DELETE FROM form_fields WHERE id = ? AND form_id = ?");
    $stmt->execute([$field_id, $form_id]);
    header("Location: fields.php?form_id=$form_id");
    exit;
}

// --- 3. LISTAR CAMPOS ---
$fields = $pdo->prepare("SELECT * FROM form_fields WHERE form_id = ? ORDER BY id ASC");
$fields->execute([$form_id]);
$fieldsList = $fields->fetchAll(PDO::FETCH_ASSOC);
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
        <div class="flex flex-col">
            <span class="text-xs text-gray-500 uppercase">Editando Formulário</span>
            <h1 class="text-xl font-bold text-gray-800"><?php echo $form['title']; ?></h1>
        </div>
        <a href="forms.php" class="text-blue-600 hover:underline">← Voltar para Lista</a>
    </nav>

    <div class="container mx-auto p-4 max-w-5xl grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded shadow sticky top-4">
                <h2 class="font-bold mb-4 text-lg border-b pb-2">Adicionar Campo</h2>
                
                <?php if($msg): ?><div class="bg-green-100 text-green-800 p-2 mb-2 text-sm rounded"><?php echo $msg; ?></div><?php endif; ?>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_field">
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Rótulo (Label)</label>
                        <input type="text" name="label" placeholder="Ex: Digite seu Whatsapp" required class="w-full p-2 border rounded">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700">Tipo de Campo</label>
                        <select name="type" class="w-full p-2 border rounded bg-white">
                            <option value="text">Texto Curto (Input)</option>
                            <option value="email">E-mail</option>
                            <option value="tel">Telefone / Whats</option>
                            <option value="textarea">Texto Longo (Mensagem)</option>
                            <option value="select">Lista Suspensa (Select)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700">Opções (Só para Lista)</label>
                        <input type="text" name="options" placeholder="Ex: Orçamento, Suporte, Outros" class="w-full p-2 border rounded">
                        <small class="text-gray-400 text-xs">Separe por vírgula</small>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="required" id="req" value="1" checked>
                        <label for="req" class="text-sm cursor-pointer">Obrigatório?</label>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 rounded hover:bg-blue-700">
                        + Adicionar
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <h2 class="font-bold mb-4 text-lg">Campos Atuais</h2>
            
            <div class="bg-white rounded shadow overflow-hidden p-6 space-y-6 border-l-4 border-blue-500">
                <?php if(count($fieldsList) == 0): ?>
                    <p class="text-gray-400 text-center italic">Nenhum campo adicionado ainda.</p>
                <?php endif; ?>

                <?php foreach($fieldsList as $field): ?>
                    <div class="relative group border border-gray-200 p-4 rounded hover:bg-gray-50 transition">
                        <a href="?form_id=<?php echo $form_id; ?>&delete_field=<?php echo $field['id']; ?>" 
                           onclick="return confirm('Apagar este campo?')"
                           class="absolute top-2 right-2 text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition">
                           🗑️
                        </a>

                        <label class="block text-sm font-bold text-gray-700 mb-1">
                            <?php echo $field['label']; ?> 
                            <?php if($field['is_required']) echo '<span class="text-red-500">*</span>'; ?>
                        </label>

                        <?php if ($field['type'] === 'textarea'): ?>
                            <textarea disabled class="w-full p-2 border bg-gray-100 rounded h-20"></textarea>
                        <?php elseif ($field['type'] === 'select'): ?>
                            <select disabled class="w-full p-2 border bg-gray-100 rounded">
                                <option>Selecione uma opção...</option>
                                <?php 
                                    $opts = explode(',', $field['options']);
                                    foreach($opts as $opt) echo "<option>$opt</option>";
                                ?>
                            </select>
                        <?php else: ?>
                            <input type="text" disabled value="Tipo: <?php echo strtoupper($field['type']); ?>" class="w-full p-2 border bg-gray-100 rounded">
                        <?php endif; ?>
                        
                        <div class="mt-1 text-xs text-gray-400 flex gap-4">
                            <span>Name: <code class="bg-gray-200 px-1 rounded"><?php echo $field['name']; ?></code></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</body>
</html>