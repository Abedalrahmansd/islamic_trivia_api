<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Response.php';

class Auth {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    // Authenticate incoming request
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

    // Login and return JWT
    public function login($username, $password) {
        $query = "SELECT * FROM admin_users WHERE username = ? AND is_active = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $token = $this->createJWT($admin);

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
                'expires_at' => date('c', time() + SESSION_DURATION)
            ];
        }

        return false;
    }

    // Logout is now optional (JWT is stateless)
    public function logout($token) {
        // If you want to keep blacklist/revocation, implement DB deletion here
        return true;
    }

    private function getBearerToken() {
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : $_SERVER;
        if (isset($headers['Authorization'])) {
            return str_replace('Bearer ', '', $headers['Authorization']);
        }
        return null;
    }

    // Validate JWT
    private function validateToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;

        list($header64, $payload64, $signature64) = $parts;
        $header = json_decode(base64_decode(strtr($header64, '-_', '+/')), true);
        $payload = json_decode(base64_decode(strtr($payload64, '-_', '+/')), true);
        $signature = base64_decode(strtr($signature64, '-_', '+/'));

        if (!$header || !$payload || !$signature) return false;
        if ($payload['exp'] < time()) return false;

        $expectedSig = hash_hmac('sha256', "$header64.$payload64", JWT_SECRET, true);
        if (!hash_equals($expectedSig, $signature)) return false;

        // Optional: fetch admin from DB to ensure user is still active
        $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE id = ? AND is_active = 1");
        $stmt->execute([$payload['userId']]);
        return $stmt->fetch();
    }

    // Create JWT
    private function createJWT($admin) {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload = [
            'userId' => $admin['id'],
            'role' => $admin['role'],
            'exp' => time() + SESSION_DURATION
        ];

        $base64Header = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
        $base64Payload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', "$base64Header.$base64Payload", JWT_SECRET, true);
        $base64Signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return "$base64Header.$base64Payload.$base64Signature";
    }
}
?>
