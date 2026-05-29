/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.php',
    './modules/**/*.php',
    './includes/**/*.php',
    './assets/js/**/*.js',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        primary:    '#7c6dff',
        'primary-h':'#6254ee',
        accent:     '#a78bfa',
        'uni-dark': '#050814',
        'uni-card': '#0a0f22',
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      backdropBlur: {
        xs: '4px',
      },
      boxShadow: {
        'glass': '0 8px 32px rgba(0,0,0,0.5), 0 1px 0 rgba(255,255,255,0.04)',
        'glass-lg': '0 24px 80px rgba(0,0,0,0.65)',
        'glow-purple': '0 0 20px rgba(124,109,255,0.4)',
        'glow-blue': '0 0 20px rgba(37,99,235,0.5)',
        'glow-cyan': '0 0 20px rgba(6,182,212,0.4)',
      },
      animation: {
        'float-slow': 'float 8s ease-in-out infinite',
        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
      },
    },
  },
  plugins: [],
};
