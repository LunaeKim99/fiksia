import { createInertiaApp } from '@inertiajs/react';
import { ThemeProvider } from '@/components/ThemeProvider';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    layout: () => ThemeProvider,
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: {
        color: '#4B5563',
    },
});
