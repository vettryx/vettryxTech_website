import type { Config } from "tailwindcss";

const config: Config = {
  content: [
    "./app/**/*.{js,ts,jsx,tsx,mdx}",
    "./components/**/*.{js,ts,jsx,tsx,mdx}",
  ],
  theme: {
    extend: {
      colors: {
        // AQUI ESTÁ A DEFINIÇÃO CENTRALIZADA
        brand: {
          dark: "#023047",    // Azul Profundo
          green: "#2ECC40",   // Verde Neon
          purple: "#5D3FD3",  // Roxo
          orange: "#FF8D37",  // Laranja
          blue: "#89D6FB",    // Azul Claro
          light: "#D4F0FC",   // Azul Gelo
        },
      },
      fontFamily: {
        rajdhani: ["var(--font-rajdhani)", "sans-serif"],
        montserrat: ["var(--font-montserrat)", "sans-serif"],
        roboto: ["var(--font-roboto)", "sans-serif"],
      },
    },
  },
  plugins: [],
};
export default config;