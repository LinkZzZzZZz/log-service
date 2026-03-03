# 📋 Log Ingestion Service

Микросервис для сбора и обработки логов от агентов с публикацией в RabbitMQ.

**Стек:** PHP 8.4 · Symfony 8 · RabbitMQ · Symfony Messenger

---

## 📐 Архитектура

```
src/
├── Gateway/                         # 🌐 HTTP-слой (тонкие Action-контроллеры)
│   ├── Api/
│   │   └── V1/
│   │       ├── Log/Ingest/
│   │       │   └── Action.php       # POST /api/v1/logs/ingest
│   │       └── routing.php          # Роутинг с префиксом /api/v1
│   └── di.php                       # DI: авто-регистрация Action-классов
│
├── Log/                             # 📝 Доменный модуль логов
│   ├── DTO/
│   │   ├── LogEntryDTO.php          # DTO записи лога (с валидацией)
│   │   ├── LogBatchRequestDTO.php   # DTO батча запроса
│   │   ├── LogBatchResponseDTO.php  # DTO батча ответа
│   │   └── IngestLogsResultDTO.php  # Результат обработки
│   ├── Handler/
│   │   ├── IngestLogsHandler.php    # Application-слой: публикация в очередь
│   │   └── di.php
│   ├── Message/
│   │   └── ProcessLogMessage.php    # Сообщение Symfony Messenger
│   └── Service/
│       ├── LogPublisher.php         # Публикация в RabbitMQ
│       └── di.php
│
├── Shared/                          # 🔧 Общие утилиты
│   └── Service/
│       ├── BatchIdGenerator.php
│       └── di.php
│
└── Infrastructure/                  # ⚙️ Инфраструктурный слой
    ├── Kernel.php
    └── Http/
        ├── BaseController.php
        ├── EventListener/
        │   └── ApiExceptionListener.php  # Обработка API-исключений
        ├── Exception/
        │   ├── ApiException.php
        │   ├── BadRequestException.php
        │   └── ValidationException.php
        ├── Contracts/
        │   └── ResponseFactoryInterface.php
        ├── Factory/
        │   └── ResponseFactory.php
        └── di.php
```

### Слои

| Слой | Назначение |
|------|-----------|
| **Gateway** | HTTP Actions, роутинг, версионность API |
| **Log** | Доменная логика: DTO, Handler, Message, Service |
| **Shared** | Общие утилиты (BatchIdGenerator) |
| **Infrastructure** | Kernel, HTTP-абстракции, фабрика ответов, обработка исключений |

### DI-конфигурация

Каждый модуль имеет `di.php` — PHP-конфигурация контейнера зависимостей (не YAML). Импортируется автоматически через `config/services.php` (glob `src/**/di.php`).

### Версионность API

Роутинг через `src/Gateway/Api/V1/routing.php` с префиксом `/api/v1`. Для V2 — создать `src/Gateway/Api/V2/routing.php`.

---

## 🚀 Быстрый старт

### С Docker

```bash
cp .env.example .env
docker compose up -d
```

| Сервис | URL |
|--------|-----|
| API | `http://localhost:8080` |
| RabbitMQ Management | `http://localhost:15672` (guest / guest) |

### Без Docker (локально)

```bash
cp .env.example .env
composer install --ignore-platform-req=ext-amqp
php -S localhost:8080 -t public
```

---

## 📡 API

### `POST /api/v1/logs/ingest`

Принимает батч логов в формате JSON. Возвращает `202 Accepted`.

#### Поля лога

| Поле | Обязательное | Ограничение | Описание |
|------|:---:|:---:|----------|
| `timestamp` | ✅ | ISO 8601 | Время события |
| `level` | ✅ | — | `emergency` · `alert` · `critical` · `error` · `warning` · `notice` · `info` · `debug` |
| `service` | ✅ | 255 символов | Имя сервиса-источника |
| `message` | ✅ | 8192 символа | Текст лога |
| `context` | — | 64 ключа | Произвольный JSON-объект |
| `trace_id` | — | 256 символов | Идентификатор трассировки |

> 📦 Максимум **1000 логов** в одном батче.

#### Пример запроса

```bash
curl -X POST http://localhost:8080/api/v1/logs/ingest \
  -H "Content-Type: application/json" \
  -d '{
    "logs": [
      {
        "timestamp": "2026-02-26T10:30:45Z",
        "level": "error",
        "service": "auth-service",
        "message": "User authentication failed",
        "context": {
          "user_id": 123,
          "ip": "192.168.1.1",
          "error_code": "INVALID_TOKEN"
        },
        "trace_id": "abc123def456"
      }
    ]
  }'
```

#### ✅ Успешный ответ (202)

```json
{
  "success": true,
  "data": {
    "batch_id": "batch_550e8400e29b41d4a716446655440000",
    "logs_count": 2
  },
  "message": null
}
```

#### ❌ Ошибка валидации (400)

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": [
    {"field": "logs[0].timestamp", "message": "Field \"timestamp\" is required."},
    {"field": "logs[0].level", "message": "Field \"level\" must be one of: emergency, alert, critical, error, warning, notice, info, debug."},
    {"field": "logs[0].message", "message": "Field \"message\" is required."}
  ]
}
```

### Формат ответов

Все ответы API используют единый формат через `ResponseFactoryInterface`:

| Поле | Тип | Описание |
|------|-----|----------|
| `success` | `bool` | Успешность запроса |
| `data` | `object\|array` | Данные (при успехе) |
| `message` | `string\|null` | Сообщение |
| `errors` | `array` | Ошибки (при неудаче) |

---

## 🧪 Тесты

```bash
make test                # Все тесты
make test-unit           # Unit тесты
make test-integration    # Integration тесты
```

---

## 🛠 Make-команды

| Команда | Описание |
|---------|----------|
| `make install` | Установить зависимости |
| `make up` / `make down` | Запуск / остановка Docker |
| `make test` | Все тесты |
| `make test-unit` | Unit тесты |
| `make test-integration` | Integration тесты |
| `make phpstan` | Статический анализ |
| `make cs-check` / `make cs-fix` | Проверка / исправление код-стиля |
| `make rector-check` / `make rector` | Проверка / применение Rector |
| `make composer-check` | Проверка зависимостей |
| `make lint` | Все проверки (без изменений) |
| `make fix` | Все автоисправления |
| `make quality` | Исправить → проверить → протестировать |

---

## 🐇 RabbitMQ

Сообщения публикуются в очередь `logs.ingest` через exchange `logs` (direct).

Каждый лог отправляется отдельным сообщением с метаданными:

| Метаданные | Описание |
|------------|----------|
| `batchId` | Уникальный идентификатор батча |
| `publishedAt` | Время публикации |
| `retryCount` | Счётчик повторных попыток (начинается с 0) |
| `version` | Версия формата сообщения |

Сообщения **persistent** (durable). Стратегия повторных попыток: 3 попытки с экспоненциальной задержкой.

---

## ⚙️ Переменные окружения

| Переменная | Описание | По умолчанию |
|-----------|----------|:------------:|
| `APP_ENV` | Окружение | `dev` |
| `APP_SECRET` | Секрет приложения | — |
| `RMQ_USER` | Пользователь RabbitMQ | `guest` |
| `RMQ_PASS` | Пароль RabbitMQ | `guest` |
| `RMQ_HOST` | Хост RabbitMQ | `localhost` |
| `RMQ_PORT` | Порт RabbitMQ | `5672` |
| `RMQ_VHOST` | Virtual host | `%2f` |
| `MESSENGER_TRANSPORT_DSN` | DSN для RabbitMQ (собирается из RMQ_*) | — |

> Скопируйте `.env.example` в `.env` и заполните значения перед запуском.