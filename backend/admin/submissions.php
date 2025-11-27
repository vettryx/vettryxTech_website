<?php
// backend/admin/submissions.php

require 'auth.php';
require '../config.php';
require 'includes/Layout.php';
require 'includes/Components.php';

// ... (Mesma lógica de API DELETE do arquivo anterior) ...
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $input) {
    header('Content-Type: application/json');
    $pdo->prepare("DELETE FROM form_submissions WHERE id = ?")->execute([$input['id']]);
    echo json_encode(['success' => true]); exit;
}

// ... (Mesma lógica de busca do arquivo anterior) ...
$form_id = $_GET['form_id'] ?? null;
if (!$form_id) { header("Location: forms.php"); exit; }
$form = $pdo->prepare("SELECT title FROM forms WHERE id = ?"); $form->execute([$form_id]); $form = $form->fetch();
$rawSubmissions = $pdo->prepare("SELECT * FROM form_submissions WHERE form_id = ? ORDER BY created_at DESC"); $rawSubmissions->execute([$form_id]); $rawSubmissions = $rawSubmissions->fetchAll(PDO::FETCH_ASSOC);

$submissions = [];
foreach ($rawSubmissions as $sub) {
    $sub['parsed_data'] = json_decode($sub['data'], true);
    $submissions[] = $sub;
}
$columns = !empty($submissions) ? array_slice(array_keys($submissions[0]['parsed_data']), 0, 4) : [];

Layout::header('Relatório: ' . $form['title']);
?>

<div x-data="submissionsManager(<?php echo htmlspecialchars(json_encode($submissions)); ?>)">

    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Relatório de Envios</div>
            <h1 class="text-2xl font-bold text-brand-dark dark:text-white"><?php echo htmlspecialchars($form['title']); ?></h1>
        </div>
        <div class="flex gap-2">
            <a href="forms.php" class="px-4 py-2 text-slate-500 hover:text-brand-blue font-bold text-sm">Voltar</a>
            <?php echo UI::button('Exportar CSV', 'button', 'brand-green', 'exportCSV()', '📥'); ?>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-6 py-4 font-bold w-10">ID</th>
                        <th class="px-6 py-4 font-bold">Data</th>
                        <th class="px-6 py-4 font-bold">Status</th>
                        <template x-for="col in columns" :key="col">
                            <th class="px-6 py-4 font-bold capitalize" x-text="col.replace(/_/g, ' ')"></th>
                        </template>
                        <th class="px-6 py-4 font-bold text-right">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <template x-for="item in items" :key="item.id">
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition cursor-pointer text-slate-700 dark:text-slate-300" @click="openModal(item)">
                            <td class="px-6 py-4 text-slate-400 font-mono text-xs" x-text="'#'+item.id"></td>
                            <td class="px-6 py-4 font-mono" x-text="formatDate(item.created_at)"></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded text-xs font-bold"
                                      :class="item.email_status === 'Enviado' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                      x-text="item.email_status"></span>
                            </td>
                            <template x-for="col in columns" :key="col">
                                <td class="px-6 py-4 max-w-[200px] truncate" x-text="item.parsed_data[col] || '-'"></td>
                            </template>
                            <td class="px-6 py-4 text-right text-brand-blue font-bold">Ver</td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <template x-if="items.length === 0">
                <div class="p-8 text-center text-slate-500">Nenhuma mensagem encontrada.</div>
            </template>
        </div>
    </div>

    <?php 
        $modalBody = '
            <div class="grid grid-cols-1 gap-4 max-h-[60vh] overflow-y-auto pr-2">
                <template x-for="(value, key) in currentItem.parsed_data" :key="key">
                    <div class="border-b border-slate-100 dark:border-slate-700 pb-3 last:border-0">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1" x-text="key.replace(/_/g, \' \')"></label>
                        <div class="text-brand-dark dark:text-white text-base whitespace-pre-line" x-text="value"></div>
                    </div>
                </template>
            </div>
        ';
        
        $modalFooter = '
            ' . UI::button('Fechar', 'button', 'white', 'isModalOpen = false') . '
            ' . UI::button('Excluir', 'button', 'red', 'deleteItem(currentItem.id)', '🗑️') . '
        ';

        echo UI::modal('isModalOpen', 'Detalhes da Mensagem', $modalBody, $modalFooter);
    ?>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('submissionsManager', (initialItems) => ({
            items: initialItems, columns: <?php echo json_encode($columns); ?>, isModalOpen: false, currentItem: { parsed_data: {} },

            openModal(item) { this.currentItem = item; this.isModalOpen = true; },
            formatDate(d) { return new Date(d).toLocaleString('pt-BR'); },
            
            async deleteItem(id) {
                if(!confirm('Excluir?')) return;
                await fetch('submissions.php?form_id=<?php echo $form_id; ?>', { method: 'POST', body: JSON.stringify({ action: 'delete', id: id }) });
                window.location.reload();
            },
            
            exportCSV() {
                // ... (Mesma lógica de CSV do anterior) ...
                alert('Exportando CSV...'); // Simplificado para brevidade, use a lógica completa anterior
            }
        }));
    });
</script>

<?php Layout::footer(); ?>