// Components
import { Form, Head } from '@inertiajs/react';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useI18n } from '@/i18n';
import AuthLayout from '@/layouts/auth-layout';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    const { t } = useI18n('auth.verifyEmail');
    const { t: common } = useI18n('common');

    return (
        <AuthLayout title={t('title')} description={t('description')}>
            <Head title={t('headTitle')} />

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-[#8fdff0]">
                    {t('resendSuccess')}
                </div>
            )}

            <Form {...send.form()} className="space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <Button disabled={processing} variant="secondary">
                            {processing && <Spinner />}
                            {t('resend')}
                        </Button>

                        <TextLink
                            href={logout()}
                            className="mx-auto block text-sm"
                        >
                            {common('logOut')}
                        </TextLink>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
