import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useI18n } from '@/i18n';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import type { NavItem } from '@/types';

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const { t } = useI18n('settings.layout');
    const sidebarNavItems: NavItem[] = [
        {
            title: t('profile'),
            href: edit(),
            icon: null,
        },
        {
            title: t('password'),
            href: editPassword(),
            icon: null,
        },
        {
            title: t('twoFactor'),
            href: show(),
            icon: null,
        },
        {
            title: t('appearance'),
            href: editAppearance(),
            icon: null,
        },
    ];

    // When server-side rendering, we only render the layout on the client...
    if (typeof window === 'undefined') {
        return null;
    }

    return (
        <div className="rounded-[2rem] border border-white/10 bg-white/8 p-6 shadow-[0_30px_80px_rgba(0,0,0,0.25)] backdrop-blur md:p-8">
            <Heading title={t('title')} description={t('description')} />

            <div className="flex flex-col gap-6 lg:flex-row lg:gap-8">
                <aside className="w-full max-w-xl lg:w-56">
                    <nav
                        className="flex flex-col space-y-2 rounded-[1.5rem] border border-white/10 bg-[#050b13]/65 p-3"
                        aria-label={t('navigationLabel')}
                    >
                        {sidebarNavItems.map((item, index) => (
                            <Button
                                key={`${toUrl(item.href)}-${index}`}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn(
                                    'h-10 w-full justify-start rounded-xl border border-transparent px-4 text-[#d4dfec] hover:border-white/10 hover:bg-white/8 hover:text-white',
                                    {
                                        'border-white/10 bg-white/10 text-white':
                                            isCurrentOrParentUrl(item.href),
                                    },
                                )}
                            >
                                <Link href={item.href}>
                                    {item.icon && (
                                        <item.icon className="h-4 w-4" />
                                    )}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-1 bg-white/10 lg:hidden" />

                <div className="flex-1">
                    <section className="space-y-6 rounded-[1.5rem] border border-white/10 bg-[#050b13]/65 p-5 md:p-6">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
