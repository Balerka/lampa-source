import { AlertCircleIcon } from 'lucide-react';
import { useI18n } from '@/i18n';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

export default function AlertError({
    errors,
    title,
}: {
    errors: string[];
    title?: string;
}) {
    const { t } = useI18n('common');

    return (
        <Alert variant="destructive">
            <AlertCircleIcon />
            <AlertTitle>{title || t('somethingWentWrong')}</AlertTitle>
            <AlertDescription>
                <ul className="list-inside list-disc text-sm">
                    {Array.from(new Set(errors)).map((error, index) => (
                        <li key={index}>{error}</li>
                    ))}
                </ul>
            </AlertDescription>
        </Alert>
    );
}
