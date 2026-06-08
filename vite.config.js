import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    server: {
        host: "127.0.0.1",
        port: 5173,
    },
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/views/forms/sales-order/css/sales-order.css",
                "resources/views/forms/sales-order/js/sales-order.js",
                "resources/js/pages/products/index.js",
                "resources/js/pages/products/import.js",
                "resources/js/pages/products/create.js",
                "resources/js/pages/users/index.js",
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@sales-order-js': '/resources/views/forms/sales-order/js',
        },
    },
    build: {
        chunkSizeWarningLimit: 1000,
        minify: "terser",
        terserOptions: {
            format: { comments: false },
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