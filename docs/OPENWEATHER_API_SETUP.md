# OpenWeatherMap API Integration Setup

## Overview
Приложението използва **OpenWeatherMap API** за реални метеорологични данни базирани на GPS координатите на избраната локация от картата.

---

## Как да получите БЕЗПЛАТЕН API ключ

### Стъпка 1: Създайте акаунт
1. Отидете на: https://home.openweathermap.org/users/sign_up
2. Попълнете формата:
   - Username
   - Email
   - Password
3. Потвърдете email адреса си

### Стъпка 2: Вземете API ключа
1. Влезте в акаунта си
2. Отидете на: https://home.openweathermap.org/api_keys
3. Ще видите вашия **default API key** (автоматично генериран)
4. Копирайте ключа (изглежда така: `a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6`)

### Стъпка 3: Конфигурирайте приложението
1. Отворете файла `.env` в root директорията на проекта
2. Намерете реда: `OPENWEATHER_API_KEY=`
3. Поставете вашия ключ след `=`:
   ```
   OPENWEATHER_API_KEY=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
   ```
4. Запазете файла

### Стъпка 4: Тествайте
1. Отворете: http://localhost/fishing_app/fe/pages/activity_feed.php
2. Кликнете на картата за да изберете локация
3. Кликнете "Calculate Fish Activity"
4. Ако всичко работи, ще видите бадж "OpenWeatherMap API" в weather секцията

---

## Безплатен план (Free Tier)

OpenWeatherMap предлага **безплатен план** с:

- ✅ **1,000 API calls на ден**
- ✅ **60 calls на минута**
- ✅ Реални weather данни
- ✅ 5-дневна прогноза
- ✅ Unlimited locations
- ✅ Без нужда от кредитна карта

**Това е повече от достатъчно за вашето приложение!**

---

## Какво взимаме от API-то

### Weather Data
- 🌡️ **Temperature** (°C)
- 💨 **Wind Speed** (m/s)
- 🧭 **Wind Direction** (degrees)
- 📊 **Barometric Pressure** (hPa)
- 💧 **Humidity** (%)
- ☁️ **Cloud Cover** (%)
- 👁️ **Visibility** (km)
- 🌅 **Sunrise/Sunset** times
- 🌦️ **Weather Conditions** (Clear, Rain, Clouds, etc.)

### Location Data
- 📍 **Location Name** (auto-detected from coordinates)
- 🏳️ **Country Code**

---

## Fallback Mode

Ако **няма конфигуриран API ключ**, приложението автоматично използва:

### Simulated Weather Data
- Генерирани реалистични данни
- Базирани на текущия сезон
- Базирани на времето на деня
- Badge показва: "Simulated Data (No API key)"

**Забележка:** Симулираните данни са за development целиadded. За production препоръчваме използване на реален API ключ.

---

## Troubleshooting

### Проблем: "Simulated Data" вместо реални данни

**Решения:**
1. Проверете дали `.env` файлът съществува
2. Проверете дали `OPENWEATHER_API_KEY` е правилно зададен
3. Уверете се, че няма интервали преди/след ключа
4. Проверете дали ключът не е `your_api_key_here`

### Проблем: API връща грешка

**Възможни причини:**
1. **Invalid API key** - проверете ключа
2. **API key not activated yet** - изчакайте 10-15 минути след създаване
3. **Exceeded quota** - проверете usage на https://home.openweathermap.org/
4. **Network issue** - проверете интернет връзката

### Проверка на логове
Отворете PHP error log:
```
C:\xampp\apache\logs\error.log
```

Търсете линии съдържащи:
- `OpenWeatherMap API`
- `Weather data error`

---

## API Request Example

### Request URL
```
https://api.openweathermap.org/data/2.5/weather
  ?lat=42.7339
  &lon=25.4858
  &appid=YOUR_API_KEY
  &units=metric
  &lang=en
```

### Response (JSON)
```json
{
  "coord": {"lon": 25.4858, "lat": 42.7339},
  "weather": [
    {
      "id": 800,
      "main": "Clear",
      "description": "clear sky",
      "icon": "01d"
    }
  ],
  "main": {
    "temp": 18.5,
    "pressure": 1013,
    "humidity": 65
  },
  "wind": {
    "speed": 3.2,
    "deg": 180
  },
  "clouds": {"all": 0},
  "sys": {
    "country": "BG",
    "sunrise": 1706158800,
    "sunset": 1706197200
  },
  "name": "Bulgaria"
}
```

---

## Security Best Practices

### ⚠️ ВАЖНО

1. **Никога не commit-вайте `.env` файла в Git!**
   - Вече е добавен в `.gitignore`
   
2. **Не споделяйте API ключа публично**
   - Използвайте `.env.example` за споделяне на конфигурацията
   
3. **Regenerate ключа ако е компрометиран**
   - Може да го направите от OpenWeatherMap dashboard

4. **Мониторирайте usage-а**
   - Проверявайте регулярно за необичайна активност

---

## Alternative Weather APIs

Ако искате да използвате друго API:

### WeatherAPI.com
- Free tier: 1M calls/month
- https://www.weatherapi.com/

### Weatherstack
- Free tier: 1,000 calls/month
- https://weatherstack.com/

### Tomorrow.io
- Free tier: 500 calls/day
- https://www.tomorrow.io/

**Забележка:** Ще трябва да адаптирате `getWeatherData()` функцията.

---

## Contact & Support

**OpenWeatherMap Support:**
- Email: info@openweathermap.org
- FAQ: https://openweathermap.org/faq
- Status: https://openweathermap.org/status

**Fishing App:**
- Вижте документацията в `/docs/`

---

**Last Updated:** January 25, 2026  
**API Version:** OpenWeatherMap 2.5
