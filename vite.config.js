/**
 * vite.config.js — Latin Shop
 *
 * Estrategia de entrypoints:
 *  app      → CSS/JS global (todas las páginas públicas)
 *  admin    → CSS/JS exclusivo del panel de administración
 *  auth     → CSS de páginas de autenticación (login, registro, 2FA)
 *  support  → CSS del sistema de tickets de soporte
 *  gems     → JS de la calculadora de gemas (lazy, solo en esa página)
 *  bots     → JS del bot farming (lazy, solo en esa página)
 *  auth-js  → JS de autenticación (login, 2FA)
 *
 * Salida:
 *  frontend/assets/dist/[name]-[hash].[ext]
 *
 * PHP lee el manifest.json generado via includes/AssetManifest.php
 * para obtener las URLs con hash correctas.
 */

import { defineConfig } from 'vite';
import { resolve } from 'path';

const SRC_CSS = 'frontend/assets/css';
const SRC_JS  = 'frontend/assets/js';

export default defineConfig(({ mode }) => ({

  root: '.',
  base: '/',
  build: {
    outDir: 'frontend/assets/dist',

    emptyOutDir: true,

    manifest: true,

    sourcemap: mode === 'development' ? 'inline' : false,

    minify: mode === 'production' ? 'esbuild' : false,

    rollupOptions: {
      input: {
        
        app:     resolve(__dirname, 'build/entrypoints/app.js'),

        
        admin:   resolve(__dirname, 'build/entrypoints/admin.js'),

        
        auth:    resolve(__dirname, 'build/entrypoints/auth.js'),

        
        support: resolve(__dirname, 'build/entrypoints/support.js'),

        
        'gems-calc':           resolve(__dirname, `${SRC_JS}/gems-calc.js`),
        'bot-farming-vendedor': resolve(__dirname, `${SRC_JS}/bot-farming-vendedor.js`),
        'auth-login':          resolve(__dirname, `${SRC_JS}/auth-login.js`),
        'auth-2fa':            resolve(__dirname, `${SRC_JS}/auth-2fa.js`),
        'protection':          resolve(__dirname, `${SRC_JS}/protection.js`),
        'pwa':                 resolve(__dirname, `${SRC_JS}/pwa.js`),
      },

      output: {
        
        entryFileNames: '[name]-[hash].js',
        chunkFileNames: 'chunks/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          
          if (assetInfo.name?.endsWith('.css')) {
            return '[name]-[hash].css';
          }
          
          return 'assets/[name]-[hash][extname]';
        },
      },
    },

    chunkSizeWarningLimit: 500,

    target: ['es2020', 'chrome80', 'firefox78', 'safari13'],
  },
  css: {
    devSourcemap: mode === 'development',
  },
}));
