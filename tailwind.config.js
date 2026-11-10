/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./*.php', './pages/**/*.php', './includes/**/*.php', './assets/js/**/*.js'],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Manrope', 'sans-serif'],
        mono: ['DM Mono', 'monospace'],
      },
      colors: {
        ink: '#0a0a0a',
        panel: '#141414',
        mint: '#3ddc97',
      },
    },
  },
  plugins: [],
};
