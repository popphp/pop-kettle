import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        tailwindcss(),
        react()
    ],
    build: {
        outDir: 'public/assets',
        rollupOptions: {
            input: 'app/assets/js/app.jsx',
            output: {
                entryFileNames: 'js/app.js',
                assetFileNames: (assetInfo) => {
                    return assetInfo.name === 'app.css' ? 'css/app.css' : 'assets/[name][extname]';
                }
            }
        }
    }
});
