import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
  plugins: [laravel({ input: ['resources/js/app.jsx', 'resources/css/app.css'], refresh: true }), react()],
  server: { host: '0.0.0.0', port: 5173, strictPort: true, origin: 'http://localhost:5173', cors: true },
});
