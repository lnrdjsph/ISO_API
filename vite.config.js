// vite.config.js
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    server: {
        host: "127.0.0.1",
        port: 5173,
    },
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
    ],
    build: {
        chunkSizeWarningLimit: 1000,
        minify: "terser",
        terserOptions: {
            format: { comments: false },  // fixes: Information Disclosure - Suspicious Comments
        },
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ["axios", "lodash"],
                    charts: ["apexcharts"],
                },
            },
        },
    },
});