<?php
// ============================================================================
// /controllers/QuestionController.php
// ============================================================================

class QuestionController {
    private $db;
    private $authMiddleware;

    public function __construct($database) {
        $this->db = $database;
        $this->authMiddleware = new AuthMiddleware($database);
    }

    public function index() {
        $page = $_GET['page'] ?? 1;
        $limit = min($_GET['limit'] ?? DEFAULT_PAGE_SIZE, MAX_PAGE_SIZE);
        $offset = ($page - 1) * $limit;
        $category_id = $_GET['category_id'] ?? null;
        $pack_id = $_GET['pack_id'] ?? null;
        $difficulty = $_GET['difficulty'] ?? null;

        $where = "WHERE q.is_active = 1";
        $params = [];

        if ($category_id) {
            $where .= " AND q.category_id = ?";
            $params[] = $category_id;
        }

        if ($pack_id) {
            $where .= " AND q.challenge_pack_id = ?";
            $params[] = $pack_id;
        }

        if ($difficulty) {
            $where .= " AND q.difficulty = ?";
            $params[] = $difficulty;
        }

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM questions q $where";
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // Get questions
        $params[] = $limit;
        $params[] = $offset;

        $query = "SELECT q.*, 
                         c.name as category_name, c.name_ar as category_name_ar,
                         cp.name as pack_name, cp.name_ar as pack_name_ar
                  FROM questions q
                  LEFT JOIN categories c ON q.category_id = c.id
                  LEFT JOIN challenge_packs cp ON q.challenge_pack_id = cp.id
                  $where
                  ORDER BY q.created_at DESC
                  LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $questions = $stmt->fetchAll();

        Response::paginated($questions, $page, $limit, $total);
    }

    public function show($id) {
        $query = "SELECT q.*, 
                         c.name as category_name, c.name_ar as category_name_ar,
                         cp.name as pack_name, cp.name_ar as pack_name_ar
                  FROM questions q
                  LEFT JOIN categories c ON q.category_id = c.id
                  LEFT JOIN challenge_packs cp ON q.challenge_pack_id = cp.id
                  WHERE q.id = ? AND q.is_active = 1";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $question = $stmt->fetch();

        if (!$question) {
            Response::error('Question not found', 404, null, 'QUESTION_NOT_FOUND');
        }

        Response::success($question);
    }

    public function random() {
        $category_id = $_GET['category_id'] ?? null;
        $pack_id = $_GET['pack_id'] ?? null;
        $limit = min($_GET['limit'] ?? 10, MAX_QUESTIONS_PER_REQUEST);
        $difficulty = $_GET['difficulty'] ?? null;

        if (!$category_id && !$pack_id) {
            Response::error('Category ID or Pack ID required', 400, null, 'SOURCE_REQUIRED');
        }

        $where = "WHERE is_active = 1";
        $params = [];

        if ($category_id) {
            $where .= " AND category_id = ?";
            $params[] = $category_id;
        }

        if ($pack_id) {
            $where .= " AND challenge_pack_id = ?";
            $params[] = $pack_id;
        }

        if ($difficulty) {
            $where .= " AND difficulty = ?";
            $params[] = $difficulty;
        }

        $params[] = $limit;

        $query = "SELECT * FROM questions $where ORDER BY RAND() LIMIT ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $questions = $stmt->fetchAll();

        if (empty($questions)) {
            Response::error('No questions found matching criteria', 404, null, 'NO_QUESTIONS_FOUND');
        }

        Response::success([
            'questions' => $questions,
            'total_returned' => count($questions),
            'difficulty_points' => [
                'easy' => EASY_POINTS,
                'medium' => MEDIUM_POINTS,
                'hard' => HARD_POINTS
            ]
        ], 'Random questions retrieved successfully');
    }

    public function store() {
        $admin = $this->authMiddleware->handle();
        $input = json_decode(file_get_contents('php://input'), true);

        $validator = new Validator();
        $validator->validate([
            'question_text' => ['required', ['minLength', 10], ['maxLength', 1000]],
            'question_text_ar' => ['required', ['minLength', 10], ['maxLength', 1000]],
            'option_a' => ['required', ['maxLength', 500]],
            'option_a_ar' => ['required', ['maxLength', 500]],
            'option_b' => ['required', ['maxLength', 500]],
            'option_b_ar' => ['required', ['maxLength', 500]],
            'option_c' => ['required', ['maxLength', 500]],
            'option_c_ar' => ['required', ['maxLength', 500]],
            'option_d' => ['required', ['maxLength', 500]],
            'option_d_ar' => ['required', ['maxLength', 500]],
            'correct_answer' => ['required', ['inArray', ['a', 'b', 'c', 'd']]],
            'difficulty' => [['inArray', ['easy', 'medium', 'hard']]],
            'timer_seconds' => [['numeric', 10, 300]]
        ], $input);

        if ($validator->fails()) {
            Response::error('Validation failed', 400, $validator->getErrors(), 'VALIDATION_ERROR');
        }

        // Validate that either category_id or challenge_pack_id is provided
        if (!isset($input['category_id']) && !isset($input['challenge_pack_id'])) {
            Response::error('Either category_id or challenge_pack_id is required', 400, null, 'SOURCE_REQUIRED');
        }

        if (isset($input['category_id']) && isset($input['challenge_pack_id'])) {
            Response::error('Question cannot belong to both category and challenge pack', 400, null, 'INVALID_SOURCE');
        }

        try {
            $query = "INSERT INTO questions (
                        category_id, challenge_pack_id, question_text, question_text_ar,
                        option_a, option_a_ar, option_b, option_b_ar, 
                        option_c, option_c_ar, option_d, option_d_ar,
                        correct_answer, explanation, explanation_ar, difficulty, timer_seconds
                      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $input['category_id'] ?? null,
                $input['challenge_pack_id'] ?? null,
                $input['question_text'],
                $input['question_text_ar'],
                $input['option_a'],
                $input['option_a_ar'],
                $input['option_b'],
                $input['option_b_ar'],
                $input['option_c'],
                $input['option_c_ar'],
                $input['option_d'],
                $input['option_d_ar'],
                $input['correct_answer'],
                $input['explanation'] ?? null,
                $input['explanation_ar'] ?? null,
                $input['difficulty'] ?? 'medium',
                $input['timer_seconds'] ?? null
            ]);

            if ($result) {
                $questionId = $this->db->lastInsertId();
                $this->logAdminAction($admin['id'], 'CREATE', 'question', $questionId, null, $input);
                Response::success(['id' => $questionId], 'Question created successfully');
            }
        } catch (Exception $e) {
            error_log("Question creation error: " . $e->getMessage());
            Response::error('Failed to create question', 500, null, 'CREATION_FAILED');
        }
    }

    public function update($id) {
        $admin = $this->authMiddleware->handle();
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$id) {
            Response::error('Question ID required', 400, null, 'ID_REQUIRED');
        }

        // Get current question
        $currentQuery = "SELECT * FROM questions WHERE id = ? AND is_active = 1";
        $currentStmt = $this->db->prepare($currentQuery);
        $currentStmt->execute([$id]);
        $currentData = $currentStmt->fetch();

        if (!$currentData) {
            Response::error('Question not found', 404, null, 'QUESTION_NOT_FOUND');
        }

        $validator = new Validator();
        $validator->validate([
            'question_text' => [['minLength', 10], ['maxLength', 1000]],
            'question_text_ar' => [['minLength', 10], ['maxLength', 1000]],
            'option_a' => [['maxLength', 500]],
            'option_a_ar' => [['maxLength', 500]],
            'option_b' => [['maxLength', 500]],
            'option_b_ar' => [['maxLength', 500]],
            'option_c' => [['maxLength', 500]],
            'option_c_ar' => [['maxLength', 500]],
            'option_d' => [['maxLength', 500]],
            'option_d_ar' => [['maxLength', 500]],
            'correct_answer' => [['inArray', ['a', 'b', 'c', 'd']]],
            'difficulty' => [['inArray', ['easy', 'medium', 'hard']]],
            'timer_seconds' => [['numeric', 10, 300]]
        ], $input);

        if ($validator->fails()) {
            Response::error('Validation failed', 400, $validator->getErrors(), 'VALIDATION_ERROR');
        }

        try {
            $query = "UPDATE questions SET 
                      question_text = COALESCE(?, question_text),
                      question_text_ar = COALESCE(?, question_text_ar),
                      option_a = COALESCE(?, option_a),
                      option_a_ar = COALESCE(?, option_a_ar),
                      option_b = COALESCE(?, option_b),
                      option_b_ar = COALESCE(?, option_b_ar),
                      option_c = COALESCE(?, option_c),
                      option_c_ar = COALESCE(?, option_c_ar),
                      option_d = COALESCE(?, option_d),
                      option_d_ar = COALESCE(?, option_d_ar),
                      correct_answer = COALESCE(?, correct_answer),
                      explanation = COALESCE(?, explanation),
                      explanation_ar = COALESCE(?, explanation_ar),
                      difficulty = COALESCE(?, difficulty),
                      timer_seconds = COALESCE(?, timer_seconds),
                      updated_at = NOW()
                      WHERE id = ?";

            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $input['question_text'] ?? null,
                $input['question_text_ar'] ?? null,
                $input['option_a'] ?? null,
                $input['option_a_ar'] ?? null,
                $input['option_b'] ?? null,
                $input['option_b_ar'] ?? null,
                $input['option_c'] ?? null,
                $input['option_c_ar'] ?? null,
                $input['option_d'] ?? null,
                $input['option_d_ar'] ?? null,
                $input['correct_answer'] ?? null,
                $input['explanation'] ?? null,
                $input['explanation_ar'] ?? null,
                $input['difficulty'] ?? null,
                $input['timer_seconds'] ?? null,
                $id
            ]);

            if ($result) {
                $this->logAdminAction($admin['id'], 'UPDATE', 'question', $id, $currentData, $input);
                Response::success(null, 'Question updated successfully');
            }
        } catch (Exception $e) {
            error_log("Question update error: " . $e->getMessage());
            Response::error('Failed to update question', 500, null, 'UPDATE_FAILED');
        }
    }

    public function destroy($id) {
        $admin = $this->authMiddleware->handle();

        if (!$id) {
            Response::error('Question ID required', 400, null, 'ID_REQUIRED');
        }

        $query = "UPDATE questions SET is_active = 0, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([$id]);

        if ($result && $stmt->rowCount() > 0) {
            $this->logAdminAction($admin['id'], 'DELETE', 'question', $id);
            Response::success(null, 'Question deleted successfully');
        } else {
            Response::error('Question not found', 404, null, 'QUESTION_NOT_FOUND');
        }
    }

    private function logAdminAction($adminId, $action, $targetType, $targetId, $oldData = null, $newData = null) {
        $query = "INSERT INTO admin_logs (admin_id, action, target_type, target_id, old_data, new_data, ip_address, user_agent) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $adminId,
            $action,
            $targetType,
            $targetId,
            $oldData ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
            $newData ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
}
