import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./app/**/*.php",
    "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
    "./vendor/laravel/jetstream/**/*.blade.php",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
};
