// @ts-check
import { defineConfig } from 'astro/config';
import tailwindcss from '@tailwindcss/vite';

// https://astro.build/config
export default defineConfig({
  site: 'https://fisiopilatesatlas.es',
  output: 'static',
  build: {
    format: 'file', // Genera fisioterapia.html en vez de fisioterapia/index.html
  },
  vite: {
    plugins: [tailwindcss()]
  }
});
