/** @type {import('postcss-load-config').Config} */
const config = {
  plugins: {
    // AQUI ESTÁ A CORREÇÃO: Usamos o pacote novo com @
    '@tailwindcss/postcss': {},
    autoprefixer: {},
  },
};

export default config;