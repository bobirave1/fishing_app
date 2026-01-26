# Avatar Management System

## Overview
Централизирана система за управление на потребителски аватари, осигуряваща консистентност навсякъде в приложението.

## Ключови файлове

### PHP
- **config/avatar_helper.php** - Централизирани функции за PHP
  - `getUserAvatar($avatarUrl, $currentPath = null)` - Връща правилния път до аватар
  - `getDefaultAvatarPath()` - Връща пътя до default avatar
  - `sanitizeAvatarUrl($avatarUrl)` - Санитизира avatar URL за безопасен HTML

### JavaScript
- **fe/assets/js/avatar_helper.js** - Централизирани функции за JS
  - `getAvatarUrl(avatarUrl)` - Връща правилния път до аватар
  - `createAvatarHtml(avatarUrl, size, extraClasses)` - Създава HTML за аватар

## Default Avatar
- **Път в БД**: `fe/assets/img/default-avatar.png`
- **Автоматично задаване**: При регистрация всеки нов потребител получава default avatar
- **Иконка**: Човече/силует за непотвърдени профили

## Използване

### В PHP файлове
```php
require_once 'config/avatar_helper.php';

// Вземи avatar URL от БД
$avatar = getUserAvatar($user['avatar_url'] ?? null);

// Използвай в HTML
<img src="<?= htmlspecialchars($avatar) ?>" class="rounded-circle">
```

### В JavaScript файлове
```javascript
// Вземи правилния път до avatar
const avatar = getAvatarUrl(user.avatar_url);

// Създай HTML елемент
const avatarHtml = createAvatarHtml(user.avatar_url, 50, 'border border-primary');
```

## Актуализирани файлове

### Backend (PHP)
1. be/auth/register.php - Задава default avatar при регистрация
2. be/users/profile.php - Използва getUserAvatar()
3. be/friends/list_friends.php - Използва getUserAvatar()
4. be/friends/list_requests.php - Използва getUserAvatar()
5. fe/components/navbar.php - Използва getUserAvatar()
6. fe/pages/edit_profile.php - Използва getUserAvatar()
7. index.php - Използва getUserAvatar()

### Frontend (JavaScript)
1. fe/assets/js/app.js - Всички функции актуализирани да използват getAvatarUrl()
   - loadComments()
   - displaySearchResults()
   - displayConversations()
   - displayNotifications()

### HTML страници
Всички страници включват avatar_helper.js:
- index.php
- be/users/profile.php
- be/friends/list_friends.php
- be/friends/list_requests.php
- fe/pages/messages.php
- fe/pages/activity_feed.php
- fe/pages/edit_profile.php

## Логика за пътища

### Структура на директории
```
/                          (root)
├── fe/
│   ├── assets/img/default-avatar.png
│   └── pages/             (fe/pages/)
└── be/                    (be/users/, be/friends/, etc.)
```

### Разрешаване на пътища
- **Root ниво** (index.php): `fe/assets/img/default-avatar.png`
- **fe/pages/** ниво: `../../fe/assets/img/default-avatar.png`
- **be/** ниво: `../../fe/assets/img/default-avatar.png`

## Ползи
✅ Консистентни аватари навсякъде
✅ Автоматичен default avatar за нови потребители
✅ Единно място за промени
✅ По-лесна поддръжка
✅ По-малко грешки с пътища
✅ DRY принцип (Don't Repeat Yourself)
