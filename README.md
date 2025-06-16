# My Books Backend API

## Огляд проекту

Це Laravel-based API для системи управління книгами з повним логуванням життєвого циклу HTTP-запитів та збереженням аналітичних даних у MongoDB.

## Основні можливості

- 🔐 Система автентифікації (реєстрація, логін, відновлення паролю)
- 📚 API для управління книгами
- 📊 **Повне логування життєвого циклу запитів з MongoDB**
- 🔍 Аналіз продуктивності та моніторинг
- ⚡ Артизанська команда для аналізу запитів

## Архітектура логування

### Система відстеження запитів

Проект реалізує комплексну систему логування, яка відстежує кожен HTTP-запит від початку до завершення:

#### Компоненти системи логування

1. **RequestTrackingMiddleware** (`app/Http/Middleware/RequestTrackingMiddleware.php`)
   - Автоматично відстежує всі HTTP-запити
   - Генерує унікальний ID для кожного запиту
   - Додає `X-Request-ID` заголовок до відповіді

2. **RequestLogger Service** (`app/Services/RequestLogger.php`)
   - Центральний сервіс для збирання даних про запити
   - Збирає метрики продуктивності
   - Відстежує SQL-запити та їх час виконання
   - Логує виключення та помилки

3. **MongoServiceProvider** (`app/Providers/MongoServiceProvider.php`)
   - Налаштовує з'єднання з MongoDB
   - Створює індекси для оптимальної продуктивності
   - Автоматично налаштовує TTL для документів (30 днів)

#### Структура даних в MongoDB

Кожен запит зберігається як окремий документ із такою структурою:

```json
{
  "_id": "uuid-запиту",
  "started_at": "2025-06-16T10:30:00.000Z",
  "finished_at": "2025-06-16T10:30:00.250Z",
  "method": "POST",
  "url": "https://api.example.com/auth/login",
  "status": 200,
  "duration_ms": 250.75,
  "ip": "192.168.1.100",
  "user_agent": "Mozilla/5.0...",
  "user_id": 123,
  "input": {
    "email": "user@example.com"
  },
  "query_count": 3,
  "db_time_ms": 45.2,
  "queries": [
    {
      "sql": "SELECT * FROM users WHERE email = ?",
      "bindings": ["user@example.com"],
      "time_ms": 15.3,
      "timestamp": "2025-06-16T10:30:00.100Z"
    }
  ],
  "events": [
    {
      "event": "[middleware] request_started",
      "timestamp": "2025-06-16T10:30:00.000Z",
      "data": {
        "method": "POST",
        "url": "https://api.example.com/auth/login"
      }
    }
  ],
  "errors": [
    {
      "class": "App\\Exceptions\\ValidationException",
      "message": "Validation failed",
      "file": "/app/Http/Requests/LoginRequest.php",
      "line": 25,
      "trace": "Stack trace..."
    }
  ]
}
```

### Можливості аналізу

#### Артизанська команда `requests:analyze`

Потужна команда для аналізу зібраних даних:

```bash
# Аналіз конкретного запиту
php artisan requests:analyze {request-id}

# Показати статистику
php artisan requests:analyze --stats

# Фільтрація по параметрах
php artisan requests:analyze --user=123 --method=POST --status=500
php artisan requests:analyze --slow=1000 --errors
php artisan requests:analyze --from="2025-06-01" --to="2025-06-16"

# Різні формати виводу
php artisan requests:analyze --format=json
php artisan requests:analyze --format=detailed
```

#### Можливості фільтрації

- **По користувачу**: `--user=ID`
- **По HTTP методу**: `--method=GET|POST|PUT|DELETE`
- **По статус коду**: `--status=200|404|500`
- **По URL**: `--url=pattern`
- **Повільні запити**: `--slow=1000` (мілісекунди)
- **Тільки помилки**: `--errors`
- **Часовий діапазон**: `--from` та `--to`

#### Статистичні звіти

```bash
php artisan requests:analyze --stats
```

Показує:
- Розподіл статус кодів
- Розподіл HTTP методів
- Статистику продуктивності
- Найчастіші помилки

## Налаштування

### Вимоги

- PHP 8.2+
- Laravel 12.0+
- MongoDB 4.4+
- Composer

### Встановлення

1. Клонуйте репозиторій:
```bash
git clone <repository-url>
cd apps/backend
```

2. Встановіть залежності:
```bash
composer install
npm install
```

3. Налаштуйте середовище:
```bash
cp .env.example .env
php artisan key:generate
```

4. Налаштуйте MongoDB у `.env`:
```env
MONGODB_DSN=mongodb://localhost:27017
MONGODB_DATABASE=my_books
MONGODB_REQUEST_COLLECTION=requests
```

5. Запустіть міграції:
```bash
php artisan migrate
```

### Конфігурація MongoDB

У `config/database.php` додано конфігурацію для MongoDB:

```php
'mongodb' => [
    'dsn' => env('MONGODB_DSN'),
    'database' => env('MONGODB_DATABASE', 'my_books'),
    'request_tracking_collection' => env('MONGODB_REQUEST_COLLECTION', 'requests'),
    'options' => [
        'connectTimeoutMS' => 3000,
        'socketTimeoutMS' => 5000,
    ],
],
```

## Розробка

### Запуск проекту

```bash
# Запуск всіх сервісів (сервер, черга, логи, Vite)
composer run dev

# Або окремо
php artisan serve
php artisan queue:listen
npm run dev
```

### Тестування

```bash
composer run test
# або
php artisan test
```

### Linting

```bash
./vendor/bin/pint
```

## Моніторинг та аналітика

### Перевірка доступності MongoDB

```php
use App\Services\RequestLogger;

if (RequestLogger::isMongoAvailable()) {
    // MongoDB доступна
}
```

### Додавання кастомних подій

```php
use App\Services\RequestLogger;

RequestLogger::addEvent('user_action', [
    'action' => 'book_created',
    'book_id' => $book->id
]);
```

### Логування запитів в реальному часі

Всі SQL-запити автоматично логуються за допомогою Laravel DB событій. Кожен запит включає:
- SQL з прив'язаними параметрами
- Час виконання
- Timestamp

## API Endpoints

### Автентифікація

- `POST /api/auth/register` - Реєстрація користувача
- `POST /api/auth/login` - Авторизація
- `POST /api/auth/logout` - Вихід
- `POST /api/auth/forgot-password` - Запит на відновлення паролю
- `POST /api/auth/reset-password` - Скидання паролю
- `POST /api/auth/verify-email` - Верифікація email

### Моніторинг

Кожен API-запит автоматично логується та доступний для аналізу через `requests:analyze` команду.

## Безпека

- Паролі не логуються (автоматично виключаються з `input`)
- MongoDB з'єднання захищене таймаутами
- TTL для документів (30 днів) для автоматичного очищення

## Індекси MongoDB

Система автоматично створює оптимізовані індекси:
- `_id` (первинний ключ)
- `started_at` (сортування за датою)
- `user_id + started_at` (запити користувача)
- `status + started_at` (фільтрація за статусом)
- `duration_ms` (аналіз продуктивності)
- TTL індекс для автоматичного видалення старих записів

## Технічна реалізація

### Життєвий цикл запиту

1. **Початок запиту** - `RequestTrackingMiddleware` генерує унікальний ID
2. **Збирання даних** - `RequestLogger` збирає інформацію про запит
3. **Відстеження SQL** - Автоматичне логування всіх database запитів
4. **Обробка помилок** - Логування виключень та помилок
5. **Завершення** - Збереження всіх даних у MongoDB

### Файли конфігурації

- `config/database.php:51-67` - Конфігурація MongoDB підключення
- `app/Providers/MongoServiceProvider.php:45-66` - Створення індексів
- `app/Http/Middleware/RequestTrackingMiddleware.php` - Middleware для відстеження

## Ліцензія

MIT License