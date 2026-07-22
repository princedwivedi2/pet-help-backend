/** @type {import('tailwindcss').Config} */
// Production Tailwind config for the RESPAW landing page (resources/views/welcome.blade.php).
// Rebuild:  npx tailwindcss -c tailwind.config.js -i resources/css/landing.css -o public/css/landing.css --minify
module.exports = {
  content: ['./resources/views/**/*.blade.php'],
  theme: {
    extend: {
      colors: {
        ink:   '#161412',
        dusk:  '#24201d',
        fog:   '#ebe6de',
        clay:  '#b86f3f',
        amber: '#d89d62',
        moss:  '#4f6957',
        cream: '#f7f3ed',
      },
      fontFamily: {
        display: ['Cormorant Garamond', 'serif'],
        body:    ['Sora', 'sans-serif'],
      },
      boxShadow: {
        soft: '0 25px 70px rgba(22,20,18,0.14)',
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
};
