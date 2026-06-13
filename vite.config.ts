import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

// Build du SPA vers public/spa (servi par Laravel via SpaController).
export default defineConfig({
    base: '/spa/',
    plugins: [react()],
    resolve: {
        alias: { '@': resolve(__dirname, 'resources/js') },
    },
    build: {
        outDir: 'public/spa',
        emptyOutDir: true,
        rollupOptions: {
            output: {
                manualChunks: {
                    react: ['react', 'react-dom', 'react-router-dom'],
                    stripe: ['@stripe/react-stripe-js', '@stripe/stripe-js'],
                    query: ['@tanstack/react-query'],
                },
            },
        },
    },
    server: {
        proxy: {
            '/api': 'http://localhost:8000',
        },
    },
});
