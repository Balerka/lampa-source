import { Link } from '@inertiajs/react';
import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

type Props = ComponentProps<typeof Link>;

export default function TextLink({
    className = '',
    children,
    ...props
}: Props) {
    return (
        <Link
            className={cn(
                'text-[#ffcb71] underline decoration-[#ffcb71]/35 underline-offset-4 transition-colors duration-300 ease-out hover:text-[#ffd894] hover:decoration-[#ffd894]',
                className,
            )}
            {...props}
        >
            {children}
        </Link>
    );
}
