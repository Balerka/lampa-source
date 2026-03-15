import { Head, Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n';
import { dashboard } from '@/routes';

type AddPageProps = {
    actionUrl: string;
    email: string;
    ttl: number;
};

type CodeState = {
    code: string;
    expires: number;
    email: string;
};

export default function Add({ actionUrl, email, ttl }: AddPageProps) {
    const { t } = useI18n('add');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [result, setResult] = useState<CodeState | null>(null);
    const [secondsLeft, setSecondsLeft] = useState<number>(0);

    async function generateCode() {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(actionUrl, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
            });

            const payload = (await response.json()) as {
                code?: string;
                expires?: number;
                email?: string;
                error?: string;
            };

            if (!response.ok || !payload.code) {
                throw new Error(payload.error || t('generateError'));
            }

            setResult({
                code: payload.code,
                expires: payload.expires || ttl,
                email: payload.email || email,
            });
            setSecondsLeft(Math.max(0, Math.floor(payload.expires || ttl)));
        } catch (err) {
            setResult(null);
            setSecondsLeft(0);
            setError(err instanceof Error ? err.message : t('generateError'));
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => {
        void generateCode();
    }, []);

    useEffect(() => {
        if (secondsLeft <= 0) {
            return;
        }

        const timer = window.setInterval(() => {
            setSecondsLeft((value) => Math.max(value - 1, 0));
        }, 1000);

        return () => window.clearInterval(timer);
    }, [secondsLeft]);

    return (
        <>
            <Head title={t('title')}></Head>
            <div className="flex min-h-screen items-center justify-center bg-[radial-gradient(circle_at_top_left,_#f9e7c6,_transparent_35%),linear-gradient(135deg,_#102542_0%,_#0a0f18_45%,_#09111b_100%)] px-6 py-10 text-white">
                <section className="flex max-w-6xl flex-col justify-between rounded-4xl border border-white/10 bg-white/8 p-8 shadow-[0_32px_80px_rgba(0,0,0,0.28)] backdrop-blur">
                    <div>
                        <p className="text-xs tracking-[0.35em] text-[#8fdff0] uppercase">
                            {t('pairCode')}
                        </p>
                        <div className="mt-6 rounded-[2rem] border border-white/10 bg-[#050b13]/85 p-8 text-center shadow-[inset_0_0_0_1px_rgba(255,255,255,0.04)]">
                            <div className="text-center text-3xl font-semibold tracking-[0.45em] text-[#ffcc73] lg:text-5xl">
                                {result?.code ?? '------'}
                            </div>
                            <p className="mt-4 text-sm text-[#cfd9e7]">
                                {result
                                    ? t('account', { email: result.email })
                                    : t('generatingCurrent')}
                            </p>
                            <p className="mt-2 text-sm text-[#8fdff0]">
                                {result
                                    ? t('expiresIn', { seconds: secondsLeft })
                                    : t('pleaseWait')}
                            </p>
                        </div>
                    </div>

                    <div className="mt-6 flex flex-col gap-3">
                        <button
                            type="button"
                            onClick={() => void generateCode()}
                            disabled={loading}
                            className="rounded-full bg-[#ffcc73] px-6 py-3 font-semibold text-[#08111d] transition hover:bg-[#ffda93] disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {loading ? t('generating') : t('generateNewCode')}
                        </button>

                        <Button
                            asChild
                            variant="outline"
                            className="w-full rounded-full border-white/15 bg-white/6 py-6 text-white hover:bg-white/12 hover:text-white"
                        >
                            <Link href={dashboard()}>
                                {t('dashboardButton')}
                            </Link>
                        </Button>
                    </div>
                </section>
            </div>
        </>
    );
}
