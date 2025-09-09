-- Islamic Trivia Game - Complete Database Schema
-- Created for website (HTML/CSS/JS) and mobile app (Flutter) platforms

-- ============================================================================
-- 1. CATEGORIES TABLE
-- ============================================================================
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    name_ar VARCHAR(255) NOT NULL, -- Arabic name
    description TEXT,
    description_ar TEXT, -- Arabic description
    difficulty ENUM('easy', 'medium', 'hard') NOT NULL DEFAULT 'medium',
    timer_seconds INT NOT NULL DEFAULT 30, -- Time limit per question
    is_default BOOLEAN DEFAULT FALSE, -- Pre-loaded categories
    is_active BOOLEAN DEFAULT TRUE,
    created_by_admin BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_difficulty (difficulty),
    INDEX idx_active (is_active),
    INDEX idx_default (is_default)
);

-- ============================================================================
-- 2. CHALLENGE PACKS TABLE
-- ============================================================================
CREATE TABLE challenge_packs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    name_ar VARCHAR(255) NOT NULL, -- Arabic name
    description TEXT,
    description_ar TEXT, -- Arabic description
    theme VARCHAR(100), -- e.g., 'ramadan', 'seerah', 'quran'
    difficulty ENUM('easy', 'medium', 'hard') NOT NULL DEFAULT 'medium',
    timer_seconds INT NOT NULL DEFAULT 30,
    is_active BOOLEAN DEFAULT TRUE,
    download_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_theme (theme),
    INDEX idx_difficulty (difficulty),
    INDEX idx_active (is_active)
);

-- ============================================================================
-- 3. QUESTIONS TABLE
-- ============================================================================
CREATE TABLE questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    challenge_pack_id INT,
    question_text TEXT NOT NULL,
    question_text_ar TEXT NOT NULL, -- Arabic question
    option_a VARCHAR(500) NOT NULL,
    option_a_ar VARCHAR(500) NOT NULL,
    option_b VARCHAR(500) NOT NULL,
    option_b_ar VARCHAR(500) NOT NULL,
    option_c VARCHAR(500) NOT NULL,
    option_c_ar VARCHAR(500) NOT NULL,
    option_d VARCHAR(500) NOT NULL,
    option_d_ar VARCHAR(500) NOT NULL,
    correct_answer ENUM('a', 'b', 'c', 'd') NOT NULL,
    explanation TEXT, -- Optional explanation
    explanation_ar TEXT, -- Arabic explanation
    difficulty ENUM('easy', 'medium', 'hard') NOT NULL DEFAULT 'medium',
    timer_seconds INT, -- Override category/pack timer if needed
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (challenge_pack_id) REFERENCES challenge_packs(id) ON DELETE CASCADE,
    
    INDEX idx_category (category_id),
    INDEX idx_pack (challenge_pack_id),
    INDEX idx_difficulty (difficulty),
    INDEX idx_active (is_active),
    
    -- Ensure question belongs to either category or challenge pack, not both
    CONSTRAINT chk_question_source CHECK (
        (category_id IS NOT NULL AND challenge_pack_id IS NULL) OR 
        (category_id IS NULL AND challenge_pack_id IS NOT NULL)
    )
);

-- ============================================================================
-- 4. GAMES TABLE (Optional - for website result saving)
-- ============================================================================
CREATE TABLE games (
    id INT PRIMARY KEY AUTO_INCREMENT,
    game_name VARCHAR(255), -- Optional game session name
    total_teams INT NOT NULL,
    total_rounds INT DEFAULT 1,
    questions_per_round INT DEFAULT 10,
    game_mode ENUM('category', 'challenge_pack') NOT NULL,
    source_id INT NOT NULL, -- category_id or challenge_pack_id
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    INDEX idx_game_mode (game_mode),
    INDEX idx_source (source_id),
    INDEX idx_created (created_at)
);

-- ============================================================================
-- 5. TEAMS TABLE (Optional - for website result saving)
-- ============================================================================
CREATE TABLE teams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    game_id INT NOT NULL,
    team_name VARCHAR(255) NOT NULL,
    team_position INT NOT NULL, -- Team A=1, Team B=2, etc.
    total_score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    
    INDEX idx_game (game_id),
    INDEX idx_position (team_position)
);

-- ============================================================================
-- 6. GAME_QUESTIONS TABLE (Track questions used in each game)
-- ============================================================================
CREATE TABLE game_questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    game_id INT NOT NULL,
    question_id INT NOT NULL,
    round_number INT NOT NULL,
    question_order INT NOT NULL, -- Order within the round
    
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_game_question (game_id, question_id),
    INDEX idx_game_round (game_id, round_number),
    INDEX idx_question_order (question_order)
);

-- ============================================================================
-- 7. TEAM_ANSWERS TABLE (Track team responses)
-- ============================================================================
CREATE TABLE team_answers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    game_id INT NOT NULL,
    team_id INT NOT NULL,
    question_id INT NOT NULL,
    round_number INT NOT NULL,
    selected_answer ENUM('a', 'b', 'c', 'd'),
    is_correct BOOLEAN DEFAULT FALSE,
    points_earned INT DEFAULT 0,
    time_taken INT, -- Seconds taken to answer
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_team_question (team_id, question_id),
    INDEX idx_game_round (game_id, round_number),
    INDEX idx_team_performance (team_id, is_correct)
);

-- ============================================================================
-- 8. ADMIN_USERS TABLE
-- ============================================================================
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- ============================================================================
-- 9. ADMIN_SESSIONS TABLE
-- ============================================================================
CREATE TABLE admin_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    
    INDEX idx_token (session_token),
    INDEX idx_expires (expires_at)
);

-- ============================================================================
-- 10. ADMIN_LOGS TABLE
-- ============================================================================
CREATE TABLE admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT,
    action VARCHAR(100) NOT NULL,
    target_type ENUM('category', 'question', 'challenge_pack', 'admin') NOT NULL,
    target_id INT,
    old_data JSON, -- Store previous state
    new_data JSON, -- Store new state
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    
    INDEX idx_admin (admin_id),
    INDEX idx_action (action),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created (created_at)
);

-- ============================================================================
-- 11. STATISTICS TABLE (Game analytics)
-- ============================================================================
CREATE TABLE statistics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stat_type ENUM('daily_games', 'pack_downloads', 'question_performance', 'category_popularity') NOT NULL,
    stat_date DATE NOT NULL,
    category_id INT NULL,
    challenge_pack_id INT NULL,
    question_id INT NULL,
    stat_value INT DEFAULT 0,
    additional_data JSON, -- Store extra metrics
    
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (challenge_pack_id) REFERENCES challenge_packs(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_daily_stat (stat_type, stat_date, category_id, challenge_pack_id, question_id),
    INDEX idx_stat_type (stat_type),
    INDEX idx_date (stat_date)
);

-- ============================================================================
-- 12. AI_GENERATED_CONTENT TABLE (Track AI-created questions)
-- ============================================================================
CREATE TABLE ai_generated_content (
    id INT PRIMARY KEY AUTO_INCREMENT,
    content_type ENUM('question', 'category') NOT NULL,
    content_id INT NOT NULL, -- question_id or category_id
    ai_model VARCHAR(100), -- e.g., 'gpt-4', 'claude-3'
    prompt_used TEXT,
    generation_cost DECIMAL(10,4) DEFAULT 0.0000,
    admin_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    
    INDEX idx_content_type (content_type),
    INDEX idx_content_id (content_id),
    INDEX idx_admin (admin_id)
);

-- ============================================================================
-- SAMPLE DATA INSERTION
-- ============================================================================

-- Default Categories
INSERT INTO categories (name, name_ar, description, description_ar, difficulty, timer_seconds, is_default) VALUES
('Quran', 'القرآن الكريم', 'Questions about the Holy Quran', 'أسئلة حول القرآن الكريم', 'medium', 30, TRUE),
('Seerah', 'السيرة النبوية', 'Prophet Muhammad\'s biography', 'سيرة الرسول صلى الله عليه وسلم', 'medium', 30, TRUE),
('Islamic History', 'التاريخ الإسلامي', 'Islamic civilization and history', 'الحضارة والتاريخ الإسلامي', 'hard', 45, TRUE),
('Fiqh', 'الفقه', 'Islamic jurisprudence', 'الفقه الإسلامي', 'medium', 30, TRUE),
('Aqeedah', 'العقيدة', 'Islamic beliefs and theology', 'العقيدة الإسلامية', 'medium', 30, TRUE);

-- Challenge Packs
INSERT INTO challenge_packs (name, name_ar, description, description_ar, theme, difficulty, timer_seconds) VALUES
('Ramadan Special', 'رمضان المبارك', 'Questions about the holy month of Ramadan', 'أسئلة حول شهر رمضان المبارك', 'ramadan', 'easy', 25),
('Prophets Stories', 'قصص الأنبياء', 'Stories of prophets mentioned in Quran', 'قصص الأنبياء المذكورة في القرآن', 'prophets', 'medium', 30),
('Hajj Journey', 'رحلة الحج', 'Everything about Hajj pilgrimage', 'كل شيء عن فريضة الحج', 'hajj', 'medium', 35);

-- Super Admin User (password should be hashed in real implementation)
INSERT INTO admin_users (username, email, password_hash, full_name, role) VALUES
('admin', 'admin@islamictrivia.com', '$2y$10$example_hash_here', 'System Administrator', 'super_admin');

-- Sample Questions for Quran category
INSERT INTO questions (category_id, question_text, question_text_ar, option_a, option_a_ar, option_b, option_b_ar, option_c, option_c_ar, option_d, option_d_ar, correct_answer, difficulty) VALUES
(1, 'How many chapters (Surahs) are in the Quran?', 'كم عدد سور القرآن الكريم؟', '110', '110', '114', '114', '116', '116', '120', '120', 'b', 'easy'),
(1, 'Which Surah is known as the heart of the Quran?', 'أي سورة تُعرف بقلب القرآن؟', 'Al-Fatiha', 'الفاتحة', 'Yasin', 'يس', 'Al-Baqarah', 'البقرة', 'Al-Ikhlas', 'الإخلاص', 'b', 'medium');

-- ============================================================================
-- USEFUL VIEWS FOR API ENDPOINTS
-- ============================================================================

-- View for active categories with question count
CREATE VIEW view_categories_with_count AS
SELECT 
    c.*,
    COUNT(q.id) as question_count
FROM categories c
LEFT JOIN questions q ON c.id = q.category_id AND q.is_active = TRUE
WHERE c.is_active = TRUE
GROUP BY c.id;

-- View for active challenge packs with question count
CREATE VIEW view_packs_with_count AS
SELECT 
    cp.*,
    COUNT(q.id) as question_count
FROM challenge_packs cp
LEFT JOIN questions q ON cp.id = q.challenge_pack_id AND q.is_active = TRUE
WHERE cp.is_active = TRUE
GROUP BY cp.id;

-- View for leaderboard statistics
CREATE VIEW view_team_statistics AS
SELECT 
    t.game_id,
    t.team_name,
    t.total_score,
    COUNT(ta.id) as questions_answered,
    SUM(CASE WHEN ta.is_correct = TRUE THEN 1 ELSE 0 END) as correct_answers,
    AVG(ta.time_taken) as avg_response_time
FROM teams t
LEFT JOIN team_answers ta ON t.id = ta.team_id
GROUP BY t.id;

-- ============================================================================
-- INDEXES FOR OPTIMIZATION
-- ============================================================================

-- Additional composite indexes for better query performance
CREATE INDEX idx_questions_category_active ON questions(category_id, is_active);
CREATE INDEX idx_questions_pack_active ON questions(challenge_pack_id, is_active);
CREATE INDEX idx_team_answers_performance ON team_answers(team_id, round_number, is_correct);
CREATE INDEX idx_games_completed ON games(completed_at, game_mode);

-- ============================================================================
-- STORED PROCEDURES (Optional - for complex operations)
-- ============================================================================

DELIMITER //

-- Procedure to get random questions from a category
CREATE PROCEDURE GetRandomQuestions(
    IN p_category_id INT,
    IN p_challenge_pack_id INT,
    IN p_limit INT
)
BEGIN
    IF p_category_id IS NOT NULL THEN
        SELECT * FROM questions 
        WHERE category_id = p_category_id AND is_active = TRUE 
        ORDER BY RAND() 
        LIMIT p_limit;
    ELSEIF p_challenge_pack_id IS NOT NULL THEN
        SELECT * FROM questions 
        WHERE challenge_pack_id = p_challenge_pack_id AND is_active = TRUE 
        ORDER BY RAND() 
        LIMIT p_limit;
    END IF;
END //

-- Procedure to update challenge pack download count
CREATE PROCEDURE UpdateDownloadCount(IN p_pack_id INT)
BEGIN
    UPDATE challenge_packs 
    SET download_count = download_count + 1 
    WHERE id = p_pack_id;
END //

DELIMITER ;

-- ============================================================================
-- TRIGGERS FOR AUDIT AND MAINTENANCE
-- ============================================================================

-- Trigger to log admin actions
DELIMITER //

CREATE TRIGGER tr_questions_audit_insert
AFTER INSERT ON questions
FOR EACH ROW
BEGIN
    INSERT INTO admin_logs (admin_id, action, target_type, target_id, new_data)
    VALUES (NULL, 'CREATE', 'question', NEW.id, JSON_OBJECT('question', NEW.question_text));
END //

CREATE TRIGGER tr_questions_audit_update
AFTER UPDATE ON questions
FOR EACH ROW
BEGIN
    INSERT INTO admin_logs (admin_id, action, target_type, target_id, old_data, new_data)
    VALUES (NULL, 'UPDATE', 'question', NEW.id, 
            JSON_OBJECT('question', OLD.question_text, 'active', OLD.is_active),
            JSON_OBJECT('question', NEW.question_text, 'active', NEW.is_active));
END //

DELIMITER ;

-- ============================================================================
-- DATABASE CONFIGURATION
-- ============================================================================

-- Set UTF8 encoding for proper Arabic text support
ALTER DATABASE islamic_trivia_game CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================================
-- SECURITY NOTES
-- ============================================================================

/*
IMPORTANT SECURITY CONSIDERATIONS:

1. Password Hashing: Use PHP's password_hash() and password_verify() functions
2. SQL Injection: Always use prepared statements in PHP
3. Admin Sessions: Implement proper session management with expiration
4. Input Validation: Validate all user inputs on both client and server side
5. CORS: Configure proper CORS headers for API endpoints
6. Rate Limiting: Implement rate limiting for API calls
7. HTTPS: Always use HTTPS in production
8. Database Backups: Set up regular automated backups
9. Error Logging: Log all errors but don't expose sensitive information
10. API Authentication: Use JWT tokens or similar for admin API access

RECOMMENDED PHP SETTINGS:
- Enable PDO with MySQL
- Set proper error reporting for development
- Configure session security settings
- Use environment variables for database credentials
*/