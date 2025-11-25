'use client';

import { useState, useEffect } from 'react';
import { API_BASE_URL } from '../utils/api';

interface Field {
  id: number;
  label: string;
  name: string;
  type: string; // text, email, textarea, select
  options: string | null;
  is_required: number;
}

interface FormStructure {
  id: number;
  title: string;
}

export default function DynamicForm({ slug }: { slug: string }) {
  const [fields, setFields] = useState<Field[]>([]);
  const [formInfo, setFormInfo] = useState<FormStructure | null>(null);
  
  // Estado para guardar os valores digitados (Objeto dinâmico)
  const [formData, setFormData] = useState<Record<string, string>>({});
  
  const [loading, setLoading] = useState(true);
  const [status, setStatus] = useState<'idle' | 'sending' | 'success' | 'error'>('idle');

  // 1. BUSCAR A ESTRUTURA DO FORMULÁRIO
  useEffect(() => {
    async function loadForm() {
      try {
        const res = await fetch(`${API_BASE_URL}/api_form.php?slug=${slug}`);
        const json = await res.json();
        
        if (json.success !== undefined && !json.success) {
            console.error("Erro API:", json);
            return;
        }

        if (json.fields) {
          setFields(json.fields);
          setFormInfo(json.form);
        }
      } catch (e) {
        console.error("Falha ao carregar form:", e);
      } finally {
        setLoading(false);
      }
    }
    loadForm();
  }, [slug]);

  // 2. GERENCIAR DIGITAÇÃO
  const handleChange = (name: string, value: string) => {
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  // 3. ENVIAR DADOS
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setStatus('sending');

    if (!formInfo) return;

    try {
      const res = await fetch(`${API_BASE_URL}/api_submit.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          form_id: formInfo.id,
          data: formData
        })
      });

      const result = await res.json();

      if (result.success) {
        setStatus('success');
        setFormData({}); // Limpa campos
      } else {
        setStatus('error');
      }
    } catch (error) {
      setStatus('error');
    }
  };

  if (loading) return <div className="text-brand-blue animate-pulse">Carregando formulário...</div>;
  if (fields.length === 0) return <div className="text-gray-500">Formulário indisponível no momento.</div>;

  return (
    <form onSubmit={handleSubmit} className="space-y-6 bg-brand-dark p-8 rounded-xl border border-white/5 shadow-2xl">
      
      {/* Renderiza os campos automaticamente */}
      <div className="grid grid-cols-1 gap-6">
        {fields.map((field) => (
          <div key={field.id}>
            <label className="block text-sm font-bold text-brand-blue mb-2 uppercase font-rajdhani">
              {field.label} {field.is_required === 1 && <span className="text-brand-orange">*</span>}
            </label>

            {/* Lógica de Renderização por Tipo */}
            {field.type === 'textarea' ? (
              <textarea
                required={field.is_required === 1}
                rows={4}
                className="w-full bg-[#012233] border border-white/10 text-white rounded p-4 focus:outline-none focus:border-brand-green focus:ring-1 focus:ring-brand-green transition-all"
                onChange={(e) => handleChange(field.name, e.target.value)}
                value={formData[field.name] || ''}
              />
            ) : field.type === 'select' ? (
              <select
                required={field.is_required === 1}
                className="w-full bg-[#012233] border border-white/10 text-white rounded p-4 focus:outline-none focus:border-brand-green"
                onChange={(e) => handleChange(field.name, e.target.value)}
                value={formData[field.name] || ''}
              >
                <option value="">Selecione...</option>
                {field.options?.split(',').map(opt => (
                  <option key={opt.trim()} value={opt.trim()}>{opt.trim()}</option>
                ))}
              </select>
            ) : (
              <input
                type={field.type} // text, email, tel
                required={field.is_required === 1}
                className="w-full bg-[#012233] border border-white/10 text-white rounded p-4 focus:outline-none focus:border-brand-green focus:ring-1 focus:ring-brand-green transition-all"
                onChange={(e) => handleChange(field.name, e.target.value)}
                value={formData[field.name] || ''}
              />
            )}
          </div>
        ))}
      </div>

      {/* Feedback de Envio */}
      {status === 'success' && (
        <div className="p-4 bg-green-900/50 text-green-200 rounded border border-green-800 text-center font-bold">
          ✅ Mensagem enviada com sucesso!
        </div>
      )}
      {status === 'error' && (
        <div className="p-4 bg-red-900/50 text-red-200 rounded border border-red-800 text-center">
          ❌ Erro ao enviar. Tente novamente.
        </div>
      )}

      <button
        type="submit"
        disabled={status === 'sending' || status === 'success'}
        className="w-full py-4 px-6 bg-brand-green hover:bg-white hover:text-brand-dark text-brand-dark font-bold uppercase tracking-widest rounded transition-all shadow-lg hover:shadow-brand-green/20 font-rajdhani disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {status === 'sending' ? 'Enviando...' : 'Enviar Mensagem'}
      </button>
    </form>
  );
}