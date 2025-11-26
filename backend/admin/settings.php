<?php
require 'auth.php';
require '../config.php';

$msg = "";

// --- PROCESSAR SALVAMENTO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    try {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        
        // 1. Salvar Campos de Texto (SMTP, Recaptcha, SEO)
        $textFields = [
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 
            'recaptcha_site_key', 'recaptcha_secret',
            'site_title', 'site_description'
        ];
        
        foreach ($textFields as $field) {
            if (isset($_POST[$field])) {
                $stmt->execute([$_POST[$field], $field]);
            }
        }

        // 2. Salvar Uploads (Logo e Favicon)
        $uploadFields = ['site_logo', 'site_favicon'];
        $uploadDir = '../uploads/';

        foreach ($uploadFields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === 0) {
                $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                $newName = $field . '_' . time() . '.' . $ext; // Ex: site_logo_12345.png
                
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDir . $newName)) {
                    // Salva o caminho no banco (ex: /uploads/site_logo_123.png)
                    $path = '/uploads/' . $newName;
                    $stmt->execute([$path, $field]);
                }
            }
        }

        $msg = "✅ Configurações e imagens salvas com sucesso!";
    } catch (Exception $e) {
        $msg = "❌ Erro ao salvar: " . $e->getMessage();
    }
}

// --- BUSCAR DADOS ATUAIS ---
$settings = [];
try {
    $stmt = $pdo->query("SELECT * FROM settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) { }
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Configurações Gerais</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen pb-10">
    <nav class="bg-white shadow p-4 mb-8 flex justify-between items-center">
        <h1 class="text-xl font-bold text-gray-800">⚙️ Configurações do Site</h1>
        <a href="index.php" class="text-blue-600 hover:underline">← Voltar</a>
    </nav>

    <div class="container mx-auto p-4 max-w-4xl">
        <?php if($msg): ?><div class="bg-green-100 text-green-800 p-4 rounded mb-4 font-bold"><?php echo $msg; ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-8">
            <input type="hidden" name="action" value="save">

            <div class="bg-white p-6 rounded-lg shadow border-l-4 border-purple-500">
                <h2 class="font-bold text-lg mb-4 text-gray-800">🎨 Identidade Visual & SEO</h2>
                
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-600 mb-1">Título do Site (Aba do navegador)</label>
                        <input type="text" name="site_title" value="<?php echo htmlspecialchars($settings['site_title'] ?? ''); ?>" 
                               placeholder="Ex: André Ventura | Full Stack Developer" class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-600 mb-1">Descrição (Para o Google)</label>
                        <input type="text" name="site_description" value="<?php echo htmlspecialchars($settings['site_description'] ?? ''); ?>" 
                               placeholder="Ex: Portfólio profissional de desenvolvimento web..." class="w-full p-2 border rounded">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-600 mb-1">Logotipo do Site</label>
                            <?php if(!empty($settings['site_logo'])): ?>
                                <div class="mb-2 bg-gray-800 p-2 rounded inline-block">
                                    <img src="..<?php echo $settings['site_logo']; ?>" class="h-10 w-auto">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="site_logo" accept="image/*" class="w-full text-sm text-gray-500">
                            <p class="text-xs text-gray-400 mt-1">Recomendado: PNG transparente.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-600 mb-1">Favicon (Ícone da Aba)</label>
                            <?php if(!empty($settings['site_favicon'])): ?>
                                <div class="mb-2">
                                    <img src="..<?php echo $settings['site_favicon']; ?>" class="h-8 w-8 border rounded">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="site_favicon" accept="image/*" class="w-full text-sm text-gray-500">
                            <p class="text-xs text-gray-400 mt-1">Recomendado: Ícone quadrado (ICO ou PNG).</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500 opacity-80 hover:opacity-100 transition">
                <h2 class="font-bold text-lg mb-4 text-gray-800">📧 E-mail (SMTP)</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500">Host</label>
                        <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>" class="w-full p-2 border rounded text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500">Porta</label>
                        <input type="text" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? ''); ?>" class="w-full p-2 border rounded text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500">Usuário</label>
                        <input type="text" name="smtp_user" value="<?php echo htmlspecialchars($settings['smtp_user'] ?? ''); ?>" class="w-full p-2 border rounded text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500">Senha</label>
                        <input type="password" name="smtp_pass" value="<?php echo htmlspecialchars($settings['smtp_pass'] ?? ''); ?>" class="w-full p-2 border rounded text-sm">
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow border-l-4 border-orange-500 opacity-80 hover:opacity-100 transition">
                <h2 class="font-bold text-lg mb-4 text-gray-800">🛡️ Google reCAPTCHA</h2>
                <div class="grid grid-cols-1 gap-4">
                    <input type="text" name="recaptcha_site_key" value="<?php echo htmlspecialchars($settings['recaptcha_site_key'] ?? ''); ?>" placeholder="Site Key" class="w-full p-2 border rounded text-sm">
                    <input type="text" name="recaptcha_secret" value="<?php echo htmlspecialchars($settings['recaptcha_secret'] ?? ''); ?>" placeholder="Secret Key" class="w-full p-2 border rounded text-sm">
                </div>
            </div>

            <div class="text-right pb-10">
                <button type="submit" class="bg-purple-600 text-white font-bold py-3 px-8 rounded shadow hover:bg-purple-700 transition transform hover:scale-105">
                    💾 Salvar Tudo
                </button>
            </div>
        </form>
    </div>
</body>
</html>