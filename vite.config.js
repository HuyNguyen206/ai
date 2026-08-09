import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
        // Bind all interfaces: vite runs in a container and the host browser reaches
        // it through the published port, which the default loopback bind refuses.
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        // The browser resolves HMR on the host, not inside the container.
        hmr: {
            host: 'localhost',
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
