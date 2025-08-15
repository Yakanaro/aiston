## Aiston — запуск в Docker и проверка API

### Требования
- Docker + Docker Compose
- Make (опционально, но удобно — есть `Makefile`)

### Быстрый старт

```bash
# имя токена можно переопределить: make setup name="my-local"
make setup
```

Открывайте API через `http://localhost:8080`.

### Проверка API через curl
```bash
# Используя токен из .env (AUTH_TOKEN)
TOKEN=$(grep '^AUTH_TOKEN=' .env | cut -d'=' -f2)

# Создать задачу
curl -sS -X POST 'http://localhost:8080/api/tasks' \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"audio_url":"https://example.com/audio.mp3","metadata":{}}'

# Получить задачу по ID (подставьте id из ответа на создание)
ID=<ID_ИЗ_ОТВЕТА>
curl -sS "http://localhost:8080/api/tasks/$ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Accept: application/json'
```

Ускоренная проверка через цели `Makefile`:
```bash
# Создать задачу
make task-create url="https://example.com/audio.mp3"

# Получить последнюю созданную задачу
make task-get

# Или по ID
make task-get id=1

# Создать задачу и дождаться завершения (пуллит статус каждые 5 сек)
make task-create-and-wait url="https://example.com/audio.mp3"
```

### Очереди
- По умолчанию используется `QUEUE_CONNECTION=database`.
- Воркер очереди поднимается как сервис `queue-worker` автоматически при `make up`.

Полезные команды:
```bash
make logs-queue     # логи воркера очереди
make queue-work     # ручной запуск воркера в app-контейнере
make queue-failed   # просмотр упавших джоб
make queue-flush    # очистка упавших джоб
```


### Тесты
```bash
make test           # все тесты
make test-feature   # только feature
make test-unit      # только unit
```

### Диагностика и обслуживание
```bash
make ps             # статус контейнеров
make logs           # все логи
make logs-nginx     # логи nginx
make logs-app       # логи php-fpm/app
make logs-postgres  # логи postgres

make restart        # перезапуск всех
make restart-app    # перезапуск app
make restart-queue  # перезапуск queue-worker

make cache-clear    # очистка кэшей Laravel
make clean          # полная очистка (контейнеры + volume)

# Выполнить любой artisan
make artisan cmd="route:list"
```

### Из чего состоит API
- `POST /api/tasks` — создать задачу транскрибации
- `GET /api/tasks/{id}` — получить состояние/результат задачи

Доступ защищён middleware по токену `Authorization: Bearer <TOKEN>`.

