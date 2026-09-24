import type { ButtonHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?: 'primary' | 'secondary';
};

export function Button({
    variant = 'primary',
    className,
    type = 'button',
    ...props
}: ButtonProps) {
    return (
        <button
            type={type}
            className={cn(
                variant === 'primary' ? 'btn-primary' : 'btn-secondary',
                className,
            )}
            {...props}
        />
    );
}
