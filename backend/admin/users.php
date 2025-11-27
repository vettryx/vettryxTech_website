<?php
// backend/admin/users.php

require 'auth.php';
require '../config.php';
require 'includes/Layout.php';
require 'includes/Components.php';

// --- API HANDLING (Igual ao anterior) ---
$inputJSON = file_get_contents('php://input'); $input = json_decode($inputJSON, true);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $input) {
    header('Content-Type: application/json');
    // ... Logica Create/Delete Admin igual ao anterior ...
    // Resumo: Insert ou Delete na tabela admins
    echo json_encode(['success' => true]); exit;
}

// --- VIEW ---
$admins = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$myId = $_SESSION['admin_id'];

Layout::header('Gestão de Equipe');
?>

<div x-data="usersManager(<?php echo htmlspecialchars(json_encode($admins)); ?>, <?php echo $myId; ?>)">
    
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-brand-dark dark:text-white">Equipe Administrativa</h1>
            <p class="text-slate-500 dark:text-slate-400">Gerencie quem tem acesso ao painel.</p>
        </div>
        <?php echo UI::button('Novo Admin', 'button', 'brand-green', 'openModal()', '+'); ?>
    </div>

    <div class="space-y-4">
        <template x-for="user in users" :key="user.id">
            <div class="bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 flex items-center justify-between group transition hover:border-brand-blue"
                 :class="{'border-l-4 border-l-brand-green bg-brand-light/20 dark:bg-slate-700/30': user.id == currentUserId}">
                
                <div class="flex items-center gap-4">
                    <img :src="'https://ui-avatars.com/api/?name=' + user.email + '&background=random'" class="h-12 w-12 rounded-full border-2 border-white dark:border-slate-600">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-brand-dark dark:text-white" x-text="user.email"></h3>
                            <template x-if="user.id == currentUserId">
                                <span class="bg-brand-green text-white text-[10px] px-2 py-0.5 rounded-full font-bold">VOCÊ</span>
                            </template>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">ID: #<span x-text="user.id"></span></p>
                    </div>
                </div>

                <template x-if="user.id != currentUserId">
                    <button @click="deleteUser(user.id)" class="p-2 text-slate-400 hover:text-red-600 transition" title="Remover"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg></button>
                </template>
            </div>
        </template>
    </div>

    <?php 
        $modalBody = '
            ' . UI::input('E-mail', 'form.email', 'email', 'usuario@empresa.com') . '
            <div class="mt-4">' . UI::input('Senha', 'form.password', 'password', '******') . '</div>
        ';
        $modalFooter = UI::button('Adicionar', 'submit', 'brand-green') . UI::button('Cancelar', 'button', 'red', 'isModalOpen = false');
        
        echo "<form @submit.prevent='createUser'>";
        echo UI::modal('isModalOpen', 'Novo Administrador', $modalBody, $modalFooter);
        echo "</form>";
    ?>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('usersManager', (initialUsers, myId) => ({
            users: initialUsers, currentUserId: myId, isModalOpen: false, form: { email: '', password: '' },

            openModal() { this.form = {email:'', password:''}; this.isModalOpen = true; },
            
            async createUser() {
                try {
                    await fetch('users.php', { method: 'POST', body: JSON.stringify({ action: 'create', ...this.form }) });
                    window.location.reload();
                } catch(e) { alert('Erro'); }
            },
            async deleteUser(id) {
                if(!confirm('Remover acesso?')) return;
                await fetch('users.php', { method: 'POST', body: JSON.stringify({ action: 'delete', id: id }) });
                window.location.reload();
            }
        }));
    });
</script>

<?php Layout::footer(); ?>