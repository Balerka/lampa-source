import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun } from 'lucide-react';
import type { HTMLAttributes } from 'react';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { useI18n } from '@/i18n';
import { cn } from '@/lib/utils';

export default function AppearanceToggleTab({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { appearance, updateAppearance } = useAppearance();
    const { t } = useI18n('settings.appearance');

    const tabs: { value: Appearance; icon: LucideIcon; label: string }[] = [
        { value: 'light', icon: Sun, label: t('light') },
        { value: 'dark', icon: Moon, label: t('dark') },
        { value: 'system', icon: Monitor, label: t('system') },
    ];

    return (
        <div
            className={cn(
                'inline-flex gap-1 rounded-[1.25rem] border border-white/10 bg-[#050b13]/65 p-1.5',
                className,
            )}
            {...props}
        >
            {tabs.map(({ value, icon: Icon, label }) => (
                <button
                    key={value}
                    onClick={() => updateAppearance(value)}
                    className={cn(
                        'flex items-center rounded-xl px-4 py-2 text-[#d4dfec] transition-colors',
                        appearance === value
                            ? 'bg-white/10 text-white shadow-[inset_0_0_0_1px_rgba(255,255,255,0.05)]'
                            : 'hover:bg-white/8 hover:text-white',
                    )}
                >
                    <Icon className="-ml-1 h-4 w-4" />
                    <span className="ml-1.5 text-sm">{label}</span>
                </button>
            ))}
        </div>
    );
}
