# Один запуск для продакшена

Из корня проекта можно поднимать фронт и backend одной командой.

## Что делает скрипт

`deploy/prod-up.sh`:

- ставит зависимости фронта;
- собирает фронт `lampa` через `npx gulp pack_web`;
- при необходимости публикует собранный фронт в нужную директорию;
- ставит `composer` и `npm` зависимости в `cub`;
- собирает Laravel assets;
- применяет миграции;
- пересобирает Laravel cache;
- опционально выставляет права;
- если в корне проекта лежит `supervisor.conf`, перезапускает websocket через Supervisor;
- иначе опционально создает и перезапускает systemd-сервис для websocket.

## Подготовка

1. Скопируй конфиг:

```bash
cp deploy/prod-up.env.example deploy/prod-up.env
```

2. Отредактируй:

```bash
nano deploy/prod-up.env
```

Минимум проверь:

- `FRONT_DIR`
- `FRONT_PUBLISH_DIR`
- `BACK_DIR`
- `BACK_ENV_FILE`

## Запуск

Из корня проекта:

```bash
sh deploy/prod-up.sh
```

Или:

```bash
chmod +x deploy/prod-up.sh
./deploy/prod-up.sh
```

## Типовой вариант для FastPanel

Если у тебя:

- фронт отдается как статический сайт;
- backend это обычный Laravel в папке `cub`;
- FastPanel для backend смотрит в `cub/public`;

то обычно достаточно:

```dotenv
FRONT_DIR="/var/www/USER/data/www/example.com/lampa-source"
FRONT_PUBLISH_DIR="/var/www/USER/data/www/example.com"
BACK_DIR="/var/www/USER/data/www/example.com/lampa-source/cub"
BACK_ENV_FILE="/var/www/USER/data/www/example.com/lampa-source/cub/.env"
```

Если фронт-сайт в FastPanel уже направлен прямо на `build/web`, тогда `FRONT_PUBLISH_DIR` можно оставить пустым.

## Важно

- Не указывай `FRONT_PUBLISH_DIR` равным корню репозитория, иначе публикация фронта затрет исходники. Либо направляй сайт сразу на `build/web`, либо используй отдельную publish-папку.
- Скрипт не ставит системные пакеты Ubuntu. Предполагается, что `php`, `composer`, `npm`, `node`, `npx` уже установлены.
- Если в `cub/.env` пустой `APP_KEY`, скрипт сам его сгенерирует.
- Если в корне проекта есть `supervisor.conf`, websocket будет перезапущен через `supervisorctl`.
- Если `supervisor.conf` нет и включен `INSTALL_SOCKET_SERVICE=true`, тогда скрипт использует systemd.
- Для systemd-сервиса websocket нужен `sudo` и права на запись в `/etc/systemd/system`.
