// utils/api.ts

// Define a URL base dependendo se está no seu PC ou na Hostinger
export const API_BASE_URL = 'https://test.asventura.com.br';

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

/**
 * Busca a lista de projetos da API PHP.
 */
export async function getProjects(): Promise<Project[]> {
  try {
    // Aponta para o arquivo que criamos na raiz: api_projects.php
    const response = await fetch(`${API_BASE_URL}/api_projects.php`, {
      cache: 'no-store', // Garante dados frescos
    });
    
    if (!response.ok) {
      throw new Error(`Erro ao buscar projetos: ${response.statusText}`);
    }

    // O seu PHP retorna o array direto, então não verificamos "result.success"
    const data = await response.json();
    return data as Project[];

  } catch (error) {
    console.error('Erro ao conectar com a API de Projetos:', error);
    // Retorna array vazio para não quebrar o site se a API falhar
    return [];
  }
}

/**
 * Envia os dados do formulário de contato.
 */
export async function submitContactForm(data: ContactForm): Promise<{ success: boolean; message: string }> {
  try {
    // Aponta para o arquivo de contato (que vamos garantir que existe depois)
    const response = await fetch(`${API_BASE_URL}/contact.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    });

    const result = await response.json();
    return result;
  } catch (error) {
    console.error('Erro ao enviar formulário:', error);
    return { success: false, message: 'Erro de conexão com o servidor.' };
  }
}