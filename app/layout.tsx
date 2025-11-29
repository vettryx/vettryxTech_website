// app/layout.tsx

import "./globals.css";
import { Rajdhani, Montserrat, Roboto } from "next/font/google";
import { API_BASE_URL, IMG_BASE_URL } from "../utils/api";

const rajdhani = Rajdhani({ subsets: ["latin"], weight: ["300","400","500","600","700"], variable: "--font-rajdhani", display: "swap" });
const montserrat = Montserrat({ subsets: ["latin"], weight: ["300","400","500","600","700"], variable: "--font-montserrat", display: "swap" });
const roboto = Roboto({ subsets: ["latin"], weight: ["300","400","500","700"], variable: "--font-roboto", display: "swap" });

export async function generateMetadata() {
  try {
    // Adicionei um timestamp na requisição para garantir que o Next não cacheie o JSON infinitamente
    const res = await fetch(`${API_BASE_URL}/api_settings.php?t=${Date.now()}`, { next: { revalidate: 60 } });
    const json = await res.json();
    
    if (json.success && json.data) {
      // TRUQUE 1: Removemos barras duplicadas caso IMG_BASE_URL termine com / e o favicon comece com /
      const cleanBaseUrl = IMG_BASE_URL.replace(/\/$/, "");
      const cleanPath = json.data.site_favicon.startsWith('/') ? json.data.site_favicon : `/${json.data.site_favicon}`;
      
      // TRUQUE 2: Adicionamos ?v=timestamp no final da imagem. 
      // Isso obriga o navegador a baixar a imagem nova e ignorar o cache antigo.
      const faviconUrl = json.data.site_favicon 
        ? `${cleanBaseUrl}${cleanPath}?v=${Date.now()}` 
        : "/favicon.ico";

      return {
        title: json.data.site_title || "André Ventura",
        description: json.data.site_description || "Desenvolvimento Web & WordPress",
        icons: {
          icon: faviconUrl,
          shortcut: faviconUrl, // Alguns navegadores preferem 'shortcut'
          apple: faviconUrl,    // Para iPhone/iPad
        },
      };
    }
  } catch (e) {
    console.error("Erro ao carregar settings para metadata", e);
  }

  return {
    title: "André Ventura",
    description: "Desenvolvimento Web Profissional",
    icons: { icon: "/favicon.ico" },
  };
}

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="pt-BR">
      <body className={`${rajdhani.variable} ${montserrat.variable} ${roboto.variable} antialiased overflow-x-hidden`}>
        {children}
      </body>
    </html>
  );
}