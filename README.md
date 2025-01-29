# CBRates

## 🌐 Описание проекта
Проект представляет собой систему для получения, хранения и отображения курсов валют Центрального Банка Российской Федерации с использованием современного стека технологий.

## 🛠 Технологический стек
- **Backend**: PHP 8, Symfony
- **Frontend**: JavaScript, HTML/CSS
- **Инфраструктура**: Docker, MySQL, Redis
- **Тестирование**: PHPUnit, Jest

## 📋 Функциональность
- Автоматическое получение курсов валют с сайта ЦБ РФ
- Хранение исторических данных о курсах
- Виджет с актуальными курсами валют
- Автоматическое обновление курсов
- Отображение трендов (рост/падение курса)

## 🚀 Установка и настройка

### Предварительные требования
- Docker
- Docker Compose
- Git

### Клонирование репозитория
```bash
git clone https://github.com/sendema/CBRates.git
cd CBRates
```

### Запуск приложения
```bash
# Сборка и запуск контейнеров
docker-compose up -d --build

# Установка зависимостей Composer
docker-compose exec php composer install

# Создание базы данных
docker-compose exec php php bin/console doctrine:database:create
docker-compose exec php php bin/console doctrine:migrations:migrate
```

## 🔧 Настройки

### Конфигурация валют
В файле `config/services.yaml` можно настроить:
- Список валют для парсинга
- Список валют для отображения в виджете

### Интервал обновления
В `public/js/exchange-rate-widget.js` настройте `updateInterval`:
```javascript
const widget = new ExchangeRateWidget('exchange-rates', {
    updateInterval: 300000,
    apiUrl: '/api/rates/current'
});
```

## 🌍 API Эндпоинты

### Получение текущих курсов
`GET /api/rates/current`

**Пример ответа**:
```json
[
  {
    "code": "CNY",
    "rate": 13.46,
    "value": "13.4568",
    "nominal": "1.0000",
    "previousValue": "13.4568",
    "change": 0,
    "trend": "same"
  },
  {
    "code": "EUR",
    "rate": 101.6,
    "value": "101.5957",
    "nominal": "1.0000",
    "previousValue": "101.5957",
    "change": 0,
    "trend": "same"
  },
  {
    "code": "USD",
    "rate": 97.97,
    "value": "97.9658",
    "nominal": "1.0000",
    "previousValue": "97.9658",
    "change": 0,
    "trend": "same"
  }
]
```

### Получение курсов за период
`GET /api/rates/history?start_date=2025-01-28&end_date=2025-01-29&currencies=USD,EUR`

**Пример ответа**:
```json
[
  {
    "date": "2025-01-29",
    "currency": "EUR",
    "rate": 101.6,
    "value": "101.5957",
    "nominal": "1.0000",
    "previousValue": "101.5957",
    "change": 0,
    "trend": "та же самая"
  },
  {
    "date": "2025-01-29",
    "currency": "USD",
    "rate": 97.97,
    "value": "97.9658",
    "nominal": "1.0000",
    "previousValue": "97.9658",
    "change": 0,
    "trend": "та же самая"
  }
]
```


## 🧪 Тестирование

### Backend-тесты
```bash
docker-compose exec php bash 
php bin/phpunit tests

```

## 🔒 Безопасность
- Все внешние подключения через защищенные контейнеры
- Логирование всех операций
- Обработка ошибок при получении курсов

## 📦 Развертывание
- Поддержка Docker для простого развертывания
- Настроены volume для персистентности данных
- Возможность масштабирования
