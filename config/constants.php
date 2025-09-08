<?php
// API Configuration Constants
define('API_VERSION', '1.0.0');
define('JWT_SECRET', getenv('JWT_SECRET') ?? 'your-secret-key-change-this');
define('SESSION_DURATION', 24 * 60 * 60); // 24 hours
define('MAX_QUESTIONS_PER_REQUEST', 50);
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Scoring Constants
define('EASY_POINTS', 10);
define('MEDIUM_POINTS', 20);
define('HARD_POINTS', 30);
define('DEFAULT_TIMER', 30);

// File Upload Constants
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Error Messages
define('ERROR_MESSAGES', [
    'INVALID_REQUEST' => 'Invalid request format',
    'MISSING_REQUIRED_FIELDS' => 'Missing required fields',
    'UNAUTHORIZED' => 'Authentication required',
    'FORBIDDEN' => 'Access denied',
    'NOT_FOUND' => 'Resource not found',
    'VALIDATION_ERROR' => 'Validation error',
    'DATABASE_ERROR' => 'Database operation failed',
    'INTERNAL_ERROR' => 'Internal server error'
]);
?>