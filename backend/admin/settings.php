<?php
// backend/admin/settings.php

require 'auth.php';
require '../config.php';
require 'includes/Layout.php';
require 'includes/Components.php';

// --- API HANDLING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        if (!isset($_POST['action']) || $_POST['action'] !== 'save') throw new Exception("Ação inválida.");
        
        $fields = ['site_title', 'site_description', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'recaptcha_site_key', 'recaptcha_secret'];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$field, $_POST[$field], $_POST[$field]]);
            }
        }
        
        $response = ['success' => true, 'message' => 'Salvo!'];
        
        // Uploads
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        foreach (['site_logo', 'site_favicon'] as $f) {
            if (isset($_FILES[$f]) && $_FILES[$f]['error'] === 0) {
                $ext = pathinfo($_FILES[$f]['name'], PATHINFO_EXTENSION);
                $newName = $f . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES[$f]['tmp_name'], $uploadDir . $newName)) {
                    $path = '/uploads/' . $newName;
                    $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$f, $path, $path]);
                    $response[$f] = $path;
                }
            }
        }
        echo json_encode($response);
    } catch (Exception $e) { echo json_encode(['success'=>false, 'message'=>$e->getMessage()]); }
    exit;
}

// --- VIEW ---
$settings = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $settings[$row['setting_key']] = $row['setting_value'];
$keys = ['site_title', 'site_description', 'site_logo', 'site_favicon', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'recaptcha_site_key', 'recaptcha_secret'];
foreach($keys as $k) { if(!isset($settings[$k])) $settings[$k] = ''; }

Layout::header('Configurações');
?>

<div x-data="settingsManager(<?php echo htmlspecialchars(json_encode($settings)); ?>)" class="pb-20">
    
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-brand-dark dark:text-white">Configurações Gerais</h1>
        <p class="text-slate-500 dark:text-slate-400">Personalize a identidade e integrações do site.</p>
    </div>

    <form @submit.prevent="saveSettings" class="space-y-8 max-w-4xl">
        
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="bg-purple-50 dark:bg-slate-700/50 px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center gap-3">
                <span class="text-brand-purple">🎨</span>
                <h2 class="font-bold text-brand-dark dark:text-white">Identidade Visual & SEO</h2>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php echo UI::input('Título do Site', 'data.site_title', 'text'); ?>
                <?php echo UI::input('Descrição (Meta Tag)', 'data.site_description', 'text'); ?>
                
                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-100 dark:border-slate-700">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Logotipo</label>
                        <div class="flex items-center gap-4">
                            <div class="h-16 w-16 bg-slate-100 dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 flex items-center justify-center overflow-hidden">
                                <template x-if="previews.logo"><img :src="previews.logo" class="h-full w-full object-contain"></template>
                                <template x-if="!previews.logo"><span class="text-xs text-slate-400">Vazio</span></template>
                            </div>
                            <input type="file" @change="handleFile($event, 'site_logo', 'logo')" class="text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-brand-purple file:text-white hover:file:bg-purple-700">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Favicon</label>
                        <div class="flex items-center gap-4">
                            <div class="h-16 w-16 bg-slate-100 dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 flex items-center justify-center overflow-hidden">
                                <template x-if="previews.favicon"><img :src="previews.favicon" class="h-8 w-8 object-contain"></template>
                                <template x-if="!previews.favicon"><span class="text-xs text-slate-400">Vazio</span></template>
                            </div>
                            <input type="file" @change="handleFile($event, 'site_favicon', 'favicon')" class="text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-brand-purple file:text-white hover:file:bg-purple-700">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="bg-blue-50 dark:bg-slate-700/50 px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center gap-3">
                <span class="text-brand-blue">📧</span>
                <h2 class="font-bold text-brand-dark dark:text-white">Servidor de E-mail (SMTP)</h2>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php echo UI::input('Host', 'data.smtp_host', 'text'); ?>
                <?php echo UI::input('Porta', 'data.smtp_port', 'text'); ?>
                <?php echo UI::input('Usuário', 'data.smtp_user', 'text'); ?>
                <?php echo UI::input('Senha', 'data.smtp_pass', 'password'); ?>
            </div>
        </div>

        <div class="fixed bottom-0 left-0 right-0 p-4 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-4 z-30 md:static md:bg-transparent md:border-0 md:p-0">
            <?php echo UI::button('<span x-text="isLoading ? \'Salvando...\' : \'Salvar Alterações\'"></span>', 'submit', 'brand-green', '', '💾'); ?>
        </div>

    </form>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('settingsManager', (initData) => ({
            data: initData, isLoading: false,
            files: { site_logo: null, site_favicon: null },
            previews: { logo: initData.site_logo, favicon: initData.site_favicon },

            handleFile(e, field, prevKey) {
                const file = e.target.files[0];
                if(file) { this.files[field] = file; this.previews[prevKey] = URL.createObjectURL(file); }
            },

            async saveSettings() {
                this.isLoading = true;
                const formData = new FormData();
                formData.append('action', 'save');
                for(let k in this.data) formData.append(k, this.data[k]);
                if(this.files.site_logo) formData.append('site_logo', this.files.site_logo);
                if(this.files.site_favicon) formData.append('site_favicon', this.files.site_favicon);

                try {
                    const res = await fetch('settings.php', { method: 'POST', body: formData });
                    const r = await res.json();
                    if(r.success) { 
                        alert('Salvo com sucesso!'); 
                        if(r.site_logo) this.data.site_logo = r.site_logo; // Atualiza URL real
                    } else alert('Erro: ' + r.message);
                } catch(e) { alert('Erro de conexão'); }
                finally { this.isLoading = false; }
            }
        }));
    });
</script>

<?php Layout::footer(); ?>