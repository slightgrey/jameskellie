import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import fs from 'node:fs';
import path from 'node:path';

const ORIGIN = 'http://localhost:5173';
const HOT = path.resolve(__dirname, 'hot');

/**
 * Writes /hot while the dev server runs so inc/Assets.php enqueues from Vite,
 * and removes it on exit and on build.
 */
function wordpressHotFile() {
  const remove = () => {
    if (fs.existsSync(HOT)) fs.rmSync(HOT);
  };
  return {
    name: 'jk-hot-file',
    configureServer(server) {
      server.httpServer?.once('listening', () => fs.writeFileSync(HOT, ORIGIN));
      ['SIGINT', 'SIGTERM', 'SIGHUP', 'exit'].forEach((sig) =>
        process.on(sig, () => {
          remove();
          if (sig !== 'exit') process.exit();
        }),
      );
    },
    buildStart() {
      if (this.meta.watchMode === false) remove();
    },
  };
}

export default defineConfig(({ command }) => ({
  base: command === 'build' ? '/wp-content/themes/jameskellie/dist/' : '/',
  plugins: [react(), tailwindcss(), wordpressHotFile()],
  publicDir: false,
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    manifest: true,
    target: 'es2020',
    rollupOptions: {
      input: {
        app: 'src/main.jsx',
        admin: 'src/admin/main.jsx',
        metabox: 'src/admin/metabox.js',
      },
    },
  },
  server: {
    host: 'localhost',
    port: 5173,
    strictPort: true,
    cors: true,
    origin: ORIGIN,
    watch: {
      ignored: ['**/vendor/**', '**/dist/**'],
    },
  },
}));
