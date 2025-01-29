# CBRates

## Описание проекта
Приложение для получения и отображения курсов валют Центрального Банка РФ.

## Технологии
- PHP 8
- Symfony
- Redis
- JavaScript
- Docker

## Требования
- Docker
- Docker Compose

## Установка
1. Клонируйте репозиторий
2. Запустите Docker-контейнеры
3. Выполните миграции базы данных

## Запуск
\`\`\`bash
docker-compose up -d
\`\`\`

## Тестирование
\`\`\`bash
# PHP тесты
php bin/phpunit

# JavaScript тесты
npm test
\`\`\`
