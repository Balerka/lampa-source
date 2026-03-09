# Бэкенд аккаунтов Lampa

Production-ready бэкенд для кастомной авторизации и синхронизации Lampa на Laravel 12 + React/Inertia.

## Возможности
- Авторизация по одноразовым 6-значным кодам через `njoguamos/laravel-otp`
- Header-based auth, совместимая с Lampa: `token` и `profile`
- Premium всегда возвращается как `2099-12-31T00:00:00.000Z`
- Синхронизация bookmarks, changelog, timeline, backup и profiles
- HTTP `dump/changelog` и raw WebSocket realtime для timeline
- Rate limiting, CORS, health endpoint и OpenAPI-спецификация
- Пользователи по умолчанию автоматически не создаются

## Стек
- PHP 8.3+
- Laravel 12
- React 19 + Vite + Inertia
- SQLite, MySQL или Postgres
- Nginx для reverse proxy
- systemd для фонового WebSocket процесса

## Продакшн: Ubuntu 24 + FastPanel
Ниже инструкция именно для одного VPS с FastPanel.

### Что поставить на сервер
```bash
sudo apt update
sudo apt install -y git curl unzip sqlite3
sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-mbstring php8.3-xml php8.3-curl php8.3-sqlite3 php8.3-mysql php8.3-pgsql php8.3-zip
```

Если Composer ещё не установлен:
```bash
cd /tmp
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
```

Если позже захотите несколько socket-процессов или несколько серверов, тогда дополнительно ставьте Redis:
```bash
sudo apt install -y redis-server php8.3-redis
```

### Что создать в FastPanel
1. Добавьте сайт `example.com`.
2. Выберите PHP 8.3.
3. Включите SSL для домена.
4. Укажите `public` как web root Laravel-проекта.
5. Создайте базу данных в FastPanel, если используете MySQL.

### Как развернуть проект
Пример пути, где FastPanel хранит сайт, зависит от вашей конфигурации. Ниже используйте реальный путь вашего проекта.

```bash
cd /var/www
git clone <repo> cub
cd cub
composer install --no-dev --optimize-autoloader
npm install
npm run build
cp .env.example .env
php artisan key:generate
```

Если используете SQLite:
```bash
touch database/database.sqlite
```

Миграции:
```bash
php artisan migrate --force
```

Права:
```bash
sudo chown -R www-data:www-data /var/www/cub
sudo chmod -R ug+rwx /var/www/cub/storage /var/www/cub/bootstrap/cache
```

### Что прописать в `.env`
#### Вариант с MySQL
```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

LAMPA_DEVICE_CODE_TTL=300
LAMPA_PREMIUM_UNTIL=2099-12-31T00:00:00.000Z
LAMPA_API_RATE_LIMIT_PER_MINUTE=120
CORS_ALLOWED_ORIGINS=*
MANUAL_DEVICE_CODE_SECRET=changeme
TIMELINE_SOCKET_HOST=127.0.0.1
TIMELINE_SOCKET_PORT=9001
```

#### Вариант с SQLite
```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/cub/database/database.sqlite

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

LAMPA_DEVICE_CODE_TTL=300
LAMPA_PREMIUM_UNTIL=2099-12-31T00:00:00.000Z
LAMPA_API_RATE_LIMIT_PER_MINUTE=120
CORS_ALLOWED_ORIGINS=*
MANUAL_DEVICE_CODE_SECRET=changeme
TIMELINE_SOCKET_HOST=127.0.0.1
TIMELINE_SOCKET_PORT=9001
```

После изменения `.env`:
```bash
php artisan config:clear
php artisan cache:clear
```

Если используете `SESSION_DRIVER=database` и `QUEUE_CONNECTION=database`, создайте таблицы и примените миграции:
```bash
php artisan session:table
php artisan queue:table
php artisan migrate --force
```

### WebSocket процесс через systemd
FastPanel сам этот процесс не поднимет. Его нужно держать отдельным systemd-сервисом.

Создайте файл `/etc/systemd/system/timeline-socket.service`:
```ini
[Unit]
Description=Lampa timeline WebSocket server
After=network.target php8.3-fpm.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/cub
ExecStart=/usr/bin/php artisan timeline:socket-serve --host=127.0.0.1 --port=9001
Restart=always
RestartSec=3
KillSignal=SIGTERM
TimeoutStopSec=10
Environment=APP_ENV=production
Environment=LOG_CHANNEL=stack

[Install]
WantedBy=multi-user.target
```

Запуск:
```bash
sudo systemctl daemon-reload
sudo systemctl enable --now timeline-socket.service
sudo systemctl status timeline-socket.service
```

Логи:
```bash
journalctl -u timeline-socket.service -f
```

### Nginx в FastPanel для WebSocket
FastPanel сам обслуживает основной сайт на `443`. Для WebSocket нужно добавить proxy до локального сокета `127.0.0.1:9001`.

Есть 2 варианта.

#### Вариант 1. Рекомендуемый
Открыть порты `8443` и `8080`, чтобы клиент работал без нестандартного `account_domain`.

Вставьте в дополнительную nginx-конфигурацию сайта или в отдельный nginx include:

```nginx
server {
    listen 8080;
    server_name example.com;

    location / {
        proxy_pass http://127.0.0.1:9001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_read_timeout 120s;
    }
}

server {
    listen 8443 ssl;
    server_name example.com;

    ssl_certificate /etc/letsencrypt/live/example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/example.com/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:9001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_read_timeout 120s;
    }
}
```

После правки nginx:
```bash
sudo nginx -t
sudo systemctl reload nginx
```

#### Вариант 2. Если FastPanel не даёт удобно поднять `8443/8080`
Просто откройте один отдельный порт, например `9001`, и проксирование не делайте вообще. Тогда Lampa будет подключаться прямо к сокету.

В этом случае:
- откройте порт `9001` в firewall
- оставьте `timeline:socket-serve` на `127.0.0.1:9001` только если есть внешний reverse proxy
- если reverse proxy нет, запускайте сокет на `0.0.0.0:9001`

Пример:
```bash
sudo systemctl stop timeline-socket.service
sudo sed -i 's/--host=127.0.0.1 --port=9001/--host=0.0.0.0 --port=9001/' /etc/systemd/system/timeline-socket.service
sudo systemctl daemon-reload
sudo systemctl start timeline-socket.service
```

### Что указать в Lampa
#### Рекомендуемый вариант через `8443/8080`
```text
account_service_name=Lampa Custom Account
account_premium_name=Lampa Premium
account_site=https://example.com
account_domain=example.com
account_assets_domain=https://example.com
account_socket_use=true
account_premium_always=true
```

#### Вариант с прямым портом `9001`
```text
account_service_name=Lampa Custom Account
account_premium_name=Lampa Premium
account_site=https://example.com
account_domain=example.com:9001
account_assets_domain=https://example.com
account_socket_use=true
account_premium_always=true
```

Важно:
- если `account_domain=example.com`, клиент пойдёт на `wss://example.com:8443`
- для legacy TV клиент пойдёт на `ws://example.com:8080`
- если в `account_domain` уже указан порт, например `example.com:9001`, клиент подключится прямо к нему

### Что проверить после деплоя
HTTP:
```bash
curl https://example.com/up
```

Socket процесс:
```bash
systemctl status timeline-socket.service
```

Порт слушается:
```bash
ss -ltnp | rg '9001|8443|8080'
```

Laravel миграции:
```bash
php artisan migrate:status
```

## Что ставить локально для теста
### Вариант 1. Самый простой
- PHP 8.3+
- Composer
- Node.js 22+
- SQLite

macOS через Homebrew:
```bash
brew install php composer node sqlite
```

Ubuntu:
```bash
sudo apt update
sudo apt install -y php8.3 php8.3-cli php8.3-mbstring php8.3-xml php8.3-curl php8.3-sqlite3 php8.3-zip sqlite3 unzip curl git
```

### Вариант 2. Для проверки production-поведения
- всё из простого варианта
- `nginx`
- `php8.3-fpm`

## Локальный запуск
```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

В отдельной вкладке для WebSocket:
```bash
composer socket:serve
```

Открыть:
- Приложение: `http://127.0.0.1:8000`
- OpenAPI: `http://127.0.0.1:8000/openapi.json`
- Health: `http://127.0.0.1:8000/up`
- Timeline socket: `ws://127.0.0.1:9001`

## Локальный тест API
```bash
DB_CONNECTION=sqlite DB_DATABASE=':memory:' SESSION_DRIVER=array CACHE_STORE=array QUEUE_CONNECTION=sync php artisan test --filter=LampaApiTest
```

## Примеры cURL
Сгенерировать manual code для существующего пользователя:
```bash
curl -X POST http://127.0.0.1:8000/api/device/code/manual \
  -H 'Content-Type: application/json' \
  -H 'X-Manual-Secret: changeme' \
  -d '{"email":"your-user@example.com"}'
```

Обменять device code на токен:
```bash
curl -X POST http://127.0.0.1:8000/api/device/add \
  -H 'Content-Type: application/json' \
  -d '{"code":"123456"}'
```

Получить текущего пользователя:
```bash
curl http://127.0.0.1:8000/api/users/get \
  -H 'token: YOUR_TOKEN'
```

Получить список профилей:
```bash
curl http://127.0.0.1:8000/api/profiles/all \
  -H 'token: YOUR_TOKEN'
```

Создать профиль:
```bash
curl -X POST http://127.0.0.1:8000/api/profiles/create \
  -H 'token: YOUR_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{"name":"Kids"}'
```

Обновить timeline:
```bash
curl -X POST http://127.0.0.1:8000/api/timeline/update \
  -H 'token: YOUR_TOKEN' \
  -H 'profile: 1' \
  -H 'Content-Type: application/json' \
  -d '{"hash":"hash1","percent":80,"time":3200,"duration":4000}'
```

## WebSocket timeline протокол
Клиент подключается к raw WebSocket и шлёт:
```json
{
  "params": {
    "hash": "abc123",
    "percent": 42,
    "time": 1350,
    "duration": 3200,
    "profile": 7
  },
  "device_id": "device-uid",
  "method": "timeline",
  "account": {
    "token": "user-token",
    "profile": {
      "id": 7
    }
  }
}
```

Другим устройствам этого же аккаунта и профиля сервер отправляет:
```json
{
  "method": "timeline",
  "data": {
    "hash": "abc123",
    "percent": 42,
    "time": 1350,
    "duration": 3200,
    "profile": 7
  }
}
```

## Примечания по API
- `POST /api/device/code/create` требует валидный `token` header и создаёт код привязки для текущего аккаунта.
- `POST /api/device/code/manual` нужен для тестов и админских сценариев, и требует явный email.
- `/add` работает только из авторизованной Laravel-сессии и генерирует OTP для текущего аккаунта.
- `POST /api/device/add` валидирует код через `njoguamos/laravel-otp` и возвращает новый токен и основной профиль.
- `GET /api/bookmarks/dump` и `GET /api/timeline/dump` возвращают `text/plain` с JSON для совместимости с Lampa.
- `GET /api/timeline/dump` и `GET /api/timeline/changelog` возвращают timeline как объект, где ключом является `hash`.
- Пустой `timeline/changelog` возвращает актуальную `version` и пустой объект `{}`.
- Любое изменение bookmarks и timeline увеличивает версию и пишет changelog.

## OpenAPI
OpenAPI-спецификация доступна по адресам:
- `public/openapi.json`
- `/openapi.json`
