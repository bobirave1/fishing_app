-- =====================================================
-- FISHINGLORY Database Optimization Migration
-- Run this once on the existing fishing_app database
-- =====================================================

-- 1. Messages: add index for reverse conversation lookups + chronological ordering
--    Queries like: WHERE (sender_id=? AND receiver_id=?) OR (receiver_id=? AND sender_id=?)
ALTER TABLE `messages`
  ADD KEY `idx_messages_receiver_sender` (`receiver_id`, `sender_id`, `created_at`);

-- 2. Friend requests: speed up "pending requests for user" query
--    Query: WHERE receiver_id = ? AND status = 'pending'
ALTER TABLE `friend_requests`
  ADD KEY `idx_freq_receiver_status` (`receiver_id`, `status`);

-- 3. Activity feed: chronological ordering
ALTER TABLE `activity_feed`
  ADD KEY `idx_activity_created` (`created_at`);

-- 4. Password resets: token lookup
ALTER TABLE `password_resets`
  ADD KEY `idx_password_resets_token` (`token`);

-- 5. Post comments: efficient ordering within a post
ALTER TABLE `post_comments`
  ADD KEY `idx_comments_post_created` (`post_id`, `created_at`);

-- 6. Messages: add created_at to the conversation composite index for sorted lookups
--    (The existing 'conversation' index is sender_id,receiver_id without created_at ordering)
ALTER TABLE `messages`
  ADD KEY `idx_messages_created` (`created_at`);

-- 7. Friends: reverse lookup optimisation (user_id is already primary key left prefix)
--    Query: WHERE friend_id = ? (finding all users who are friends with X)
--    Already has KEY `friend_id` — OK

-- 8. Users: email lookup for login
--    Already has UNIQUE KEY on email — OK
