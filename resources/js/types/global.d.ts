import type { Auth } from '@/types/auth';
import type { FlashMessage } from '@/types';
import type { SettingsSnapshot } from '@/types/settings';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            settings: SettingsSnapshot | null;
            flash: FlashMessage;
            [key: string]: unknown;
        };
    }
}
