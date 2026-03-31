# 🎣 FISHINGLORY - Social Fishing Platform

A modern social networking platform designed specifically for fishing enthusiasts. Connect with fellow anglers, share your catches, track fishing spots, and predict fish activity based on scientific algorithms.

![PHP](https://img.shields.io/badge/PHP-8.2-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple)

## 🚀 Quick Start - Database Setup

After cloning this repository:

1. **Import the database:**
   ```bash
   # Option 1: Using MySQL command line
   mysql -u root -p < fishing_app.sql
   
   # Option 2: Using phpMyAdmin
   # - Open phpMyAdmin (http://localhost/phpmyadmin)
   # - Create a new database called 'fishing_app'
   # - Go to Import tab and select fishing_app.sql
   ```

2. **Update database credentials** if needed in `config/database.php`

3. **Run migrations** (if any):
   ```bash
   mysql -u root -p fishing_app < migrations/add_notifications.sql
   ```

## ✨ Features

### 🔐 User Management
- **Secure Authentication** - Login/Register with CSRF protection and rate limiting
- **Profile Management** - Customizable profiles with avatar upload
- **Experience Levels** - Beginner, Advanced, and Pro classifications
- **Bio & Location** - Share your fishing story and favorite spots

### 📱 Social Features
- **Post Creation** - Share your catches with images and descriptions
- **Likes & Comments** - Engage with the community
- **Friend System** - Connect with other anglers
- **Visibility Controls** - Public, Friends-only, or Private posts
- **Real-time Notifications** - Stay updated on interactions

### 🐟 Fish Activity Prediction
- **Solunar Theory Algorithm** - Professional fish activity calculations
- **Weather Integration** - Real-time weather data from OpenWeatherMap API
- **Interactive Map** - Select any location worldwide using Leaflet.js
- **Activity Scoring** - Multi-factor analysis including:
  - Temperature (25% weight)
  - Barometric Pressure (25% weight)
  - Solunar Periods (20% weight)
  - Wind & Cloud Cover (15% weight)
  - Time of Day (15% weight)

### 🔒 Security
- **CSRF Protection** - Token-based request validation
- **Rate Limiting** - Prevents brute force attacks
- **Input Validation** - XSS and SQL injection prevention
- **Secure Headers** - CSP, Permissions-Policy, X-Frame-Options
- **Password Hashing** - bcrypt with secure salts

### 🎨 Modern Design
- **Responsive Layout** - Mobile-first Bootstrap 5 design
- **Custom Theme** - Cyan/Teal color scheme with gradients
- **Google Fonts** - Poppins for headings, Inter for body text
- **Smooth Animations** - Cubic-bezier transitions and hover effects
- **Dark Mode Ready** - CSS variables for easy theming

## 🚀 Installation

### Prerequisites
- PHP 8.2 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (optional, for future dependencies)

### Setup Steps

1. **Clone the repository**
```bash
git clone https://github.com/yourusername/fishing_app.git
cd fishing_app
```

2. **Create database**
```bash
mysql -u root -p
```
```sql
CREATE DATABASE fishing_app CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
exit;
```

3. **Import database schema**
```bash
mysql -u root -p fishing_app < fishing_app.sql
```

4. **Configure environment**
```bash
cp .env.example .env
```

Edit `.env` file with your settings:
```env
DB_HOST=localhost
DB_NAME=fishing_app
DB_USER=root
DB_PASS=your_password
OPENWEATHER_API_KEY=your_api_key_here
```

5. **Set permissions**
```bash
chmod 755 fe/assets/img/avatars
```

6. **Access the application**
Open your browser and navigate to:
```
http://localhost/fishing_app/
```

## 📁 Project Structure

```
fishing_app/
├── be/                      # Backend PHP files
│   ├── activity/           # Fish activity calculations
│   ├── auth/               # Login, register, logout
│   ├── friends/            # Friend system endpoints
│   ├── messages/           # Messaging system
│   ├── notifications/      # Notification handling
│   ├── posts/              # Post CRUD operations
│   ├── users/              # User profile management
│   └── weather/            # Weather API integration
├── Config/                  # Configuration files
│   ├── database.php        # Database connection
│   ├── security.php        # Security functions
│   └── weather_api.php     # Weather API wrapper
├── fe/                      # Frontend files
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css   # Main stylesheet
│   │   ├── img/            # Images and avatars
│   │   └── js/
│   │       └── app.js      # Main JavaScript
│   ├── auth/               # Login/Register forms
│   ├── pages/              # Main pages
│   └── posts/              # Post forms and modals
├── docs/                    # Documentation
├── .env.example            # Environment template
├── .gitignore              # Git ignore rules
├── fishing_app.sql         # Database schema
├── index.php               # Main homepage
└── README.md               # This file
```

## 🛠️ Technologies

### Backend
- **PHP 8.2** - Server-side scripting
- **MySQL/MariaDB** - Relational database
- **PDO** - Database abstraction layer
- **cURL** - HTTP requests for API calls

### Frontend
- **Bootstrap 5.3.3** - CSS framework
- **Font Awesome 6.5** - Icon library
- **Leaflet.js 1.9.4** - Interactive maps
- **Vanilla JavaScript** - No jQuery dependencies
- **Google Fonts** - Poppins & Inter

### APIs
- **OpenWeatherMap API 2.5** - Weather data
- **OpenStreetMap** - Map tiles

## 🔧 Configuration

### Database Connection
Edit `Config/database.php` or use `.env` file:
```php
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'fishing_app';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
```

### Weather API
Get your free API key from [OpenWeatherMap](https://openweathermap.org/api):
```env
OPENWEATHER_API_KEY=your_api_key_here
```

### Security Headers
Customize in `Config/security.php`:
- Content Security Policy (CSP)
- Permissions-Policy
- X-Frame-Options
- X-Content-Type-Options

## 📖 Usage

### Creating an Account
1. Click "Register" on the homepage
2. Fill in your details (Full Name, Username, Email, Password)
3. Login with your credentials

### Posting Content
1. Click "Create Post" on the homepage
2. Add a title, description, and optional image
3. Choose visibility (Public, Friends, Private)
4. Click "Post"

## ✅ Fast Testing & Debug (Defense Ready)

Use this section as your quick validation script before presentation.

### 1) Manual Smoke Checklist (10 minutes)

1. Auth: register with a new user, logout, login again.
2. Feed: create a post (text only), then create a post with image/video.
3. Social: like and unlike a post; add and delete a comment.
4. Friends: send request, accept request, remove friend.
5. Messaging: send message with text, then with attachment.
6. Notifications: open notifications, mark one read, then mark all read.
7. Profile: edit profile info and verify avatar rendering.
8. Security checks: submit form with missing CSRF and verify rejection.
9. Validation checks: try empty comment/post and confirm proper user feedback.
10. Theme checks: toggle light/dark and verify text contrast and button readability.

### 2) Frontend Debug Mode

Enable a compact debug panel in browser console:

```js
localStorage.setItem('fishingDebug', '1');
location.reload();
```

Disable:

```js
localStorage.removeItem('fishingDebug');
location.reload();
```

What you get:
- in-app error notices for common request failures
- captured unhandled JS errors and promise rejections
- compact debug panel with recent events

### 3) Quick Presentation Script (2 minutes)

1. Open home feed and create a post.
2. Show like/comment interaction.
3. Open profile and remove a friend (with confirmation).
4. Toggle dark/light mode to demonstrate UI consistency.
5. Show debug panel enabled and explain faster issue tracing.

### Fish Activity Prediction
1. Navigate to "Activity Feed"
2. Click on the interactive map to select a location
3. View detailed activity score based on:
   - Current weather conditions
   - Solunar periods
   - Time of day
   - Environmental factors

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 👨‍💻 Author

**Your Name**
- GitHub: [@bobirave1](https://github.com/bobirave1)
- Email: your.email@example.com

## 🙏 Acknowledgments

- [Bootstrap](https://getbootstrap.com/) - CSS Framework
- [Font Awesome](https://fontawesome.com/) - Icons
- [Leaflet](https://leafletjs.com/) - Maps
- [OpenWeatherMap](https://openweathermap.org/) - Weather Data
- [OpenStreetMap](https://www.openstreetmap.org/) - Map Tiles

## 📸 Screenshots

### Homepage
Beautiful landing page with real-time weather widget and post feed.

### Fish Activity Prediction
Interactive map with scientific fish activity calculations based on Solunar Theory.

### User Profile
Customizable profiles with avatar upload and bio.

---

**Happy Fishing! 🎣**
