<?php
// backend/admin/contract_view.php

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/Layout.php';

// Validação de Segurança
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: auth/login.php');
    exit;
}

$id = $_GET['id'] ?? '';
if (!$id) {
    header('Location: contracts.php');
    exit;
}

$message = '';
$msgType = '';

// --- LÓGICA DE BACKEND ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // 1. ADICIONAR ADITIVO / REAJUSTE
        if ($action === 'add_addendum') {
            $type = $_POST['type'];
            $date = $_POST['effective_date'];
            $desc = $_POST['description'];
            $old_val = !empty($_POST['old_value']) ? $_POST['old_value'] : null;
            $new_val = !empty($_POST['new_value']) ? str_replace(',', '.', $_POST['new_value']) : null;

            // Salva o histórico na tabela nova
            $stmt = $pdo->prepare("INSERT INTO contract_addendums (contract_id, type, description, old_value, new_value, effective_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, $type, $desc, $old_val, $new_val, $date]);

            // SE TIVER VALOR NOVO, ATUALIZA O PREÇO DO CONTRATO AUTOMATICAMENTE
            if ($new_val) {
                $pdo->prepare("UPDATE contracts SET monthly_price = ? WHERE id = ?")->execute([$new_val, $id]);
                $message = "Aditivo registrado e valor mensal atualizado para R$ $new_val!";
            } else {
                $message = "Evento registrado no histórico.";
            }
            $msgType = 'success';
        }

        // 2. SALVAR OBSERVAÇÕES
        elseif ($action === 'save_obs') {
            $obs = $_POST['observations'];
            $pdo->prepare("UPDATE contracts SET observations = ? WHERE id = ?")->execute([$obs, $id]);
            $message = "Observações atualizadas.";
            $msgType = 'success';
        }

        // 3. EXCLUIR ADITIVO (Caso tenha errado)
        elseif ($action === 'delete_addendum') {
            $addendum_id = $_POST['addendum_id'];
            $pdo->prepare("DELETE FROM contract_addendums WHERE id = ?")->execute([$addendum_id]);
            $message = "Registro removido.";
            $msgType = 'success';
        }

    } catch (PDOException $e) {
        $message = "Erro: " . $e->getMessage();
        $msgType = 'error';
    }
}

// --- BUSCAR DADOS COMPLETOS DO CONTRATO ---
$sql = "SELECT c.*, cl.name as client_name, p.name as plan_name, pm.name as payment_name 
        FROM contracts c
        JOIN clients cl ON c.client_id = cl.id
        JOIN plans p ON c.plan_id = p.id
        JOIN payment_methods pm ON c.payment_method_id = pm.id
        WHERE c.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$contract = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contract) die("Contrato não encontrado.");

// --- BUSCAR HISTÓRICO DE ADITIVOS ---
$addendums = $pdo->prepare("SELECT * FROM contract_addendums WHERE contract_id = ? ORDER BY effective_date DESC, created_at DESC");
$addendums->execute([$id]);
$history = $addendums->fetchAll();

Layout::header('Detalhes do Contrato');
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
    <div>
        <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
            <a href="contracts.php" class="hover:text-brand-green">← Voltar para Lista</a>
            <span class="text-slate-300">|</span>
            <span class="font-mono text-xs bg-slate-100 px-2 py-1 rounded">ID: <?php echo $contract['id']; ?></span>
        </div>
        <h1 class="text-3xl font-bold text-brand-dark dark:text-white flex items-center gap-3">
            <?php echo $contract['contract_number']; ?>
            <span class="text-base font-normal px-2 py-1 rounded text-white text-sm <?php echo $contract['status'] == 'active' ? 'bg-green-500' : 'bg-slate-500'; ?>">
                <?php echo strtoupper($contract['status']); ?>
            </span>
        </h1>
        <p class="text-slate-500 font-bold text-lg"><?php echo htmlspecialchars($contract['client_name']); ?></p>
    </div>
</div>

<?php if($message): ?>
    <div class="p-4 mb-6 rounded-lg text-center font-bold border <?php echo $msgType === 'success' ? 'bg-green-100 border-green-200 text-green-800' : 'bg-red-100 border-red-200 text-red-800'; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="contractView()">

    <div class="lg:col-span-2 space-y-6">
        
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
            <h2 class="font-bold text-lg text-brand-dark dark:text-white mb-4 border-b pb-2 dark:border-slate-700">Resumo Financeiro</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Valor Atual</label>
                    <p class="font-bold text-green-600 text-2xl">R$ <?php echo number_format($contract['monthly_price'], 2, ',', '.'); ?></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Vencimento</label>
                    <p class="font-bold text-slate-700 dark:text-slate-200">Dia <?php echo $contract['due_day']; ?></p>
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-400 uppercase">Plano Ativo</label>
                    <p class="font-bold text-slate-700 dark:text-slate-200"><?php echo $contract['plan_name']; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
            <div class="flex justify-between items-center mb-4 border-b pb-2 dark:border-slate-700">
                <h2 class="font-bold text-lg text-brand-dark dark:text-white flex items-center gap-2">
                    📜 Linha do Tempo
                </h2>
                <button @click="openAditivoModal()" class="text-sm bg-blue-100 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-200 font-bold transition flex items-center gap-2">
                    <span>+</span> Adicionar Evento / Reajuste
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-slate-400 border-b dark:border-slate-700 text-xs uppercase">
                            <th class="pb-2">Data Vigência</th>
                            <th class="pb-2">Tipo</th>
                            <th class="pb-2">Descrição</th>
                            <th class="pb-2 text-right">Mudança de Valor</th>
                            <th class="pb-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        <tr class="opacity-70 bg-slate-50 dark:bg-slate-900/50">
                            <td class="py-3 px-2 text-slate-500 font-mono"><?php echo date('d/m/Y', strtotime($contract['start_date'])); ?></td>
                            <td class="py-3 font-bold text-slate-600">Início de Contrato</td>
                            <td class="py-3 text-slate-500">Criação do contrato original.</td>
                            <td class="py-3 text-right font-mono text-slate-500">-</td>
                            <td></td>
                        </tr>

                        <?php foreach($history as $h): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition">
                            <td class="py-3 px-2 text-brand-dark dark:text-white font-bold font-mono"><?php echo date('d/m/Y', strtotime($h['effective_date'])); ?></td>
                            <td class="py-3">
                                <span class="px-2 py-1 rounded text-xs font-bold <?php echo $h['new_value'] ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'; ?>">
                                    <?php echo $h['type']; ?>
                                </span>
                            </td>
                            <td class="py-3 text-slate-600 dark:text-slate-300 max-w-xs truncate" title="<?php echo htmlspecialchars($h['description']); ?>">
                                <?php echo htmlspecialchars($h['description']); ?>
                            </td>
                            <td class="py-3 text-right font-mono">
                                <?php if($h['new_value']): ?>
                                    <div class="flex flex-col items-end leading-tight">
                                        <span class="text-xs text-slate-400 line-through">R$ <?php echo number_format($h['old_value'], 2, ',', '.'); ?></span>
                                        <span class="text-green-600 font-bold">R$ <?php echo number_format($h['new_value'], 2, ',', '.'); ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-slate-300">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 text-right">
                                <form method="POST" onsubmit="return confirm('ATENÇÃO: Apagar este registro NÃO reverte o preço atual do contrato automaticamente. Deseja continuar?');">
                                    <input type="hidden" name="action" value="delete_addendum">
                                    <input type="hidden" name="addendum_id" value="<?php echo $h['id']; ?>">
                                    <button class="text-slate-300 hover:text-red-500 p-1" title="Excluir Registro">✕</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-yellow-50 dark:bg-slate-800 rounded-xl shadow-sm border border-yellow-200 dark:border-slate-700 p-6 sticky top-6">
            <h2 class="font-bold text-lg text-yellow-800 dark:text-yellow-500 mb-2 flex items-center gap-2">
                📝 Notas Internas
            </h2>
            <p class="text-xs text-yellow-700/70 dark:text-slate-400 mb-4">Informações visíveis apenas para administradores.</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="save_obs">
                <textarea name="observations" rows="15" class="w-full p-4 text-sm bg-white dark:bg-slate-900 border border-yellow-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 dark:text-white shadow-inner leading-relaxed" placeholder="- Cliente prefere contato via WhatsApp&#10;- Falar com Mariana no financeiro&#10;- Senha do FTP: ..."><?php echo htmlspecialchars($contract['observations'] ?? ''); ?></textarea>
                <div class="mt-4 text-right">
                    <button type="submit" class="bg-yellow-400 hover:bg-yellow-500 text-yellow-900 px-6 py-2 rounded-lg font-bold transition shadow-sm w-full md:w-auto">Salvar Anotações</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="showModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/75 p-4 backdrop-blur-sm" x-cloak>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all" @click.away="showModal = false">
            
            <div class="bg-blue-600 px-6 py-4 flex justify-between items-center text-white">
                <h3 class="font-bold text-lg">Registrar Evento / Aditivo</h3>
                <button @click="showModal = false" class="hover:text-blue-200">✕</button>
            </div>

            <form method="POST" class="p-6 space-y-5">
                <input type="hidden" name="action" value="add_addendum">
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Tipo de Registro</label>
                    <select name="type" x-model="type" class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:border-blue-500 outline-none">
                        <option value="Reajuste Financeiro">💰 Reajuste Financeiro (IGP-M/IPCA)</option>
                        <option value="Mudança de Plano">📦 Mudança de Plano (Upgrade/Down)</option>
                        <option value="Aditivo Contratual">📄 Aditivo de Cláusula</option>
                        <option value="Registro">ℹ️ Apenas Registro</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Data de Vigência</label>
                    <input type="date" name="effective_date" required class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white" value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div x-show="type === 'Reajuste Financeiro' || type === 'Mudança de Plano'" class="bg-slate-50 dark:bg-slate-900 p-4 rounded-lg border dark:border-slate-700">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 mb-1">Valor Atual</label>
                            <input type="text" name="old_value" readonly value="<?php echo $contract['monthly_price']; ?>" class="w-full bg-transparent font-mono text-slate-500 border-b border-transparent">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-green-600 mb-1">Novo Valor (R$)</label>
                            <input type="number" step="0.01" name="new_value" placeholder="0.00" class="w-full px-2 py-1 border border-green-200 rounded text-green-700 font-bold focus:ring-green-500 outline-none">
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-2">* Isso atualizará o valor principal do contrato.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Detalhes / Justificativa</label>
                    <textarea name="description" required rows="3" class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:border-blue-500 outline-none" placeholder="Ex: Reajuste anual de 4.5% conforme índice acumulado..."></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded transition">Cancelar</button>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 shadow-lg transition transform hover:-translate-y-0.5">Confirmar e Salvar</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('contractView', () => ({
        showModal: false,
        type: 'Reajuste Financeiro',
        openAditivoModal() { this.showModal = true; }
    }));
});
</script>

<?php Layout::footer(); ?>