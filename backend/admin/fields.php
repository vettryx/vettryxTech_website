<?php
// backend/admin/fields.php

require 'auth.php';
require '../config.php';
require 'includes/Layout.php';
require 'includes/Components.php';

$form_id = $_GET['form_id'] ?? null;
if (!$form_id) die("ID ausente.");

// --- API HANDLING ---
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $input) {
    header('Content-Type: application/json');
    try {
        if (isset($input['action']) && $input['action'] === 'save_field') {
            $label = $input['label'];
            $name = $input['name'] ?: strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $label)));
            $type = $input['type'];
            $options = isset($input['options']) ? json_encode($input['options']) : null; 
            $required = $input['required'] ? 1 : 0;
            $placeholder = $input['placeholder'] ?? '';
            $id = $input['id'] ?? null;

            if ($id) {
                $stmt = $pdo->prepare("UPDATE form_fields SET label=?, name=?, type=?, options=?, is_required=?, placeholder=? WHERE id=? AND form_id=?");
                $stmt->execute([$label, $name, $type, $options, $required, $placeholder, $id, $form_id]);
            } else {
                $stmtOrder = $pdo->prepare("SELECT MAX(order_index) as max_ord FROM form_fields WHERE form_id = ?"); $stmtOrder->execute([$form_id]);
                $nextOrder = ($stmtOrder->fetch()['max_ord'] ?? 0) + 1;
                $stmt = $pdo->prepare("INSERT INTO form_fields (form_id, label, name, type, options, is_required, placeholder, order_index) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$form_id, $label, $name, $type, $options, $required, $placeholder, $nextOrder]);
            }
            echo json_encode(['success' => true]); exit;
        }

        if (isset($input['action']) && $input['action'] === 'reorder') {
            foreach ($input['order'] as $index => $fieldId) {
                $pdo->prepare("UPDATE form_fields SET order_index = ? WHERE id = ? AND form_id = ?")->execute([$index, $fieldId, $form_id]);
            }
            echo json_encode(['success' => true]); exit;
        }

        if (isset($input['action']) && $input['action'] === 'delete') {
            $pdo->prepare("DELETE FROM form_fields WHERE id = ? AND form_id = ?")->execute([$input['id'], $form_id]);
            echo json_encode(['success' => true]); exit;
        }
    } catch (Exception $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()]); exit; }
}

// --- VIEW ---
$form = $pdo->prepare("SELECT * FROM forms WHERE id = ?"); $form->execute([$form_id]); $form = $form->fetch();
$fields = $pdo->prepare("SELECT * FROM form_fields WHERE form_id = ? ORDER BY order_index ASC"); $fields->execute([$form_id]); $fields = $fields->fetchAll(PDO::FETCH_ASSOC);

foreach ($fields as &$f) {
    $f['options'] = json_decode($f['options'] ?? '[]');
    if (json_last_error() !== JSON_ERROR_NONE && !empty($f['options'])) $f['options'] = explode(',', $f['options']);
} unset($f);

Layout::header('Campos: ' . $form['title']);
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<style>.ghost-class { opacity: 0.5; background: #2ECC40; border: 2px dashed #023047; }</style>

<div x-data="formBuilder(<?php echo htmlspecialchars(json_encode($fields)); ?>, <?php echo $form_id; ?>)">

    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Editor de Formulário</div>
            <h1 class="text-2xl font-bold text-brand-dark dark:text-white"><?php echo htmlspecialchars($form['title']); ?></h1>
        </div>
        <div class="flex gap-2">
            <a href="forms.php" class="px-4 py-2 text-slate-500 hover:text-brand-blue font-bold text-sm">Voltar</a>
            <?php echo UI::button('Novo Campo', 'button', 'brand-green', 'openModal()', '+'); ?>
        </div>
    </div>
    
    <div id="fields-list" class="space-y-3 max-w-3xl mx-auto">
        <template x-if="fields.length === 0">
            <div class="text-center py-16 bg-white dark:bg-slate-800 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-700">
                <p class="text-slate-400">Nenhum campo criado ainda.</p>
                <button @click="openModal()" class="text-brand-green font-bold hover:underline mt-2">Adicionar o primeiro</button>
            </div>
        </template>

        <template x-for="(field, index) in fields" :key="field.id">
            <div :data-id="field.id" class="bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 hover:border-brand-blue dark:hover:border-brand-blue transition flex items-center justify-between group">
                <div class="flex items-center gap-4 flex-1">
                    <div class="drag-handle text-slate-300 dark:text-slate-600 hover:text-brand-dark dark:hover:text-white cursor-grab active:cursor-grabbing p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" /></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-lg text-brand-dark dark:text-white" x-text="field.label"></span>
                            <span x-show="field.is_required" class="text-red-500 text-[10px] font-bold bg-red-50 dark:bg-red-900/30 px-2 py-0.5 rounded-full">OBRIGATÓRIO</span>
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 flex gap-2 font-mono mt-1">
                            <span class="uppercase bg-slate-100 dark:bg-slate-700 px-1.5 rounded text-brand-blue font-bold" x-text="getTypeLabel(field.type)"></span>
                            <span x-text="field.name"></span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click="openModal(field)" class="p-2 text-slate-400 hover:text-brand-purple transition" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg></button>
                    <button @click="deleteField(field.id)" class="p-2 text-slate-400 hover:text-red-600 transition" title="Excluir"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg></button>
                </div>
            </div>
        </template>
    </div>

    <?php 
        $modalBody = '
            ' . UI::input('Rótulo (Label)', 'currentField.label', 'text') . '
            
            <div class="mt-4">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1 ml-1">Tipo de Campo</label>
                <select x-model="currentField.type" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg p-3 text-slate-800 dark:text-white focus:outline-none focus:border-brand-green focus:ring-1 focus:ring-brand-green transition">
                    <option value="text">Texto Curto</option>
                    <option value="email">E-mail</option>
                    <option value="tel">Telefone/WhatsApp</option>
                    <option value="textarea">Mensagem Longa</option>
                    <option value="select">Lista (Select)</option>
                    <option value="radio">Múltipla Escolha (Radio)</option>
                    <option value="checkbox">Caixa de Seleção (Checkbox)</option>
                </select>
            </div>

            <div class="mt-4" x-show="[\'text\',\'email\',\'tel\',\'textarea\'].includes(currentField.type)">
                ' . UI::input('Texto de Ajuda (Placeholder)', 'currentField.placeholder', 'text') . '
            </div>

            <div class="mt-4 bg-slate-50 dark:bg-slate-900/50 p-4 rounded-lg border border-slate-200 dark:border-slate-700" x-show="[\'select\',\'radio\',\'checkbox\'].includes(currentField.type)">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Opções da Lista</label>
                <ul class="space-y-2">
                    <template x-for="(opt, idx) in currentField.options" :key="idx">
                        <li class="flex gap-2">
                            <input type="text" x-model="currentField.options[idx]" class="flex-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded px-2 py-1 text-sm text-slate-700 dark:text-white focus:border-brand-green outline-none">
                            <button @click="removeOption(idx)" class="text-red-500 hover:text-red-700 px-2 font-bold">×</button>
                        </li>
                    </template>
                </ul>
                <button @click="addOption()" class="text-sm text-brand-blue font-bold hover:underline mt-2 flex items-center gap-1">+ Adicionar Opção</button>
            </div>

            <div class="mt-4 flex items-center gap-2">
                <input id="req" type="checkbox" x-model="currentField.required" class="h-5 w-5 text-brand-green rounded border-gray-300 focus:ring-brand-green">
                <label for="req" class="text-sm font-bold text-slate-700 dark:text-slate-300">Preenchimento Obrigatório</label>
            </div>
        ';
        
        $modalFooter = UI::button('Salvar Campo', 'submit', 'brand-green', '', '') . UI::button('Cancelar', 'button', 'red', 'isModalOpen = false');

        echo "<form @submit.prevent='saveField'>";
        echo UI::modal('isModalOpen', '<span x-text="currentField.id ? \'Editar Campo\' : \'Novo Campo\'"></span>', $modalBody, $modalFooter);
        echo "</form>";
    ?>

</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('formBuilder', (initialFields, formId) => ({
            fields: initialFields, formId: formId, isModalOpen: false,
            currentField: { id: null, label: '', type: 'text', options: [], required: false, placeholder: '', name: '' },

            init() {
                Sortable.create(document.getElementById('fields-list'), {
                    animation: 150, handle: '.drag-handle', ghostClass: 'ghost-class',
                    onEnd: () => this.reorderFields()
                });
            },
            getTypeLabel(type) {
                const map = {'text':'Texto','email':'Email','textarea':'Texto Longo','select':'Lista','radio':'Seleção','checkbox':'Checkbox','tel':'Telefone'};
                return map[type] || type;
            },
            openModal(field = null) {
                if (field) { this.currentField = JSON.parse(JSON.stringify(field)); if(!Array.isArray(this.currentField.options)) this.currentField.options=[]; this.currentField.required = !!field.is_required; }
                else { this.currentField = { id: null, label: '', type: 'text', options: [], required: true, placeholder: '', name: '' }; }
                this.isModalOpen = true;
            },
            addOption() { this.currentField.options.push('Nova Opção'); },
            removeOption(idx) { this.currentField.options.splice(idx, 1); },
            async saveField() {
                if(!this.currentField.label) return alert('Nome obrigatório');
                await this.sendRequest({ action: 'save_field', ...this.currentField });
                window.location.reload();
            },
            async deleteField(id) {
                if(!confirm('Excluir este campo?')) return;
                await this.sendRequest({ action: 'delete', id: id });
                this.fields = this.fields.filter(f => f.id !== id);
            },
            async reorderFields() {
                const order = [];
                document.querySelectorAll('#fields-list > div').forEach(el => order.push(el.getAttribute('data-id')));
                await this.sendRequest({ action: 'reorder', order: order });
            },
            async sendRequest(data) {
                return await (await fetch(`fields.php?form_id=${this.formId}`, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) })).json();
            }
        }));
    });
</script>

<?php Layout::footer(); ?>