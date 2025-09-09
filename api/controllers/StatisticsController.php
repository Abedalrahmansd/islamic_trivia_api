<?php
// ============================================================================
// /controllers/StatisticsController.php
// ============================================================================

class StatisticsController {
    private $db;
    private $authMiddleware;

    public function __construct($database) {
        $this->db = $database;
        $this->authMiddleware = new AuthMiddleware($database);
    }

    public function dashboard() {
        $admin = $this->authMiddleware->handle();

        try {
            // Total counts
            $countsQuery = "SELECT 
                (SELECT COUNT(*) FROM categories WHERE is_active = 1) as total_categories,
                (SELECT COUNT(*) FROM challenge_packs WHERE is_active = 1) as total_packs,
                (SELECT COUNT(*) FROM questions WHERE is_active = 1) as total_questions,
                (SELECT COUNT(*) FROM games WHERE completed_at IS NOT NULL) as total_games,
                (SELECT COUNT(*) FROM admin_users WHERE is_active = 1) as total_admins,
                (SELECT SUM(download_count) FROM challenge_packs) as total_downloads";

            $countsStmt = $this->db->prepare($countsQuery);
            $countsStmt->execute();
            $counts = $countsStmt->fetch();

            // Recent activity (last 7 days)
            $activityQuery = "SELECT 
                DATE(al.created_at) as date,
                al.action,
                al.target_type,
                COUNT(*) as count 
                FROM admin_logs al 
                WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                GROUP BY DATE(al.created_at), al.action, al.target_type 
                ORDER BY date DESC, count DESC 
                LIMIT 20";

            $activityStmt = $this->db->prepare($activityQuery);
            $activityStmt->execute();
            $activity = $activityStmt->fetchAll();

            // Popular categories
            $popularQuery = "SELECT 
                c.name, 
                c.name_ar, 
                COUNT(q.id) as question_count,
                AVG(CASE q.difficulty 
                    WHEN 'easy' THEN 1 
                    WHEN 'medium' THEN 2 
                    WHEN 'hard' THEN 3 
                    END) as avg_difficulty
                FROM categories c 
                LEFT JOIN questions q ON c.id = q.category_id AND q.is_active = 1 
                WHERE c.is_active = 1 
                GROUP BY c.id 
                ORDER BY question_count DESC 
                LIMIT 5";

            $popularStmt = $this->db->prepare($popularQuery);
            $popularStmt->execute();
            $popular = $popularStmt->fetchAll();

            // Top downloaded packs
            $topPacksQuery = "SELECT 
                name, 
                name_ar, 
                download_count, 
                (SELECT COUNT(*) FROM questions WHERE challenge_pack_id = cp.id AND is_active = 1) as question_count
                FROM challenge_packs cp
                WHERE is_active = 1 
                ORDER BY download_count DESC 
                LIMIT 5";

            $topPacksStmt = $this->db->prepare($topPacksQuery);
            $topPacksStmt->execute();
            $topPacks = $topPacksStmt->fetchAll();

            Response::success([
                'counts' => $counts,
                'recent_activity' => $activity,
                'popular_categories' => $popular,
                'top_downloaded_packs' => $topPacks
            ], 'Dashboard statistics retrieved successfully');

        } catch (Exception $e) {
            error_log("Dashboard stats error: " . $e->getMessage());
            Response::error('Failed to retrieve dashboard statistics', 500, null, 'STATS_FAILED');
        }
    }

    public function categories() {
        $admin = $this->authMiddleware->handle();

        try {
            $query = "SELECT 
                c.*,
                COUNT(q.id) as question_count,
                AVG(CASE q.difficulty 
                    WHEN 'easy' THEN 1 
                    WHEN 'medium' THEN 2 
                    WHEN 'hard' THEN 3 
                    END) as avg_difficulty_score,
                SUM(CASE WHEN q.difficulty = 'easy' THEN 1 ELSE 0 END) as easy_questions,
                SUM(CASE WHEN q.difficulty = 'medium' THEN 1 ELSE 0 END) as medium_questions,
                SUM(CASE WHEN q.difficulty = 'hard' THEN 1 ELSE 0 END) as hard_questions
                FROM categories c
                LEFT JOIN questions q ON c.id = q.category_id AND q.is_active = 1
                WHERE c.is_active = 1
                GROUP BY c.id
                ORDER BY question_count DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats = $stmt->fetchAll();

            Response::success($stats, 'Category statistics retrieved successfully');

        } catch (Exception $e) {
            error_log("Category stats error: " . $e->getMessage());
            Response::error('Failed to retrieve category statistics', 500, null, 'STATS_FAILED');
        }
    }

    public function packs() {
        $admin = $this->authMiddleware->handle();

        try {
            $query = "SELECT 
                cp.*,
                COUNT(q.id) as question_count,
                cp.download_count,
                AVG(CASE q.difficulty 
                    WHEN 'easy' THEN 1 
                    WHEN 'medium' THEN 2 
                    WHEN 'hard' THEN 3 
                    END) as avg_difficulty_score,
                SUM(CASE WHEN q.difficulty = 'easy' THEN 1 ELSE 0 END) as easy_questions,
                SUM(CASE WHEN q.difficulty = 'medium' THEN 1 ELSE 0 END) as medium_questions,
                SUM(CASE WHEN q.difficulty = 'hard' THEN 1 ELSE 0 END) as hard_questions
                FROM challenge_packs cp
                LEFT JOIN questions q ON cp.id = q.challenge_pack_id AND q.is_active = 1
                WHERE cp.is_active = 1
                GROUP BY cp.id
                ORDER BY cp.download_count DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats = $stmt->fetchAll();

            Response::success($stats, 'Challenge pack statistics retrieved successfully');

        } catch (Exception $e) {
            error_log("Pack stats error: " . $e->getMessage());
            Response::error('Failed to retrieve pack statistics', 500, null, 'STATS_FAILED');
        }
    }

    public function questions() {
        $admin = $this->authMiddleware->handle();

        try {
            // Questions by difficulty
            $difficultyQuery = "SELECT 
                difficulty, 
                COUNT(*) as count,
                AVG(timer_seconds) as avg_timer
                FROM questions 
                WHERE is_active = 1 
                GROUP BY difficulty
                ORDER BY FIELD(difficulty, 'easy', 'medium', 'hard')";

            $difficultyStmt = $this->db->prepare($difficultyQuery);
            $difficultyStmt->execute();
            $difficulty = $difficultyStmt->fetchAll();

            // Questions by source
            $sourceQuery = "SELECT 
                COUNT(CASE WHEN category_id IS NOT NULL THEN 1 END) as category_questions,
                COUNT(CASE WHEN challenge_pack_id IS NOT NULL THEN 1 END) as pack_questions,
                COUNT(*) as total_questions
                FROM questions 
                WHERE is_active = 1";

            $sourceStmt = $this->db->prepare($sourceQuery);
            $sourceStmt->execute();
            $source = $sourceStmt->fetch();

            // Recent questions (last 30 days)
            $recentQuery = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as questions_added
                FROM questions 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND is_active = 1
                GROUP BY DATE(created_at)
                ORDER BY date DESC";

            $recentStmt = $this->db->prepare($recentQuery);
            $recentStmt->execute();
            $recent = $recentStmt->fetchAll();

            Response::success([
                'by_difficulty' => $difficulty,
                'by_source' => $source,
                'recent_additions' => $recent
            ], 'Question statistics retrieved successfully');

        } catch (Exception $e) {
            error_log("Question stats error: " . $e->getMessage());
            Response::error('Failed to retrieve question statistics', 500, null, 'STATS_FAILED');
        }
    }

    public function games() {
        try {
            // Game statistics
            $gameStatsQuery = "SELECT 
                COUNT(*) as total_games,
                AVG(total_teams) as avg_teams,
                AVG(questions_per_round) as avg_questions_per_round,
                AVG(total_rounds) as avg_rounds,
                COUNT(CASE WHEN game_mode = 'category' THEN 1 END) as category_games,
                COUNT(CASE WHEN game_mode = 'challenge_pack' THEN 1 END) as pack_games
                FROM games 
                WHERE completed_at IS NOT NULL";

            $gameStatsStmt = $this->db->prepare($gameStatsQuery);
            $gameStatsStmt->execute();
            $gameStats = $gameStatsStmt->fetch();

            // Games over time (last 30 days)
            $timeQuery = "SELECT 
                DATE(completed_at) as date,
                COUNT(*) as games_completed
                FROM games 
                WHERE completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                GROUP BY DATE(completed_at)
                ORDER BY date DESC";

            $timeStmt = $this->db->prepare($timeQuery);
            $timeStmt->execute();
            $timeData = $timeStmt->fetchAll();

            Response::success([
                'overview' => $gameStats,
                'games_over_time' => $timeData
            ], 'Game statistics retrieved successfully');

        } catch (Exception $e) {
            error_log("Game stats error: " . $e->getMessage());
            Response::error('Failed to retrieve game statistics', 500, null, 'STATS_FAILED');
        }
    }

    public function general() {
        try {
            $query = "SELECT 
                (SELECT COUNT(*) FROM categories WHERE is_active = 1) as categories,
                (SELECT COUNT(*) FROM challenge_packs WHERE is_active = 1) as packs,
                (SELECT COUNT(*) FROM questions WHERE is_active = 1) as questions,
                (SELECT COUNT(*) FROM games WHERE completed_at IS NOT NULL) as completed_games,
                (SELECT COUNT(*) FROM admin_users WHERE is_active = 1) as admins,
                (SELECT SUM(download_count) FROM challenge_packs) as total_downloads,
                (SELECT COUNT(*) FROM admin_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as actions_last_24h";

            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats = $stmt->fetch();

            Response::success($stats, 'General statistics retrieved successfully');

        } catch (Exception $e) {
            error_log("General stats error: " . $e->getMessage());
            Response::error('Failed to retrieve general statistics', 500, null, 'STATS_FAILED');
        }
    }
}
?>