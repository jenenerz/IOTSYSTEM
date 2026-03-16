/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        'dark-green': '#1a472a',
        'card-pink': '#ffd8e3',
        'bg-cream': '#f7f3f1',
      },
    },
  },
  plugins: [],
}
