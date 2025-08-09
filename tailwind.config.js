/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  darkMode: ['class', '[data-theme="dark"]'], // Add this line
  theme: {
    extend: {
      fontFamily: {
        heading: ["Inter", "sans-serif"], // Headings
        body: ["Roboto", "sans-serif"], // Other text
      },
    },
  },
  daisyui: {
    // themes: ["light", "dark"],
    themes: [
      {
        light: {
          ...require("daisyui/src/theming/themes")["light"],
          primary: "#2596be",
          secondary: "#163e5d",
        },
      },
      "dark"
    ],
  },
  plugins: [
    require('daisyui'),
    require('@tailwindcss/typography'),
    require('@tailwindcss/forms'),
  ],
};
