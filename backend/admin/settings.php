<?php
// backend/admin/settings.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'auth.php';
require '../config.php';

// --- HELPER FUNCTION ---
function saveSetting($pdo, $key, $value) {
    // Tenta atualizar, se não afetar linhas (porque não existe), insere
    // Nota: Em MySQL puro usaria INSERT ... ON DUPLICATE KEY UPDATE, 
    // mas a lógica abaixo é compatível com mais bancos e segura o suficiente aqui.
    $stmt = $pdo->prepare("SELECT count(*) FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    if ($stmt->fetchColumn() > 0) {
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?")->execute([$value, $key]);
    } else {
        $pdo->prepare("INSERT INTO settings (setting_value, setting_key) VALUES (?, ?)")->execute([$value, $key]);
    }
}

// --- API HANDLING (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    try {
        if (!isset($_POST['action']) || $_POST['action'] !== 'save') {
            throw new Exception("Ação inválida.");
        }

        // 1. Campos de Texto
        $fields = [
            'site_title', 'site_description', 
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 
            'recaptcha_site_key', 'recaptcha_secret'
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                saveSetting($pdo, $field, $_POST[$field]);
            }
        }

        // 2. Uploads (Com tratamento de erro e nomes únicos)
        $uploadFields = ['site_logo', 'site_favicon'];
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        foreach ($uploadFields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === 0) {
                $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'ico', 'webp'];
                
                if (!in_array($ext, $allowed)) {
                    throw new Exception("Formato de arquivo inválido para $field. Use imagens.");
                }

                $newName = $field . '_' . time() . '.' . $ext;
                
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDir . $newName)) {
                    $webPath = '/uploads/' . $newName;
                    saveSetting($pdo, $field, $webPath);
                    $response[$field] = $webPath; // Retorna nova URL para atualizar front
                }
            }
        }

        $response['success'] = true;
        $response['message'] = "Configurações atualizadas com sucesso!";

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}

// --- VIEW (GET) ---
$settings = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Garante chaves vazias para não quebrar o JS
$keys = ['site_title', 'site_description', 'site_logo', 'site_favicon', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'recaptcha_site_key', 'recaptcha_secret'];
foreach($keys as $k) { if(!isset($settings[$k])) $settings[$k] = ''; }
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Configurações do Sistema</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen font-sans"
      x-data="settingsManager(<?php echo htmlspecialchars(json_encode($settings)); ?>)">

    <nav class="bg-white border-b border-slate-200 sticky top-0 z-20">
        <div class="max-w-5xl mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-purple-600 to-pink-600">
                ⚙️ Configurações Gerais
            </h1>
            <a href="index.php" class="text-sm text-slate-500 hover:text-purple-600 flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Voltar ao Dashboard
            </a>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto p-6 pb-24">

        <form @submit.prevent="saveSettings" class="space-y-8">
            
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="bg-purple-50 px-6 py-4 border-b border-purple-100 flex items-center gap-3">
                    <div class="bg-purple-100 p-2 rounded-lg text-purple-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-800">Identidade & SEO</h2>
                        <p class="text-xs text-gray-500">Como o site aparece no navegador e Google.</p>
                    </div>
                </div>
                
                <div class="p-6 grid grid-cols-1 gap-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Título do Site</label>
                            <input type="text" x-model="data.site_title" placeholder="Ex: Minha Empresa" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500 py-2 px-3 border transition">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Descrição (Meta Tag)</label>
                            <input type="text" x-model="data.site_description" placeholder="Ex: Soluções em tecnologia..." class="w-full border-gray-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500 py-2 px-3 border transition">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2 border-t border-slate-100">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Logotipo</label>
                            <div class="flex items-center gap-4">
                                <div class="h-16 w-16 bg-slate-100 rounded-lg border border-slate-200 flex items-center justify-center overflow-hidden relative group">
                                    <template x-if="previews.logo">
                                        <img :src="previews.logo" class="h-full w-full object-contain">
                                    </template>
                                    <template x-if="!previews.logo">
                                        <span class="text-xs text-gray-400">Sem Logo</span>
                                    </template>
                                </div>
                                <div class="flex-1">
                                    <input type="file" @change="handleFile($event, 'site_logo', 'logo')" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 cursor-pointer">
                                    <p class="text-xs text-gray-400 mt-1">Recomendado: PNG Transparente</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Favicon (Ícone da Aba)</label>
                            <div class="flex items-center gap-4">
                                <div class="h-16 w-16 bg-slate-100 rounded-lg border border-slate-200 flex items-center justify-center overflow-hidden">
                                    <template x-if="previews.favicon">
                                        <img :src="previews.favicon" class="h-8 w-8 object-contain">
                                    </template>
                                    <template x-if="!previews.favicon">
                                        <span class="text-xs text-gray-400">Sem Ícone</span>
                                    </template>
                                </div>
                                <div class="flex-1">
                                    <input type="file" @change="handleFile($event, 'site_favicon', 'favicon')" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 cursor-pointer">
                                    <p class="text-xs text-gray-400 mt-1">Recomendado: ICO ou PNG 32x32</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="bg-blue-50 px-6 py-4 border-b border-blue-100 flex items-center gap-3">
                    <div class="bg-blue-100 p-2 rounded-lg text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-800">Servidor de E-mail (SMTP)</h2>
                        <p class="text-xs text-gray-500">Configuração necessária para o formulário enviar e-mails.</p>
                    </div>
                </div>
                
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Host SMTP</label>
                        <input type="text" x-model="data.smtp_host" placeholder="smtp.hostinger.com" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Porta</label>
                        <input type="text" x-model="data.smtp_port" placeholder="587 ou 465" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Usuário / E-mail</label>
                        <input type="text" x-model="data.smtp_user" placeholder="no-reply@seusite.com" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border transition">
                    </div>
                    <div x-data="{ showPass: false }">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Senha</label>
                        <div class="relative">
                            <input :type="showPass ? 'text' : 'password'" x-model="data.smtp_pass" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border transition pr-10">
                            <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 px-3 text-gray-400 hover:text-gray-600 focus:outline-none">
                                <svg x-show="!showPass" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg x-show="showPass" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.054 10.054 0 01-3.772 4.241m-9.03-9.03l9.03 9.03" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="bg-orange-50 px-6 py-4 border-b border-orange-100 flex items-center gap-3">
                    <div class="bg-orange-100 p-2 rounded-lg text-orange-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-800">Segurança (reCAPTCHA v2)</h2>
                        <p class="text-xs text-gray-500">Evita spam nos seus formulários.</p>
                    </div>
                </div>
                
                <div class="p-6 grid grid-cols-1 gap-4">
                    <input type="text" x-model="data.recaptcha_site_key" placeholder="Site Key" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-orange-500 focus:ring-orange-500 py-2 px-3 border transition">
                    <input type="text" x-model="data.recaptcha_secret" placeholder="Secret Key" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-orange-500 focus:ring-orange-500 py-2 px-3 border transition">
                </div>
            </div>

            <div class="fixed bottom-0 left-0 right-0 bg-white border-t p-4 flex justify-end gap-4 z-30 shadow-2xl md:static md:bg-transparent md:border-0 md:shadow-none md:p-0">
                <button type="submit" :disabled="isLoading" class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold py-3 px-8 rounded-lg shadow hover:shadow-lg transition transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <svg x-show="isLoading" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="isLoading ? 'Salvando...' : 'Salvar Alterações'"></span>
                </button>
            </div>

        </form>

        <div x-show="notification.show" x-transition.move.bottom.duration.300ms class="fixed bottom-5 right-5 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 font-bold flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            <span x-text="notification.message"></span>
        </div>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('settingsManager', (initialData) => ({
                data: initialData,
                isLoading: false,
                notification: { show: false, message: '' },
                
                // Armazena arquivos selecionados (Files) e Previews (URLs)
                files: { site_logo: null, site_favicon: null },
                previews: { 
                    logo: initialData.site_logo || null, 
                    favicon: initialData.site_favicon || null 
                },

                init() {
                    // Ajusta paths relativos para visualização se necessário
                    // Se o path vier "../uploads/..." ajustamos para visualização
                    // Aqui assumimos que o valor no banco já é "/uploads/..." ou "https://..."
                },

                handleFile(event, fieldName, previewKey) {
                    const file = event.target.files[0];
                    if (file) {
                        this.files[fieldName] = file;
                        // Cria URL temporária para preview imediato
                        this.previews[previewKey] = URL.createObjectURL(file);
                    }
                },

                async saveSettings() {
                    this.isLoading = true;
                    const formData = new FormData();
                    formData.append('action', 'save');

                    // Adiciona campos de texto
                    for (const key in this.data) {
                        formData.append(key, this.data[key]);
                    }

                    // Adiciona arquivos se houver novos
                    if (this.files.site_logo) formData.append('site_logo', this.files.site_logo);
                    if (this.files.site_favicon) formData.append('site_favicon', this.files.site_favicon);

                    try {
                        const res = await fetch('settings.php', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await res.json();

                        if (result.success) {
                            this.showToast(result.message);
                            // Se o servidor retornou novos caminhos de arquivo, atualiza o estado
                            if(result.site_logo) this.data.site_logo = result.site_logo;
                            if(result.site_favicon) this.data.site_favicon = result.site_favicon;
                        } else {
                            alert('Erro: ' + result.message);
                        }
                    } catch (err) {
                        alert('Erro de conexão ao salvar.');
                    } finally {
                        this.isLoading = false;
                    }
                },

                showToast(msg) {
                    this.notification.message = msg;
                    this.notification.show = true;
                    setTimeout(() => this.notification.show = false, 3000);
                }
            }));
        });
    </script>
</body>
</html>