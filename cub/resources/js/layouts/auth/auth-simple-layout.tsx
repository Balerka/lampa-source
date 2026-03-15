import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="flex min-h-svh items-center justify-center bg-[radial-gradient(circle_at_top_left,_#f9e7c6,_transparent_35%),linear-gradient(135deg,_#102542_0%,_#0a0f18_45%,_#09111b_100%)] px-6 py-10 text-[#f5efe3] md:px-10">
            <div className="w-full max-w-md">
                <div className="flex flex-col gap-8 rounded-[2rem] border border-white/10 bg-white/8 p-8 shadow-[0_30px_80px_rgba(0,0,0,0.35)] backdrop-blur">
                    <div className="flex flex-col items-center gap-4 text-center">
                        <Link
                            href={home()}
                            className="flex flex-col items-center gap-3 font-medium"
                        >
                            <div className="mb-1 flex h-14 w-14 items-center justify-center rounded-2xl border border-white/10 bg-white/8 shadow-[inset_0_0_0_1px_rgba(255,255,255,0.04)]">
                                <AppLogoIcon className="size-9 fill-current text-[#ffcb71]" />
                            </div>
                            <span className="sr-only">{title}</span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-2xl leading-tight font-bold text-white">
                                {title}
                            </h1>
                            <p className="text-sm leading-6 text-[#d2d9e6]">
                                {description}
                            </p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
