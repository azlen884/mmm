<?php
/**
 * ApexSMM Database Seeder
 * Populates realistic production-grade seed data for immediate testing and demonstration.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

echo "Seeding ApexSMM database...\n";

// 1. Ensure Admin exists
$adminPass = password_hash('Admin@2026!', PASSWORD_DEFAULT);
Database::query("
    INSERT INTO users (id, username, email, password, role, balance, spent, status, api_key)
    VALUES (1, 'admin', 'admin@apexsmm.com', ?, 'admin', 500.0000, 0.0000, 'active', 'smm_admin_api_key_secure_991823')
    ON DUPLICATE KEY UPDATE password = VALUES(password), status = 'active', role = 'admin'
", [$adminPass]);

// 2. Demo User
$demoPass = password_hash('DemoUser123!', PASSWORD_DEFAULT);
Database::query("
    INSERT INTO users (id, username, email, password, role, balance, spent, status, api_key)
    VALUES (2, 'demo', 'demo@apexsmm.com', ?, 'user', 150.0000, 42.5000, 'active', 'smm_demo_user_key_8492049')
    ON DUPLICATE KEY UPDATE password = VALUES(password), balance = 150.0000, status = 'active'
", [$demoPass]);

// 3. Providers
Database::query("
    INSERT INTO providers (id, name, api_url, api_key, status, balance, currency)
    VALUES 
    (1, 'Global SMM Hub', 'https://api.globalsmmhub.demo/v2', 'demo_provider_key_abc123', 'active', 1245.8000, 'USD'),
    (2, 'Prime Media API', 'https://api.primemedia.demo/v2', 'demo_provider_key_xyz789', 'active', 830.4000, 'USD')
    ON DUPLICATE KEY UPDATE name = VALUES(name), status = 'active'
");

// 4. Categories
$categories = [
    [1, 'Instagram Followers & Likes', 1],
    [2, 'YouTube Views, Watchtime & Subs', 2],
    [3, 'TikTok Followers, Likes & Shares', 3],
    [4, 'Telegram Channel Members & Views', 4],
    [5, 'X (Twitter) Followers & Retweets', 5],
    [6, 'Facebook Page Likes & Reactions', 6],
    [7, 'Spotify Streams & Monthly Listeners', 7],
    [8, 'Discord Members & Server Boosts', 8]
];

foreach ($categories as $cat) {
    Database::query("
        INSERT INTO categories (id, name, sort_order, status)
        VALUES (?, ?, ?, 'active')
        ON DUPLICATE KEY UPDATE name = VALUES(name), sort_order = VALUES(sort_order), status = 'active'
    ", [$cat[0], $cat[1], $cat[2]]);
}

// 5. Services
$services = [
    // Instagram
    [1, 1, 1, '101', 'Instagram Followers [HQ Real Profiles] [30 Days Refill] [Speed: 10K/Day]', 'Default', 1.8500, 1.2000, 50, 50000, 1, 1, 1, 'Guaranteed high-quality non-drop followers with instant start and 30-day refill button.'],
    [2, 1, 1, '102', 'Instagram Likes [Real Active Users] [Instant Start] [Speed: 50K/Day]', 'Default', 0.6500, 0.3500, 20, 100000, 0, 1, 1, 'Ultra-fast likes from organic accounts. Drip-feed supported.'],
    [3, 1, 1, '103', 'Instagram Reels Views [High Retention + Reach Booster]', 'Default', 0.2000, 0.0900, 100, 1000000, 0, 1, 0, 'Explode your Reels into Explore algorithm. Retention avg 90%.'],
    [4, 1, 1, '104', 'Instagram Custom Comments [Positive Real Comments]', 'Custom Comments', 14.5000, 8.5000, 5, 2000, 0, 0, 0, 'Enter one custom comment per line. Posted by real active accounts.'],
    
    // YouTube
    [5, 2, 2, '201', 'YouTube Views [Monetizable Lifetime Guaranteed] [Speed: 20K/Day]', 'Default', 3.2000, 2.1000, 500, 500000, 1, 1, 1, 'High retention views completely safe for AdSense monetization. Non-drop.'],
    [6, 2, 2, '202', 'YouTube Subscribers [Non-Drop Ultra High Quality] [30 Days Refill]', 'Default', 18.5000, 12.0000, 20, 10000, 1, 1, 0, 'Authentic subscribers with high profile score. Natural speed 50-100/day.'],
    [7, 2, 2, '203', 'YouTube 4000 Watch Hours Package [Monetization Ready]', 'Package', 65.0000, 45.0000, 1, 1, 1, 0, 0, 'Complete 4000 watch hours on videos of 60+ minutes. Safe and guaranteed.'],
    [8, 2, 2, '204', 'YouTube Likes [Instant Start + Non-Drop]', 'Default', 2.1000, 1.1000, 50, 50000, 1, 1, 1, 'Boost video engagement score for top search rankings.'],
    
    // TikTok
    [9, 3, 1, '301', 'TikTok Followers [For FYP Algorithm] [Instant Start]', 'Default', 2.4000, 1.5000, 50, 100000, 1, 1, 1, 'Trigger the FYP algorithm with high-engagement followers.'],
    [10, 3, 1, '302', 'TikTok Likes [Real Active FYP Users]', 'Default', 0.7500, 0.4000, 20, 200000, 0, 1, 1, 'Instant start likes delivery within 60 seconds.'],
    [11, 3, 1, '303', 'TikTok Video Views [Ultra Fast 10M/Day]', 'Default', 0.0500, 0.0200, 100, 5000000, 0, 1, 0, 'The fastest views on the market. Starts immediately.'],
    
    // Telegram
    [12, 4, 1, '401', 'Telegram Channel Members [0% Drop Non-Drop] [1 Year Refill]', 'Default', 1.9500, 1.1000, 50, 50000, 1, 1, 0, 'Real looking channel subscribers with active online history.'],
    [13, 4, 1, '402', 'Telegram Post Views [Last 5 Posts Auto-Views]', 'Default', 0.1500, 0.0600, 100, 100000, 0, 0, 0, 'Views distributed on your latest channel posts.'],
    
    // X Twitter
    [14, 5, 2, '501', 'X (Twitter) Followers [Active Worldwide Profiles]', 'Default', 3.8000, 2.4000, 50, 25000, 1, 1, 1, 'Organic looking followers with avatars, bios, and tweet history.'],
    [15, 5, 2, '502', 'X (Twitter) Retweets & Reposts [Instant Start]', 'Default', 1.9000, 1.0000, 20, 20000, 0, 1, 1, 'Viral boost for your tweets with instant delivery.'],
    
    // Facebook
    [16, 6, 2, '601', 'Facebook Page Likes + Followers [Worldwide Quality]', 'Default', 4.5000, 2.8000, 50, 50000, 1, 1, 0, 'Dual action page likes and followers for public FB pages.'],
    
    // Spotify
    [17, 7, 2, '701', 'Spotify Track Plays [USA / EU Tier 1 Premium Streamers]', 'Default', 1.6000, 0.9500, 500, 1000000, 0, 1, 1, 'Eligible for Spotify royalties. 90-120 seconds listen time.'],
    
    // Discord
    [18, 8, 1, '801', 'Discord Server Offline Members [Realistic Usernames]', 'Default', 5.5000, 3.2000, 50, 10000, 0, 0, 0, 'Populate your Discord community with custom realistic accounts.']
];

foreach ($services as $srv) {
    Database::query("
        INSERT INTO services (
            id, category_id, provider_id, provider_service_id, name, type, 
            rate, provider_rate, min_quantity, max_quantity, refill, cancel, dripfeed, description, status, provider_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'active')
        ON DUPLICATE KEY UPDATE 
            category_id = VALUES(category_id), name = VALUES(name), rate = VALUES(rate),
            min_quantity = VALUES(min_quantity), max_quantity = VALUES(max_quantity),
            refill = VALUES(refill), status = 'active'
    ", [
        $srv[0], $srv[1], $srv[2], $srv[3], $srv[4], $srv[5],
        $srv[6], $srv[7], $srv[8], $srv[9], $srv[10], $srv[11], $srv[12], $srv[13]
    ]);
}

// 6. Payment Gateways - Activate Bank Transfer and Stripe for testing
Database::query("
    UPDATE payment_gateways 
    SET status = 'active', instructions = 'Direct Bank Wire or ACH transfer. Processing within 1-2 hours upon receipt.'
    WHERE code = 'bank_transfer'
");

Database::query("
    UPDATE payment_gateways 
    SET status = 'active', credentials = '{\"publishable_key\":\"pk_test_sample_51O9DemoKey\",\"secret_key\":\"sk_test_sample_demo\",\"webhook_secret\":\"whsec_demo\"}'
    WHERE code = 'stripe'
");

Database::query("
    UPDATE payment_gateways 
    SET status = 'active', credentials = '{\"client_id\":\"demo_client_id\",\"client_secret\":\"demo_client_secret\",\"mode\":\"sandbox\"}'
    WHERE code = 'paypal'
");

Database::query("
    UPDATE payment_gateways 
    SET status = 'active', credentials = '{\"merchant_id\":\"demo_merchant\",\"public_key\":\"demo_pub\",\"private_key\":\"demo_priv\",\"ipn_secret\":\"demo_ipn\"}'
    WHERE code = 'coinpayments'
");

// 7. Seed Sample Orders for Demo User
$sampleOrders = [
    [1001, 2, 1, 1, '10001', 'https://instagram.com/techfounder', 2000, 3.7000, 1420, 0, 'completed', 0, 0],
    [1002, 2, 5, 2, '20002', 'https://youtube.com/watch?v=dQw4w9WgXcQ', 5000, 16.0000, 890, 4110, 'in_progress', 0, 0],
    [1003, 2, 9, 1, '30003', 'https://tiktok.com/@growthhacks', 1000, 2.4000, 300, 700, 'processing', 0, 0],
    [1004, 2, 12, 1, '40004', 'https://t.me/cryptoleaks_vip', 1500, 2.9250, 0, 1500, 'pending', 0, 0],
    [1005, 2, 2, 1, '10005', 'https://instagram.com/p/C48xK9yZ0/', 1500, 0.9750, 520, 0, 'completed', 0, 0],
    [1006, 2, 8, 2, '20006', 'https://youtube.com/watch?v=dQw4w9WgXcQ', 1000, 2.1000, 45, 0, 'completed', 0, 0]
];

foreach ($sampleOrders as $ord) {
    Database::query("
        INSERT INTO orders (
            id, user_id, service_id, provider_id, provider_order_id, link, quantity, 
            charge, start_count, remains, status, runs, interval_minutes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE status = VALUES(status)
    ", $ord);
}

// 8. Seed Sample Transactions
$sampleTransactions = [
    [1, 2, 'deposit', 100.0000, 0.0000, 100.0000, 'DEP-88391', 'Deposit via Stripe Card Checkout'],
    [2, 2, 'order', 3.7000, 100.0000, 96.3000, 'ORD-1001', 'Order #1001 - Instagram Followers'],
    [3, 2, 'order', 16.0000, 96.3000, 80.3000, 'ORD-1002', 'Order #1002 - YouTube Views'],
    [4, 2, 'deposit', 75.0000, 80.3000, 155.3000, 'DEP-88402', 'Deposit via Crypto USDT'],
    [5, 2, 'order', 2.4000, 155.3000, 152.9000, 'ORD-1003', 'Order #1003 - TikTok Followers'],
    [6, 2, 'order', 2.9000, 152.9000, 150.0000, 'ORD-1004', 'Order #1004 - Telegram Channel Members']
];

foreach ($sampleTransactions as $tx) {
    Database::query("
        INSERT INTO transactions (id, user_id, type, amount, balance_before, balance_after, reference_id, description)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE description = VALUES(description)
    ", $tx);
}

// 9. Seed Sample Tickets
Database::query("
    INSERT INTO tickets (id, user_id, subject, category, priority, status)
    VALUES (1, 2, 'Speed inquiry for YouTube 4K Views order', 'order', 'medium', 'answered')
    ON DUPLICATE KEY UPDATE subject = VALUES(subject)
");

Database::query("
    INSERT INTO ticket_messages (id, ticket_id, user_id, is_admin, message)
    VALUES 
    (1, 1, 2, 0, 'Hi Team, I placed order #1002 for YouTube views. Could you confirm when the delivery speed reaches 20k/day? Thank you!'),
    (2, 1, 1, 1, 'Hello Demo! Thank you for contacting ApexSMM support. Your YouTube order #1002 has started warming up and the delivery speed will ramp up to full 20,000 views per day within the next 2 hours. Everything is running smoothly on our premium servers!')
    ON DUPLICATE KEY UPDATE message = VALUES(message)
");

Database::query("
    INSERT INTO tickets (id, user_id, subject, category, priority, status)
    VALUES (2, 2, 'Requesting Custom API Key Integration Documentation', 'service', 'low', 'open')
    ON DUPLICATE KEY UPDATE subject = VALUES(subject)
");

Database::query("
    INSERT INTO ticket_messages (id, ticket_id, user_id, is_admin, message)
    VALUES (3, 2, 2, 0, 'Hello, I want to connect my automated ordering script to the ApexSMM API endpoint. Where can I find example cURL requests for the orders.add action?')
    ON DUPLICATE KEY UPDATE message = VALUES(message)
");

// 10. Seed Notifications
Database::query("
    INSERT INTO notifications (id, user_id, title, message, type, link, is_read)
    VALUES 
    (1, 2, 'Welcome to ApexSMM!', 'Your account has been successfully created. Explore our 25+ verified social growth services.', 'success', '/services', 1),
    (2, 2, 'Deposit Successful', 'Your wallet was credited with $75.00 via Crypto USDT.', 'success', '/user/transactions.php', 0),
    (3, NULL, 'Service Speed Update', 'All Instagram and YouTube services are now delivering at 2x turbo speed following our network upgrade.', 'announcement', '/services', 0)
    ON DUPLICATE KEY UPDATE title = VALUES(title)
");

echo "Seeding completed successfully!\n";
