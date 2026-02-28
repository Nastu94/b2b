import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

/**
 * Configurazione Vite per Laravel.
 *
 * - Usa laravel-vite-plugin per:
 *   - output in public/build
 *   - manifest per @vite(...)
 *   - HMR in dev
 */
export default defineConfig({
  plugins: [
    laravel({
      // Entry points standard Jetstream/Livewire
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
  ],
});