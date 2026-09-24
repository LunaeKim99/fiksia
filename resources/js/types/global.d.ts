import type { Auth } from '@/types/auth';
import type { FlashMessage } from '@/types';

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
            flash: FlashMessage;
            [key: string]: unknown;
        };
    }
}
