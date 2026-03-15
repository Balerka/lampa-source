import { usePage } from '@inertiajs/react';
import { en } from './en';
import { ru } from './ru';

const dictionaries = {
    ru,
    en,
} as const;

type AppLocale = keyof typeof dictionaries;

type Params = Record<string, string | number>;

type SharedProps = {
    locale?: string;
};

function interpolate(template: string, params: Params = {}): string {
    return Object.entries(params).reduce((result, [key, value]) => {
        return result.replaceAll(`{{${key}}}`, String(value));
    }, template);
}

function resolve(locale: AppLocale, key: string): string {
    const segments = key.split('.');
    let value: unknown = dictionaries[locale];

    for (const segment of segments) {
        value = (value as Record<string, unknown> | undefined)?.[segment];
    }

    if (typeof value === 'string') {
        return value;
    }

    return key;
}

export function translate(locale: string | undefined, key: string, params?: Params): string {
    const normalized = locale === 'en' ? 'en' : 'ru';

    return interpolate(resolve(normalized, key), params);
}

export function useI18n(namespace: string) {
    const { props } = usePage<SharedProps>();
    const locale = props.locale === 'en' ? 'en' : 'ru';

    return {
        locale,
        t: (key: string, params?: Params) => translate(locale, `${namespace}.${key}`, params),
    };
}
