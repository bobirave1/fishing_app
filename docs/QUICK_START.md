# 🎣 Fish Activity - Quick Start Guide

## Настройка на реални метеорологични данни

### 🚀 Бърз старт (5 минути)

#### 1️⃣ Вземете БЕЗПЛАТЕН API ключ

**OpenWeatherMap** предлага **1000 безплатни заявки на ден**!

1. Регистрирайте се: https://home.openweathermap.org/users/sign_up
2. Потвърдете email-a
3. Отидете на: https://home.openweathermap.org/api_keys
4. Копирайте вашия API key

#### 2️⃣ Конфигурирайте приложението

Отворете `.env` файл и добавете ключа:

```env
OPENWEATHER_API_KEY=your_api_key_here_paste_it
```

**Готово!** 🎉

---

## 🗺️ Как да използвате Fish Activity

### Стъпка 1: Изберете локация
- Отворете: http://localhost/fishing_app/fe/pages/activity_feed.php
- **Кликнете навсякъде на картата** 🗺️
- ИЛИ кликнете на **🎣 маркер** за популярно място

### Стъпка 2: Вижте предикцията
- Натиснете **"Calculate Fish Activity"**
- Изчаква се реален метео данни от API-то
- Вижте резултата с:
  - 📊 Activity Score (0-100%)
  - 🌡️ Температура
  - 💨 Вятър
  - 📊 Налягане
  - 🌙 Solunar периоди
  - 💡 Препоръки

---

## 📍 Как работи

```
1. Кликаш на картата → Координати (lat, lon)
                           ↓
2. Frontend изпраща → Backend API
                           ↓
3. Backend извиква → OpenWeatherMap API
                           ↓
4. API връща → Реални метео данни за тази локация
                           ↓
5. Алгоритъм → Изчислява fish activity
                           ↓
6. Резултат → Показва се на екрана
```

---

## 🌐 Какво получавате от API-то

### Реални данни:
- ✅ Температура за точната локация
- ✅ Вятър (скорост + посока)
- ✅ Барометрично налягане
- ✅ Влажност
- ✅ Облачност
- ✅ Видимост
- ✅ Sunrise/Sunset часове
- ✅ Име на локацията
- ✅ Държава

### Без API ключ:
- Симулирани данни (за тестване)
- Badge показва: "Simulated Data"

---

## 🔍 Тестване

### С API ключ:
1. Добавете ключа в `.env`
2. Рестартирайте Apache (в XAMPP)
3. Отворете страницата
4. Кликнете на картата
5. Calculate → Виждате badge **"OpenWeatherMap API"** ✅

### Без API ключ:
- Приложението работи със симулирани данни
- Badge показва **"Simulated Data"**

---

## 🎨 Features

### Interactive Map
- 🗺️ Leaflet.js с OpenStreetMap
- 📍 Click anywhere = Custom location
- 🎣 Pre-marked popular spots:
  - Варненско езеро
  - Бургаско езеро
  - Река Дунав
  - Язовир Искър
  - Язовир Батак
  - Черно море
  - Язовир Панчарево

### Professional Algorithm
- 🌡️ Temperature (25%)
- 📊 Barometric Pressure (25%)
- 🌙 Solunar Periods (20%)
- 💨 Wind & Clouds (15%)
- ⏰ Time of Day (15%)

### Solunar Theory
- 🔴 Major Periods (moon overhead/underfoot)
- 🟡 Minor Periods (moonrise/moonset)
- 🌕 Moon Phase effects

---

## 📚 Документация

Виж пълната документация:
- **API Setup:** [docs/OPENWEATHER_API_SETUP.md](docs/OPENWEATHER_API_SETUP.md)
- **Algorithm:** [docs/FISH_ACTIVITY_ALGORITHM.md](docs/FISH_ACTIVITY_ALGORITHM.md)
- **Map Feature:** [docs/MAP_FEATURE.md](docs/MAP_FEATURE.md)

---

## ❓ Често задавани въпроси

### Колко струва OpenWeatherMap?
**БЕЗПЛАТНО!** Free tier: 1,000 calls/day (достатъчно!)

### Трябва ли кредитна карта?
**НЕ!** Безплатният план не изисква карта.

### Работи ли без API ключ?
**ДА!** Използва симулирани данни за тестване.

### Как знам че API-то работи?
Търсете badge **"OpenWeatherMap API"** в weather секцията.

### API ключът не работи?
- Изчакайте 10-15 минути след създаване
- Проверете за грешки в `C:\xampp\apache\logs\error.log`

---

## 🛠️ Troubleshooting

### Проблем: Badge показва "Simulated Data"

**Решение:**
```
1. Проверете .env файла
2. OPENWEATHER_API_KEY трябва да е попълнен
3. Рестартирайте Apache
4. Refresh страницата
```

### Проблем: API грешка

**Проверете:**
```
C:\xampp\apache\logs\error.log
```

Търсете: `OpenWeatherMap API`

---

## 🎯 Next Steps

След като настроите API-то:

1. ✅ Тествайте различни локации
2. ✅ Сравнете с реалните условия
3. ✅ Проверете solunar периодите
4. ✅ Отидете на риболов! 🎣

---

**Happy Fishing!** 🐟🎣

**Version:** 2.0 with Real API Integration  
**Date:** January 25, 2026
