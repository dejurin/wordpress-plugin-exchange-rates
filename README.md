# Exchange Rates

[![CI](https://github.com/dejurin/wordpress-plugin-exchange-rates/actions/workflows/ci.yml/badge.svg)](https://github.com/dejurin/wordpress-plugin-exchange-rates/actions/workflows/ci.yml)

Основной публичный репозиторий разработки WordPress-плагина **Exchange Rates**.
Опубликованную версию устанавливайте из [каталога WordPress.org](https://wordpress.org/plugins/exchange-rates/).
GitHub ZIP исходников всего репозитория не является установочным ZIP плагина.

Код в `plugin/trunk/` импортирован из официального релиза **1.3.2** без изменений.
Плагин предоставляет виджеты и шорткоды конвертера, таблицы курсов и ценовых бейджей.
Описание функций и источников данных находится в [readme плагина](plugin/trunk/readme.txt).

## Структура

```text
plugin/trunk/          Единственная рабочая копия плагина, включая vendor
wordpress/            Локальные WordPress, MariaDB и WP-CLI
scripts/              Проверка, упаковка и создание локального .env
tests/                Изолированные smoke-проверки с синтетическими курсами
docs/                 Разработка, выпуск и происхождение импортированного кода
.github/workflows/    CI без публикации в WordPress SVN
```

SVN `tags/`, `.svn`, WordPress core, БД и реальные `.env` в Git не хранятся.
Код редактируется на хосте в `plugin/trunk/`; контейнер видит его через read-only bind mount.

## Быстрый запуск разработки

Нужны Git, Python 3.9+, Docker Engine/Desktop и Docker Compose v2 с поддержкой `up --wait`.
Команды выполняются из корня репозитория:

```sh
python3 scripts/dev-env.py
docker compose --env-file wordpress/.env -f wordpress/compose.yaml up -d --wait --wait-timeout 180
docker compose --env-file wordpress/.env -f wordpress/compose.yaml run --rm --entrypoint sh cli /opt/dev/init.sh
```

Сайт: <http://127.0.0.1:18972>, админка: <http://127.0.0.1:18972/wp-admin/>.
Логин и сгенерированный пароль находятся в локальном `wordpress/.env`.
Генератор не перезаписывает существующий `.env`; при повторном запуске этот шаг пропускается.
Обычный режим получает реальные курсы по HTTP из API плагина. Синтетический тестовый режим включается отдельно.

Остановка сохраняет БД и файлы сайта:

```sh
docker compose --env-file wordpress/.env -f wordpress/compose.yaml down
```

## Проверки и установочный ZIP

```sh
python3 scripts/plugin.py check
python3 scripts/plugin.py lint
python3 scripts/plugin.py package
```

Lint требует установленный PHP. ZIP и SHA-256 создаются в `dist/`; повторная упаковка заменяет только эти сборочные файлы.
Результат: `dist/exchange-rates.1.3.2.zip`, внутри — каталог `exchange-rates/`.
CI проверяет синтаксис на PHP 5.6, 8.3 и 8.4, выполняет smoke на WordPress 7.1.3 / PHP 8.3 и сохраняет ZIP как артефакт.
Проверка синтаксиса не означает полной runtime-совместимости со всеми этими версиями PHP.

- [Разработка и smoke](docs/development.md)
- [Упаковка и порядок выпуска](docs/release.md)
- [Происхождение кода и отложенная CSS-правка](docs/normalization.md)

## Лицензия

GPL version 2 or later, как указано в заголовках опубликованного плагина; текст GPLv2 — в [LICENSE](LICENSE).
Сторонние библиотеки сохраняют собственные лицензии в `plugin/trunk/vendor/`.
