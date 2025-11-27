<?php
// backend/admin/includes/Components.php

class UI {
    
    // Botão Padronizado (Com suporte a Dark Mode)
    public static function button($text, $type = 'button', $color = 'brand-green', $alpineClick = '', $icon = '') {
        $colors = [
            // Verde 
            'brand-green' => 'bg-brand-green hover:bg-green-600 text-brand-dark hover:text-white', 
            
            // Azul Claro [cite: 6]
            'brand-blue'  => 'bg-brand-blue hover:bg-blue-400 text-brand-dark', 
            
            // Roxo [cite: 3]
            'brand-purple'=> 'bg-brand-purple hover:bg-purple-800 text-white',
            
            // Laranja [cite: 4]
            'brand-orange'=> 'bg-brand-orange hover:bg-orange-600 text-white',
            
            'red'   => 'bg-red-100 text-red-600 hover:bg-red-600 hover:text-white dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-700 dark:hover:text-white',
            
            // Botão "Branco" adapta para cinza escuro no Dark Mode
            'white' => 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700'
        ];
        
        $class = $colors[$color] ?? $colors['brand-green'];
        $click = $alpineClick ? "@click=\"$alpineClick\"" : '';
        
        return "<button type='$type' $click class='$class px-4 py-2 rounded-lg shadow-sm font-bold transition-all duration-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed transform active:scale-95'>
                    $icon <span>$text</span>
                </button>";
    }

    // Modal Padrão (Fundo escuro ou claro dependendo do tema)
    public static function modal($idModel, $title, $content, $footer) {
        return "
        <div x-show=\"$idModel\" x-cloak class=\"fixed inset-0 z-50 overflow-y-auto\">
            <div class=\"flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0\">
                
                <div x-show=\"$idModel\" x-transition.opacity class=\"fixed inset-0 bg-brand-dark/80 dark:bg-black/80 backdrop-blur-sm transition-opacity\" @click=\"$idModel = false\"></div>
                
                <div x-show=\"$idModel\" x-transition.scale 
                     class=\"inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-slate-100 dark:border-slate-700\">
                    
                    <div class=\"px-6 pt-6 pb-4\">
                        <h3 class=\"text-xl font-bold text-brand-dark dark:text-white mb-4 flex items-center gap-2\">
                            $title
                        </h3>
                        <div class=\"space-y-5 text-slate-600 dark:text-slate-300\">
                            $content
                        </div>
                    </div>
                    
                    <div class=\"bg-slate-50 dark:bg-slate-900/50 px-6 py-4 sm:flex sm:flex-row-reverse gap-2 border-t border-slate-100 dark:border-slate-700\">
                        $footer
                    </div>
                </div>
            </div>
        </div>";
    }

    // Input Padronizado
    public static function input($label, $model, $type = 'text', $placeholder = '') {
        return "
        <div>
            <label class=\"block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1 ml-1\">$label</label>
            <input type=\"$type\" x-model=\"$model\" placeholder=\"$placeholder\" 
                   class=\"w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg p-3 text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:border-brand-green focus:ring-1 focus:ring-brand-green transition\">
        </div>";
    }
}
?>