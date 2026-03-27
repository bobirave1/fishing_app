<?php
/**
 * Language translations for FISHINGLORY
 * Supports English (en) and Bulgarian (bg)
 */

$lang = $_SESSION['lang'] ?? 'bg'; // Default to Bulgarian for diploma project

$translations = [
    'en' => [
        // Navigation
        'home' => 'Home',
        'messages' => 'Messages',
        'fish_activity' => 'Fish Activity',
        'search_placeholder' => 'Search Fishinglory',
        'quick_links' => 'Quick Links',
        'my_profile' => 'My Profile',
        'friends' => 'Friends',
        'requests' => 'Requests',
        'notifications' => 'Notifications',
        'mark_all_read' => 'Mark all read',
        'no_notifications' => 'No new notifications',
        'edit_profile' => 'Edit Profile',
        'logout' => 'Logout',
        'login' => 'Login',
        'sign_up' => 'Sign Up',

        // Posts
        'create_post' => 'Create Post',
        'post_placeholder' => 'Share your fishing experience...',
        'public' => 'Public',
        'friends' => 'Friends',
        'private' => 'Private',
        'post' => 'Post',
        'like' => 'Like',
        'comment' => 'Comment',
        'comments' => 'Comments',
        'follow' => 'Follow',
        'unfollow' => 'Unfollow',
        'no_posts' => 'No posts yet. Be the first to share your catch!',
        'delete_post' => 'Delete Post',
        'edit_post' => 'Edit Post',
        'cancel' => 'Cancel',
        'save' => 'Save',

        // Auth
        'username' => 'Username',
        'password' => 'Password',
        'confirm_password' => 'Confirm Password',
        'email' => 'Email',
        'full_name' => 'Full Name',
        'login_title' => 'Login to Fishinglory',
        'register_title' => 'Join Fishinglory',
        'register' => 'Register',
        'already_have_account' => 'Already have an account?',
        'dont_have_account' => 'Don\'t have an account?',
        'login_here' => 'Login here',
        'register_here' => 'Register here',

        // Profile
        'bio' => 'Bio',
        'location' => 'Location',
        'experience_level' => 'Experience Level',
        'beginner' => 'Beginner',
        'advanced' => 'Advanced',
        'pro' => 'Pro',
        'joined' => 'Joined',
        'posts' => 'Posts',
        'followers' => 'Followers',
        'following' => 'Following',

        // Messages
        'send_message' => 'Send Message',
        'type_message' => 'Type a message...',
        'no_conversations' => 'No conversations yet',

        // Activity Feed
        'activity_feed' => 'Activity Feed',
        'no_activity' => 'No recent activity',

        // Weather/Fish Activity
        'current_weather' => 'Current Weather',
        'fish_activity' => 'Fish Activity',
        'select_location' => 'Select Location',
        'get_activity' => 'Get Activity',

        // Settings
        'theme' => 'Theme',
        'light_mode' => 'Light Mode',
        'dark_mode' => 'Dark Mode',
        'language' => 'Language',
        'english' => 'English',
        'bulgarian' => 'Bulgarian',
    ],
    'bg' => [
        // Navigation
        'home' => 'Начало',
        'messages' => 'Съобщения',
        'fish_activity' => 'Рибна активност',
        'search_placeholder' => 'Търсене във Fishinglory',
        'quick_links' => 'Бързи връзки',
        'my_profile' => 'Моят профил',
        'friends' => 'Приятели',
        'requests' => 'Заявки',
        'notifications' => 'Известия',
        'mark_all_read' => 'Маркирай всички като прочетени',
        'no_notifications' => 'Няма нови известия',
        'edit_profile' => 'Редактирай профил',
        'logout' => 'Изход',
        'login' => 'Вход',
        'sign_up' => 'Регистрация',

        // Posts
        'create_post' => 'Създай публикация',
        'post_placeholder' => 'Сподели своя риболовен опит...',
        'public' => 'Публично',
        'friends' => 'Приятели',
        'private' => 'Лично',
        'post' => 'Публикувай',
        'like' => 'Харесай',
        'comment' => 'Коментирай',
        'comments' => 'Коментари',
        'follow' => 'Последвай',
        'unfollow' => 'Откажи следването',
        'no_posts' => 'Все още няма публикации. Бъди първият, който сподели своя улов!',
        'delete_post' => 'Изтрий публикация',
        'edit_post' => 'Редактирай публикация',
        'cancel' => 'Отказ',
        'save' => 'Запази',

        // Auth
        'username' => 'Потребителско име',
        'password' => 'Парола',
        'confirm_password' => 'Потвърди парола',
        'email' => 'Имейл',
        'full_name' => 'Пълно име',
        'login_title' => 'Вход в Fishinglory',
        'register_title' => 'Присъедини се към Fishinglory',
        'register' => 'Регистрирай се',
        'already_have_account' => 'Вече имаш акаунт?',
        'dont_have_account' => 'Нямаш акаунт?',
        'login_here' => 'Влез тук',
        'register_here' => 'Регистрирай се тук',

        // Profile
        'bio' => 'Био',
        'location' => 'Местоположение',
        'experience_level' => 'Ниво на опит',
        'beginner' => 'Начинаещ',
        'advanced' => 'Напреднал',
        'pro' => 'Професионалист',
        'joined' => 'Присъединил се',
        'posts' => 'Публикации',
        'followers' => 'Последователи',
        'following' => 'Следвани',

        // Messages
        'send_message' => 'Изпрати съобщение',
        'type_message' => 'Напиши съобщение...',
        'no_conversations' => 'Все още няма разговори',

        // Activity Feed
        'activity_feed' => 'Лента с активности',
        'no_activity' => 'Няма скорошни активности',

        // Weather/Fish Activity
        'current_weather' => 'Текущо време',
        'fish_activity' => 'Рибна активност',
        'select_location' => 'Избери местоположение',
        'get_activity' => 'Получи активност',

        // Settings
        'theme' => 'Тема',
        'light_mode' => 'Светъл режим',
        'dark_mode' => 'Тъмен режим',
        'language' => 'Език',
        'english' => 'Английски',
        'bulgarian' => 'Български',
    ]
];

// Function to get translated text
function __($key) {
    global $lang, $translations;
    return $translations[$lang][$key] ?? $key;
}

// Function to get current language
function getCurrentLang() {
    global $lang;
    return $lang;
}

// Function to get available languages
function getAvailableLanguages() {
    return ['en' => 'English', 'bg' => 'Български'];
}
?>