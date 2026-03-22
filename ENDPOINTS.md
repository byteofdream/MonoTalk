# MonoTalk - Список эндпойнтов и ответы сервера

## 📄 Страницы (HTML страницы)

### Главная страница
**GET** `/index.php`
- **Параметры:** `?category=ID&sort=hot|new|popular|discussed`
- **Ответ:** HTML страница с лентой постов, окно авторизации/мобильное меню (если не залогирован)
- **Статус:** 200 OK

### Страница регистрации
**GET** `/register.php`
- **Ответ:** HTML форма регистрации
- **Статус:** 200 OK

### Страница входа
**GET** `/login.php`
- **Ответ:** HTML форма входа
- **Статус:** 200 OK

### Страница добро пожаловать
**GET** `/welcome.php`
- **Требует:** Авторизация
- **Ответ:** HTML интро-страница для новых пользователей
- **Статус:** 200 OK

### Страница профиля
**GET** `/profile.php`
- **Параметры:** `?username=USERNAME` (опционально)
- **Ответ:** HTML профиль пользователя с постами и данными
- **Статус:** 200 OK / 404 (если пользователь не найден)

### Страница поста
**GET** `/post.php`
- **Параметры:** `?id=POST_ID`
- **Ответ:** HTML страница поста с комментариями
- **Статус:** 200 OK / 404 (если пост не найден)

### Страница создания поста
**GET** `/create.php`
- **Требует:** Авторизация
- **Ответ:** HTML форма создания поста
- **Статус:** 200 OK / редирект на login.php (если не авторизован)

### Страница создания сабреддита
**GET** `/create_subreddit.php`
- **Требует:** Авторизация
- **Ответ:** HTML форма создания сабреддита
- **Статус:** 200 OK / редирект на login.php (если не авторизован)

### Страница сабреддита
**GET** `/rules.php`
- **Параметры:** `?category=ID`
- **Ответ:** HTML правила сабреддита
- **Статус:** 200 OK

### Страница поиска
**GET** `/search.php`
- **Параметры:** `?query=SEARCH_QUERY&category=ID`
- **Ответ:** HTML результаты поиска
- **Статус:** 200 OK

### Страница новостей/уведомлений
**GET** `/news.php`
- **Требует:** Авторизация
- **Ответ:** HTML список уведомлений
- **Статус:** 200 OK

### Страница настроек
**GET** `/settings.php`
- **Требует:** Авторизация
- **Ответ:** HTML форма настроек пользователя
- **Статус:** 200 OK

---

## 🔌 API Эндпойнты

### Аутентификация

#### Регистрация пользователя
**POST** `/api/register.php`
- **Параметры (JSON or Form):**
  - `username` (string, обязательно) - минимум 3 символа, буквы/цифры/_
  - `password` (string, обязательно) - минимум 6 символов
  - `email` (string)
  
- **Успешный ответ (200):**
```json
{
  "success": true,
  "redirect": "/welcome.php",
  "user_id": 123
}
```

- **Ошибки:**
```json
{
  "success": false,
  "error": "Username минимум 3 символа"
}
// или
{
  "success": false,
  "error": "Пароль минимум 6 символов"
}
// или
{
  "success": false,
  "error": "Username только буквы, цифры и _"
}
// или
{
  "success": false,
  "error": "Username уже занят"
}
```

- **Побочные эффекты:**
  - Создаёт нового пользователя в `data/users.json`
  - Устанавливает cookie сессии
  - Редирект на welcome.php

---

#### Вход в аккаунт
**POST** `/api/login.php`
- **Параметры (JSON or Form):**
  - `username` (string, обязательно)
  - `password` (string, обязательно)
  - `redirect` (string, опционально)

- **Условия проверки:**
  - Требует метод POST
  - Проверяет username и пароль
  - Проверяет, не забанен ли пользователь

- **Успешный ответ (200):**
```json
{
  "success": true,
  "redirect": "/index.php"  // или custom redirect
}
```

- **Ошибки:**
```json
{
  "success": false,
  "error": "Fill in all fields"
}
// или
{
  "success": false,
  "error": "Invalid username or password"
}
// или
{
  "success": false,
  "error": "Account is banned"
}
```

- **Побочные эффекты:**
  - Устанавливает cookie сессии
  - Редирект на указанный URL или index.php

---

#### Выход из аккаунта
**GET/POST** `/api/logout.php`
- **Требует:** Авторизация
- **Ответ:** Редирект на `/index.php`
- **Побочные эффекты:**
  - Удаляет cookie сессии
  - Редирект на главную

---

### Посты

#### Создание поста
**POST** `/api/create_post.php`
- **Требует:** Авторизация
- **Параметры (JSON or Form):**
  - `title` (string, обязательно) - название поста
  - `content` (string, опционально) - текст поста
  - `category` (string, обязательно) - ID сабреддита
  - `anonymous` (boolean, опционально) - анонимный ли пост
  - `image` (file, опционально) - изображение (макс 5 MB, JPG/PNG/GIF/WEBP)

- **Проверки:**
  - Спам-защита: макс 1 пост за 10 секунд
  - Проверка модерации (слова-триггеры)
  - Требует либо текст, либо изображение
  - Валидация формата изображения

- **Успешный ответ (200):**
```json
{
  "success": true,
  "post_id": 456,
  "redirect": "/post.php?id=456"
}
```

- **Ошибки:**
```json
{
  "success": false,
  "error": "Title is required"
}
// или
{
  "success": false,
  "error": "Choose a valid subreddit"
}
// или
{
  "success": false,
  "error": "Image must be 5 MB or smaller"
}
// или
{
  "success": false,
  "error": "Only JPG, PNG, GIF, WEBP are allowed"
}
// или (модерация)
{
  "success": false,
  "error": "Content contains banned word",
  "moderation": {
    "allowed": false,
    "reason": "...",
    "action": "mute|ban",
    "strikes": 1
  }
}
```

- **Статус код:** 405 (если не POST), 200 (OK), 400 (валидация)

---

#### Получение постов
**GET/POST** `/api/get_posts.php`
- **Параметры:**
  - `category` (string, опционально) - ID сабреддита для фильтра
  - `sort` (string, опционально) - `hot|new|popular|discussed` (по умолчанию `hot`)
  - `limit` (int, опционально) - количество постов в ответе
  - `offset` (int, опционально) - смещение для пагинации
  - `search` (string, опционально) - текстовый поиск по заголовку и содержанию

- **Успешный ответ (200):**
```json
{
  "success": true,
  "total": 123,
  "count": 20,
  "category": "1",
  "sort": "hot",
  "posts": [ ... ]
}
```

- **Статус код:** 405 (если метод не GET/POST), 200 (OK)

---

#### Получение информации о посте
**GET/POST** `/api/get_post_info.php`
- **Параметры:**
  - `post_id` (int, обязательно) - ID поста

- **Успешный ответ (200):**
```json
{
  "success": true,
  "post": {
    "id": 123,
    "title": "...",
    "content": "...",
    "category": "1",
    "category_info": { ... },
    "author_id": 456,
    "author_name": "...",
    "author_info": { ... },
    "anonymous": false,
    "image": "...",
    "created_at": "...",
    "likes": 10,
    "comments_count": 5
  }
}
```

- **Ошибки:**
```json
{
  "success": false,
  "error": "Invalid post_id"
}
// или
{
  "success": false,
  "error": "Post not found"
}
```

- **Статус код:** 405 (если метод не GET/POST), 200 (OK), 404 (пост не найден)

---

#### Получение информации о пользователе
**GET/POST** `/api/get_user_info_from_id.php`
- **Параметры:**
  - `user_id` (int, обязательно) - ID пользователя

- **Успешный ответ (200):**
```json
{
  "success": true,
  "user": {
    "id": 456,
    "username": "...",
    "verified": true,
    "created_at": "...",
    "subscriptions_count": 5,
    "role": "user",
    "status": "active"
  }
}
```

- **Ошибки:**
```json
{
  "success": false,
  "error": "Invalid user_id"
}
// или
{
  "success": false,
  "error": "User not found"
}
```

- **Статус код:** 405 (если метод не GET/POST), 200 (OK), 404 (пользователь не найден)

---

#### Редактирование поста
**POST** `/api/edit_post.php`
- **Требует:** Авторизация + автор поста
- **Параметры (JSON or Form):**
  - `post_id` (int, обязательно)
  - `title` (string, обязательно)
  - `content` (string, опционально)

- **Проверки:**
  - Проверка авторства (автор = текущий пользователь)
  - Проверка модерации
  - Title обязателен

- **Успешный ответ (200):**
```json
{
  "success": true,
  "redirect": "/post.php?id=456"
}
```

- **Ошибки:**
```json
{
  "success": false,
  "error": "Post not found"
}
// или
{
  "success": false,
  "error": "No permission to edit this post"  // HTTP 403
}
// или
{
  "success": false,
  "error": "Title is required"
}
```

---

### Комментарии

#### Добавление комментария
**POST** `/api/add_comment.php`
- **Требует:** Авторизация
- **Параметры (JSON or Form):**
  - `post_id` (int, обязательно) - ID поста
  - `content` (string, опционально) - текст комментария
  - `anonymous` (boolean, опционально)
  - `image` (file, опционально) - изображение (макс 5 MB)

- **Проверки:**
  - Спам-защита: макс 1 комментарий за 5 секунд
  - Проверка существования поста
  - Требует текст или изображение
  - Проверка модерации

- **Успешный ответ (200):**
```json
{
  "success": true,
  "comment_id": 789,
  "redirect": "/post.php?id=456#comment-789"
}
```

- **Ошибки:**
```json
{
  "success": false,
  "error": "Authorization required"
}
// или
{
  "success": false,
  "error": "Post not found"
}
// или
{
  "success": false,
  "error": "Please wait before posting another comment"
}
// или (модерация)
{
  "success": false,
  "error": "Content contains banned word",
  "moderation": {...}
}
```

---

#### Редактирование комментария
**POST** `/api/edit_comment.php`
- **Требует:** Авторизация + автор комментария
- **Параметры (JSON or Form):**
  - `comment_id` (int, обязательно)
  - `content` (string, обязательно)

- **Проверки:**
  - Авторство (автор = текущий пользователь)
  - Проверка модерации
  - Content обязателен (или image должен быть)

- **Успешный ответ (200):**
```json
{
  "success": true,
  "comment_id": 789
}
```

- **Ошибки:**
```json
{
  "success": false,
  "error": "Comment not found"
}
// или
{
  "success": false,
  "error": "No permission to edit this comment"  // HTTP 403
}
```

---

### Лайки

#### Добавление/удаление лайка
**POST** `/api/like.php`
- **Требует:** Авторизация
- **Параметры (JSON or Form):**
  - `type` (string: 'post' | 'comment', обязательно)
  - `target_id` (int, обязательно) - ID поста или комментария

- **Проверки:**
  - type должен быть 'post' или 'comment'
  - target_id > 0

- **Логика:**
  - Если пользователь уже лайкнул → удаляет лайк (unlike)
  - Если не лайкнул → добавляет лайк

- **Успешный ответ (200):**
```json
{
  "success": true,
  "liked": true,      // true = только что лайкнул, false = удалил лайк
  "count": 42         // текущее количество лайков
}
```

- **Ошибки:**
```json
{
  "success": false,
  "error": "Требуется авторизация"
}
// или
{
  "success": false,
  "error": "Неверные данные"
}
```

---

### Сабреддиты

#### Создание сабреддита
**POST** `/api/create_subreddit.php`
- **Требует:** Авторизация
- **Параметры (JSON or Form):**
  - `name` (string, обязательно) - название (3-30 символов)
  - `description` (string, опционально)
  - `emoji` (string, опционально) - эмодзи (макс 4 символа, по умолчанию 🧵)

- **Проверки:**
  - Спам-защита: макс 1 сабреддит за 20 секунд
  - Длина name: 3-30 символов
  - ID генерируется из name (приводится в lowercase, пробелы → underscore)
  - ID должен быть 3-30 символов после обработки
  - Эмодзи макс 4 символа

- **Успешный ответ (200):**
```json
{
  "success": true,
  "subreddit_id": "new_sub",
  "redirect": "/index.php?category=new_sub"
}
```

- **Ошибки:**
```json
{
  "success": false,
  "error": "Название должно быть от 3 до 30 символов"
}
// или
{
  "success": false,
  "error": "Не удалось сформировать ID сабреддита из названия"
}
// или
{
  "success": false,
  "error": "Эмодзи слишком длинное"
}
// или
{
  "success": false,
  "error": "Подождите перед созданием нового сабреддита"
}
```

---

#### Подписка/Отписка на сабреддит
**POST** `/api/toggle_subscription.php`
- **Требует:** Авторизация
- **Параметры (JSON or Form):**
  - `subreddit_id` (string, обязательно) - ID сабреддита
  - `action` (string: 'subscribe' | 'unsubscribe', обязательно)

- **Проверки:**
  - SubredditId не должен быть пустым
  - action должен быть 'subscribe' или 'unsubscribe'

- **Успешный ответ (200):**
```json
{
  "success": true,
  "message": "Subscribed",  // или "Unsubscribed"
  "action": "subscribe",    // или "unsubscribe"
  "subscribers_count": 150
}
```

- **Ошибки:**
```json
{
  "success": false,
  "error": "Authorization required"
}
// или
{
  "success": false,
  "error": "Invalid parameters"
}
// или
{
  "success": false,
  "error": "Failed to update subscription"
}
```

---

### Пользователь

#### Обновление профиля
**POST** `/api/update_profile.php`
- **Требует:** Авторизация
- **Параметры (Form-Data):**
  - `avatar` (file, опционально) - файл аватара (JPG/PNG/GIF/WEBP)
  - `avatar_url` (string, опционально) - URL аватара
  
- **Проверки:**
  - Если указан файл → загружает в `/uploads/avatars/`
  - Если указан URL и валидный → сохраняет URL
  - Расширение: jpg, jpeg, png, gif, webp

- **Успешный ответ (200):**
```json
{
  "success": true,
  "redirect": "/profile.php"
}
```

- **Ошибки:**
```json
{
  "success": false,
  "error": "Method not allowed"  // HTTP 405 для non-POST
}
// или
{
  "success": false,
  "error": "Требуется авторизация"
}
```

---

#### Отметить welcome как просмотренный
**POST** `/api/seen_welcome.php`
- **Требует:** Авторизация
- **Параметры:** нет
- **Ответ (200):**
```json
{
  "success": true
}
```

- **На ошибку:**
```json
{
  "success": false
}
```

---

### Пользовательские настройки

#### Установка темы
**POST/GET** `/api/set_theme.php`
- **Параметры (POST or GET):**
  - `theme` (string: 'light' | 'dark', обязательно)
  - `redirect` (string, опционально) - куда редиректить после установки

- **Логика:**
  - Проверяет что theme входит в список разрешённых
  - Устанавливает cookie на 1 год
  - Редирект на указанный URL или `/index.php`

- **Ответ:** Редирект (302 Found)
  - Заголовок: `Location: /index.php` (или custom)
  - Cookie: `theme=light` или `theme=dark` (на 1 год)

---

#### Установка языка
**POST/GET** `/api/set_language.php`
- **Параметры (POST or GET):**
  - `lang` (string: 'ru' | 'en', обязательно)
  - `redirect` (string, опционально)

- **Логика:**
  - Проверяет что lang входит в ['ru', 'en']
  - Сохраняет в сессию и cookie
  - Редирект на указанный URL или `/index.php`

- **Ответ:** Редирект (302 Found)
  - Cookie: `lang=ru` или `lang=en` (на 1 год)
  - Session: `$_SESSION['lang']` установлен

---

## ❌ Страницы ошибок

### 403 Forbidden
**GET** `/403.php`
- **Ответ:** HTML страница ошибки 403 (доступ запрещён)

### 404 Not Found
**GET** `/404.php`
- **Ответ:** HTML страница ошибки 404 (не найдено)

### 500 Internal Server Error
**GET** `/500.php`
- **Ответ:** HTML страница ошибки 500 (внутренняя ошибка)

---

## 🔐 Общие правила безопасности и валидации

### Стандартные ответы ошибок API:
- **405 Method Not Allowed** - когда используется неправильный HTTP метод для API
- Все API эндпойнты возвращают `Content-Type: application/json`

### Спам-защита:
- Создание поста: макс 1 раз за 10 секунд
- Добавление комментария: макс 1 раз за 5 секунд
- Создание сабреддита: макс 1 раз за 20 секунд

### Модерация:
- Проверяет содержимое на запрещённые слова
- Может наложить мут или бан пользователю
- При достижении 3 strikes → мут на 24 часа
- При достижении 5 strikes → бан
- Возвращает детали модерации в ответ

### Аватары и файлы:
- Максимальный размер изображения: 5 MB
- Разрешённые форматы: JPG, PNG, GIF, WEBP
- Загружаются в: `/uploads/avatars/`, `/uploads/posts/`, `/uploads/comments/`
- Имена файлов: timestamp + random hex

### Валидация URL редиректов:
- Проверяет что redirect не содержит `://` (не позволяет внешние ссылки)
- По умолчанию редирект на `/index.php` если параметр пустой или небезопасный

---

### Дополнительные API для клиентов

#### Получение списка сабреддитов
**GET/POST** `/api/get_subreddits.php`
- **Параметры:**
  - `limit` (int, опционально) - количество сабреддитов в ответе
  - `offset` (int, опционально) - смещение для пагинации
  - `search` (string, опционально) - поиск по названию и описанию

- **Успешный ответ (200):**
```json
{
  "success": true,
  "total": 50,
  "count": 20,
  "subreddits": [ ... ]
}
```

- **Статус код:** 405 (если метод не GET/POST), 200 (OK)

---

#### Получение комментариев поста
**GET/POST** `/api/get_comments.php`
- **Параметры:**
  - `post_id` (int, обязательно) - ID поста

- **Успешный ответ (200):**
```json
{
  "success": true,
  "post_id": 123,
  "count": 5,
  "comments": [ ... ]
}
```

- **Ошибки:**
```json
{
  "success": false,
  "error": "Invalid post_id"
}
```

- **Статус код:** 405 (если метод не GET/POST), 200 (OK), 400 (невалидный ID)

---

#### Получение постов пользователя
**GET/POST** `/api/get_user_posts.php`
- **Параметры:**
  - `user_id` (int, обязательно) - ID пользователя
  - `sort` (string, опционально) - `hot|new|popular|discussed` (по умолчанию `new`)
  - `limit` (int, опционально) - количество постов
  - `offset` (int, опционально) - смещение для пагинации

- **Успешный ответ (200):**
```json
{
  "success": true,
  "user_id": 456,
  "total": 25,
  "count": 10,
  "sort": "new",
  "posts": [ ... ]
}
```

- **Ошибки:**
```  "success": false,
  "error": "Invalid user_id"
}
// или
{
  "success": false,
  "error": "User not found"
}
```

- **Статус код:** 405 (если метод не GET/POST), 200 (OK), 400 (невалидный ID), 404 (пользователь не найден)

---

#### Получение подписок пользователя
**GET/POST** `/api/get_user_subscriptions.php`
- **Параметры:**
  - `user_id` (int, обязательно) - ID пользователя

- **Успешный ответ (200):**
```json
{
  "success": true,
  "user_id": 456,
  "count": 5,
  "subscriptions": [ ... ]
}
```

- **Ошибки:**
```json
{
  "success": false,
  "error": "Invalid user_id"
}
// или
{
  "success": false,
  "error": "User not found"
}
```

- **Статус код:** 405 (если метод не GET/POST), 200 (OK), 400 (невалидный ID), 404 (пользователь не найден)

---

## 📊 HTTP методы по эндпойнтам

| Метод  | Эндпойнт | Требует | JSON |
|--------|----------|---------|------|
| GET    | /        | -       | -    |
| GET    | /login.php | -    | -    |
| GET    | /register.php | -  | -    |
| GET    | /profile.php | -   | -    |
| GET    | /post.php | -      | -    |
| GET    | /create.php | Авт.  | -    |
| GET    | /settings.php | Авт. | -   |
| POST   | /api/login.php | -  | Yes  |
| POST   | /api/register.php | - | Yes |
| POST   | /api/create_post.php | Авт. | Yes |
| POST   | /api/add_comment.php | Авт. | Yes |
| POST   | /api/like.php | Авт.  | Yes  |
| POST   | /api/toggle_subscription.php | Авт. | Yes |
| POST   | /api/create_subreddit.php | Авт. | Yes |
| POST   | /api/update_profile.php | Авт. | Form-Data |
| POST   | /api/logout.php | Авт. | - |
| POST/GET | /api/set_theme.php | - | - |
| POST/GET | /api/set_language.php | - | - |
| GET/POST | /api/get_posts.php | - | - |
| GET/POST | /api/get_post_info.php | - | - |
| GET/POST | /api/get_user_info_from_id.php | - | - |
| GET/POST | /api/get_subreddits.php | - | - |
| GET/POST | /api/get_comments.php | - | - |
| GET/POST | /api/get_user_posts.php | - | - |
| GET/POST | /api/get_user_subscriptions.php | - | - |

---

## 🔑 Условные обозначения

- **Авт.** - требует авторизация (залогированный пользователь)
- **JSON** - параметры передаются в JSON или Form-Data
- **Form-Data** - форма с file uploads
