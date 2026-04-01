-- =====================================================
-- Feature 1: Real-time Chat
-- =====================================================

-- Track user online status for presence indicators
CREATE TABLE `user_online_status` (
  `user_id` int(11) NOT NULL,
  `last_seen` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_typing_to` int(11) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_online_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add seen_at to existing messages table for read receipts
ALTER TABLE `messages` ADD COLUMN `seen_at` timestamp NULL DEFAULT NULL AFTER `is_read`;

-- Index for efficient polling: "give me new messages since X"
ALTER TABLE `messages` ADD KEY `idx_messages_receiver_read_created` (`receiver_id`, `is_read`, `created_at`);

-- =====================================================
-- Feature 2: Push Subscriptions
-- =====================================================

CREATE TABLE `push_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `endpoint` text NOT NULL,
  `p256dh_key` varchar(255) NOT NULL,
  `auth_key` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_push_user` (`user_id`),
  CONSTRAINT `fk_push_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Feature 3: Advanced Search — FULLTEXT indexes
-- =====================================================

ALTER TABLE `posts` ADD FULLTEXT KEY `ft_posts_title_content` (`title`, `content`);
ALTER TABLE `waterbodies` ADD FULLTEXT KEY `ft_waterbodies_name_desc` (`name`, `description`);
ALTER TABLE `users` ADD FULLTEXT KEY `ft_users_name` (`full_name`, `username`);

-- Add fish species search support
ALTER TABLE `fish_catches` ADD KEY `idx_catches_species` (`fish_species`);
ALTER TABLE `fish_catches` ADD KEY `idx_catches_date` (`catch_date`);

-- =====================================================
-- Feature 4: Gamification
-- =====================================================

CREATE TABLE `badge_definitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `name_en` varchar(100) NOT NULL,
  `name_bg` varchar(100) NOT NULL,
  `description_en` varchar(255) NOT NULL,
  `description_bg` varchar(255) NOT NULL,
  `icon` varchar(50) NOT NULL DEFAULT 'fa-trophy',
  `color` varchar(20) NOT NULL DEFAULT '#FFD700',
  `xp_reward` int(11) NOT NULL DEFAULT 50,
  `criteria_type` enum('post_count','catch_count','friend_count','like_received','comment_count','streak_days','first_catch','big_fish','species_variety') NOT NULL,
  `criteria_value` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `user_badges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `earned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_badge` (`user_id`, `badge_id`),
  KEY `badge_id` (`badge_id`),
  CONSTRAINT `fk_ub_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ub_badge` FOREIGN KEY (`badge_id`) REFERENCES `badge_definitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `user_streaks` (
  `user_id` int(11) NOT NULL,
  `current_streak` int(11) NOT NULL DEFAULT 0,
  `longest_streak` int(11) NOT NULL DEFAULT 0,
  `last_activity_date` date DEFAULT NULL,
  `total_xp` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_streak_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `user_xp_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `xp_amount` int(11) NOT NULL,
  `reason` varchar(100) NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_xp_user` (`user_id`, `created_at`),
  CONSTRAINT `fk_xp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed badge definitions
INSERT INTO `badge_definitions` (`slug`, `name_en`, `name_bg`, `description_en`, `description_bg`, `icon`, `color`, `xp_reward`, `criteria_type`, `criteria_value`) VALUES
('first_post',       'First Cast',        'Първо Хвърляне',     'Create your first post',                    'Създай първата си публикация',           'fa-pen',           '#4CAF50', 25,  'post_count',      1),
('ten_posts',        'Active Angler',     'Активен Рибар',      'Create 10 posts',                           'Създай 10 публикации',                  'fa-fire',          '#FF5722', 100, 'post_count',      10),
('fifty_posts',      'Storyteller',       'Разказвач',           'Create 50 posts',                           'Създай 50 публикации',                  'fa-book-open',     '#9C27B0', 250, 'post_count',      50),
('first_catch',      'First Catch',       'Първи Улов',         'Log your first catch',                      'Запиши първия си улов',                  'fa-fish',          '#2196F3', 50,  'first_catch',     1),
('ten_catches',      'Seasoned Fisher',   'Опитен Рибар',       'Log 10 catches',                            'Запиши 10 улова',                        'fa-anchor',        '#00BCD4', 150, 'catch_count',     10),
('big_fish',         'Trophy Hunter',     'Трофеен Ловец',      'Catch a fish over 5kg',                     'Хвани риба над 5кг',                     'fa-crown',         '#FFD700', 200, 'big_fish',        5),
('five_friends',     'Social Butterfly',  'Социална Пеперуда',  'Make 5 friends',                            'Направи 5 приятели',                    'fa-user-friends',  '#E91E63', 75,  'friend_count',    5),
('twenty_friends',   'Community Leader',  'Лидер на Общността',  'Make 20 friends',                           'Направи 20 приятели',                   'fa-users',         '#FF9800', 200, 'friend_count',    20),
('hundred_likes',    'Fan Favorite',      'Фаворит на Феновете','Receive 100 likes',                         'Получи 100 харесвания',                 'fa-heart',         '#F44336', 150, 'like_received',   100),
('week_streak',      'Weekly Warrior',    'Седмичен Воин',      'Maintain a 7-day activity streak',          'Поддържай 7-дневна поредица активност',  'fa-calendar-check','#3F51B5', 100, 'streak_days',     7),
('month_streak',     'Monthly Master',    'Месечен Майстор',    'Maintain a 30-day activity streak',         'Поддържай 30-дневна поредица активност', 'fa-medal',         '#FF6F00', 500, 'streak_days',     30),
('five_species',     'Species Explorer',  'Изследовател',       'Catch 5 different species',                 'Хвани 5 различни вида',                 'fa-binoculars',    '#009688', 150, 'species_variety', 5);
