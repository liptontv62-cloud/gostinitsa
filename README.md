# HOTEL — ИС Гостиница
Учебный проект ПМ.05. PHP 8.2+, MySQL 8 / MariaDB, HTML, CSS, JavaScript. Документ-корень — `public/` (не весь репозиторий).

## Open Server Panel
1. Клонируйте репозиторий, откройте каталог в Visual Studio Code. В Open Server Panel включите PHP и MySQL/MariaDB. Создайте локальный домен, укажите document root на `public/`. Если версия панели требует конфигурационный файл проекта, задайте корень по правилам этой версии. Убедитесь, что PHP имеет `pdo_mysql`.
2. В SQL-клиенте (например, phpMyAdmin) импортируйте `database/mysql.sql`. Создайте `config.local.php` копированием `config.example.php`; укажите действительные хост, порт, логин и пароль БД. Для учебного проекта локальный root допустим, для эксплуатации создайте отдельного пользователя только с SELECT/INSERT/UPDATE на hotel.
3. В терминале VS Code с PHP из панели выполните `php tools/init-users.php`. Откройте адрес локального домена в браузере. Демо: `admin / HotelAdmin!26`, `viewer / HotelViewer!26`. Не используйте эти пароли вне учебного стенда.
4. Обзор → Новая бронь → заполните данные, выберите номер и период → Сохранить. Журнал поддерживает поиск, редактирование и отмену с подтверждением. Отмена логическая: строка остаётся в БД. Роль viewer только читает. Для демонстрации дефектов переключитесь на ветку `module-2` в отдельной рабочей копии.

## Проверка в Linux (не Open Server Panel)
В тестовом окружении используется SQLite, тот же PHP и SQL-операции, отдельная схема `database/sqlite.sql`. `python3 tools/init-sqlite.py /tmp/hotel.sqlite`; `HOTEL_TEST_SQLITE=/tmp/hotel.sqlite php -d ffi.enable=true tools/init-users.php`; `HOTEL_TEST_SQLITE=/tmp/hotel.sqlite php -d ffi.enable=true -S 127.0.0.1:8080 -t public`. При наличии PDO SQLite FFI не нужен. Тестовый адаптер не предназначен для развёртывания MySQL.

## GitHub
Исходники учебной практики опубликованы в данном репозитории. Коммиты отражают этапы разработки, проверки и исправления дефектов; ветка module-2 сохраняет учебную версию. Не добавляйте config.local.php или данные настоящих гостей.

## Figma
https://www.figma.com/design/u11cPyPioyPweNHXMy57r1 — кликабельные сценарии; это прототип, а не подключённая БД.
