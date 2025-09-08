<?php





// ============================================================================
// /controllers/AdminController.php
// ============================================================================

class AdminController {
    private $db;
    private $authMiddleware;

    public function __construct($database) {
        $this->db = $database;
        $this->authMiddleware = new AuthMiddleware($database);
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', 405, null, 'METHOD_NOT_ALLOWED');
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $validator = new Validator();
        $validator->validate([
            'username' => ['required', ['minLength', 3]],
            'password' => ['required', ['minLength', 6]]
        ], $input);

        if ($validator->fails()) {
            Response::error('Validation failed', 400, $validator->getErrors(), 'VALIDATION_ERROR');
        }

        $auth = new Auth($this->db);
        $result = $auth->login($input['username'], $input['password']);

        if ($result) {
            Response::success($result, 'Login successful');
        } else {
            // Log failed login attempt
            $this->logFailedLogin($input['username']);
            Response::error('Invalid credentials', 401, null, 'INVALID_CREDENTIALS');
        }
    }

    public function logout() {
        $admin = $this->authMiddleware->handle();
        
        $headers = apache_request_headers();
        $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

        $auth = new Auth($this->db);
        $result = $auth->logout($token);

        if ($result) {
            Response::success(null, 'Logged out successfully');
        } else {
            Response::error('Logout failed', 500, null, 'LOGOUT_FAILED');
        }
    }

    public function profile() {
        $admin = $this->authMiddleware->handle();

        $profile = [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'email' => $admin['email'],
            'full_name' => $admin['full_name'],
            'role' => $admin['role'],
            'last_login' => $admin['last_login'],
            'created_at' => $admin['created_at']
        ];

        Response::success($profile, 'Profile retrieved successfully');
    }

    public function updateProfile() {
        $admin = $this->authMiddleware->handle();
        $input = json_decode(file_get_contents('php://input'), true);

        $validator = new Validator();
        $validator->validate([
            'email' => ['email'],
            'full_name' => [['maxLength', 255]],
            'current_password' => [['minLength', 6]],
            'new_password' => [['minLength', 6]]
        ], $input);

        if ($validator->fails()) {
            Response::error('Validation failed', 400, $validator->getErrors(), 'VALIDATION_ERROR');
        }

        // If changing password, verify current password
        if (isset($input['new_password'])) {
            if (!isset($input['current_password'])) {
                Response::error('Current password required to change password', 400, null, 'CURRENT_PASSWORD_REQUIRED');
            }

            $checkQuery = "SELECT password_hash FROM admin_users WHERE id = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$admin['id']]);
            $currentHash = $checkStmt->fetch()['password_hash'];

            if (!password_verify($input['current_password'], $currentHash)) {
                Response::error('Current password is incorrect', 400, null, 'INVALID_CURRENT_PASSWORD');
            }
        }

        try {
            $updates = [];
            $params = [];

            if (isset($input['email'])) {
                $updates[] = "email = ?";
                $params[] = $input['email'];
            }

            if (isset($input['full_name'])) {
                $updates[] = "full_name = ?";
                $params[] = $input['full_name'];
            }

            if (isset($input['new_password'])) {
                $updates[] = "password_hash = ?";
                $params[] = password_hash($input['new_password'], PASSWORD_DEFAULT);
            }

            if (!empty($updates)) {
                $updates[] = "updated_at = NOW()";
                $params[] = $admin['id'];

                $query = "UPDATE admin_users SET " . implode(', ', $updates) . " WHERE id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute($params);

                Response::success(null, 'Profile updated successfully');
            } else {
                Response::success(null, 'No changes made');
            }
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            Response::error('Failed to update profile', 500, null, 'UPDATE_FAILED');
        }
    }

    public function logs() {
        $admin = $this->authMiddleware->handle();
        
        $page = $_GET['page'] ?? 1;
        $limit = min($_GET['limit'] ?? DEFAULT_PAGE_SIZE, MAX_PAGE_SIZE);
        $offset = ($page - 1) * $limit;
        $action = $_GET['action'] ?? null;
        $target_type = $_GET['target_type'] ?? null;
        $admin_id = $_GET['admin_id'] ?? null;

        $where = "WHERE 1=1";
        $params = [];

        if ($action) {
            $where .= " AND al.action = ?";
            $params[] = $action;
        }

        if ($target_type) {
            $where .= " AND al.target_type = ?";
            $params[] = $target_type;
        }

        if ($admin_id) {
            $where .= " AND al.admin_id = ?";
            $params[] = $admin_id;
        }

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM admin_logs al $where";
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // Get logs
        $params[] = $limit;
        $params[] = $offset;

        $query = "SELECT al.*, au.username as admin_username, au.full_name as admin_full_name
                  FROM admin_logs al 
                  LEFT JOIN admin_users au ON al.admin_id = au.id 
                  $where 
                  ORDER BY al.created_at DESC 
                  LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        Response::paginated($logs, $page, $limit, $total, 'Admin logs retrieved successfully');
    }

    public function generateWithAI() {
        $admin = $this->authMiddleware->handle();
        $this->authMiddleware->checkRole($admin, ['super_admin', 'admin']);

        $input = json_decode(file_get_contents('php://input'), true);

        $validator = new Validator();
        $validator->validate([
            'type' => ['required', ['inArray', ['question', 'category']]],
            'prompt' => ['required', ['minLength', 10], ['maxLength', 2000]],
            'ai_model' => [['maxLength', 100]]
        ], $input);

        if ($validator->fails()) {
            Response::error('Validation failed', 400, $validator->getErrors(), 'VALIDATION_ERROR');
        }

        try {
            // This is a placeholder for AI integration
            // You would integrate with OpenAI, Claude, or other AI services here
            
            $aiResponse = [
                'generated_content' => [
                    'type' => $input['type'],
                    'prompt_used' => $input['prompt'],
                    'suggestion' => 'AI integration placeholder - implement with your preferred AI service',
                    'model_used' => $input['ai_model'] ?? 'placeholder',
                    'cost_estimate' => 0.001,
                    'tokens_used' => 150
                ]
            ];

            // Log AI usage
            $logQuery = "INSERT INTO ai_generated_content (content_type, content_id, ai_model, prompt_used, generation_cost, admin_id) 
                         VALUES (?, ?, ?, ?, ?, ?)";
            $logStmt = $this->db->prepare($logQuery);
            $logStmt->execute([
                $input['type'],
                0, // Will be updated when content is actually created
                $aiResponse['generated_content']['model_used'],
                $input['prompt'],
                $aiResponse['generated_content']['cost_estimate'],
                $admin['id']
            ]);

            Response::success($aiResponse, 'AI content generated successfully');

        } catch (Exception $e) {
            error_log("AI generation error: " . $e->getMessage());
            Response::error('Failed to generate AI content', 500, null, 'AI_GENERATION_FAILED');
        }
    }

    public function createAdmin() {
        $admin = $this->authMiddleware->handle();
        $this->authMiddleware->checkRole($admin, ['super_admin']);

        $input = json_decode(file_get_contents('php://input'), true);

        $validator = new Validator();
        $validator->validate([
            'username' => ['required', ['minLength', 3], ['maxLength', 100]],
            'email' => ['required', 'email', ['maxLength', 255]],
            'password' => ['required', ['minLength', 8]],
            'full_name' => ['required', ['maxLength', 255]],
            'role' => ['required', ['inArray', ['admin', 'moderator']]]
        ], $input);

        if ($validator->fails()) {
            Response::error('Validation failed', 400, $validator->getErrors(), 'VALIDATION_ERROR');
        }

        // Check if username or email already exists
        $checkQuery = "SELECT id FROM admin_users WHERE username = ? OR email = ?";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([$input['username'], $input['email']]);
        
        if ($checkStmt->fetch()) {
            Response::error('Username or email already exists', 400, null, 'DUPLICATE_ADMIN');
        }

        try {
            $query = "INSERT INTO admin_users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $input['username'],
                $input['email'],
                password_hash($input['password'], PASSWORD_DEFAULT),
                $input['full_name'],
                $input['role']
            ]);

            if ($result) {
                $adminId = $this->db->lastInsertId();
                $this->logAdminAction($admin['id'], 'CREATE', 'admin', $adminId, null, $input);
                Response::success(['id' => $adminId], 'Admin created successfully');
            }
        } catch (Exception $e) {
            error_log("Admin creation error: " . $e->getMessage());
            Response::error('Failed to create admin', 500, null, 'CREATION_FAILED');
        }
    }

    private function logFailedLogin($username) {
        $query = "INSERT INTO admin_logs (admin_id, action, target_type, target_id, new_data, ip_address, user_agent) 
                  VALUES (NULL, 'FAILED_LOGIN', 'admin', NULL, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            json_encode(['username' => $username], JSON_UNESCAPED_UNICODE),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }

    private function logAdminAction($adminId, $action, $targetType, $targetId, $oldData = null, $newData = null) {
        // Remove sensitive data from logging
        if ($newData && isset($newData['password'])) {
            $newData['password'] = '[HIDDEN]';
        }

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
