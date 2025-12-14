/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./*.php",
    "./components/**/*.php",
    "./public/pages/**/*.php",
    "./public/**/*.php",
    "./**/*.html"
  ],

  safelist: [
    "bg-[#e6b949]",
    "bg-[#092363]",
    "text-[#e6b949]",
    "text-[#092363]",
    "hover:bg-[#e6b949]",
    "hover:text-[#092363]",
    "bg-fuchsia-100",
    "text-fuchsia-100"
  ]
};
