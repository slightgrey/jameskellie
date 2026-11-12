import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

/**
 * Block editor bundle (src/blocks/editor.jsx → dist/blocks/editor.js + editor.css).
 *
 * A classic IIFE, not an ES module: the blocks must be registered before the editor parses
 * the post. React and the JSX runtime come from WordPress (the `react` and
 * `react-jsx-runtime` script handles, WP 6.6+); the editor packages are read from window.wp.
 * Runs after the main build (npm run build), which empties dist/.
 */
export default defineConfig({
  plugins: [react(), tailwindcss()],
  publicDir: false,
  define: { 'process.env.NODE_ENV': JSON.stringify('production') },
  build: {
    outDir: 'dist/blocks',
    emptyOutDir: true,
    target: 'es2020',
    cssCodeSplit: false,
    lib: {
      entry: 'src/blocks/editor.jsx',
      formats: ['iife'],
      name: 'JKBlocksEditor',
      fileName: () => 'editor.js',
      cssFileName: 'editor',
    },
    rollupOptions: {
      external: ['react', 'react-dom', 'react/jsx-runtime'],
      output: {
        globals: {
          react: 'React',
          'react-dom': 'ReactDOM',
          'react/jsx-runtime': 'ReactJSXRuntime',
        },
      },
    },
  },
});
