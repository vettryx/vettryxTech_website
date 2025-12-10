<?php
// backend/admin/contracts.php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/Layout.php';
require_once __DIR__ . '/includes/Components.php';

// Segurança
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: auth/login.php');
    exit;
}

$message = '';
$msgType = '';

// --- LÓGICA DE GERAÇÃO DE NÚMERO (AAAA/0000) ---
function generateContractNumber($pdo) {
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT contract_number FROM contracts WHERE contract_number LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute(["$year/%"]);
    $last = $stmt->fetchColumn();

    if ($last) {
        $parts = explode('/', $last);
        $seq = intval($parts[1]) + 1;
    } else {
        $seq = 1;
    }
    return $year . '/' . str_pad($seq, 4, '0', STR_PAD_LEFT);
}

// --- BACKEND (Salvar) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create' || $action === 'edit') {
            $client_id = $_POST['client_id'];
            $plan_id = $_POST['plan_id'];
            $payment_method_id = $_POST['payment_method_id'];
            $domain_url = $_POST['domain_url'];
            $monthly_price = str_replace(',', '.', $_POST['monthly_price']);
            $due_day = $_POST['due_day'];
            $issue_date = $_POST['issue_date'];
            $start_date = $_POST['start_date'];
            $status = $_POST['status'];

            if ($action === 'create') {
                // REMOVIDO: Lógica de Token e PDF
                $contract_number = generateContractNumber($pdo);

                // SQL corrigido: Não tenta mais salvar 'token'
                $sql = "INSERT INTO contracts (contract_number, client_id, plan_id, payment_method_id, domain_url, monthly_price, due_day, issue_date, start_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$contract_number, $client_id, $plan_id, $payment_method_id, $domain_url, $monthly_price, $due_day, $issue_date, $start_date, $status]);
                $message = "Contrato $contract_number criado com sucesso!";
            } else {
                $id = $_POST['id'];
                $sql = "UPDATE contracts SET client_id=?, plan_id=?, payment_method_id=?, domain_url=?, monthly_price=?, due_day=?, issue_date=?, start_date=?, status=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$client_id, $plan_id, $payment_method_id, $domain_url, $monthly_price, $due_day, $issue_date, $start_date, $status, $id]);
                $message = "Contrato atualizado!";
            }
            $msgType = 'success';

        } elseif ($action === 'delete') {
            $id = $_POST['id'];
            $pdo->prepare("DELETE FROM contracts WHERE id = ?")->execute([$id]);
            $message = "Contrato removido.";
            $msgType = 'success';
        }
    } catch (PDOException $e) {
        $message = "Erro: " . $e->getMessage();
        $msgType = 'error';
    }
}

// --- BUSCAR DADOS ---
$clients = $pdo->query("SELECT id, name FROM clients ORDER BY name ASC")->fetchAll();
$plans = $pdo->query("SELECT * FROM plans WHERE active = 1")->fetchAll();
$payment_methods = $pdo->query("SELECT * FROM payment_methods WHERE active = 1")->fetchAll();

$sqlList = "SELECT c.*, cl.name as client_name, p.name as plan_name 
            FROM contracts c 
            JOIN clients cl ON c.client_id = cl.id 
            JOIN plans p ON c.plan_id = p.id 
            ORDER BY c.created_at DESC";
$contracts = $pdo->query($sqlList)->fetchAll();

Layout::header('Gestão de Contratos');
?>

<div x-data="contractManager()">
    
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-brand-dark dark:text-white">Contratos</h1>
            <p class="text-slate-500 dark:text-slate-400">Gerencie planos e vigências.</p>
        </div>
        <button @click="openModal()" class="bg-brand-green hover:bg-green-600 text-white font-bold py-3 px-6 rounded-lg shadow-lg hover:shadow-green-500/30 transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Novo Contrato
        </button>
    </div>

    <?php if($message): ?>
        <div class="p-4 mb-6 rounded-lg text-center font-bold border <?php echo $msgType === 'success' ? 'bg-green-100 border-green-200 text-green-800' : 'bg-red-100 border-red-200 text-red-800'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-500 dark:text-slate-300 text-xs uppercase font-bold border-b border-slate-100 dark:border-slate-700">
                        <th class="p-4">Nº Contrato</th>
                        <th class="p-4">Cliente</th>
                        <th class="p-4">Plano / Site</th>
                        <th class="p-4">Valor</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <?php foreach($contracts as $contract): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition text-sm text-slate-700 dark:text-slate-300">
                        <td class="p-4 font-mono font-bold text-brand-blue"><?php echo $contract['contract_number']; ?></td>
                        <td class="p-4 font-bold"><?php echo htmlspecialchars($contract['client_name']); ?></td>
                        <td class="p-4">
                            <div class="font-bold text-brand-dark dark:text-white"><?php echo htmlspecialchars($contract['plan_name']); ?></div>
                            <a href="<?php echo htmlspecialchars($contract['domain_url']); ?>" target="_blank" class="text-xs text-slate-400 hover:text-brand-green truncate block max-w-[200px]"><?php echo htmlspecialchars($contract['domain_url']); ?></a>
                        </td>
                        <td class="p-4">R$ <?php echo number_format($contract['monthly_price'], 2, ',', '.'); ?></td>
                        <td class="p-4">
                            <?php 
                                $statusColors = [
                                    'draft' => 'bg-gray-100 text-gray-600',
                                    'active' => 'bg-green-100 text-green-700',
                                    'suspended' => 'bg-red-100 text-red-700',
                                    'cancelled' => 'bg-slate-200 text-slate-500 line-through'
                                ];
                                $color = $statusColors[$contract['status']] ?? 'bg-gray-100';
                                $label = [
                                    'draft' => 'Rascunho', 'active' => 'Ativo', 'suspended' => 'Suspenso', 'cancelled' => 'Cancelado'
                                ][$contract['status']] ?? $contract['status'];
                            ?>
                            <span class="px-2 py-1 rounded text-xs font-bold uppercase <?php echo $color; ?>"><?php echo $label; ?></span>
                        </td>
                        <td class="p-4 text-right flex justify-end gap-2">
                            <button @click='editContract(<?php echo json_encode($contract); ?>)' class="text-slate-400 hover:text-brand-blue p-1"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg></button>
                            <form method="POST" onsubmit="return confirm('Excluir este contrato?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $contract['id']; ?>">
                                <button class="text-slate-400 hover:text-red-500 p-1"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if(empty($contracts)): ?>
                <div class="p-8 text-center text-slate-400">Nenhum contrato encontrado.</div>
            <?php endif; ?>
        </div>
    </div>

    <div x-show="showModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-gray-900 bg-opacity-75"></div>
            <div x-show="showModal" x-transition.scale class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                
                <div class="bg-brand-dark px-4 py-3 sm:px-6 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-white" x-text="isEdit ? 'Editar Contrato' : 'Novo Contrato'"></h3>
                        <p x-show="!isEdit" class="text-xs text-brand-blue">Número será gerado automaticamente (Ex: <?php echo date('Y'); ?>/000X)</p>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-white">✕</button>
                </div>

                <form method="POST" class="p-6">
                    <input type="hidden" name="action" :value="isEdit ? 'edit' : 'create'">
                    <input type="hidden" name="id" x-model="form.id">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Cliente</label>
                            <select name="client_id" x-model="form.client_id" required class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:border-brand-green outline-none">
                                <option value="">Selecione...</option>
                                <?php foreach($clients as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Plano Escolhido</label>
                            <select name="plan_id" x-model="form.plan_id" @change="updatePrice" required class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:border-brand-green outline-none">
                                <option value="">Selecione...</option>
                                <?php foreach($plans as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>">
                                        <?php echo htmlspecialchars($p['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Domínio do Site (URL)</label>
                            <input type="url" name="domain_url" x-model="form.domain_url" required placeholder="https://..." class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:border-brand-green outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Valor Mensal (R$)</label>
                            <input type="number" step="0.01" name="monthly_price" x-model="form.monthly_price" required class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:border-brand-green outline-none font-bold text-brand-green">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Forma Pagamento</label>
                            <select name="payment_method_id" x-model="form.payment_method_id" required class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                                <?php foreach($payment_methods as $pm): ?>
                                    <option value="<?php echo $pm['id']; ?>"><?php echo htmlspecialchars($pm['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Dia Vencimento</label>
                            <input type="number" min="1" max="31" name="due_day" x-model="form.due_day" required class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Início Vigência</label>
                            <input type="date" name="start_date" x-model="form.start_date" required class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                         <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Data de Emissão</label>
                            <input type="date" name="issue_date" x-model="form.issue_date" required class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Status</label>
                            <select name="status" x-model="form.status" class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white font-bold">
                                <option value="draft">Rascunho</option>
                                <option value="active" class="text-green-600">Ativo</option>
                                <option value="suspended" class="text-red-600">Suspenso</option>
                                <option value="cancelled">Cancelado</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showModal = false" class="px-4 py-2 border rounded text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-brand-green text-white font-bold rounded hover:bg-green-600 transition">Salvar Contrato</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('contractManager', () => ({
        showModal: false, isEdit: false,
        plans: <?php echo json_encode($plans); ?>,
        form: { id: '', client_id: '', plan_id: '', payment_method_id: 1, domain_url: '', monthly_price: '', due_day: 10, issue_date: '', start_date: '', status: 'draft' },
        
        openModal() {
            this.isEdit = false;
            const today = new Date().toISOString().split('T')[0];
            this.form = { id: '', client_id: '', plan_id: '', payment_method_id: 1, domain_url: '', monthly_price: '', due_day: 10, issue_date: today, start_date: today, status: 'draft' };
            this.showModal = true;
        },
        editContract(contract) {
            this.isEdit = true;
            this.form = { ...contract };
            this.showModal = true;
        },
        updatePrice(e) {
            const planId = e.target.value;
            const plan = this.plans.find(p => p.id == planId);
            if(plan) this.form.monthly_price = plan.price;
        }
    }));
});
</script>

<?php Layout::footer(); ?>