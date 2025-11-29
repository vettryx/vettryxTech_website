// utils/api.ts

// A URL da API (deve terminar com /api em produção)
export const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

// A URL do Site/Imagens (deve ser a raiz, sem /api)
export const IMG_BASE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'http://localhost:8000';

export interface Project {
  id: number;
  title: string;
  description: string;
  image_url: string;
  link: string;
}

export interface ContactForm {
  name: string;
  email: string;
  message: string;
}

export interface SiteSettings {
  site_title: string;
  site_description: string;
  site_logo: string;
  site_favicon: string;
  [key: string]: string;
}

export async function getProjects(): Promise<Project[]> {
  try {
    // Busca projetos sem cache
    const response = await fetch(`${API_BASE_URL}/api_projects.php`, { cache: 'no-store' });
    if (!response.ok) throw new Error('Erro API');
    return await response.json();
  } catch (error) {
    // CORREÇÃO 1: Usar a variável error para limpar o warning e ajudar no debug
    console.error("Erro ao buscar projetos:", error);
    return [];
  }
}

export async function submitContactForm(data: ContactForm): Promise<{ success: boolean; message: string }> {
  try {
    const response = await fetch(`${API_BASE_URL}/api_submit.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    });
    return await response.json();
  } catch (error) {
    // CORREÇÃO 2: Usar a variável error aqui também
    console.error("Erro ao enviar formulário:", error);
    return { success: false, message: 'Erro de conexão.' };
  }
}

export async function getSettings(): Promise<SiteSettings> {
  try {
    const response = await fetch(`${API_BASE_URL}/api_settings.php`, { 
      cache: 'no-store'
    });
    
    const json = await response.json();
    return json.data || {};
  } catch (error) {
    console.error("Erro ao buscar settings:", error);
    // Retorna vazio para evitar quebrar a página, mas loga o erro acima
    return { 
        site_title: 'André Ventura', 
        site_description: '', 
        site_logo: '', 
        site_favicon: '' 
    };
  }
}