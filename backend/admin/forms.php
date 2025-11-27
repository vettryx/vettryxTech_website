<?php
// backend/admin/forms.php

require 'auth.php';
require '../config.php';
require 'includes/Layout.php';
require 'includes/Components.php';

// --- API HANDLING ---
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $input) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    try {
        $action = $input['action'] ?? '';
        if ($action === 'save') {
            // Lógica de save (igual ao anterior)
            $id = $input['id'] ?? null;
            $title = $input['title']; $slug = $input['slug']; $email = $input['recipient_email'];
            if ($id) {
                $pdo->prepare("UPDATE forms SET title=?, slug=?, recipient_email=? WHERE id=?")->execute([$title, $slug, $email, $id]);
            } else {
                $pdo->prepare("INSERT INTO forms (title, slug, recipient_email) VALUES (?, ?, ?)")->execute([$title, $slug, $email]);
            }
            $response['success'] = true;
        }
        if ($action === 'delete') {
            $pdo->prepare("DELETE FROM forms WHERE id = ?")->execute([$input['id']]);
            $response['success'] = true;
        }
    } catch (Exception $e) { $response['message'] = $e->getMessage(); }
    echo json_encode($response); exit;
}

// --- VIEW ---
$forms = $pdo->query("SELECT * FROM forms ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

Layout::header('Meus Formulários');
?>

<div x-data="formsManager(<?php echo htmlspecialchars(json_encode($forms)); ?>)">

    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-brand-dark dark:text-white">Gerenciador de Formulários</h1>
            <p class="text-slate-500 dark:text-slate-400">Configure para onde os contatos do site são enviados.</p>
        </div>
        <?php echo UI::button('Criar Formulário', 'button', 'brand-green', 'openModal()', '+'); ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="form in forms" :key="form.id">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6 flex flex-col relative group hover:shadow-md transition">
                
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="font-bold text-lg text-brand-dark dark:text-white" x-text="form.title"></h3>
                        <div class="text-xs font-mono text-brand-blue bg-blue-50 dark:bg-slate-900/50 px-2 py-1 rounded mt-1 inline-block" x-text="form.slug"></div>
                    </div>
                    <div class="flex gap-1">
                        <button @click="openModal(form)" class="text-slate-400 hover:text-brand-purple p-1 transition" title="Configurar"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg></button>
                        <button @click="deleteForm(form.id)" class="text-slate-400 hover:text-red-500 p-1 transition" title="Excluir"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                    </div>
                </div>

                <div class="mb-6 text-sm text-slate-500 dark:text-slate-400">
                    <span class="block text-xs uppercase font-bold text-slate-400 mb-1">Destino:</span>
                    <span x-text="form.recipient_email"></span>
                </div>

                <div class="mt-auto grid grid-cols-2 gap-3">
                    <a :href="'fields.php?form_id=' + form.id" class="flex items-center justify-center gap-2 bg-slate-50 dark:bg-slate-700 text-slate-600 dark:text-slate-200 hover:bg-brand-blue hover:text-brand-dark py-2 rounded-lg font-bold text-sm transition">
                        ✏️ Campos
                    </a>
                    <a :href="'submissions.php?form_id=' + form.id" class="flex items-center justify-center gap-2 bg-slate-50 dark:bg-slate-700 text-slate-600 dark:text-slate-200 hover:bg-brand-green hover:text-white py-2 rounded-lg font-bold text-sm transition">
                        ✉️ Envios
                    </a>
                </div>
            </div>
        </template>
    </div>

    <?php 
        $modalBody = '
            ' . UI::input('Nome do Formulário', 'currentForm.title', 'text', 'Ex: Contato Principal') . '
            <div class="mt-4">' . UI::input('Slug (ID Único)', 'currentForm.slug', 'text', 'contact-form') . '</div>
            <div class="mt-4">' . UI::input('E-mail de Destino', 'currentForm.recipient_email', 'email', 'admin@empresa.com') . '</div>
        ';
        $modalFooter = UI::button('Salvar', 'submit', 'brand-green', '', '') . UI::button('Cancelar', 'button', 'red', 'isModalOpen = false');
        
        echo "<form @submit.prevent='saveForm'>";
        echo UI::modal('isModalOpen', '<span x-text="currentForm.id ? \'Configurar\' : \'Novo Formulário\'"></span>', $modalBody, $modalFooter);
        echo "</form>";
    ?>

</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('formsManager', (initialForms) => ({
            forms: initialForms, isModalOpen: false, isLoading: false,
            currentForm: { id: null, title: '', slug: '', recipient_email: '' },

            openModal(form = null) {
                this.currentForm = form ? { ...form } : { id: null, title: '', slug: '', recipient_email: '' };
                this.isModalOpen = true;
            },

            async saveForm() {
                try {
                    const res = await fetch('forms.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'save', ...this.currentForm }) });
                    const data = await res.json();
                    if (data.success) window.location.reload(); else alert('Erro: ' + data.message);
                } catch (err) { alert('Erro.'); }
            },
            async deleteForm(id) {
                if(!confirm('Isso apagará todas as mensagens deste formulário. Continuar?')) return;
                try {
                    await fetch('forms.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'delete', id: id }) });
                    window.location.reload();
                } catch(err) { alert('Erro.'); }
            }
        }));
    });
</script>

<?php Layout::footer(); ?>