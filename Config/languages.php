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
        'visibility_label' => 'Post privacy',
        'public' => 'Public',
        'friends' => 'Friends',
        'private' => 'Private',
        'attach_file' => 'Attach file',
        'selected_file' => 'Selected file',
        'no_file_selected' => 'No file selected',
        'files_selected' => 'files selected',
        'add_to_post' => 'Add to your post',
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
        'welcome_title' => 'Welcome to FISHINGLORY',
        'join_title' => 'Join FISHINGLORY',
        'create_account' => 'Create your account',
        'terms_conditions' => 'Terms & Conditions',
        'agree_to' => 'I agree to the',
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
        'login_here_excl' => 'Login here!',
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
        'user_not_found' => 'User not found',
        'request_sent' => 'Request sent',
        'add_friend' => 'Add Friend',
        'view_all_friends' => 'View All Friends',
        'no_friends' => 'No friends yet',
        'start_connecting' => 'Start connecting with other anglers!',
        'following' => 'Following',

        // Messages
        'send_message' => 'Send Message',
        'type_message' => 'Type a message...',
        'loading_conversations' => 'Loading conversations...',
        'select_conversation' => 'Select a conversation to start messaging',
        'no_messages' => 'No messages yet',
        'no_conversations' => 'No conversations yet',

        // Activity Feed
        'activity_feed' => 'Activity Feed',
        'no_activity' => 'No recent activity',

        // Weather/Fish Activity
        'current_weather' => 'Current Weather',
        'fish_activity' => 'Fish Activity',
        'fish_activity_prediction' => 'Fish Activity Prediction',
        'select_location' => 'Select Location',
        'change_location' => 'Change Location',
        'getting_your_location' => 'Getting your location...',
        'calculating_fish_activity' => 'Calculating fish activity for your location...',
        'calculating' => 'Calculating...',
        'high' => 'HIGH',
        'medium' => 'MEDIUM',
        'low' => 'LOW',
        'excellent_activity' => 'Excellent fish activity',
        'good_activity' => 'Good fish activity',
        'moderate_activity' => 'Moderate fish activity',
        'low_activity' => 'Low fish activity',
        'very_low_activity' => 'Very low fish activity',
        'major_times' => 'MAJOR TIMES',
        'minor_times' => 'MINOR TIMES',
        'select_fishing_location' => 'Select Fishing Location',
        'close' => 'Close',
        'search_city_town' => 'Search city or town...',
        'calculate_fish_activity_btn' => 'Calculate Fish Activity',
        'get_activity' => 'Get Activity',
        'search' => 'Search',
        'today' => 'Today',
        'sun' => 'Sun', 'mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat',
        'fetching_weather' => 'Fetching weather based on your location...',

        // Settings
        'theme' => 'Theme',
        'light_mode' => 'Light Mode',
        'dark_mode' => 'Dark Mode',
        'language' => 'Language',
        'english' => 'English',
        'bulgarian' => 'Bulgarian',

        // Alerts & Placeholders
        'post_title_placeholder' => 'What\'s on your mind?',
        'loading_comments' => 'Loading comments...',
        'write_comment' => 'Write a comment...',
        'delete_permanently' => 'Delete Permanently',
        'my_friends' => 'My Friends',
        'back_to_profile' => 'Back to Profile',
        'find_friends' => 'Find Friends',
        'view_profile' => 'View Profile',
        'no_pending_requests' => 'No pending requests',
        'all_caught_up' => 'You\'re all caught up!',
        'go_to_home' => 'Go to Home',
        'accept' => 'Accept',
        'reject' => 'Reject',
        'all_rights_reserved' => 'All rights reserved.',
        'footer_tagline' => 'Connect with fellow anglers and share your catches!',
        'choose_avatar' => 'Choose New Avatar',
        'email_no_change' => 'Email cannot be changed',
        'location_placeholder' => 'Where do you fish?',
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
        'visibility_label' => 'Поверителност на публикацията',
        'public' => 'Публично',
        'friends' => 'Приятели',
        'private' => 'Лично',
        'attach_file' => 'Прикачи файл',
        'selected_file' => 'Избран файл',
        'no_file_selected' => 'Няма избран файл',
        'files_selected' => 'избрани файла',
        'add_to_post' => 'Добави към публикацията',
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
        'welcome_title' => 'Добре дошли във FISHINGLORY',
        'join_title' => 'Присъедини се към FISHINGLORY',
        'create_account' => 'Създай свой акаунт',
        'terms_conditions' => 'Общите условия',
        'agree_to' => 'Съгласен съм с',
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
        'login_here_excl' => 'Влез тук!',
        'register_here' => 'Регистрирай се тук',

        // Profile
        'bio' => 'Биография',
        'location' => 'Местоположение',
        'experience_level' => 'Ниво на опит',
        'beginner' => 'Начинаещ',
        'advanced' => 'Напреднал',
        'pro' => 'Професионалист',
        'joined' => 'Присъединил се',
        'posts' => 'Публикации',
        'followers' => 'Последователи',
        'user_not_found' => 'Потребителят не е намерен',
        'request_sent' => 'Заявката е изпратена',
        'add_friend' => 'Добави приятел',
        'view_all_friends' => 'Виж всички приятели',
        'no_friends' => 'Все още няма приятели',
        'start_connecting' => 'Започни да се свързваш с други рибари!',
        'following' => 'Следвани',

        // Messages
        'send_message' => 'Изпрати съобщение',
        'type_message' => 'Напиши съобщение...',
        'loading_conversations' => 'Зареждане на разговори...',
        'select_conversation' => 'Избери разговор, за да започнеш да пишеш',
        'no_messages' => 'Все още няма съобщения',
        'no_conversations' => 'Все още няма разговори',

        // Activity Feed
        'activity_feed' => 'Лента с активности',
        'no_activity' => 'Няма скорошни активности',

        // Weather/Fish Activity
        'current_weather' => 'Текущо време',
        'fish_activity' => 'Рибна активност',
        'fish_activity_prediction' => 'Прогноза за рибна активност',
        'select_location' => 'Избери местоположение',
        'change_location' => 'Промени местоположението',
        'getting_your_location' => 'Получаване на местоположение...',
        'calculating_fish_activity' => 'Изчисляване на рибната активност за вашето местоположение...',
        'calculating' => 'Изчисляване...',
        'high' => 'ВИСОКА',
        'medium' => 'СРЕДНА',
        'low' => 'НИСКА',
        'excellent_activity' => 'Отлична рибна активност',
        'good_activity' => 'Добра рибна активност',
        'moderate_activity' => 'Умерена рибна активност',
        'low_activity' => 'Ниска рибна активност',
        'very_low_activity' => 'Много ниска рибна активност',
        'major_times' => 'ОСНОВНИ ПЕРИОДИ',
        'minor_times' => 'МАЛКИ ПЕРИОДИ',
        'select_fishing_location' => 'Избери място за риболов',
        'close' => 'Затвори',
        'search_city_town' => 'Търси град или селище...',
        'calculate_fish_activity_btn' => 'Изчисли рибна активност',
        'get_activity' => 'Получи активност',
        'search' => 'Търсене',
        'today' => 'Днес',
        'sun' => 'Нед', 'mon' => 'Пон', 'tue' => 'Вто', 'wed' => 'Сря', 'thu' => 'Чет', 'fri' => 'Пет', 'sat' => 'Съб',
        'fetching_weather' => 'Зареждане на времето за вашето местоположение...',

        // Settings
        'theme' => 'Тема',
        'light_mode' => 'Светъл режим',
        'dark_mode' => 'Тъмен режим',
        'language' => 'Език',
        'english' => 'Английски',
        'bulgarian' => 'Български',

        // Alerts & Placeholders
        'post_title_placeholder' => 'Какво мислиш?',
        'loading_comments' => 'Зареждане на коментари...',
        'write_comment' => 'Напиши коментар...',
        'delete_permanently' => 'Изтрий завинаги',
        'my_friends' => 'Моите приятели',
        'back_to_profile' => 'Обратно към профила',
        'find_friends' => 'Намери приятели',
        'view_profile' => 'Виж профила',
        'no_pending_requests' => 'Няма чакащи заявки',
        'all_caught_up' => 'Всичко е прегледано!',
        'go_to_home' => 'Към началната страница',
        'accept' => 'Приеми',
        'reject' => 'Откажи',
        'all_rights_reserved' => 'Всички права запазени.',
        'footer_tagline' => 'Свържете се с колеги рибари и споделете своя улов!',
        'choose_avatar' => 'Избери нов аватар',
        'email_no_change' => 'Имейлът не може да бъде променян',
        'location_placeholder' => 'Къде ловите риба?',
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