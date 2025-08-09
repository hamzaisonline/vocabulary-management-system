import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

// Correctly import TailwindCSS and PostCSS plugins
import tailwindcss from 'tailwindcss';
import autoprefixer from 'autoprefixer';

export default defineConfig({
  plugins: [
    vue(), // Vue plugin for Vite
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'), // Alias for src folder
    },
  },
  css: {
    postcss: {
      plugins: [
        tailwindcss(), // TailwindCSS plugin for PostCSS
        autoprefixer(), // Autoprefixer plugin for cross-browser compatibility
      ],
    },
  },
  server: {
    port: 3000, // Custom port for development server
    open: true, // Automatically open the browser
  },
  build: {
    outDir: 'dist', // Output directory for production builds
    sourcemap: true, // Enable sourcemaps for easier debugging in production
  },
});
