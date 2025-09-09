<?php
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class CategoryController {
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

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM categories WHERE is_active = 1";
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute();
        $total = $countStmt->fetch()['total'];

        // Get categories
        $query = "SELECT * FROM view_categories_with_count 
                  ORDER BY name 
                  LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$limit, $offset]);
        $categories = $stmt->fetchAll();

        Response::paginated($categories, $page, $limit, $total);
    }

    public function show($id) {
        $query = "SELECT * FROM view_categories_with_count WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $category = $stmt->fetch();

        if (!$category) {
            Response::error('Category not found', 404, null, 'CATEGORY_NOT_FOUND');
        }

        Response::success($category);
    }

    public function store() {
        $admin = $this->authMiddleware->handle();
        $input = json_decode(file_get_contents('php://input'), true);

        $validator = new Validator();
        $validator->validate([
            'name' => ['required', ['minLength', 2], ['maxLength', 255]],
            'name_ar' => ['required', ['minLength', 2], ['maxLength', 255]],
            'difficulty' => [['inArray', ['easy', 'medium', 'hard']]],
            'timer_seconds' => [['numeric', 'timer_seconds', 10]]
        ], $input);

        if ($validator->fails()) {
            Response::error('Validation failed', 400, $validator->getErrors(), 'VALIDATION_ERROR');
        }

        try {
            $query = "INSERT INTO categories (name, name_ar, description, description_ar, difficulty, timer_seconds) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $input['name'],
                $input['name_ar'],
                $input['description'] ?? null,
                $input['description_ar'] ?? null,
                $input['difficulty'] ?? 'medium',
                $input['timer_seconds'] ?? DEFAULT_TIMER
            ]);

            if ($result) {
                $categoryId = $this->db->lastInsertId();
                $this->logAdminAction($admin['id'], 'CREATE', 'category', $categoryId, null, $input);
                Response::success(['id' => $categoryId], 'Category created successfully');
            }
        } catch (Exception $e) {
            error_log("Category creation error: " . $e->getMessage());
            Response::error('Failed to create category', 500, null, 'CREATION_FAILED');
        }
    }

    public function update($id) {
        $admin = $this->authMiddleware->handle();
        $input = json_decode(file_get_contents('php://input'), true);

        // Get current category
        $currentQuery = "SELECT * FROM categories WHERE id = ? AND is_active = 1";
        $currentStmt = $this->db->prepare($currentQuery);
        $currentStmt->execute([$id]);
        $currentData = $currentStmt->fetch();

        if (!$currentData) {
            Response::error('Category not found', 404, null, 'CATEGORY_NOT_FOUND');
        }

        $validator = new Validator();
        $validator->validate([
            'name' => [['minLength', 2], ['maxLength', 255]],
            'name_ar' => [['minLength', 2], ['maxLength', 255]],
            'difficulty' => [['inArray', ['easy', 'medium', 'hard']]],
            'timer_seconds' => [['numeric', 'timer_seconds', 10]]
        ], $input);

        if ($validator->fails()) {
            Response::error('Validation failed', 400, $validator->getErrors(), 'VALIDATION_ERROR');
        }

        try {
            $query = "UPDATE categories SET 
                      name = COALESCE(?, name), 
                      name_ar = COALESCE(?, name_ar), 
                      description = COALESCE(?, description), 
                      description_ar = COALESCE(?, description_ar), 
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
                $input['difficulty'] ?? null,
                $input['timer_seconds'] ?? null,
                $id
            ]);

            if ($result) {
                $this->logAdminAction($admin['id'], 'UPDATE', 'category', $id, $currentData, $input);
                Response::success(null, 'Category updated successfully');
            }
        } catch (Exception $e) {
            error_log("Category update error: " . $e->getMessage());
            Response::error('Failed to update category', 500, null, 'UPDATE_FAILED');
        }
    }

    public function destroy($id) {
        $admin = $this->authMiddleware->handle();

        $query = "UPDATE categories SET is_active = 0, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([$id]);

        if ($result && $stmt->rowCount() > 0) {
            $this->logAdminAction($admin['id'], 'DELETE', 'category', $id);
            Response::success(null, 'Category deleted successfully');
        } else {
            Response::error('Category not found', 404, null, 'CATEGORY_NOT_FOUND');
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
