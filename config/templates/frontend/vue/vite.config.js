import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    publicDir: false,
    plugins: [
        tailwindcss(),
        vue()
    ],
    build: {
        outDir: 'public/assets',
        rollupOptions: {
            input: 'app/assets/js/app.js',
            output: {
                entryFileNames: 'js/app.js',
                assetFileNames: (assetInfo) => {
                    return assetInfo.name === 'app.css' ? 'css/app.css' : 'assets/[name][extname]';
                }
            }
        }
    }
});
