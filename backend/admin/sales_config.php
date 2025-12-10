<?php
// backend/admin/sales_config.php

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/Layout.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: auth/login.php');
    exit;
}

$message = '';
$msgType = '';

// --- LÓGICA DE BACKEND ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // 1. AÇÕES DE PLANOS
        if ($action === 'save_plan') {
            $id = $_POST['id'] ?? '';
            $name = $_POST['name'];
            $price = str_replace(',', '.', $_POST['price']);
            $desc = $_POST['description'];

            if ($id) {
                $pdo->prepare("UPDATE plans SET name=?, price=?, description=? WHERE id=?")->execute([$name, $price, $desc, $id]);
                $message = "Plano atualizado!";
            } else {
                $pdo->prepare("INSERT INTO plans (name, price, description) VALUES (?, ?, ?)")->execute([$name, $price, $desc]);
                $message = "Novo plano criado!";
            }
            $msgType = 'success';
        }
        elseif ($action === 'delete_plan') {
            $pdo->prepare("DELETE FROM plans WHERE id=?")->execute([$_POST['id']]);
            $message = "Plano removido."; $msgType = 'success';
        }

        // 2. AÇÕES DE PAGAMENTO
        elseif ($action === 'save_payment') {
            $id = $_POST['id'] ?? '';
            $name = $_POST['name'];
            $details = $_POST['details'];

            if ($id) {
                $pdo->prepare("UPDATE payment_methods SET name=?, details=? WHERE id=?")->execute([$name, $details, $id]);
                $message = "Método atualizado!";
            } else {
                $pdo->prepare("INSERT INTO payment_methods (name, details) VALUES (?, ?)")->execute([$name, $details]);
                $message = "Novo método criado!";
            }
            $msgType = 'success';
        }
        elseif ($action === 'delete_payment') {
            $pdo->prepare("DELETE FROM payment_methods WHERE id=?")->execute([$_POST['id']]);
            $message = "Método removido."; $msgType = 'success';
        }

    } catch (PDOException $e) {
        $message = "Erro: " . $e->getMessage();
        $msgType = 'error';
    }
}

// Buscar Dados
$plans = $pdo->query("SELECT * FROM plans ORDER BY price ASC")->fetchAll();
$payments = $pdo->query("SELECT * FROM payment_methods")->fetchAll();

Layout::header('Configurações de Vendas');
?>

<div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
    <div>
        <h1 class="text-3xl font-bold text-brand-dark dark:text-white">Configurações de Vendas</h1>
        <p class="text-slate-500 dark:text-slate-400">Gerencie seus produtos e formas de recebimento.</p>
    </div>
</div>

<?php if($message): ?>
    <div class="p-4 mb-6 rounded-lg text-center font-bold border <?php echo $msgType === 'success' ? 'bg-green-100 border-green-200 text-green-800' : 'bg-red-100 border-red-200 text-red-800'; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8" x-data="salesConfig()">
    
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold text-brand-dark dark:text-white flex items-center gap-2">
                <span class="p-2 bg-blue-100 text-blue-600 rounded-lg">📦</span> Planos de Serviço
            </h2>
            <button @click="openPlanModal()" class="text-sm bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded transition">+ Novo Plano</button>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700 text-slate-500 uppercase font-bold text-xs">
                    <tr>
                        <th class="p-4">Nome</th>
                        <th class="p-4">Preço</th>
                        <th class="p-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <?php foreach($plans as $plan): ?>
                    <tr class="group hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="p-4">
                            <div class="font-bold text-brand-dark dark:text-white"><?php echo htmlspecialchars($plan['name']); ?></div>
                            <div class="text-xs text-slate-400 truncate max-w-[200px]"><?php echo htmlspecialchars($plan['description']); ?></div>
                        </td>
                        <td class="p-4 font-mono font-bold text-green-600">R$ <?php echo number_format($plan['price'], 2, ',', '.'); ?></td>
                        <td class="p-4 text-right flex justify-end gap-2 opacity-60 group-hover:opacity-100">
                            <button @click='editPlan(<?php echo json_encode($plan); ?>)' class="text-blue-500 hover:text-blue-700">Editar</button>
                            <form method="POST" onsubmit="return confirm('Excluir este plano?');">
                                <input type="hidden" name="action" value="delete_plan">
                                <input type="hidden" name="id" value="<?php echo $plan['id']; ?>">
                                <button class="text-red-400 hover:text-red-600">✕</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold text-brand-dark dark:text-white flex items-center gap-2">
                <span class="p-2 bg-green-100 text-green-600 rounded-lg">💳</span> Formas de Pagamento
            </h2>
            <button @click="openPayModal()" class="text-sm bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded transition">+ Nova Forma</button>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700 text-slate-500 uppercase font-bold text-xs">
                    <tr>
                        <th class="p-4">Nome</th>
                        <th class="p-4">Detalhes (Chave/Instrução)</th>
                        <th class="p-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <?php foreach($payments as $pay): ?>
                    <tr class="group hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="p-4 font-bold text-brand-dark dark:text-white"><?php echo htmlspecialchars($pay['name']); ?></td>
                        <td class="p-4 text-slate-500 text-xs font-mono truncate max-w-[150px]"><?php echo htmlspecialchars($pay['details']); ?></td>
                        <td class="p-4 text-right flex justify-end gap-2 opacity-60 group-hover:opacity-100">
                            <button @click='editPay(<?php echo json_encode($pay); ?>)' class="text-blue-500 hover:text-blue-700">Editar</button>
                            <form method="POST" onsubmit="return confirm('Excluir este método?');">
                                <input type="hidden" name="action" value="delete_payment">
                                <input type="hidden" name="id" value="<?php echo $pay['id']; ?>">
                                <button class="text-red-400 hover:text-red-600">✕</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="showPlanModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/75 p-4" style="display:none">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md overflow-hidden" @click.away="showPlanModal = false">
            <div class="bg-blue-600 px-4 py-3 text-white font-bold flex justify-between">
                <span x-text="planForm.id ? 'Editar Plano' : 'Novo Plano'"></span>
                <button @click="showPlanModal = false">✕</button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="save_plan">
                <input type="hidden" name="id" x-model="planForm.id">
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nome do Plano</label>
                    <input type="text" name="name" x-model="planForm.name" required class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Preço Mensal (R$)</label>
                    <input type="number" step="0.01" name="price" x-model="planForm.price" required class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Descrição Curta</label>
                    <textarea name="description" x-model="planForm.description" rows="3" class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white"></textarea>
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded">Salvar Plano</button>
            </form>
        </div>
    </div>

    <div x-show="showPayModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/75 p-4" style="display:none">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md overflow-hidden" @click.away="showPayModal = false">
            <div class="bg-green-600 px-4 py-3 text-white font-bold flex justify-between">
                <span x-text="payForm.id ? 'Editar Pagamento' : 'Nova Forma de Pagamento'"></span>
                <button @click="showPayModal = false">✕</button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="save_payment">
                <input type="hidden" name="id" x-model="payForm.id">
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nome (Ex: PIX)</label>
                    <input type="text" name="name" x-model="payForm.name" required class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Detalhes (Chave PIX / Instruções)</label>
                    <textarea name="details" x-model="payForm.details" rows="3" class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white" placeholder="Ex: Chave CNPJ 00.000..."></textarea>
                </div>
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 rounded">Salvar Método</button>
            </form>
        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('salesConfig', () => ({
        showPlanModal: false,
        showPayModal: false,
        planForm: { id: '', name: '', price: '', description: '' },
        payForm: { id: '', name: '', details: '' },

        openPlanModal() { this.planForm = { id: '', name: '', price: '', description: '' }; this.showPlanModal = true; },
        editPlan(plan) { this.planForm = { ...plan }; this.showPlanModal = true; },

        openPayModal() { this.payForm = { id: '', name: '', details: '' }; this.showPayModal = true; },
        editPay(pay) { this.payForm = { ...pay }; this.showPayModal = true; }
    }));
});
</script>

<?php Layout::footer(); ?>