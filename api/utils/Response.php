<?php
class Response {
    public static function json($data, $status = 200) {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function success($data = null, $message = "Success", $meta = null) {
        $response = [
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ];
        
        if ($meta) {
            $response['meta'] = $meta;
        }
        
        self::json($response);
    }

    public static function error($message, $status = 400, $errors = null, $code = null) {
        $response = [
            'status' => 'error',
            'message' => $message,
            'timestamp' => date('c')
        ];
        
        if ($errors) {
            $response['errors'] = $errors;
        }
        
        if ($code) {
            $response['error_code'] = $code;
        }
        
        self::json($response, $status);
    }

    public static function paginated($data, $page, $limit, $total, $message = "Success") {
        $meta = [
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$limit,
                'total' => (int)$total,
                'total_pages' => ceil($total / $limit),
                'has_more' => ($page * $limit) < $total
            ]
        ];
        
        self::success($data, $message, $meta);
    }
}
?>