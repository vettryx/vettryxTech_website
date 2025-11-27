// components/DynamicForm.tsx

'use client';

import { useState, useEffect } from 'react';
import { API_BASE_URL } from '../utils/api'; 

interface Field {
  id: number;
  label: string;
  name: string;
  type: string; 
  options: string | string[] | null; 
  placeholder?: string;
  is_required: number | boolean;
}

interface FormStructure {
  id: number;
  title: string;
}

export default function DynamicForm({ slug }: { slug: string }) {
  const [fields, setFields] = useState<Field[]>([]);
  const [formInfo, setFormInfo] = useState<FormStructure | null>(null);
  const [formData, setFormData] = useState<Record<string, string>>({});
  
  const [loading, setLoading] = useState(true);
  const [status, setStatus] = useState<'idle' | 'sending' | 'success' | 'error'>('idle');

  const renderOptions = (options: string | string[] | null) => {
    if (!options) return null;
    let opts: string[] = [];

    if (Array.isArray(options)) {
      opts = options; 
    } else {
      try {
        if (options.startsWith('[')) {
            opts = JSON.parse(options);
        } else {
            opts = options.split(',');
        }
      } catch {
        opts = options.split(',');
      }
    }

    return opts.map((opt, index) => {
      const val = typeof opt === 'string' ? opt.trim() : opt;
      return (
        <option key={index} value={val} className="bg-[#023047] text-white">
          {val}
        </option>
      );
    });
  };

  useEffect(() => {
    async function loadForm() {
      try {
        const baseUrl = typeof API_BASE_URL !== 'undefined' ? API_BASE_URL : 'http://localhost/backend';
        const res = await fetch(`${baseUrl}/api_form.php?slug=${slug}`);
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
    if(slug) loadForm();
  }, [slug]);

  const handleChange = (name: string, value: string) => {
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setStatus('sending');

    if (!formInfo) return;

    try {
      const baseUrl = typeof API_BASE_URL !== 'undefined' ? API_BASE_URL : 'http://localhost/backend';
      
      const res = await fetch(`${baseUrl}/api_submit.php`, {
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
        setFormData({});
        (e.target as HTMLFormElement).reset();
      } else {
        setStatus('error');
      }
    } catch (error) {
      console.error(error);
      setStatus('error');
    }
  };

  if (loading) return <div className="text-[#2ECC40] animate-pulse font-rajdhani">Carregando formulário...</div>;
  if (fields.length === 0) return <div className="text-gray-500 font-rajdhani">Formulário indisponível.</div>;

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      
      <div className="grid grid-cols-1 gap-6">
        {fields.map((field) => {
           const isReq = field.is_required === 1 || field.is_required === true;
           
           return (
            <div key={field.id}>
                <label className="block text-sm font-bold text-[#89D6FB] mb-2 uppercase font-rajdhani tracking-wider">
                {field.label} {isReq && <span className="text-[#FF8D37]">*</span>}
                </label>

                {field.type === 'textarea' ? (
                <textarea
                    required={isReq}
                    rows={4}
                    placeholder={field.placeholder || ''}
                    className="w-full bg-[#023047]/50 border border-[#2ECC40]/30 text-white rounded-lg p-4 focus:outline-none focus:border-[#2ECC40] focus:ring-1 focus:ring-[#2ECC40] transition-all placeholder:text-white/20 font-roboto"
                    onChange={(e) => handleChange(field.name, e.target.value)}
                    value={formData[field.name] || ''}
                />
                ) : field.type === 'select' ? (
                <div className="relative">
                    <select
                        required={isReq}
                        className="w-full bg-[#023047]/50 border border-[#2ECC40]/30 text-white rounded-lg p-4 appearance-none focus:outline-none focus:border-[#2ECC40] focus:ring-1 focus:ring-[#2ECC40] transition-all cursor-pointer font-roboto"
                        onChange={(e) => handleChange(field.name, e.target.value)}
                        value={formData[field.name] || ''}
                    >
                        <option value="" className="bg-[#023047]">Selecione...</option>
                        {renderOptions(field.options)}
                    </select>
                </div>
                ) : (
                <input
                    type={field.type} 
                    required={isReq}
                    placeholder={field.placeholder || ''}
                    className="w-full bg-[#023047]/50 border border-[#2ECC40]/30 text-white rounded-lg p-4 focus:outline-none focus:border-[#2ECC40] focus:ring-1 focus:ring-[#2ECC40] transition-all placeholder:text-white/20 font-roboto"
                    onChange={(e) => handleChange(field.name, e.target.value)}
                    value={formData[field.name] || ''}
                />
                )}
            </div>
          );
        })}
      </div>

      {status === 'success' && (
        <div className="p-4 bg-green-900/40 text-[#2ECC40] rounded border border-[#2ECC40]/50 text-center font-bold font-rajdhani animate-fade-in">
          ✅ Mensagem enviada com sucesso!
        </div>
      )}
      {status === 'error' && (
        <div className="p-4 bg-red-900/40 text-[#FF8D37] rounded border border-[#FF8D37]/50 text-center font-bold font-rajdhani animate-fade-in">
          ❌ Erro ao enviar. Tente novamente.
        </div>
      )}

      <button
        type="submit"
        disabled={status === 'sending' || status === 'success'}
        className="w-full py-4 px-6 bg-[#2ECC40] hover:bg-white hover:text-[#023047] text-[#023047] font-bold uppercase tracking-widest rounded transition-all shadow-[0_0_15px_rgba(46,204,64,0.2)] hover:shadow-[0_0_25px_rgba(46,204,64,0.4)] font-rajdhani disabled:opacity-50 disabled:cursor-not-allowed mt-4"
      >
        {status === 'sending' ? 'Enviando...' : 'Enviar Mensagem'}
      </button>
    </form>
  );
}