import { Head } from '@inertiajs/react';
import { useI18n } from '@/i18n';

type WelcomeProps = {
    openapiUrl: string;
    healthUrl: string;
};

export default function Welcome({ openapiUrl, healthUrl }: WelcomeProps) {
    const { t } = useI18n('welcome');

    return (
        <>
            <Head title={t('title')}>
            </Head>
            <div
                className="min-h-screen flex items-center justify-center bg-[radial-gradient(circle_at_top_left,_#f9e7c6,_transparent_35%),linear-gradient(135deg,_#102542_0%,_#0a0f18_45%,_#09111b_100%)] px-6 py-10 text-[#f5efe3] md:px-10">
                <div className="mx-auto max-w-6xl flex flex-col-reverse lg:flex-row gap-6 items-center justify-center">
                    <section
                        className="overflow-hidden rounded-4xl border border-white/10 bg-white/8 p-8 shadow-[0_30px_80px_rgba(0,0,0,0.35)] backdrop-blur">
                        <p className="text-xs uppercase tracking-[0.35em] text-[#ffcb71]">
                            {t('badge')}
                        </p>
                        <h1 className="mt-4 max-w-3xl text-2xl font-bold leading-tight">
                            {t('heading')}
                        </h1>
                        <p className="mt-5 max-w-2xl text-base leading-7 text-[#d2d9e6] md:text-lg">
                            {t('description')}
                        </p>
                        <div className="mt-8 flex flex-wrap gap-3 text-sm">
                            <a
                                href={openapiUrl}
                                className="rounded-full bg-[#ffcb71] px-5 py-3 font-semibold text-[#0a0f18] transition hover:bg-[#ffd894]"
                            >
                                {t('openapi')}
                            </a>
                            <a
                                href={healthUrl}
                                className="rounded-full border border-white/15 px-5 py-3 font-semibold text-[#f5efe3] transition hover:border-[#ffcb71] hover:text-[#ffcb71]"
                            >
                                {t('health')}
                            </a>
                        </div>
                    </section>

                    <section
                        className="rounded-4xl h-full border border-[#ffcb71]/20 bg-[#0d1726]/85 p-6 shadow-[0_24px_70px_rgba(0,0,0,0.28)]">
                        <p className="text-xs uppercase tracking-[0.3em] text-[#ffcb71]">
                            {t('signInBadge')}
                        </p>
                        <h2 className="mt-4 text-3xl font-bold leading-tight text-white">
                            {t('signInHeading')}
                        </h2>
                        <p className="mt-4 text-sm leading-7 text-[#d8dfec]">
                            {t('signInDescription')}
                        </p>
                        <div className="mt-6 flex flex-wrap gap-3 text-sm">
                            <a
                                href="/login"
                                className="rounded-full bg-white px-5 py-3 font-semibold text-[#0d1726] transition hover:bg-[#f3f3f3]"
                            >
                                {t('login')}
                            </a>
                            <a
                                href="/register"
                                className="rounded-full border border-white/15 px-5 py-3 font-semibold text-[#f5efe3] transition hover:border-[#ffcb71] hover:text-[#ffcb71]"
                            >
                                {t('register')}
                            </a>
                        </div>
                    </section>
                </div>
            </div>
        </>
    );
}
