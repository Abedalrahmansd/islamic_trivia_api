<?php
// ============================================================================
// /controllers/ChallengePackController.php
// ============================================================================

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class ChallengePackController {
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
        $theme = $_GET['theme'] ?? null;

        $where = "WHERE is_active = 1";
        $params = [];

        if ($theme) {
            $where .= " AND theme = ?";
            $params[] = $theme;
        }

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM challenge_packs $where";
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // Get packs
        $params[] = $limit;
        $params[] = $offset;
        
        $query = "SELECT * FROM view_packs_with_count 
                  $where
                  ORDER BY name 
                  LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $packs = $stmt->fetchAll();

        Response::paginated($packs, $page, $limit, $total);
    }

    public function show($id) {
        $query = "SELECT * FROM view_packs_with_count WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $pack = $stmt->fetch();

        if (!$pack) {
            Response::error('Challenge pack not found', 404, null, 'PACK_NOT_FOUND');
        }

        Response::success($pack);
    }

    public function download($id) {
        // Update download count
        $updateQuery = "UPDATE challenge_packs SET download_count = download_count + 1 WHERE id = ? AND is_active = 1";
        $updateStmt = $this->db->prepare($updateQuery);
        $updateStmt->execute([$id]);

        if ($updateStmt->rowCount() === 0) {
            Response::error('Challenge pack not found', 404, null, 'PACK_NOT_FOUND');
        }

        // Get pack with all questions
        $packQuery = "SELECT * FROM challenge_packs WHERE id = ? AND is_active = 1";
        $packStmt = $this->db->prepare($packQuery);
        $packStmt->execute([$id]);
        $pack = $packStmt->fetch();

        $questionsQuery = "SELECT * FROM questions WHERE challenge_pack_id = ? AND is_active = 1 ORDER BY RAND()";
        $questionsStmt = $this->db->prepare($questionsQuery);
        $questionsStmt->execute([$id]);
        $questions = $questionsStmt->fetchAll();

        $packData = [
            'pack_info' => $pack,
            'questions' => $questions,
            'download_timestamp' => date('c'),
            'total_questions' => count($questions)
        ];

        Response::success($packData, 'Challenge pack downloaded successfully');
    }

    public function store() {
        $admin = $this->authMiddleware->handle();
        $input = json_decode(file_get_contents('php://input'), true);

        $validator = new Validator();
        $validator->validate([
            'name' => ['required', ['minLength', 2], ['maxLength', 255]],
            'name_ar' => ['required', ['minLength', 2], ['maxLength', 255]],
            'theme' => [['maxLength', 100]],
            'difficulty' => [['inArray', ['easy', 'medium', 'hard']]],
            'timer_seconds' => [['numeric', 10, 300]]
        ], $input);

        if ($validator->fails()) {
            Response::error('Validation failed', 400, $validator->getErrors(), 'VALIDATION_ERROR');
        }

        try {
            $query = "INSERT INTO challenge_packs (name, name_ar, description, description_ar, theme, difficulty, timer_seconds) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $input['name'],
                $input['name_ar'],
                $input['description'] ?? null,
                $input['description_ar'] ?? null,
                $input['theme'] ?? null,
                $input['difficulty'] ?? 'medium',
                $input['timer_seconds'] ?? DEFAULT_TIMER
            ]);

            if ($result) {
                $packId = $this->db->lastInsertId();
                $this->logAdminAction($admin['id'], 'CREATE', 'challenge_pack', $packId, null, $input);
                Response::success(['id' => $packId], 'Challenge pack created successfully');
            }
        } catch (Exception $e) {
            error_log("Challenge pack creation error: " . $e->getMessage());
            Response::error('Failed to create challenge pack', 500, null, 'CREATION_FAILED');
        }
    }

    public function update($id) {
        $admin = $this->authMiddleware->handle();
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$id) {
            Response::error('Pack ID required', 400, null, 'ID_REQUIRED');
        }

        // Get current pack
        $currentQuery = "SELECT * FROM challenge_packs WHERE id = ? AND is_active = 1";
        $currentStmt = $this->db->prepare($currentQuery);
        $currentStmt->execute([$id]);
        $currentData = $currentStmt->fetch();

        if (!$currentData) {
            Response::error('Challenge pack not found', 404, null, 'PACK_NOT_FOUND');
        }

        $validator = new Validator();
        $validator->validate([
            'name' => [['minLength', 2], ['maxLength', 255]],
            'name_ar' => [['minLength', 2], ['maxLength', 255]],
            'theme' => [['maxLength', 100]],
            'difficulty' => [['inArray', ['easy', 'medium', 'hard']]],
            'timer_seconds' => [['numeric', 10, 300]]
        ], $input);

        if ($validator->fails()) {
            Response::error('Validation failed', 400, $validator->getErrors(), 'VALIDATION_ERROR');
        }

        try {
            $query = "UPDATE challenge_packs SET 
                      name = COALESCE(?, name), 
                      name_ar = COALESCE(?, name_ar), 
                      description = COALESCE(?, description), 
                      description_ar = COALESCE(?, description_ar), 
                      theme = COALESCE(?, theme), 
                      difficulty = COALESCE(?, difficulty), 
                      timer_seconds = COALESCE(?, timer_seconds), 
                      updated_at = NOW() 
                      WHERE id = ?";

            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $input['name'] ?? null,
                $input['name_ar'] ?? null,
                $input['description'] ?? null,
                $input['description_ar'] ?? null,
                $input['theme'] ?? null,
                $input['difficulty'] ?? null,
                $input['timer_seconds'] ?? null,
                $id
            ]);

            if ($result) {
                $this->logAdminAction($admin['id'], 'UPDATE', 'challenge_pack', $id, $currentData, $input);
                Response::success(null, 'Challenge pack updated successfully');
            }
        } catch (Exception $e) {
            error_log("Challenge pack update error: " . $e->getMessage());
            Response::error('Failed to update challenge pack', 500, null, 'UPDATE_FAILED');
        }
    }

    public function destroy($id) {
        $admin = $this->authMiddleware->handle();

        if (!$id) {
            Response::error('Pack ID required', 400, null, 'ID_REQUIRED');
        }

        $query = "UPDATE challenge_packs SET is_active = 0, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([$id]);

        if ($result && $stmt->rowCount() > 0) {
            $this->logAdminAction($admin['id'], 'DELETE', 'challenge_pack', $id);
            Response::success(null, 'Challenge pack deleted successfully');
        } else {
            Response::error('Challenge pack not found', 404, null, 'PACK_NOT_FOUND');
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