import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // lighter app (owner, 23 Oct 2026): one stylesheet and one script; the template demos are gone
                'resources/css/app.css',
                'resources/js/index.js',
            ],
            refresh: true,
        }),
    ],
});
