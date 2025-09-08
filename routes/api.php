<?php
require_once __DIR__ . '/../controllers/CategoryController.php';
require_once __DIR__ . '/../controllers/ChallengePackController.php';
require_once __DIR__ . '/../controllers/QuestionController.php';
require_once __DIR__ . '/../controllers/GameController.php';
require_once __DIR__ . '/../controllers/AdminController.php';
require_once __DIR__ . '/../controllers/StatisticsController.php';

class ApiRouter {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function route($method, $endpoint) {
        switch ($endpoint[0] ?? '') {
            case 'categories':
                $this->handleCategories($method, $endpoint);
                break;
            case 'challenge-packs':
                $this->handleChallengePacks($method, $endpoint);
                break;  
            case 'questions':
                $this->handleQuestions($method, $endpoint);
                break;
            case 'games':
                $this->handleGames($method, $endpoint);
                break;
            case 'admin':
                $this->handleAdmin($method, $endpoint);
                break;
            case 'statistics':
                $this->handleStatistics($method, $endpoint);
                break;
            default:
                Response::error('Endpoint not found', 404, null, 'ENDPOINT_NOT_FOUND');
        }
    }

    private function handleCategories($method, $endpoint) {
        $controller = new CategoryController($this->db);
        
        switch ($method) {
            case 'GET':
                if (isset($endpoint[1])) {
                    $controller->show($endpoint[1]);
                } else {
                    $controller->index();
                }
                break;
            case 'POST':
                $controller->store();
                break;
            case 'PUT':
                $controller->update($endpoint[1] ?? null);
                break;
            case 'DELETE':
                $controller->destroy($endpoint[1] ?? null);
                break;
            default:
                Response::error('Method not allowed', 405, null, 'METHOD_NOT_ALLOWED');
        }
    }

    // Similar methods for other controllers...
    private function handleChallengePacks($method, $endpoint) {
    $controller = new ChallengePackController($this->db);
    
    switch ($method) {
        case 'GET':
            // Handle GET /challenge-packs/download/{id}
            if (isset($endpoint[1]) && $endpoint[1] === 'download' && isset($endpoint[2]) && is_numeric($endpoint[2])) {
                $controller->download($endpoint[2]);
            }
            // Handle GET /challenge-packs/{id}
            else if (isset($endpoint[1]) && is_numeric($endpoint[1])) {
                $controller->show($endpoint[1]);
            }
            // Handle GET /challenge-packs (list all)
            else {
                $controller->index();
            }
            break;
        case 'POST':
            $controller->store();
            break;
        case 'PUT':
            // Handle PUT /challenge-packs/{id}
            if (isset($endpoint[1]) && is_numeric($endpoint[1])) {
                $controller->update($endpoint[1]);
            } else {
                Response::error('Pack ID required', 400, null, 'ID_REQUIRED');
            }
            break;
        case 'DELETE':
            // Handle DELETE /challenge-packs/{id}
            if (isset($endpoint[1]) && is_numeric($endpoint[1])) {
                $controller->destroy($endpoint[1]);
            } else {
                Response::error('Pack ID required', 400, null, 'ID_REQUIRED');
            }
            break;
        default:
            Response::error('Method not allowed', 405, null, 'METHOD_NOT_ALLOWED');
    }
}

    private function handleQuestions($method, $endpoint) {
        $controller = new QuestionController($this->db);
    switch ($method) {
        case 'GET':
            // Check if sub-endpoint exists
            $sub = $endpoint[1] ?? null;

            if ($sub === 'random') {
                $controller->random(); // call random() method
            } elseif ($sub !== null && is_numeric($sub)) {
                $controller->show((int)$sub); // call show() with numeric ID
            } else {
                Response::error('Invalid question request', 400, null, 'INVALID_REQUEST');
            }
            break;

        case 'POST':
            $controller->store();
            break;

        case 'PUT':
            $id = $endpoint[1] ?? null;
            if ($id && is_numeric($id)) {
                $controller->update((int)$id);
            } else {
                Response::error('Invalid question ID', 400, null, 'INVALID_ID');
            }
            break;

        case 'DELETE':
            $id = $endpoint[1] ?? null;
            if ($id && is_numeric($id)) {
                $controller->destroy((int)$id);
            } else {
                Response::error('Invalid question ID', 400, null, 'INVALID_ID');
            }
            break;

        default:
            Response::error('Method not allowed', 405, null, 'METHOD_NOT_ALLOWED');
    }
}


    private function handleGames($method, $endpoint) {
    $controller = new GameController($this->db);
    
    switch ($method) {
        case 'GET':
            // Handle GET /games (list) and GET /games/{id} (show specific)
            if (isset($endpoint[1]) && is_numeric($endpoint[1])) {
                $controller->show($endpoint[1]);
            } else {
                $controller->show(); // Shows list of games
            }
            break;
        case 'POST':
            // Handle POST /games (create) and POST /games/save (save results)
            if (isset($endpoint[1]) && $endpoint[1] === 'save') {
                $controller->save();
            } else {
                $controller->create();
            }
            break;
        default:
            Response::error('Method not allowed', 405, null, 'METHOD_NOT_ALLOWED');
    }
}

    private function handleAdmin($method, $endpoint) {
    $controller = new AdminController($this->db);
    
    // Get the action from the endpoint
    $action = $endpoint[1] ?? '';
    
    switch ($method) {
        case 'POST':
            // Handle POST /admin/login
            if ($action === 'login') {
                $controller->login();
            }
            // Handle POST /admin/logout
            else if ($action === 'logout') {
                $controller->logout();
            }
            // Handle POST /admin/ai-generate
            else if ($action === 'ai-generate') {
                $controller->generateWithAI();
            }
            // Handle POST /admin/create (create new admin)
            else if ($action === 'create') {
                $controller->createAdmin();
            }
            else {
                Response::error('Endpoint not found', 404, null, 'ENDPOINT_NOT_FOUND');
            }
            break;
        case 'GET':
            // Handle GET /admin/profile
            if ($action === 'profile') {
                $controller->profile();
            }
            // Handle GET /admin/logs
            else if ($action === 'logs') {
                $controller->logs();
            }
            else {
                Response::error('Endpoint not found', 404, null, 'ENDPOINT_NOT_FOUND');
            }
            break;
        case 'PUT':
            // Handle PUT /admin/profile (update profile)
            if ($action === 'profile') {
                $controller->updateProfile();
            }
            else {
                Response::error('Endpoint not found', 404, null, 'ENDPOINT_NOT_FOUND');
            }
            break;
        default:
            Response::error('Method not allowed', 405, null, 'METHOD_NOT_ALLOWED');
    }
}

    private function handleStatistics($method, $endpoint) {
    $controller = new StatisticsController($this->db);
    
    // Get the specific statistics type from the endpoint
    $statsType = $endpoint[1] ?? '';
    
    switch ($method) {
        case 'GET':
            // Handle GET /statistics/dashboard
            if ($statsType === 'dashboard') {
                $controller->dashboard();
            }
            // Handle GET /statistics/categories
            else if ($statsType === 'categories') {
                $controller->categories();
            }
            // Handle GET /statistics/packs
            else if ($statsType === 'packs') {
                $controller->packs();
            }
            // Handle GET /statistics/questions
            else if ($statsType === 'questions') {
                $controller->questions();
            }
            // Handle GET /statistics/games
            else if ($statsType === 'games') {
                $controller->games();
            }
            // Handle GET /statistics/general
            else if ($statsType === 'general') {
                $controller->general();
            }
            else {
                Response::error('Statistics endpoint not found', 404, null, 'ENDPOINT_NOT_FOUND');
            }
            break;
        default:
            Response::error('Method not allowed', 405, null, 'METHOD_NOT_ALLOWED');
    }
}

    
}
?>