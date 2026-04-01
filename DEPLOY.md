# Развёртывание CRM на сервере

## 1. Структура файлов

```
crmShop/
├── api/
│   ├── order_accept.php      # Приём денег админом
│   ├── order_status.php      # Смена статуса заказа
│   ├── variations.php        # Вариации товара (AJAX)
│   └── variations_all.php    # Все вариации
├── config/
│   ├── database.php         # Подключение к БД
│   └── path.php             # Базовый путь (для подпапки)
├── includes/
│   └── header.php           # Бургер-меню и шапка
├── css/
│   └── style.css            # Общие стили
├── js/
│   ├── orders_create.js     # Форма создания заказа
│   └── products_add.js      # Динамические вариации
├── auth.php                 # Функции авторизации
├── database.sql             # Схема БД
├── index.php                # Редирект (входная точка)
├── login.php
├── logout.php
├── register.php
├── dashboard.php
├── orders.php
├── orders_create.php
├── order_view.php
├── requests.php             # Запросы (ожидают приёма денег)
├── cashbox.php              # История кассы
├── products.php
└── products_add.php
```

## 2. Требования сервера

- PHP 7.4+ (рекомендуется 8.x)
- MySQL 5.7+ или MariaDB 10.3+
- Расширения PHP: PDO, pdo_mysql, session, json, mbstring

## 3. Шаги развёртывания

### 3.1. Загрузка файлов

Загрузите все файлы на сервер через FTP/SFTP или панель хостинга. Сохраните структуру папок.

### 3.2. База данных

1. Создайте базу данных в панели хостинга (phpMyAdmin и т.п.).
2. Импортируйте `database.sql` — выполните весь SQL-файл.
3. Имя БД в `database.sql`: `crm_shop`. В `config/database.php` укажите то же имя: `define('DB_NAME', 'crm_shop');`

### 3.3. Настройка config/database.php

```php
define('DB_HOST', 'localhost');   // или хост БД хостинга
define('DB_NAME', 'crm_shop');    // имя вашей БД
define('DB_USER', 'ваш_пользователь');
define('DB_PASS', 'ваш_пароль');
```

### 3.4. Настройка config/path.php

- **Проект в корне домена** (например, `https://mysite.com/`):
  ```php
  define('BASE_PATH', '');
  ```

- **Проект в подпапке** (например, `https://mysite.com/crm/`):
  ```php
  define('BASE_PATH', '/crm');
  ```

### 3.5. Права доступа

- Папки: 755
- Файлы: 644
- `config/database.php` и `config/path.php` — 644 (не 777)

### 3.6. Безопасность

1. Удалите или ограничьте доступ к `register.php` после создания админа (если регистрация не нужна).
2. Установите надёжный пароль для БД.
3. Используйте HTTPS.
4. Проверьте, что `config/` не доступен напрямую по URL (на shared hosting обычно недоступен).

## 4. Проверка после развёртывания

1. Откройте сайт — должен открыться `login.php`.
2. Войдите как admin (email: admin@example.com, пароль: password — из database.sql).
3. **Сразу смените пароль** — зарегистрируйте нового админа или измените в БД.
4. Проверьте: Заказы, Запросы, Товары, Создание заказа.

## 5. Частые проблемы

| Проблема | Решение |
|----------|---------|
| Белый экран | Включите отображение ошибок: `error_reporting(E_ALL)` в начале index.php для отладки |
| Ошибка подключения к БД | Проверьте DB_HOST, DB_NAME, DB_USER, DB_PASS в config/database.php |
| 404 на страницах | Проверьте BASE_PATH в config/path.php |
| Сессия не сохраняется | Проверьте права на папку сессий, на shared hosting обычно работает |
| Ссылки ведут не туда | Убедитесь, что BASE_PATH указан правильно |

## 6. Роли пользователей

- **admin** — полный доступ: заказы, запросы, товары, создание заказов, приём денег.
- **courier** — только свои заказы, смена статуса (Доставлен/Отменить).
