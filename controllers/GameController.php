<?php
// ============================================================================
// /controllers/GameController.php
// ============================================================================

class GameController {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);

        $validator = new Validator();
        $validator->validate([
            'total_teams' => ['required', ['numeric', 1, 10]],
            'game_mode' => ['required', ['inArray', ['category', 'challenge_pack']]],
            'source_id' => ['required', ['numeric', 1]],
            'questions_per_round' => [['numeric', 1, MAX_QUESTIONS_PER_REQUEST]],
            'total_rounds' => [['numeric', 1, 10]]
        ], $input);

        if ($validator->fails()) {
            Response::error('Validation failed', 400, $validator->getErrors(), 'VALIDATION_ERROR');
        }

        // Validate source exists
        $sourceTable = $input['game_mode'] === 'category' ? 'categories' : 'challenge_packs';
        $sourceQuery = "SELECT id FROM $sourceTable WHERE id = ? AND is_active = 1";
        $sourceStmt = $this->db->prepare($sourceQuery);
        $sourceStmt->execute([$input['source_id']]);

        if (!$sourceStmt->fetch()) {
            Response::error('Invalid source ID', 400, null, 'INVALID_SOURCE');
        }

        try {
            $query = "INSERT INTO games (game_name, total_teams, total_rounds, questions_per_round, game_mode, source_id) 
                      VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $input['game_name'] ?? null,
                $input['total_teams'],
                $input['total_rounds'] ?? 1,
                $input['questions_per_round'] ?? 10,
                $input['game_mode'],
                $input['source_id']
            ]);

            if ($result) {
                $gameId = $this->db->lastInsertId();
                Response::success(['game_id' => $gameId], 'Game session created successfully');
            }
        } catch (Exception $e) {
            error_log("Game creation error: " . $e->getMessage());
            Response::error('Failed to create game session', 500, null, 'CREATION_FAILED');
        }
    }

    public function save() {
        $input = json_decode(file_get_contents('php://input'), true);

        $validator = new Validator();
        $validator->validate([
            'game_id' => ['required', ['numeric', 1]],
            'teams' => ['required'],
            'results' => ['required']
        ], $input);

        if ($validator->fails()) {
            Response::error('Validation failed', 400, $validator->getErrors(), 'VALIDATION_ERROR');
        }

        // Validate game exists and is not completed
        $gameQuery = "SELECT * FROM games WHERE id = ? AND completed_at IS NULL";
        $gameStmt = $this->db->prepare($gameQuery);
        $gameStmt->execute([$input['game_id']]);
        $game = $gameStmt->fetch();

        if (!$game) {
            Response::error('Game not found or already completed', 404, null, 'GAME_NOT_FOUND');
        }

        $this->db->beginTransaction();

        try {
            $teamIds = [];
            
            // Save teams
            foreach ($input['teams'] as $position => $team) {
                $teamQuery = "INSERT INTO teams (game_id, team_name, team_position, total_score) VALUES (?, ?, ?, ?)";
                $teamStmt = $this->db->prepare($teamQuery);
                $teamStmt->execute([
                    $input['game_id'], 
                    $team['name'], 
                    $position + 1, 
                    $team['score'] ?? 0
                ]);
                $teamIds[$position] = $this->db->lastInsertId();
            }

            // Save game questions if provided
            if (isset($input['questions'])) {
                foreach ($input['questions'] as $round => $questions) {
                    foreach ($questions as $order => $questionId) {
                        $gameQuestionQuery = "INSERT INTO game_questions (game_id, question_id, round_number, question_order) 
                                             VALUES (?, ?, ?, ?)";
                        $gameQuestionStmt = $this->db->prepare($gameQuestionQuery);
                        $gameQuestionStmt->execute([$input['game_id'], $questionId, $round + 1, $order + 1]);
                    }
                }
            }

            // Save team answers
            foreach ($input['results'] as $result) {
                $answerQuery = "INSERT INTO team_answers 
                               (game_id, team_id, question_id, round_number, selected_answer, is_correct, points_earned, time_taken) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $answerStmt = $this->db->prepare($answerQuery);
                $answerStmt->execute([
                    $input['game_id'],
                    $teamIds[$result['team_index']] ?? null,
                    $result['question_id'],
                    ($result['round'] ?? 0) + 1,
                    $result['selected_answer'] ?? null,
                    $result['is_correct'] ? 1 : 0,
                    $result['points_earned'] ?? 0,
                    $result['time_taken'] ?? null
                ]);
            }

            // Mark game as completed
            $completeQuery = "UPDATE games SET completed_at = NOW() WHERE id = ?";
            $completeStmt = $this->db->prepare($completeQuery);
            $completeStmt->execute([$input['game_id']]);

            $this->db->commit();
            Response::success(null, 'Game results saved successfully');

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Game save error: " . $e->getMessage());
            Response::error('Failed to save game results: ' . $e->getMessage(), 500, null, 'SAVE_FAILED');
        }
    }

    public function show($gameId = null) {
        if (!$gameId) {
            // Get recent games
            $page = $_GET['page'] ?? 1;
            $limit = min($_GET['limit'] ?? DEFAULT_PAGE_SIZE, MAX_PAGE_SIZE);
            $offset = ($page - 1) * $limit;

            $countQuery = "SELECT COUNT(*) as total FROM games WHERE completed_at IS NOT NULL";
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute();
            $total = $countStmt->fetch()['total'];

            $query = "SELECT g.*, 
                             CASE 
                                 WHEN g.game_mode = 'category' THEN c.name 
                                 ELSE cp.name 
                             END as source_name,
                             CASE 
                                 WHEN g.game_mode = 'category' THEN c.name_ar 
                                 ELSE cp.name_ar 
                             END as source_name_ar,
                             COUNT(DISTINCT t.id) as teams_count
                      FROM games g 
                      LEFT JOIN categories c ON g.game_mode = 'category' AND g.source_id = c.id
                      LEFT JOIN challenge_packs cp ON g.game_mode = 'challenge_pack' AND g.source_id = cp.id
                      LEFT JOIN teams t ON g.id = t.game_id 
                      WHERE g.completed_at IS NOT NULL 
                      GROUP BY g.id 
                      ORDER BY g.completed_at DESC 
                      LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$limit, $offset]);
            $games = $stmt->fetchAll();

            Response::paginated($games, $page, $limit, $total);
        } else {
            // Get specific game with full details
            $gameQuery = "SELECT g.*, 
                                 CASE 
                                     WHEN g.game_mode = 'category' THEN c.name 
                                     ELSE cp.name 
                                 END as source_name,
                                 CASE 
                                     WHEN g.game_mode = 'category' THEN c.name_ar 
                                     ELSE cp.name_ar 
                                 END as source_name_ar
                          FROM games g 
                          LEFT JOIN categories c ON g.game_mode = 'category' AND g.source_id = c.id
                          LEFT JOIN challenge_packs cp ON g.game_mode = 'challenge_pack' AND g.source_id = cp.id
                          WHERE g.id = ?";
            
            $gameStmt = $this->db->prepare($gameQuery);
            $gameStmt->execute([$gameId]);
            $game = $gameStmt->fetch();

            if (!$game) {
                Response::error('Game not found', 404, null, 'GAME_NOT_FOUND');
            }

            $teamsQuery = "SELECT * FROM view_team_statistics WHERE game_id = ? ORDER BY total_score DESC";
            $teamsStmt = $this->db->prepare($teamsQuery);
            $teamsStmt->execute([$gameId]);
            $teams = $teamsStmt->fetchAll();

            Response::success([
                'game' => $game,
                'teams' => $teams
            ], 'Game details retrieved successfully');
        }
    }
}