import { Head } from '@inertiajs/react';
import {
    BarChart3,
    BookOpen,
    KeyRound,
    ShieldCheck,
    Sparkles,
} from 'lucide-react';
import { type ReactNode, useMemo } from 'react';
import { useI18n } from '@/i18n';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type DashboardStats = {
    profiles: number;
    bookmarks: number;
    timelines: number;
    tokens: number;
};

type DashboardProfile = {
    id: number;
    name: string;
    icon: string;
    main: boolean;
    child: boolean;
    age: number;
    bookmarksCount: number;
    timelinesCount: number;
    bookmarksVersion: number;
    timelinesVersion: number;
    updatedAt: string | null;
};

type BookmarkType = {
    type: string;
    total: number;
};

type RecentTimeline = {
    id: number;
    hash: string;
    percent: number;
    time: number;
    duration: number;
    version: number;
    profileName: string | null;
    updatedAt: string | null;
};

type Props = {
    stats: DashboardStats;
    profiles: DashboardProfile[];
    bookmarkTypes: BookmarkType[];
    recentTimelines: RecentTimeline[];
};

function formatDateTime(value: string | null, locale: string): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat(locale === 'en' ? 'en-US' : 'ru-RU', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function formatSeconds(totalSeconds: number): string {
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    }

    if (minutes > 0) {
        return `${minutes}m ${seconds}s`;
    }

    return `${seconds}s`;
}

function formatBookmarkType(
    type: string,
    t: (key: string, params?: { [key: string]: string | number }) => string,
): string {
    const labels: Record<string, string> = {
        book: t('bookmarkType.labels.book'),
        like: t('bookmarkType.labels.like'),
        wath: t('bookmarkType.labels.wath'),
        history: t('bookmarkType.labels.history'),
    };

    return labels[type] ?? type;
}

function GlassPanel({
    title,
    count,
    children,
}: {
    title: string;
    count?: number;
    children: ReactNode;
}) {
    return (
        <section className="rounded-[2rem] border border-white/10 bg-[linear-gradient(180deg,rgba(8,17,29,0.94)_0%,rgba(5,11,19,0.88)_100%)] p-5 shadow-[0_28px_70px_rgba(0,0,0,0.32)] backdrop-blur">
            <div className="mb-4 flex items-center justify-between gap-3">
                <h2 className="text-lg font-semibold text-white">{title}</h2>
                {typeof count === 'number' && (
                    <div className="rounded-full border border-white/10 bg-white/6 px-3 py-1 text-xs font-medium text-[#cfe0f2]">
                        {count}
                    </div>
                )}
            </div>
            {children}
        </section>
    );
}

export default function Dashboard({
    stats,
    profiles,
    bookmarkTypes,
    recentTimelines,
}: Props) {
    const { t, locale } = useI18n('dashboard');
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('title'),
            href: dashboard(),
        },
    ];

    const statCards = useMemo(
        () => [
            {
                key: 'profiles',
                label: t('stats.profiles'),
                value: stats.profiles,
                icon: ShieldCheck,
                tone: 'text-[#9be7d1]',
            },
            {
                key: 'bookmarks',
                label: t('stats.bookmarks'),
                value: stats.bookmarks,
                icon: BookOpen,
                tone: 'text-[#ffcc73]',
            },
            {
                key: 'timelines',
                label: t('stats.timelines'),
                value: stats.timelines,
                icon: BarChart3,
                tone: 'text-[#8fdff0]',
            },
            {
                key: 'tokens',
                label: t('stats.tokens'),
                value: stats.tokens,
                icon: KeyRound,
                tone: 'text-[#cda6ff]',
            },
        ],
        [stats, t],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('title')} />

            <div className="min-h-full">
                <div className="flex w-full flex-col gap-6">
                    <section className="relative overflow-hidden rounded-[2.2rem] border border-white/10 bg-[linear-gradient(135deg,rgba(8,17,29,0.96)_0%,rgba(16,37,66,0.9)_52%,rgba(34,24,10,0.88)_100%)] p-6 shadow-[0_32px_80px_rgba(0,0,0,0.36)] backdrop-blur md:p-8">
                        <div className="absolute inset-y-0 right-0 hidden w-1/3 bg-[radial-gradient(circle_at_center,rgba(255,204,115,0.22),transparent_65%)] lg:block" />
                        <div className="absolute inset-x-0 top-0 h-px bg-[linear-gradient(90deg,transparent,rgba(255,255,255,0.25),transparent)]" />

                        <div className="relative z-10 grid gap-8 lg:grid-cols-[1.15fr_0.85fr] lg:items-end">
                            <div className="max-w-2xl">
                                <div className="inline-flex items-center gap-2 rounded-full border border-[#8fdff0]/20 bg-[#8fdff0]/10 px-3 py-1 text-xs tracking-[0.25em] text-[#8fdff0] uppercase">
                                    <Sparkles className="size-3.5" />
                                    {t('title')}
                                </div>
                                <h1 className="mt-4 text-3xl font-semibold tracking-tight text-white md:text-4xl">
                                    {t('title')}
                                </h1>
                                <p className="mt-3 max-w-xl text-sm leading-6 text-[#d4dfec] md:text-base">
                                    {t('subtitle')}
                                </p>
                            </div>

                            <div className="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-2">
                                {statCards
                                    .slice(0, 4)
                                    .map(
                                        ({
                                            key,
                                            label,
                                            value,
                                            icon: Icon,
                                            tone,
                                        }) => (
                                            <div
                                                key={key}
                                                className="rounded-[1.5rem] border border-white/10 bg-[linear-gradient(180deg,rgba(255,255,255,0.06)_0%,rgba(5,11,19,0.38)_100%)] p-4 shadow-[inset_0_0_0_1px_rgba(255,255,255,0.03)]"
                                            >
                                                <div className="flex items-center justify-between">
                                                    <span
                                                        className={`rounded-full bg-white/6 p-2 ${tone}`}
                                                    >
                                                        <Icon className="size-4" />
                                                    </span>
                                                    <span className="text-right text-xs text-[#b3c2d2]">
                                                        {label}
                                                    </span>
                                                </div>
                                                <div className="mt-6 text-3xl font-semibold tracking-tight text-white">
                                                    {value}
                                                </div>
                                            </div>
                                        ),
                                    )}
                            </div>
                        </div>
                    </section>

                    <div className="grid gap-6 xl:grid-cols-[1.35fr_0.65fr]">
                        <GlassPanel
                            title={t('sections.profiles')}
                            count={profiles.length}
                        >
                            {profiles.length === 0 ? (
                                <p className="text-sm text-[#9db2c8]">
                                    {t('empty')}
                                </p>
                            ) : (
                                <div className="grid gap-4 lg:grid-cols-2">
                                    {profiles.map((profile) => (
                                        <article
                                            key={profile.id}
                                            className="rounded-[1.5rem] border border-white/10 bg-[#050b13]/70 p-4"
                                        >
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <h3 className="font-medium text-white">
                                                            {profile.name}
                                                        </h3>
                                                        {profile.main && (
                                                            <span className="rounded-full bg-[#8fdff0]/14 px-2.5 py-1 text-[11px] font-medium text-[#8fdff0]">
                                                                {t(
                                                                    'profile.main',
                                                                )}
                                                            </span>
                                                        )}
                                                        {profile.child && (
                                                            <span className="rounded-full bg-[#ffcc73]/14 px-2.5 py-1 text-[11px] font-medium text-[#ffcc73]">
                                                                {t(
                                                                    'profile.child',
                                                                )}
                                                            </span>
                                                        )}
                                                    </div>
                                                    <p className="mt-1 text-xs text-[#9db2c8]">
                                                        {t('profile.age', {
                                                            age: profile.age,
                                                        })}
                                                    </p>
                                                </div>

                                                <div className="rounded-full border border-white/10 bg-white/8 px-3 py-1 text-xs font-medium text-[#cfe0f2]">
                                                    {profile.icon}
                                                </div>
                                            </div>

                                            <div className="mt-4 grid grid-cols-2 gap-3">
                                                <div className="rounded-[1.2rem] border border-white/8 bg-white/6 px-3 py-3 text-sm text-[#d7e2ee]">
                                                    {t(
                                                        'profile.bookmarksCount',
                                                        {
                                                            value: profile.bookmarksCount,
                                                        },
                                                    )}
                                                </div>
                                                <div className="rounded-[1.2rem] border border-white/8 bg-white/6 px-3 py-3 text-sm text-[#d7e2ee]">
                                                    {t(
                                                        'profile.timelinesCount',
                                                        {
                                                            value: profile.timelinesCount,
                                                        },
                                                    )}
                                                </div>
                                            </div>

                                            <div className="mt-4 space-y-1 text-xs text-[#91a4bb]">
                                                <p>
                                                    {t(
                                                        'profile.bookmarksVersion',
                                                        {
                                                            value: profile.bookmarksVersion,
                                                        },
                                                    )}
                                                </p>
                                                <p>
                                                    {t(
                                                        'profile.timelinesVersion',
                                                        {
                                                            value: profile.timelinesVersion,
                                                        },
                                                    )}
                                                </p>
                                                <p>
                                                    {t('profile.updatedAt', {
                                                        value: formatDateTime(
                                                            profile.updatedAt,
                                                            locale,
                                                        ),
                                                    })}
                                                </p>
                                            </div>
                                        </article>
                                    ))}
                                </div>
                            )}
                        </GlassPanel>

                        <GlassPanel
                            title={t('sections.bookmarkTypes')}
                            count={bookmarkTypes.length}
                        >
                            {bookmarkTypes.length === 0 ? (
                                <p className="text-sm text-[#9db2c8]">
                                    {t('empty')}
                                </p>
                            ) : (
                                <div className="space-y-3">
                                    {bookmarkTypes.map((item, index) => (
                                        <div
                                            key={item.type}
                                            className="rounded-[1.35rem] border border-white/10 bg-[#050b13]/70 px-4 py-4"
                                        >
                                            <div className="flex items-center justify-between gap-4">
                                                <div>
                                                    <p className="font-medium text-white">
                                                        {formatBookmarkType(
                                                            item.type,
                                                            t,
                                                        )}
                                                    </p>
                                                    <p className="mt-1 text-xs text-[#91a4bb]">
                                                        {t(
                                                            'bookmarkType.total',
                                                            {
                                                                value: item.total,
                                                            },
                                                        )}
                                                    </p>
                                                </div>
                                                <div className="text-right">
                                                    <div className="text-2xl font-semibold text-[#ffcc73]">
                                                        {item.total}
                                                    </div>
                                                    <div className="mt-1 text-[11px] tracking-[0.22em] text-[#6d839a] uppercase">
                                                        0{index + 1}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </GlassPanel>
                    </div>

                    <GlassPanel
                        title={t('sections.recentTimelines')}
                        count={recentTimelines.length}
                    >
                        {recentTimelines.length === 0 ? (
                            <p className="text-sm text-[#9db2c8]">
                                {t('empty')}
                            </p>
                        ) : (
                            <div className="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                                {recentTimelines.map((timeline) => (
                                    <article
                                        key={timeline.id}
                                        className="rounded-[1.5rem] border border-white/10 bg-[#050b13]/70 p-4"
                                    >
                                        <div className="flex items-center justify-between gap-3">
                                            <h3 className="truncate font-medium text-white">
                                                {timeline.profileName ||
                                                    t('timeline.noProfile')}
                                            </h3>
                                            <div className="rounded-full bg-[#8fdff0]/14 px-2.5 py-1 text-xs font-medium text-[#8fdff0]">
                                                {timeline.percent}%
                                            </div>
                                        </div>

                                        <p className="mt-2 truncate font-mono text-xs text-[#91a4bb]">
                                            {timeline.hash}
                                        </p>

                                        <div className="mt-4 h-2 overflow-hidden rounded-full bg-white/8">
                                            <div
                                                className="h-full rounded-full bg-[linear-gradient(90deg,#e3a34f_0%,#d16d46_100%)]"
                                                style={{
                                                    width: `${timeline.percent}%`,
                                                }}
                                            />
                                        </div>

                                        <div className="mt-4 grid gap-1 text-xs text-[#9db2c8]">
                                            <p>
                                                {t('timeline.progress', {
                                                    value: timeline.percent,
                                                })}
                                            </p>
                                            <p>
                                                {t('timeline.time', {
                                                    value: formatSeconds(
                                                        timeline.time,
                                                    ),
                                                })}
                                            </p>
                                            <p>
                                                {t('timeline.duration', {
                                                    value: formatSeconds(
                                                        timeline.duration,
                                                    ),
                                                })}
                                            </p>
                                            <p>
                                                {t('timeline.version', {
                                                    value: timeline.version,
                                                })}
                                            </p>
                                            <p>
                                                {t('timeline.updatedAt', {
                                                    value: formatDateTime(
                                                        timeline.updatedAt,
                                                        locale,
                                                    ),
                                                })}
                                            </p>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        )}
                    </GlassPanel>
                </div>
            </div>
        </AppLayout>
    );
}
