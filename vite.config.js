import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            // A separate public.js entry (spec §3.2/§16) keeps the
            // respondent-facing public survey page's JS payload to just
            // what it needs, instead of shipping the entire admin
            // dashboard bundle to an anonymous mobile visitor.
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/public.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
