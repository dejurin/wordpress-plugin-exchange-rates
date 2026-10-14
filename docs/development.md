# Локальная разработка

## Назначение и границы

`plugin/trunk/` — единственный источник кода. В Git не входят установленный WordPress, содержимое volumes, локальные секреты и результаты сборки.
`wordpress/compose.yaml` запускает отдельные WordPress, MariaDB и WP-CLI. Образы закреплены версией и manifest digest; обновлять оба значения нужно осознанно с повторным smoke.

По умолчанию используется проект Compose `exchange-rates-dev`, порт `127.0.0.1:18972`, WordPress 7.1.3, PHP 8.3, MariaDB 11.4.8 и WP-CLI 2.12.0.
БД не публикует порт на хосте. Образы имеют варианты amd64 и arm64.

## Запуск

Из корня репозитория:

```sh
python3 scripts/dev-env.py
docker compose --env-file wordpress/.env -f wordpress/compose.yaml config --quiet
docker compose --env-file wordpress/.env -f wordpress/compose.yaml up -d --wait --wait-timeout 180
docker compose --env-file wordpress/.env -f wordpress/compose.yaml run --rm --entrypoint sh cli /opt/dev/init.sh
```

Генератор создаёт `.env` с правами `0600` и случайными паролями. Существующий файл не заменяется.
При ручной настройке можно скопировать `.env.example` в `.env` и заполнить три пустых пароля; Compose откажется запускаться с пустыми значениями.
Не публикуйте `.env`, вывод `docker compose config` без `--quiet` или environment контейнеров.

Инициализация устанавливает WordPress только при отсутствии установки, не меняет пароль существующего пользователя и активирует плагин.
Имя пользователя — `WP_ADMIN_USER`, пароль — `WP_ADMIN_PASSWORD` из `.env`.
При активации обычного окружения плагин запрашивает реальные курсы с `https://api-bank.fex.to/`.
Отсутствие сети или данных не исправляется подстановкой тестовых курсов.

Редактирование `plugin/trunk/` на хосте сразу видно контейнерам; обновление/изменение файлов через админку отключено.
Автоматическое обновление WordPress и автоматический WP-Cron отключены для воспроизводимости.
Часовой HTTP-fallback самого плагина остаётся неизменным. Ручной запуск его события:

```sh
docker compose --env-file wordpress/.env -f wordpress/compose.yaml run --rm cli cron event run exchange-rates_data_update
```

## Логи и остановка

```sh
docker compose --env-file wordpress/.env -f wordpress/compose.yaml ps
docker compose --env-file wordpress/.env -f wordpress/compose.yaml logs --tail=100 wordpress
docker compose --env-file wordpress/.env -f wordpress/compose.yaml exec wordpress sh -c 'test ! -f wp-content/debug.log || tail -n 100 wp-content/debug.log'
docker compose --env-file wordpress/.env -f wordpress/compose.yaml down
```

`down` сохраняет оба named volume. **Не добавляйте `--volumes` для обычной остановки: это удалит локальную БД и сайт.**
Смена паролей в `.env` не меняет уже созданного пользователя БД: сначала требуется согласованное изменение пользователя внутри БД. Генератор поэтому не заменяет `.env`.

Порт выбирайте до первой установки. Если меняете `WP_PORT` у существующего сайта, пересоздайте контейнеры и обновите сохранённый URL перед повторной инициализацией, например для порта 18973:

```sh
docker compose --env-file wordpress/.env -f wordpress/compose.yaml up -d --wait
docker compose --env-file wordpress/.env -f wordpress/compose.yaml run --rm cli search-replace http://127.0.0.1:18972 http://127.0.0.1:18973 --all-tables-with-prefix --skip-columns=guid
```

Эта команда изменяет URL в **локальной БД выбранного Compose-проекта**. Не используйте её для удалённых сайтов.

## Изолированный smoke с синтетическими курсами

Используйте отдельное имя проекта; тесты создают страницу и записывают тестовые курсы в его БД.
Если обычный стенд уже запущен, остановите его или выберите свободный порт для QA.
Следующие команды используют отдельные volumes и порт 18973:

```sh
export COMPOSE_PROJECT_NAME=exchange-rates-qa
export COMPOSE_FILE=wordpress/compose.yaml:wordpress/compose.test.yaml
export WP_PORT=18973
docker compose --env-file wordpress/.env up -d --wait --wait-timeout 180
docker compose --env-file wordpress/.env run --rm --entrypoint sh cli /opt/dev/init.sh
docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/smoke.php
docker compose --env-file wordpress/.env down
unset COMPOSE_PROJECT_NAME COMPOSE_FILE WP_PORT
```

Override подключает MU-плагин только в тестовом окружении. Он отвечает синтетическим JSON на точные endpoints `rates/currencyrate.json` и `sources.json` сервиса `api-bank.fex.to` и блокирует остальные исходящие запросы WordPress HTTP API.
Синтетические значения не предназначены для финансового использования и не попадают в ZIP плагина.
Обычный Compose не подключает fixture. Не используйте одни и те же volumes для тестового и обычного режимов: сохранённые тестовые курсы останутся в БД.

Smoke проверяет активацию `exchange-rates/Install.php`, автозагрузку, регистрацию cron, обновление данных через HTTP API WordPress, три шорткода, два виджета, рендер настроек и область загрузки admin CSS.
В конце он выводит URL страницы `Exchange Rates Smoke - synthetic data`.
Откройте страницу и админку: проверьте работу конвертера, таблицы и бейджа, сохранение настроек, загрузку CSS/JS и отсутствие ошибок PHP/браузера.
Синтетический smoke не подтверждает доступность или корректность реальных внешних источников.

## CI и ограничения

- PHP 5.6 / 8.3 / 8.4: синтаксис всех 56 PHP-файлов плагина, включая поставляемый vendor.
- WordPress 7.1.3 / PHP 8.3: изолированный runtime smoke и HTTP-доступность ресурсов.
- Упаковка: согласованность версии и точное совпадение содержимого ZIP с `plugin/trunk/`.

Старые версии WordPress и полный диапазон PHP не проверяются runtime-тестом. Минимальные требования исходного релиза не изменяются в рамках нормализации.
CI имеет только `contents: read`, не использует SVN-секреты и не публикует релизы.
Очистка с `down --volumes` в CI применяется только к временному проекту с уникальным именем конкретного запуска.
