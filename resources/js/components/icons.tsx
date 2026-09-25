import type { JSX, SVGProps } from 'react';

export function GoogleIcon(props: SVGProps<SVGSVGElement>): JSX.Element {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true" {...props}>
            <path
                fill="#4285F4"
                d="M23.5 12.27c0-.85-.08-1.66-.22-2.45H12v4.64h6.45a5.52 5.52 0 0 1-2.39 3.62v3h3.87c2.26-2.09 3.57-5.16 3.57-8.81Z"
            />
            <path
                fill="#34A853"
                d="M12 24c3.24 0 5.96-1.07 7.94-2.91l-3.87-3c-1.08.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96H1.29v3.1A12 12 0 0 0 12 24Z"
            />
            <path
                fill="#FBBC05"
                d="M5.27 14.29A7.2 7.2 0 0 1 4.89 12c0-.8.14-1.57.38-2.29v-3.1H1.29a12 12 0 0 0 0 10.78l3.98-3.1Z"
            />
            <path
                fill="#EA4335"
                d="M12 4.75c1.76 0 3.35.61 4.6 1.8l3.42-3.42A11.98 11.98 0 0 0 12 0 12 12 0 0 0 1.29 6.61l3.98 3.1c.95-2.85 3.6-4.96 6.73-4.96Z"
            />
        </svg>
    );
}

export function SearchIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.8}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            {...props}
        >
            <circle cx="11" cy="11" r="7" />
            <path d="m21 21-4.35-4.35" />
        </svg>
    );
}
