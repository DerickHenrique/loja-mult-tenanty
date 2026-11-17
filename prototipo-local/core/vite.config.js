import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
  plugins: [laravel({
    input: ['resources/js/app.jsx', 'resources/css/app.css'],
    refresh: [
      { paths: ['app/Livewire/**', 'app/View/Components/**', 'lang/**', 'resources/lang/**', 'resources/views/**', 'routes/**'] },
      { paths: ['../tenants/**/*.json'] },
    ],
  }), react()],
  server: { host: '0.0.0.0', port: 5173, strictPort: true, origin: 'http://localhost:5173', cors: true },
});
