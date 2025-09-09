<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Response.php';

class Auth {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function authenticate() {
        $token = $this->getBearerToken();
        
        if (!$token) {
            Response::error('Authentication token required', 401, null, 'TOKEN_MISSING');
        }

        $admin = $this->validateToken($token);
        
        if (!$admin) {
            Response::error('Invalid or expired token', 401, null, 'TOKEN_INVALID');
        }

        return $admin;
    }

    public function login($username, $password) {
        $query = "SELECT * FROM admin_users WHERE username = ? AND is_active = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && (password_verify($password, $admin['password_hash']))) {
            $token = $this->createSession($admin['id']);
            
            // Update last login
            $updateQuery = "UPDATE admin_users SET last_login = NOW() WHERE id = ?";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->execute([$admin['id']]);

            return [
                'admin' => [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'email' => $admin['email'],
                    'full_name' => $admin['full_name'],
                    'role' => $admin['role']
                ],
                'token' => $token,
                'expires_at' => date('c', strtotime('+' . SESSION_DURATION . ' seconds'))
            ];
        }

        return false;
    }

    public function logout($token) {
        $query = "DELETE FROM admin_sessions WHERE session_token = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$token]);
    }

    private function getBearerToken() {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            return str_replace('Bearer ', '', $headers['Authorization']);
        }
        return null;
    }

    private function validateToken($token) {
        $query = "SELECT au.*, s.expires_at 
                  FROM admin_users au 
                  JOIN admin_sessions s ON au.id = s.admin_id 
                  WHERE s.session_token = ? AND s.expires_at > NOW() AND au.is_active = 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    private function createSession($adminId) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + SESSION_DURATION);
        
        $query = "INSERT INTO admin_sessions (admin_id, session_token, expires_at) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$adminId, $token, $expires]);
        
        return $token;
    }
}
?>