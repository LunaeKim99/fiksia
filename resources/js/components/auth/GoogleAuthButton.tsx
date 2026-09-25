import type { JSX } from 'react';

import { GoogleIcon } from '@/components/icons';

export function GoogleAuthButton({ label }: { label: string }): JSX.Element {
    return (
        <a href="/auth/google/redirect" className="btn-secondary w-full">
            <GoogleIcon className="h-5 w-5 shrink-0" />
            {label}
        </a>
    );
}
