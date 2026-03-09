# Деплой на Ubuntu 24 + FastPanel

Эта инструкция рассчитана на текущий репозиторий `lampa-source`.

Важно:

- Для production здесь не нужен `npm run start`.
- `npm run start` поднимает локальный dev-режим через `gulp` и для продакшена не подходит.
- Production-артефакты для статической публикации собираются в каталог `build/web/`.
- Это в первую очередь статический фронтенд. Отдельный backend для `account_site`, `socket_url` и похожих интеграций в этот репозиторий не входит.

## Что в итоге нужно получить

На сервере должен открываться сайт, который отдает содержимое каталога `build/web/` как обычный статический сайт через Nginx/FastPanel.

## Вариант 1. Рекомендуемый: собрать локально и загрузить в FastPanel

Этот вариант проще и надежнее: серверу не нужен Node.js, если вы выкладываете уже готовую сборку.

### 1. Соберите проект локально

Из корня репозитория:

```bash
npm install
npx gulp pack_web
```

После сборки нужные файлы будут лежать в:

```bash
build/web/
```

Проверьте, что там есть как минимум:

```text
build/web/index.html
build/web/app.js
build/web/css/app.css
build/web/img/
build/web/lang/
build/web/vender/
```

### 2. Создайте сайт в FastPanel

В FastPanel:

1. Создайте новый сайт.
2. Укажите домен.
3. Выберите `Nginx` как веб-сервер.
4. Включите SSL через Let's Encrypt.
5. Включите редирект HTTP -> HTTPS.

### 3. Загрузите сборку

В корень сайта в FastPanel загрузите содержимое каталога `build/web/`.

Важно:

- Загружать нужно именно содержимое каталога, а не саму папку `lampa`.
- В корне сайта после загрузки должен лежать файл `index.html`.

То есть структура на сервере должна быть такой:

```text
<document_root>/index.html
<document_root>/app.js
<document_root>/css/app.css
<document_root>/img/...
<document_root>/lang/...
<document_root>/vender/...
```

### 4. Проверьте сайт

После загрузки откройте:

```text
https://ваш-домен/
```

Если открывается пустая страница или видна ошибка загрузки файла:

1. Проверьте, что `index.html` лежит именно в document root.
2. Проверьте, что рядом доступны `app.js`, `css/app.css`, `img`, `lang`, `vender`.
3. Проверьте, что домен работает по HTTPS без mixed content и без редирект-циклов.

## Вариант 2. Сборка прямо на Ubuntu 24

Этот вариант нужен, если вы хотите собирать проект прямо на сервере.

### 1. Установите пакеты

```bash
sudo apt update
sudo apt install -y curl git
```

### 2. Установите Node.js LTS

Для этого проекта разумно использовать Node.js 20 LTS:

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

### 3. Склонируйте проект и соберите production

```bash
git clone <URL_ВАШЕГО_РЕПОЗИТОРИЯ> /opt/lampa-source
cd /opt/lampa-source
npm install
npx gulp pack_web
```

После этого готовая публикация будет лежать в:

```bash
/opt/lampa-source/build/web
```

### 4. Скопируйте сборку в document root сайта

Пример:

```bash
rsync -av --delete /opt/lampa-source/build/web/ /var/www/USER/data/www/DOMAIN/
```

Подставьте свой фактический путь сайта из FastPanel.

## Настройка Nginx в FastPanel

Для базового деплоя отдельная сложная конфигурация не нужна. Обычно достаточно стандартного статического сайта.

Если хотите добавить fallback для SPA-маршрутов, в дополнительные директивы Nginx можно положить:

```nginx
location / {
    try_files $uri $uri/ /index.html;
}
```

Если в вашей конфигурации и так уже используется `try_files`, дублировать блок не нужно.

## Обновление релиза

Схема обновления:

1. Обновить код репозитория.
2. Пересобрать:

```bash
npm install
npx gulp pack_web
```

3. Залить заново содержимое `build/web/` в document root сайта.

Если используете серверную сборку:

```bash
cd /opt/lampa-source
git pull
npm install
npx gulp pack_web
rsync -av --delete build/web/ /var/www/USER/data/www/DOMAIN/
```

## Если нужен свой `account_site` или WebSocket

В репозитории есть настройки для кастомной интеграции через `window.lampa_settings`, например:

- `account_site`
- `account_domain`
- `socket_url`
- `account_socket_use`

Но сам backend для этих значений здесь не реализован.

Это значит:

- сам интерфейс Lampa на FastPanel поднимется как статический сайт;
- функции, завязанные на ваш auth/socket backend, надо подключать отдельно;
- если такого backend нет, оставляйте обычный статический деплой без этих интеграций.

## Быстрая проверка после деплоя

Проверьте в браузере:

1. Открывается `https://домен/`.
2. Возвращается `200` на `index.html`.
3. Возвращается `200` на `app.js`.
4. Возвращается `200` на `css/app.css`.
5. В DevTools нет `404` на ассеты.

## Что использовать в этом проекте

Для production используйте:

```bash
npx gulp pack_web
```

Не используйте для production:

```bash
npm run start
```
