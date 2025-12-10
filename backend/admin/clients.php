<?php
// backend/admin/clients.php
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

// --- FUNÇÕES PHP (Visualização na Lista) ---
function formatDoc($doc) {
    $doc = preg_replace("/[^0-9]/", "", $doc);
    $len = strlen($doc);
    if ($len == 11) return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $doc);
    if ($len == 14) return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $doc);
    return $doc;
}
function formatPhone($phone) {
    $phone = preg_replace("/[^0-9]/", "", $phone);
    if (strlen($phone) == 11) return preg_replace("/(\d{2})(\d{5})(\d{4})/", "(\$1) \$2-\$3", $phone);
    if (strlen($phone) == 10) return preg_replace("/(\d{2})(\d{4})(\d{4})/", "(\$1) \$2-\$3", $phone);
    return $phone;
}
function formatCep($cep) {
    $cep = preg_replace("/[^0-9]/", "", $cep);
    if (strlen($cep) == 8) return preg_replace("/(\d{5})(\d{3})/", "\$1-\$2", $cep);
    return $cep;
}

// --- BACKEND (Salvar) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create' || $action === 'edit') {
            // Limpa formatação antes de salvar
            $type = $_POST['type'];
            $name = $_POST['name'];
            $document = preg_replace('/\D/', '', $_POST['document']); 
            $email = $_POST['email'];
            $billing_email = $_POST['billing_email'];
            $phone = preg_replace('/\D/', '', $_POST['phone']); 
            $website = $_POST['website'];
            $zip = preg_replace('/\D/', '', $_POST['zip_code']); 
            $addr = $_POST['address'];
            $num = $_POST['number'];
            $comp = $_POST['complement'];
            $neigh = $_POST['neighborhood'];
            $city = $_POST['city'];
            $state = $_POST['state'];

            if ($action === 'create') {
                $sql = "INSERT INTO clients (type, name, document, email, billing_email, phone, website, zip_code, address, number, complement, neighborhood, city, state) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$type, $name, $document, $email, $billing_email, $phone, $website, $zip, $addr, $num, $comp, $neigh, $city, $state]);
                $message = "Cliente cadastrado!";
            } else {
                $id = $_POST['id'];
                $sql = "UPDATE clients SET type=?, name=?, document=?, email=?, billing_email=?, phone=?, website=?, zip_code=?, address=?, number=?, complement=?, neighborhood=?, city=?, state=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$type, $name, $document, $email, $billing_email, $phone, $website, $zip, $addr, $num, $comp, $neigh, $city, $state, $id]);
                $message = "Cliente atualizado!";
            }
            $msgType = 'success';
        } elseif ($action === 'delete') {
            $id = $_POST['id'];
            $pdo->prepare("DELETE FROM clients WHERE id = ?")->execute([$id]);
            $message = "Cliente removido.";
            $msgType = 'success';
        }
    } catch (PDOException $e) {
        $message = "Erro: " . $e->getMessage();
        $msgType = 'error';
    }
}

$clients = $pdo->query("SELECT * FROM clients ORDER BY created_at DESC")->fetchAll();

Layout::header('Gestão de Clientes');
?>

<div x-data="clientManager()">
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-brand-dark dark:text-white">Meus Clientes</h1>
            <p class="text-slate-500 dark:text-slate-400">Gerencie sua carteira e dados cadastrais.</p>
        </div>
        <button @click="openModal()" class="bg-brand-green hover:bg-green-600 text-white font-bold py-3 px-6 rounded-lg shadow-lg hover:shadow-green-500/30 transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Novo Cliente
        </button>
    </div>

    <?php if($message): ?>
        <div class="p-4 mb-6 rounded-lg text-center font-bold border <?php echo $msgType === 'success' ? 'bg-green-100 border-green-200 text-green-800' : 'bg-red-100 border-red-200 text-red-800'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach($clients as $client): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm border border-slate-100 dark:border-slate-700 hover:border-brand-green/50 transition relative group">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 rounded-lg <?php echo $client['type'] == 'PJ' ? 'bg-blue-50 text-blue-600' : 'bg-purple-50 text-purple-600'; ?>">
                        <span class="font-bold text-xs"><?php echo $client['type']; ?></span>
                    </div>
                    <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition">
                        <button @click='editClient(<?php echo json_encode($client); ?>)' class="text-slate-400 hover:text-brand-blue" title="Editar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
                        </button>
                        <form method="POST" onsubmit="return confirm('Tem certeza?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $client['id']; ?>">
                            <button type="submit" class="text-slate-400 hover:text-red-500" title="Excluir">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                            </button>
                        </form>
                    </div>
                </div>
                <h3 class="font-bold text-lg text-brand-dark dark:text-white mb-1 truncate"><?php echo htmlspecialchars($client['name']); ?></h3>
                
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 font-mono select-all"><?php echo formatDoc($client['document']); ?></p>
                
                <div class="space-y-1 text-sm text-slate-600 dark:text-slate-300">
                    <p class="truncate">📧 <?php echo htmlspecialchars($client['email']); ?></p>
                    <p>📱 <?php echo formatPhone($client['phone']); ?></p>
                    <?php if($client['zip_code']): ?>
                        <p class="text-xs text-slate-400">📍 <?php echo htmlspecialchars($client['city']) . '/' . htmlspecialchars($client['state']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div x-show="showModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-gray-900 bg-opacity-75"></div>
            <div x-show="showModal" x-transition.scale class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                
                <div class="bg-brand-dark px-4 py-3 sm:px-6 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-medium text-white" x-text="isEdit ? 'Editar Cliente' : 'Novo Cliente'"></h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-white">✕</button>
                </div>

                <form method="POST" class="p-6">
                    <input type="hidden" name="action" :value="isEdit ? 'edit' : 'create'">
                    <input type="hidden" name="id" x-model="form.id">

                    <div class="mb-4">
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Tipo de Pessoa</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer p-2 border rounded hover:bg-slate-50 dark:hover:bg-slate-700 dark:border-slate-600">
                                <input type="radio" name="type" value="PJ" x-model="form.type" @change="form.document = ''" class="text-brand-green focus:ring-brand-green">
                                <span class="dark:text-white font-bold">Pessoa Jurídica (PJ)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer p-2 border rounded hover:bg-slate-50 dark:hover:bg-slate-700 dark:border-slate-600">
                                <input type="radio" name="type" value="PF" x-model="form.type" @change="form.document = ''" class="text-brand-green focus:ring-brand-green">
                                <span class="dark:text-white font-bold">Pessoa Física (PF)</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1" x-text="form.type === 'PJ' ? 'Razão Social' : 'Nome Completo'"></label>
                            <input type="text" name="name" x-model="form.name" required class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:border-brand-green focus:outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1" x-text="form.type === 'PJ' ? 'CNPJ' : 'CPF'"></label>
                            <input type="text" 
                                   name="document" 
                                   x-model="form.document"
                                   @input="maskDocument"
                                   required 
                                   class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:border-brand-green focus:outline-none placeholder-slate-500"
                                   :placeholder="form.type === 'PJ' ? '00.000.000/0000-00' : '000.000.000-00'">
                        </div>
                    </div>

                    <div class="mb-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <h4 class="font-bold text-brand-green text-sm mb-3 uppercase">Endereço</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">CEP</label>
                                <div class="relative">
                                    <input type="text" name="zip_code" x-model="form.zip_code" @input="maskZip" @blur="fetchAddress" placeholder="00000-000" class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:border-brand-green focus:outline-none">
                                    <div x-show="loadingZip" class="absolute right-2 top-2 text-brand-green text-xs">...</div>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Rua / Logradouro</label>
                                <input type="text" name="address" x-model="form.address" required class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white bg-slate-50">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Número</label>
                                <input type="text" name="number" x-model="form.number" required class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Complemento</label>
                                <input type="text" name="complement" x-model="form.complement" class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Bairro</label>
                                <input type="text" name="neighborhood" x-model="form.neighborhood" required class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white bg-slate-50">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Cidade</label>
                                <input type="text" name="city" x-model="form.city" required class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white bg-slate-50">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Estado (UF)</label>
                                <input type="text" name="state" x-model="form.state" required maxlength="2" class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white bg-slate-50">
                            </div>
                        </div>
                    </div>

                    <div class="mb-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <h4 class="font-bold text-brand-blue text-sm mb-3 uppercase">Contatos & Web</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">E-mail Principal</label>
                                <input type="email" name="email" x-model="form.email" required class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">E-mail Cobrança (Opcional)</label>
                                <input type="email" name="billing_email" x-model="form.billing_email" class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">WhatsApp</label>
                                <input type="text" name="phone" x-model="form.phone" @input="maskPhone" required class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white" placeholder="(00) 00000-0000">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Site / URL</label>
                                <input type="url" name="website" x-model="form.website" class="w-full px-3 py-2 border rounded dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showModal = false" class="px-4 py-2 border rounded text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-brand-green text-white font-bold rounded hover:bg-green-600 transition">Salvar Cliente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('clientManager', () => ({
        showModal: false, isEdit: false, loadingZip: false,
        form: { id: '', type: 'PJ', name: '', document: '', email: '', billing_email: '', phone: '', website: '', zip_code: '', address: '', number: '', complement: '', neighborhood: '', city: '', state: '' },
        
        openModal() { this.isEdit = false; this.resetForm(); this.showModal = true; },
        editClient(client) {
            this.isEdit = true;
            this.form = { ...client };
            // Aplica máscaras ao carregar para edição
            this.maskDocument();
            this.maskPhone();
            this.maskZip();
            this.showModal = true;
        },
        resetForm() {
            this.form = { id: '', type: 'PJ', name: '', document: '', email: '', billing_email: '', phone: '', website: '', zip_code: '', address: '', number: '', complement: '', neighborhood: '', city: '', state: '' };
        },
        
        // MÁSCARAS MANUAIS (INFALÍVEIS)
        maskDocument() {
            let v = this.form.document.replace(/\D/g, '');
            if (this.form.type === 'PJ') {
                if (v.length > 14) v = v.substring(0, 14);
                v = v.replace(/^(\d{2})(\d)/, '$1.$2');
                v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
                v = v.replace(/(\d{4})(\d)/, '$1-$2');
            } else {
                if (v.length > 11) v = v.substring(0, 11);
                v = v.replace(/(\d{3})(\d)/, '$1.$2');
                v = v.replace(/(\d{3})(\d)/, '$1.$2');
                v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            }
            this.form.document = v;
        },
        maskPhone() {
            let v = this.form.phone.replace(/\D/g, '');
            v = v.substring(0, 11);
            v = v.replace(/^(\d{2})(\d)/g, '($1) $2');
            v = v.replace(/(\d)(\d{4})$/, '$1-$2');
            this.form.phone = v;
        },
        maskZip() {
            let v = this.form.zip_code.replace(/\D/g, '');
            v = v.substring(0, 8);
            v = v.replace(/^(\d{5})(\d)/, '$1-$2');
            this.form.zip_code = v;
        },

        async fetchAddress() {
            const cep = this.form.zip_code.replace(/\D/g, '');
            if (cep.length === 8) {
                this.loadingZip = true;
                try {
                    const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                    const data = await res.json();
                    if (!data.erro) {
                        this.form.address = data.logradouro;
                        this.form.neighborhood = data.bairro;
                        this.form.city = data.localidade;
                        this.form.state = data.uf;
                    } else { alert('CEP não encontrado.'); }
                } catch (e) { console.error(e); } finally { this.loadingZip = false; }
            }
        }
    }));
});
</script>

<?php Layout::footer(); ?>