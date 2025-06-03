import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, './assets/js'),
      '@components': resolve(__dirname, './components'),
      '@store': resolve(__dirname, './store'),
      '@router': resolve(__dirname, './router'),
      '@assets': resolve(__dirname, './assets'),
      '@api': resolve(__dirname, './api')
    }
  },
  build: {
    outDir: 'assets/dist',
    assetsDir: '',
    manifest: true,
    rollupOptions: {
      input: 'assets/js/main.js'
    }
  },
  server: {
    cors: true, // Habilitar CORS para el servidor de desarrollo
    proxy: {
      '/api': {
        target: 'http://citas.salu.pro/api',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, '')
      }
    },
    // Configuración adicional para resolver problemas CORS
    headers: {
      "Access-Control-Allow-Origin": "*",
      "Access-Control-Allow-Methods": "GET, POST, PUT, DELETE, PATCH, OPTIONS",
      "Access-Control-Allow-Headers": "X-Requested-With, content-type, Authorization"
    }
  }
});
