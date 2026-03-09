export const translations = {
    ru: {
        welcome: {
            title: 'Бэкенд аккаунтов Lampa',
            badge: 'Laravel + React / Lampa API',
            heading: 'Кастомный бэкенд авторизации и синхронизации для клиентов Lampa.',
            description:
                'Авторизация по device-code, синхронизация с учетом профиля, version/changelog и OpenAPI-спецификация.',
            openapi: 'OpenAPI JSON',
            health: 'Проверка Health',
            signInBadge: 'Вход',
            signInHeading: 'Войдите или зарегистрируйтесь.',
            signInDescription:
                'Привязка работает только для текущего авторизованного аккаунта. После входа открой страницу добавления и используй сгенерированный 6-значный код в Lampa.',
            login: 'Войти',
            register: 'Регистрация',
        },
        add: {
            title: 'Добавление аккаунта',
            badge: 'Привязка аккаунта Lampa',
            heading:
                'Эта страница работает только для текущего аккаунта, авторизованного в Laravel.',
            description:
                'Код привязки будет сгенерирован для {{email}}. Вернись в Lampa и заверши привязку аккаунта. Код действует {{ttl}} секунд.',
            activeAccount: 'Активный аккаунт: {{email}}',
            generating: 'Генерация...',
            generateNewCode: 'Сгенерировать новый код',
            pairCode: 'Код привязки',
            account: 'Аккаунт: {{email}}',
            generatingCurrent: 'Генерируем код для текущего аккаунта...',
            expiresIn: 'Истекает через {{seconds}}с',
            pleaseWait: 'Подожди...',
            stepOne: '1. Открой `/add`, когда уже вошел в Laravel.',
            stepTwo: '2. Дождись появления 6-значного кода.',
            stepThree: '3. Вернись в Lampa и заверши привязку аккаунта.',
            generateError: 'Не удалось сгенерировать код.',
        },
    },
    en: {
        welcome: {
            title: 'Lampa Account Backend',
            badge: 'Laravel + React / Lampa API',
            heading: 'Custom auth and sync backend for Lampa clients.',
            description:
                'Device-code authorization, profile-aware sync, version/changelog and OpenAPI specification.',
            openapi: 'OpenAPI JSON',
            health: 'Health Check',
            signInBadge: 'Sign In',
            signInHeading: 'Log in or Register.',
            signInDescription:
                'Pairing works only for the currently authenticated account. After login, open the add page and use the generated 6-digit code in Lampa.',
            login: 'Login',
            register: 'Register',
        },
        add: {
            title: 'Add Account',
            badge: 'Lampa Account Pairing',
            heading: 'This page works only for the current logged-in Laravel account.',
            description:
                'Pairing code is generated for {{email}}. Return to Lampa and finish account binding. The code stays valid for {{ttl}} seconds.',
            activeAccount: 'Active account: {{email}}',
            generating: 'Generating...',
            generateNewCode: 'Generate new code',
            pairCode: 'Pair Code',
            account: 'Account: {{email}}',
            generatingCurrent: 'Generating code for the current account...',
            expiresIn: 'Expires in {{seconds}}s',
            pleaseWait: 'Please wait...',
            stepOne: '1. Open `/add` while logged into Laravel.',
            stepTwo: '2. Wait until the 6-digit code appears.',
            stepThree: '3. Return to Lampa and complete account binding.',
            generateError: 'Failed to generate code.',
        },
    },
} as const;

export type AppLocale = keyof typeof translations;
