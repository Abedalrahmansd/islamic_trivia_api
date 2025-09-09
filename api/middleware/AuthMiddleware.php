<?php
require_once __DIR__ . '/../utils/Auth.php';

class AuthMiddleware {
    private $auth;

    public function __construct($database) {
        $this->auth = new Auth($database);
    }

    public function handle() {
        return $this->auth->authenticate();
    }

    public function checkRole($admin, $allowedRoles) {
        if (!in_array($admin['role'], $allowedRoles)) {
            Response::error('Insufficient permissions', 403, null, 'INSUFFICIENT_PERMISSIONS');
        }
    }
}
?>