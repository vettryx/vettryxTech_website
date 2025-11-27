// app/layout.tsx
import "./globals.css";
import { Rajdhani, Montserrat, Roboto } from "next/font/google";
import { API_BASE_URL } from "../utils/api";

const rajdhani = Rajdhani({ subsets: ["latin"], weight: ["300","400","500","600","700"], variable: "--font-rajdhani", display: "swap" });
const montserrat = Montserrat({ subsets: ["latin"], weight: ["300","400","500","600","700"], variable: "--font-montserrat", display: "swap" });
const roboto = Roboto({ subsets: ["latin"], weight: ["300","400","500","700"], variable: "--font-roboto", display: "swap" });

// Metadata dinâmico com fallback
export async function generateMetadata() {
  try {
    const res = await fetch(`${API_BASE_URL}/api_settings.php`, { next: { revalidate: 60 } });
    const json = await res.json();
    if (json.success && json.data) {
      const favicon = json.data.site_favicon ? `${API_BASE_URL}${json.data.site_favicon}` : "/favicon.ico";
      return {
        title: json.data.site_title || "André Ventura",
        description: json.data.site_description || "Desenvolvimento Web & WordPress",
        icons: { icon: favicon },
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