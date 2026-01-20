# FISHINGLORY - Setup & Installation Guide

## 🚀 Quick Start

### Prerequisites
- XAMPP (with PHP 7.2+, MySQL/MariaDB)
- Web browser
- Local machine or server access

### Installation Steps

#### 1. Database Setup
Navigate to the app folder and open the initialization script:
```
http://localhost/fishing_app/init_tables.php
```

This will automatically create all required database tables:
- `post_likes`
- `post_comments`
- `follows`
- `messages`
- `notifications` (with sender_id column)
- `activity_feed`
- `waterbodies` (with sample fishing spots)

#### 2. Access the Application
```
http://localhost/fishing_app/
```

#### 3. Create Your Account
- Click "Register" button
- Fill in your details
- Click "Register"

#### 4. Login
- Use your credentials to login
- Set up your profile (avatar, bio, location)

---

## 📁 Directory Structure

```
fishing_app/
├── index.php                 # Main feed page
├── messages.php              # Direct messaging
├── activity_feed.php         # Activity timeline
├── init_tables.php           # Database initialization
├── setup_db.php              # Old setup script (can be deleted)
├── FEATURES.md               # Complete feature list
│
├── be/                       # Backend handlers
│   ├── auth/                 # Authentication
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── register.php
│   ├── posts/                # Post operations
│   │   ├── create.php
│   │   ├── edit.php
│   │   ├── delete.php
│   │   ├── like.php          # ✨ NEW
│   │   └── comment.php       # ✨ NEW
│   ├── users/                # User operations
│   │   ├── profile.php
│   │   ├── edit_profile.php
│   │   ├── upload_avatar.php
│   │   └── follow.php        # ✨ NEW
│   ├── notifications/        # ✨ NEW
│   │   ├── get_notifications.php
│   │   └── mark_read.php
│   ├── messages/             # ✨ NEW
│   │   └── message.php
│   ├── activity/             # ✨ NEW
│   │   └── feed.php
│   ├── search.php            # ✨ NEW
│   ├── weather/
│   │   └── get_weather.php
│   └── friends/              # Friend system
│       ├── send_request.php
│       ├── accept_request.php
│       ├── reject_request.php
│       └── list_friends.php
│
├── fe/                       # Frontend
│   ├── auth/                 # Auth forms
│   │   ├── login_form.php
│   │   └── register_form.php
│   ├── users/                # User forms
│   │   └── edit_profile_form.php
│   ├── posts/                # Post modals
│   │   ├── edit_form.php
│   │   └── delete_confirm.php
│   └── assets/
│       ├── css/
│       │   └── style.css
│       ├── js/
│       │   └── app.js        # ✨ NEW - Main JS library
│       └── img/
│           ├── avatars/      # User avatars
│           └── ...           # Post images
│
├── Config/
│   └── database.php          # Database connection
│
└── fishing_app.sql           # Original database dump
```

---

## 🔑 Key Features

### ✨ All 9 Major Features

1. **Likes & Reactions** - Like/unlike posts with real-time counts
2. **Comments** - Add, view, and delete comments on posts
3. **Follow System** - Follow/unfollow users and see their activity
4. **Search** - Search users and posts in real-time
5. **Notifications** - Real-time notification bell with counts
6. **Direct Messaging** - Send and receive private messages
7. **Activity Feed** - Timeline of what your friends are doing
8. **Post Statistics** - Like counts, comment counts, engagement metrics
9. **User Profiles** - Avatar upload, bio, location, experience level

---

## 🔐 Security

The application includes:
- Session-based authentication
- User ID verification for all actions
- Ownership checks for edit/delete operations
- SQL injection prevention (prepared statements)
- CSRF-ready structure

---

## 💻 API Endpoints

### Posts
- `POST be/posts/like.php` - Like/unlike a post
- `POST be/posts/comment.php` - Add/get/delete comments
- `POST be/posts/create.php` - Create new post
- `POST be/posts/edit.php` - Edit existing post
- `POST be/posts/delete.php` - Delete post

### Users
- `POST be/users/follow.php` - Follow/unfollow user
- `GET be/users/profile.php` - View user profile
- `POST be/users/edit_profile.php` - Update profile
- `POST be/users/upload_avatar.php` - Upload avatar

### Messaging
- `POST be/messages/message.php` - Send message / Get conversation

### Notifications
- `GET be/notifications/get_notifications.php` - Get notifications
- `POST be/notifications/mark_read.php` - Mark as read

### Other
- `GET be/search.php` - Search functionality
- `GET be/activity/feed.php` - Get activity feed

---

## 🎨 Frontend Files

### JavaScript Library
**`fe/assets/js/app.js`** (400+ lines)
- `toggleLike(postId, button)` - Handle likes
- `addComment(postId)` - Add comment
- `deleteComment(postId, commentId)` - Remove comment
- `toggleComments(postId)` - Show/hide comments
- `toggleFollow(userId, button)` - Follow/unfollow
- `performSearch(query)` - Search functionality
- `loadNotifications()` - Fetch notifications
- `displayNotifications(notifications)` - Render notifications
- `loadConversations()` - Get message threads
- `openConversation(userId)` - Load conversation
- `sendMessage(receiverId)` - Send message
- `formatDate(dateString)` - Format timestamps

### Pages
- `index.php` - Main feed with posts and engagement
- `messages.php` - Direct messaging interface
- `activity_feed.php` - Activity timeline
- `be/users/profile.php` - User profile view

---

## 📊 Database Tables

### Core Tables
- `users` - User accounts
- `posts` - User posts
- `user_profiles` - Extended user info

### Engagement Tables (✨ NEW)
- `post_likes` - Post reactions
- `post_comments` - Post discussions
- `follows` - User relationships
- `messages` - Direct messages
- `notifications` - User notifications
- `activity_feed` - Activity timeline
- `waterbodies` - Fishing spots

---

## 🔧 Configuration

All configuration is in `Config/database.php`:
```php
$host = 'localhost';
$db = 'fishing_app';
$user = 'root';
$password = '';
```

Adjust these values if your database setup is different.

---

## 🐛 Troubleshooting

### Table not found error
- Run `http://localhost/fishing_app/init_tables.php`
- Check if database exists: `CREATE DATABASE fishing_app;`

### Avatar not displaying
- Create folder: `fe/assets/img/avatars/`
- Ensure permissions allow file uploads

### Notifications not loading
- Clear browser cache
- Check JavaScript console for errors
- Verify PHP error logs

### Database connection failed
- Verify MySQL/MariaDB is running
- Check credentials in `Config/database.php`
- Ensure `fishing_app` database exists

---

## ✅ Testing Checklist

After setup, test these features:

- [ ] Register new account
- [ ] Edit profile and upload avatar
- [ ] Create a post
- [ ] Like a post
- [ ] Add comment to post
- [ ] Follow another user
- [ ] Search for users
- [ ] Check notifications bell
- [ ] Send message to user
- [ ] View activity feed
- [ ] View message conversation

---

## 📝 Notes

- **Avatar Storage**: Avatars are stored in `fe/assets/img/avatars/`
- **Post Images**: Post images are stored in `fe/assets/img/`
- **Auto-refresh**: Notifications refresh every 30 seconds, messages every 10 seconds
- **Search Debounce**: 300ms delay between search queries
- **Visibility Filtering**: Posts respect visibility settings (public/friends/private)

---

## 🚀 Performance Tips

- Database indexing on frequently queried columns
- Pagination ready in activity feed
- AJAX for non-blocking interactions
- Lazy loading for better UX

---

## 📞 Support

For issues or feature requests, refer to:
- `FEATURES.md` - Complete feature documentation
- `be/` directory - Backend implementation
- `fe/assets/js/app.js` - Frontend functionality
- PHP error logs in XAMPP console

---

**FISHINGLORY is ready to use!** 🎣

Start with: `http://localhost/fishing_app/`
