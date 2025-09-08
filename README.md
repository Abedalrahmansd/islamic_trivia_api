# Islamic Trivia Game API - Brief

A complete REST API for the Islamic Trivia Game supporting both web and mobile platforms.

## Features

- Complete CRUD operations for categories, challenge packs, and questions
- Admin authentication and authorization
- Bilingual support (English/Arabic)
- Game session management
- Statistics and analytics
- Comprehensive logging
- Security best practices

## Installation

1. Upload all files to your web server
2. Create MySQL database and import `database.sql`
3. Update database credentials in `config/database.php`
4. Set proper file permissions (755 for directories, 644 for files)
5. Configure your web server to point to this directory

## SECURITY CHECKLIST:

1. Change database credentials
2. Use HTTPS in production
3. Implement rate limiting
4. Set up proper error logging
5. Configure firewall rules
6. Regular security updates
7. Strong admin passwords
8. Database backups
9. Monitor access logs
10. Input validation and sanitization

## Environment Variables

Create a `.env` file or set these variables:
DB_HOST=localhost
DB_NAME=islamic_trivia_game
DB_USER=your_username
DB_PASS=your_password
JWT_SECRET=your-secret-key

## API Endpoints

### Public Endpoints
- `GET /categories` - List all categories
- `GET /categories/{id}` - Get specific category
- `GET /challenge-packs` - List all challenge packs  
- `GET /challenge-packs/{id}` - Get specific pack
- `GET /challenge-packs/download/{id}` - Download pack with questions
- `GET /questions/random?category_id={id}&limit={n}` - Get random questions
- `POST /games` - Create new game session
- `POST /games/save` - Save game results
- `GET /games/{id}` - Get game results

### Admin Endpoints (Authentication Required)
- `POST /admin/login` - Admin login
- `POST /admin/logout` - Admin logout
- `GET /admin/profile` - Get admin profile
- `GET /admin/logs` - Get admin action logs
- `POST /admin/ai-generate` - Generate content with AI
- `POST /categories` - Create category
- `PUT /categories/{id}` - Update category
- `DELETE /categories/{id}` - Delete category
- `POST /challenge-packs` - Create challenge pack
- `PUT /challenge-packs/{id}` - Update challenge pack
- `DELETE /challenge-packs/{id}` - Delete challenge pack
- `POST /questions` - Create question
- `PUT /questions/{id}` - Update question
- `DELETE /questions/{id}` - Delete question
- `GET /statistics/dashboard` - Dashboard statistics
- `GET /statistics/categories` - Category statistics
- `GET /statistics/packs` - Pack statistics
- `GET /statistics/questions` - Question statistics


## Security

- All admin endpoints require authentication
- SQL injection protection with prepared statements
- Input validation on all endpoints
- CORS configuration
- Rate limiting ready
- Comprehensive error handling

# Quiz Game API - Comprehensive Documentation with Output Examples

## Table of Contents
1. [Overview](#overview)
2. [Base URL & Authentication](#base-url--authentication)
3. [Categories Endpoints](#categories-endpoints)
4. [Challenge Packs Endpoints](#challenge-packs-endpoints)
5. [Questions Endpoints](#questions-endpoints)
6. [Games Endpoints](#games-endpoints)
7. [Admin Endpoints](#admin-endpoints)
8. [Statistics Endpoints](#statistics-endpoints)
9. [Error Handling](#error-handling)
10. [Response Format](#response-format)
11. [Complete Examples](#complete-examples)

## Overview

This API powers a quiz game application with support for multiple categories, challenge packs, game sessions, and comprehensive statistics. The API supports both public endpoints for gameplay and admin endpoints for content management.

## Base URL & Authentication

**Base URL**: `https://yourdomain.com/api/`

**Authentication**: Most endpoints require a JWT token in the Authorization header:
```
Authorization: Bearer {your_jwt_token}
```

## Categories Endpoints

### List Categories
- **URL**: `GET /categories`
- **Description**: Retrieves paginated list of categories
- **Query Parameters**:
  - `page` (optional): Page number (default: 1)
  - `limit` (optional): Items per page (default: 10)
- **Example**:
```bash
curl -X GET "https://yourdomain.com/api/categories?page=1&limit=10"
```
- **Output**:
```json
{
  "success": true,
  "message": "Categories retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "name": "History",
        "name_ar": "التاريخ",
        "description": "History questions",
        "description_ar": "أسئلة تاريخية",
        "difficulty": "medium",
        "timer_seconds": 30,
        "question_count": 15,
        "is_active": 1,
        "created_at": "2024-01-15 10:30:00",
        "updated_at": "2024-01-15 10:30:00"
      },
      {
        "id": 2,
        "name": "Science",
        "name_ar": "العلوم",
        "description": "Science questions",
        "description_ar": "أسئلة علمية",
        "difficulty": "hard",
        "timer_seconds": 45,
        "question_count": 20,
        "is_active": 1,
        "created_at": "2024-01-15 11:30:00",
        "updated_at": "2024-01-15 11:30:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 10,
      "total": 25,
      "total_pages": 3
    }
  }
}
```

### Get Category
- **URL**: `GET /categories/{id}`
- **Description**: Retrieves a specific category by ID
- **Example**:
```bash
curl -X GET "https://yourdomain.com/api/categories/1"
```
- **Output**:
```json
{
  "success": true,
  "message": "Category retrieved successfully",
  "data": {
    "id": 1,
    "name": "History",
    "name_ar": "التاريخ",
    "description": "History questions",
    "description_ar": "أسئلة تاريخية",
    "difficulty": "medium",
    "timer_seconds": 30,
    "question_count": 15,
    "is_active": 1,
    "created_at": "2024-01-15 10:30:00",
    "updated_at": "2024-01-15 10:30:00"
  }
}
```

### Create Category (Admin)
- **URL**: `POST /categories`
- **Authentication**: Required (Admin)
- **Request Body**:
```json
{
  "name": "Geography",
  "name_ar": "الجغرافيا",
  "description": "Geography questions",
  "description_ar": "أسئلة الجغرافيا",
  "difficulty": "medium",
  "timer_seconds": 30
}
```
- **Example**:
```bash
curl -X POST "https://yourdomain.com/api/categories" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Geography",
    "name_ar": "الجغرافيا",
    "description": "Geography questions",
    "description_ar": "أسئلة الجغرافيا",
    "difficulty": "medium",
    "timer_seconds": 30
  }'
```
- **Output**:
```json
{
  "success": true,
  "message": "Category created successfully",
  "data": {
    "id": 3
  }
}
```

### Update Category (Admin)
- **URL**: `PUT /categories/{id}`
- **Authentication**: Required (Admin)
- **Request Body**:
```json
{
  "name": "World History",
  "difficulty": "hard"
}
```
- **Example**:
```bash
curl -X PUT "https://yourdomain.com/api/categories/1" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "World History",
    "difficulty": "hard"
  }'
```
- **Output**:
```json
{
  "success": true,
  "message": "Category updated successfully",
  "data": null
}
```

### Delete Category (Admin)
- **URL**: `DELETE /categories/{id}`
- **Authentication**: Required (Admin)
- **Example**:
```bash
curl -X DELETE "https://yourdomain.com/api/categories/1" \
  -H "Authorization: Bearer {token}"
```
- **Output**:
```json
{
  "success": true,
  "message": "Category deleted successfully",
  "data": null
}
```

## Challenge Packs Endpoints

### List Challenge Packs
- **URL**: `GET /challenge-packs`
- **Description**: Retrieves paginated list of challenge packs
- **Query Parameters**:
  - `page` (optional): Page number (default: 1)
  - `limit` (optional): Items per page (default: 10)
  - `theme` (optional): Filter by theme
- **Example**:
```bash
curl -X GET "https://yourdomain.com/api/challenge-packs?page=1&limit=10&theme=science"
```
- **Output**:
```json
{
  "success": true,
  "message": "Challenge packs retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "name": "Science Pack",
        "name_ar": "حزمة العلوم",
        "description": "Science questions pack",
        "description_ar": "حزمة أسئلة العلوم",
        "theme": "science",
        "difficulty": "medium",
        "timer_seconds": 30,
        "download_count": 150,
        "question_count": 25,
        "is_active": 1,
        "created_at": "2024-01-15 10:30:00",
        "updated_at": "2024-01-15 10:30:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 10,
      "total": 15,
      "total_pages": 2
    }
  }
}
```

### Get Challenge Pack
- **URL**: `GET /challenge-packs/{id}`
- **Description**: Retrieves details of a specific challenge pack
- **Example**:
```bash
curl -X GET "https://yourdomain.com/api/challenge-packs/1"
```
- **Output**:
```json
{
  "success": true,
  "message": "Challenge pack retrieved successfully",
  "data": {
    "id": 1,
    "name": "Science Pack",
    "name_ar": "حزمة العلوم",
    "description": "Science questions pack",
    "description_ar": "حزمة أسئلة العلوم",
    "theme": "science",
    "difficulty": "medium",
    "timer_seconds": 30,
    "download_count": 150,
    "question_count": 25,
    "is_active": 1,
    "created_at": "2024-01-15 10:30:00",
    "updated_at": "2024-01-15 10:30:00"
  }
}
```

### Download Challenge Pack
- **URL**: `GET /challenge-packs/download/{id}`
- **Description**: Downloads a challenge pack with all its questions
- **Example**:
```bash
curl -X GET "https://yourdomain.com/api/challenge-packs/download/1"
```
- **Output**:
```json
{
  "success": true,
  "message": "Challenge pack downloaded successfully",
  "data": {
    "pack_info": {
      "id": 1,
      "name": "Science Pack",
      "name_ar": "حزمة العلوم",
      "description": "Science questions pack",
      "description_ar": "حزمة أسئلة العلوم",
      "theme": "science",
      "difficulty": "medium",
      "timer_seconds": 30,
      "download_count": 151,
      "is_active": 1,
      "created_at": "2024-01-15 10:30:00",
      "updated_at": "2024-01-15 10:30:00"
    },
    "questions": [
      {
        "id": 1,
        "question_text": "What is the chemical symbol for water?",
        "question_text_ar": "ما هو الرمز الكيميائي للماء؟",
        "option_a": "H2O",
        "option_a_ar": "H2O",
        "option_b": "CO2",
        "option_b_ar": "CO2",
        "option_c": "O2",
        "option_c_ar": "O2",
        "option_d": "NaCl",
        "option_d_ar": "NaCl",
        "correct_answer": "a",
        "explanation": "H2O is the chemical formula for water.",
        "explanation_ar": "H2O هو الصيغة الكيميائية للماء.",
        "difficulty": "easy",
        "timer_seconds": 30,
        "category_id": null,
        "challenge_pack_id": 1,
        "is_active": 1,
        "created_at": "2024-01-15 10:30:00",
        "updated_at": "2024-01-15 10:30:00"
      }
    ],
    "download_timestamp": "2024-01-20T14:30:45+00:00",
    "total_questions": 25
  }
}
```

### Create Challenge Pack (Admin)
- **URL**: `POST /challenge-packs`
- **Authentication**: Required (Admin)
- **Request Body**:
```json
{
  "name": "Math Pack",
  "name_ar": "حزمة الرياضيات",
  "description": "Mathematics questions",
  "description_ar": "أسئلة الرياضيات",
  "theme": "math",
  "difficulty": "hard",
  "timer_seconds": 45
}
```
- **Example**:
```bash
curl -X POST "https://yourdomain.com/api/challenge-packs" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Math Pack",
    "name_ar": "حزمة الرياضيات",
    "description": "Mathematics questions",
    "description_ar": "أسئلة الرياضيات",
    "theme": "math",
    "difficulty": "hard",
    "timer_seconds": 45
  }'
```
- **Output**:
```json
{
  "success": true,
  "message": "Challenge pack created successfully",
  "data": {
    "id": 2
  }
}
```

## Questions Endpoints

### List Questions
- **URL**: `GET /questions`
- **Description**: Retrieves paginated list of questions
- **Query Parameters**:
  - `page` (optional): Page number (default: 1)
  - `limit` (optional): Items per page (default: 10)
  - `category_id` (optional): Filter by category ID
  - `pack_id` (optional): Filter by challenge pack ID
  - `difficulty` (optional): Filter by difficulty
- **Example**:
```bash
curl -X GET "https://yourdomain.com/api/questions?category_id=1&difficulty=medium&limit=5"
```
- **Output**:
```json
{
  "success": true,
  "message": "Questions retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "question_text": "What is the capital of France?",
        "question_text_ar": "ما هي عاصمة فرنسا؟",
        "option_a": "London",
        "option_a_ar": "لندن",
        "option_b": "Paris",
        "option_b_ar": "باريس",
        "option_c": "Berlin",
        "option_c_ar": "برلين",
        "option_d": "Madrid",
        "option_d_ar": "مدريد",
        "correct_answer": "b",
        "explanation": "Paris is the capital of France.",
        "explanation_ar": "باريس هي عاصمة فرنسا.",
        "difficulty": "easy",
        "timer_seconds": 30,
        "category_id": 1,
        "challenge_pack_id": null,
        "category_name": "Geography",
        "category_name_ar": "الجغرافيا",
        "pack_name": null,
        "pack_name_ar": null,
        "is_active": 1,
        "created_at": "2024-01-15 10:30:00",
        "updated_at": "2024-01-15 10:30:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 5,
      "total": 15,
      "total_pages": 3
    }
  }
}
```

### Get Question
- **URL**: `GET /questions/{id}`
- **Description**: Retrieves a specific question by ID
- **Example**:
```bash
curl -X GET "https://yourdomain.com/api/questions/1"
```
- **Output**:
```json
{
  "success": true,
  "message": "Question retrieved successfully",
  "data": {
    "id": 1,
    "question_text": "What is the capital of France?",
    "question_text_ar": "ما هي عاصمة فرنسا؟",
    "option_a": "London",
    "option_a_ar": "لندن",
    "option_b": "Paris",
    "option_b_ar": "باريس",
    "option_c": "Berlin",
    "option_c_ar": "برلين",
    "option_d": "Madrid",
    "option_d_ar": "مدريد",
    "correct_answer": "b",
    "explanation": "Paris is the capital of France.",
    "explanation_ar": "باريس هي عاصمة فرنسا.",
    "difficulty": "easy",
    "timer_seconds": 30,
    "category_id": 1,
    "challenge_pack_id": null,
    "category_name": "Geography",
    "category_name_ar": "الجغرافيا",
    "pack_name": null,
    "pack_name_ar": null,
    "is_active": 1,
    "created_at": "2024-01-15 10:30:00",
    "updated_at": "2024-01-15 10:30:00"
  }
}
```

### Get Random Questions
- **URL**: `GET /questions/random`
- **Description**: Retrieves random questions for gameplay
- **Query Parameters**:
  - `category_id` (optional): Category ID to get questions from
  - `pack_id` (optional): Challenge pack ID to get questions from
  - `limit` (optional): Number of questions (default: 10)
  - `difficulty` (optional): Filter by difficulty
- **Example**:
```bash
curl -X GET "https://yourdomain.com/api/questions/random?category_id=1&limit=10"
```
- **Output**:
```json
{
  "success": true,
  "message": "Random questions retrieved successfully",
  "data": {
    "questions": [
      {
        "id": 1,
        "question_text": "What is the capital of France?",
        "question_text_ar": "ما هي عاصمة فرنسا؟",
        "option_a": "London",
        "option_a_ar": "لندن",
        "option_b": "Paris",
        "option_b_ar": "باريس",
        "option_c": "Berlin",
        "option_c_ar": "برلين",
        "option_d": "Madrid",
        "option_d_ar": "مدريد",
        "correct_answer": "b",
        "explanation": "Paris is the capital of France.",
        "explanation_ar": "باريس هي عاصمة فرنسا.",
        "difficulty": "easy",
        "timer_seconds": 30,
        "category_id": 1,
        "challenge_pack_id": null,
        "is_active": 1,
        "created_at": "2024-01-15 10:30:00",
        "updated_at": "2024-01-15 10:30:00"
      }
    ],
    "total_returned": 10,
    "difficulty_points": {
      "easy": 10,
      "medium": 20,
      "hard": 30
    }
  }
}
```

### Create Question (Admin)
- **URL**: `POST /questions`
- **Authentication**: Required (Admin)
- **Request Body**:
```json
{
  "question_text": "What is 2 + 2?",
  "question_text_ar": "ما هو 2 + 2؟",
  "option_a": "3",
  "option_a_ar": "3",
  "option_b": "4",
  "option_b_ar": "4",
  "option_c": "5",
  "option_c_ar": "5",
  "option_d": "6",
  "option_d_ar": "6",
  "correct_answer": "b",
  "explanation": "2 + 2 equals 4",
  "explanation_ar": "2 + 2 يساوي 4",
  "difficulty": "easy",
  "timer_seconds": 20,
  "category_id": 1
}
```
- **Example**:
```bash
curl -X POST "https://yourdomain.com/api/questions" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "question_text": "What is 2 + 2?",
    "question_text_ar": "ما هو 2 + 2؟",
    "option_a": "3",
    "option_a_ar": "3",
    "option_b": "4",
    "option_b_ar": "4",
    "option_c": "5",
    "option_c_ar": "5",
    "option_d": "6",
    "option_d_ar": "6",
    "correct_answer": "b",
    "explanation": "2 + 2 equals 4",
    "explanation_ar": "2 + 2 يساوي 4",
    "difficulty": "easy",
    "timer_seconds": 20,
    "category_id": 1
  }'
```
- **Output**:
```json
{
  "success": true,
  "message": "Question created successfully",
  "data": {
    "id": 25
  }
}
```

## Games Endpoints

### Create Game Session
- **URL**: `POST /games`
- **Description**: Creates a new game session
- **Request Body**:
```json
{
  "game_name": "Friday Quiz",
  "total_teams": 2,
  "game_mode": "category",
  "source_id": 1,
  "questions_per_round": 10,
  "total_rounds": 1
}
```
- **Example**:
```bash
curl -X POST "https://yourdomain.com/api/games" \
  -H "Content-Type: application/json" \
  -d '{
    "game_name": "Friday Quiz",
    "total_teams": 2,
    "game_mode": "category",
    "source_id": 1,
    "questions_per_round": 10,
    "total_rounds": 1
  }'
```
- **Output**:
```json
{
  "success": true,
  "message": "Game session created successfully",
  "data": {
    "game_id": 123
  }
}
```

### Save Game Results
- **URL**: `POST /games/save`
- **Description**: Saves game results and marks game as completed
- **Request Body**:
```json
{
  "game_id": 123,
  "teams": [
    {"name": "Team A", "score": 150},
    {"name": "Team B", "score": 120}
  ],
  "questions": [[1, 2, 3, 4, 5]],
  "results": [
    {
      "team_index": 0,
      "question_id": 1,
      "round": 0,
      "selected_answer": "b",
      "is_correct": true,
      "points_earned": 20,
      "time_taken": 15
    }
  ]
}
```
- **Example**:
```bash
curl -X POST "https://yourdomain.com/api/games/save" \
  -H "Content-Type: application/json" \
  -d '{
    "game_id": 123,
    "teams": [
      {"name": "Team A", "score": 150},
      {"name": "Team B", "score": 120}
    ],
    "questions": [[1, 2, 3, 4, 5]],
    "results": [
      {
        "team_index": 0,
        "question_id": 1,
        "round": 0,
        "selected_answer": "b",
        "is_correct": true,
        "points_earned": 20,
        "time_taken": 15
      }
    ]
  }'
```
- **Output**:
```json
{
  "success": true,
  "message": "Game results saved successfully",
  "data": null
}
```

### Get Games List
- **URL**: `GET /games`
- **Description**: Retrieves paginated list of completed games
- **Query Parameters**:
  - `page` (optional): Page number (default: 1)
  - `limit` (optional): Items per page (default: 10)
- **Example**:
```bash
curl -X GET "https://yourdomain.com/api/games?page=1&limit=10"
```
- **Output**:
```json
{
  "success": true,
  "message": "Games retrieved successfully",
  "data": {
    "items": [
      {
        "id": 123,
        "game_name": "Friday Quiz",
        "total_teams": 2,
        "total_rounds": 1,
        "questions_per_round": 10,
        "game_mode": "category",
        "source_id": 1,
        "source_name": "Geography",
        "source_name_ar": "الجغرافيا",
        "teams_count": 2,
        "completed_at": "2024-01-20 14:30:45",
        "created_at": "2024-01-20 14:15:30"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 10,
      "total": 45,
      "total_pages": 5
    }
  }
}
```

### Get Game Details
- **URL**: `GET /games/{id}`
- **Description**: Retrieves detailed information about a specific game
- **Example**:
```bash
curl -X GET "https://yourdomain.com/api/games/123"
```
- **Output**:
```json
{
  "success": true,
  "message": "Game details retrieved successfully",
  "data": {
    "game": {
      "id": 123,
      "game_name": "Friday Quiz",
      "total_teams": 2,
      "total_rounds": 1,
      "questions_per_round": 10,
      "game_mode": "category",
      "source_id": 1,
      "source_name": "Geography",
      "source_name_ar": "الجغرافيا",
      "completed_at": "2024-01-20 14:30:45",
      "created_at": "2024-01-20 14:15:30"
    },
    "teams": [
      {
        "team_id": 456,
        "team_name": "Team A",
        "team_position": 1,
        "total_score": 150,
        "correct_answers": 8,
        "total_answers": 10,
        "average_time": 12.5
      },
      {
        "team_id": 457,
        "team_name": "Team B",
        "team_position": 2,
        "total_score": 120,
        "correct_answers": 6,
        "total_answers": 10,
        "average_time": 14.2
      }
    ]
  }
}
```

## Admin Endpoints

### Admin Login
- **URL**: `POST /admin/login`
- **Request Body**:
```json
{
  "username": "admin",
  "password": "your_password"
}
```
- **Example**:
```bash
curl -X POST "https://yourdomain.com/api/admin/login" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "your_password"
  }'
```
- **Output**:
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "admin": {
      "id": 1,
      "username": "admin",
      "email": "admin@example.com",
      "full_name": "System Administrator",
      "role": "super_admin",
      "last_login": "2024-01-20 14:25:30"
    }
  }
}
```

### Admin Logout
- **URL**: `POST /admin/logout`
- **Authentication**: Required
- **Example**:
```bash
curl -X POST "https://yourdomain.com/api/admin/logout" \
  -H "Authorization: Bearer {token}"
```
- **Output**:
```json
{
  "success": true,
  "message": "Logged out successfully",
  "data": null
}
```

### Get Admin Profile
- **URL**: `GET /admin/profile`
- **Authentication**: Required
- **Example**:
```bash
curl -X GET "https://yourdomain.com/api/admin/profile" \
  -H "Authorization: Bearer {token}"
```
- **Output**:
```json
{
  "success": true,
  "message": "Profile retrieved successfully",
  "data": {
    "id": 1,
    "username": "admin",
    "email": "admin@example.com",
    "full_name": "System Administrator",
    "role": "super_admin",
    "last_login": "2024-01-20 14:25:30",
    "created_at": "2024-01-01 00:00:00"
  }
}
```

### Update Admin Profile
- **URL**: `PUT /admin/profile`
- **Authentication**: Required
- **Request Body**:
```json
{
  "email": "new@email.com",
  "full_name": "New Name",
  "current_password": "oldpass",
  "new_password": "newpass"
}
```
- **Example**:
```bash
curl -X PUT "https://yourdomain.com/api/admin/profile" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "new@email.com",
    "full_name": "New Name",
    "current_password": "oldpass",
    "new_password": "newpass"
  }'
```
- **Output**:
```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": null
}
```

### Get Admin Logs
- **URL**: `GET /admin/logs`
- **Authentication**: Required
- **Query Parameters**:
  - `page` (optional): Page number
  - `limit` (optional): Items per page
  - `action` (optional): Filter by action type
  - `target_type` (optional): Filter by target type
  - `admin_id` (optional): Filter by admin ID
- **Example**:
```bash
curl -X GET "https://yourdomain.com/api/admin/logs?page=1&limit=10&action=CREATE" \
  -H "Authorization: Bearer {token}"
```
- **Output**:
```json
{
  "success": true,
  "message": "Admin logs retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "admin_id": 1,
        "admin_username": "admin",
        "admin_full_name": "System Administrator",
        "action": "CREATE",
        "target_type": "question",
        "target_id": 25,
        "old_data": null,
        "new_data": "{\"question_text\":\"What is 2 + 2?\",...}",
        "ip_address": "192.168.1.1",
        "user_agent": "curl/7.68.0",
        "created_at": "2024-01-20 14:35:20"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 10,
      "total": 150,
      "total_pages": 15
    }
  }
}
```

### AI Content Generation
- **URL**: `POST /admin/ai-generate`
- **Authentication**: Required (Admin or Super Admin)
- **Request Body**:
```json
{
  "type": "question",
  "prompt": "Generate a question about science",
  "ai_model": "gpt-3.5-turbo"
}
```
- **Example**:
```bash
curl -X POST "https://yourdomain.com/api/admin/ai-generate" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "question",
    "prompt": "Generate a question about science",
    "ai_model": "gpt-3.5-turbo"
  }'
```
- **Output**:
```json
{
  "success": true,
  "message": "AI content generated successfully",
  "data": {
    "generated_content": {
      "type": "question",
      "prompt_used": "Generate a question about science",
      "suggestion": "AI integration placeholder - implement with your preferred AI service",
      "model_used": "gpt-3.5-turbo",
      "cost_estimate": 0.001,
      "tokens_used": 150
    }
  }
}
```

### Create Admin User
- **URL**: `POST /admin/create`
- **Authentication**: Required (Super Admin only)
- **Request Body**:
```json
{
  "username": "newadmin",
  "email": "newadmin@example.com",
  "password": "password123",
  "full_name": "New Admin",
  "role": "admin"
}
```
- **Example**:
```bash
curl -X POST "https://yourdomain.com/api/admin/create" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "newadmin",
    "email": "newadmin@example.com",
    "password": "password123",
    "full_name": "New Admin",
    "role": "admin"
  }'
```
- **Output**:
```json
{
  "success": true,
  "message": "Admin created successfully",
  "data": {
    "id": 2
  }
}
```

## Statistics Endpoints (Admin Only)

### Dashboard Statistics
- **URL**: `GET /statistics/dashboard`
- **Authentication**: Required
- **Example**:
```bash
curl -X GET "https://yourdomain.com/api/statistics/dashboard" \
  -H "Authorization: Bearer {token}"
```
- **Output**:
```json
{
  "success": true,
  "message": "Dashboard statistics retrieved successfully",
  "data": {
    "counts": {
      "total_categories": 15,
      "total_packs": 8,
      "total_questions": 250,
      "total_games": 45,
      "total_admins": 3,
      "total_downloads": 350
    },
    "recent_activity": [
      {
        "date": "2024-01-20",
        "action": "CREATE",
        "target_type": "question",
        "count": 5
      }
    ],
    "popular_categories": [
      {
        "name": "Geography",
        "name_ar": "الجغرافيا",
        "question_count": 35,
        "avg_difficulty": 1.8
      }
    ],
    "top_downloaded_packs": [
      {
        "name": "Science Pack",
        "name_ar": "حزمة العلوم",
        "download_count": 150,
        "question_count": 25
      }
    ]
  }
}
```

### Category Statistics
- **URL**: `GET /statistics/categories`
- **Authentication**: Required
- **Example**:
```bash
curl -X GET "https://yourdomain.com/api/statistics/categories" \
  -H "Authorization: Bearer {token}"
```
- **Output**:
```json
{
  "success": true,
  "message": "Category statistics retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Geography",
      "name_ar": "الجغرافيا",
      "description": "Geography questions",
      "description_ar": "أسئلة الجغرافيا",
      "difficulty": "medium",
      "timer_seconds": 30,
      "question_count": 35,
      "avg_difficulty_score": 1.8,
      "easy_questions": 15,
      "medium_questions": 15,
      "hard_questions": 5,
      "is_active": 1,
      "created_at": "2024-01-15 10:30:00",
      "updated_at": "2024-01-15 10:30:00"
    }
  ]
}
```

### Challenge Pack Statistics
- **URL**: `GET /statistics/packs`
- **Authentication**: Required
- **Example**:
```bash
curl -X GET "https://yourdomain.com/api/statistics/packs" \
  -H "Authorization: Bearer {token}"
```
- **Output**:
```json
{
  "success": true,
  "message": "Challenge pack statistics retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Science Pack",
      "name_ar": "حزمة العلوم",
      "description": "Science questions pack",
      "description_ar": "حزمة أسئلة العلوم",
      "theme": "science",
      "difficulty": "medium",
      "timer_seconds": 30,
      "download_count": 150,
      "question_count": 25,
      "avg_difficulty_score": 2.1,
      "easy_questions": 10,
      "medium_questions": 10,
      "hard_questions": 5,
      "is_active": 1,
      "created_at": "2024-01-15 10:30:00",
      "updated_at": "2024-01-15 10:30:00"
    }
  ]
}
```

### Question Statistics
- **URL**: `GET /statistics/questions`
- **Authentication**: Required
- **Example**:
```bash
curl -X GET "https://yourdomain.com/api/statistics/questions" \
  -H "Authorization: Bearer {token}"
```
- **Output**:
```json
{
  "success": true,
  "message": "Question statistics retrieved successfully",
  "data": {
    "by_difficulty": [
      {
        "difficulty": "easy",
        "count": 120,
        "avg_timer": 25.5
      },
      {
        "difficulty": "medium",
        "count": 80,
        "avg_timer": 35.2
      },
      {
        "difficulty": "hard",
        "count": 50,
        "avg_timer": 45.8
      }
    ],
    "by_source": {
      "category_questions": 180,
      "pack_questions": 70,
      "total_questions": 250
    },
    "recent_additions": [
      {
        "date": "2024-01-20",
        "questions_added": 5
      }
    ]
  }
}
```

### Game Statistics
- **URL**: `GET /statistics/games`
- **Authentication**: Required
- **Example**:
```bash
curl -X GET "https://yourdomain.com/api/statistics/games" \
  -H "Authorization: Bearer {token}"
```
- **Output**:
```json
{
  "success": true,
  "message": "Game statistics retrieved successfully",
  "data": {
    "overview": {
      "total_games": 45,
      "avg_teams": 2.8,
      "avg_questions_per_round": 8.5,
      "avg_rounds": 1.2,
      "category_games": 30,
      "pack_games": 15
    },
    "games_over_time": [
      {
        "date": "2024-01-20",
        "games_completed": 5
      }
    ]
  }
}
```

### General Statistics
- **URL**: `GET /statistics/general`
- **Authentication**: Required
- **Example**:
```bash
curl -X GET "https://yourdomain.com/api/statistics/general" \
  -H "Authorization: Bearer {token}"
```
- **Output**:
```json
{
  "success": true,
  "message": "General statistics retrieved successfully",
  "data": {
    "categories": 15,
    "packs": 8,
    "questions": 250,
    "completed_games": 45,
    "admins": 3,
    "total_downloads": 350,
    "actions_last_24h": 25
  }
}
```

## Error Handling

All endpoints return consistent error responses:
```json
{
  "success": false,
  "message": "Error description",
  "code": "ERROR_CODE",
  "data": null
}
```

**Example Error Outputs:**

**Validation Error**:
```json
{
  "success": false,
  "message": "Validation failed",
  "code": "VALIDATION_ERROR",
  "data": {
    "name": ["Name is required"],
    "email": ["Invalid email format"]
  }
}
```

**Authentication Error**:
```json
{
  "success": false,
  "message": "Invalid credentials",
  "code": "INVALID_CREDENTIALS",
  "data": null
}
```

**Authorization Error**:
```json
{
  "success": false,
  "message": "Access denied",
  "code": "FORBIDDEN",
  "data": null
}
```

**Not Found Error**:
```json
{
  "success": false,
  "message": "Category not found",
  "code": "CATEGORY_NOT_FOUND",
  "data": null
}
```

## Response Format

Successful responses follow this format:
```json
{
  "success": true,
  "message": "Success message",
  "data": {...}
}
```

For paginated responses:
```json
{
  "success": true,
  "message": "Success message",
  "data": {
    "items": [...],
    "pagination": {
      "current_page": 1,
      "per_page": 10,
      "total": 100,
      "total_pages": 10
    }
  }
}
```

## Complete Examples

### Complete Game Flow Example

1. **Get random questions for a category**:
```bash
curl -X GET "https://yourdomain.com/api/questions/random?category_id=1&limit=10"
```

**Output**:
```json
{
  "success": true,
  "message": "Random questions retrieved successfully",
  "data": {
    "questions": [
      {
        "id": 1,
        "question_text": "What is the capital of France?",
        "question_text_ar": "ما هي عاصمة فرنسا؟",
        "option_a": "London",
        "option_a_ar": "لندن",
        "option_b": "Paris",
        "option_b_ar": "باريس",
        "option_c": "Berlin",
        "option_c_ar": "برلين",
        "option_d": "Madrid",
        "option_d_ar": "مدريد",
        "correct_answer": "b",
        "explanation": "Paris is the capital of France.",
        "explanation_ar": "باريس هي عاصمة فرنسا.",
        "difficulty": "easy",
        "timer_seconds": 30,
        "category_id": 1,
        "challenge_pack_id": null,
        "is_active": 1,
        "created_at": "2024-01-15 10:30:00",
        "updated_at": "2024-01-15 10:30:00"
      }
    ],
    "total_returned": 10,
    "difficulty_points": {
      "easy": 10,
      "medium": 20,
      "hard": 30
    }
  }
}
```

2. **Create a game session**:
```bash
curl -X POST "https://yourdomain.com/api/games" \
  -H "Content-Type: application/json" \
  -d '{
    "game_name": "Team Quiz",
    "total_teams": 2,
    "game_mode": "category",
    "source_id": 1,
    "questions_per_round": 10,
    "total_rounds": 1
  }'
```

**Output**:
```json
{
  "success": true,
  "message": "Game session created successfully",
  "data": {
    "game_id": 123
  }
}
```

3. **Save game results**:
```bash
curl -X POST "https://yourdomain.com/api/games/save" \
  -H "Content-Type: application/json" \
  -d '{
    "game_id": 123,
    "teams": [
      {"name": "Team A", "score": 150},
      {"name": "Team B", "score": 120}
    ],
    "questions": [[1, 2, 3, 4, 5]],
    "results": [
      {
        "team_index": 0,
        "question_id": 1,
        "round": 0,
        "selected_answer": "b",
        "is_correct": true,
        "points_earned": 20,
        "time_taken": 15
      }
    ]
  }'
```

**Output**:
```json
{
  "success": true,
  "message": "Game results saved successfully",
  "data": null
}
```

4. **View game results**:
```bash
curl -X GET "https://yourdomain.com/api/games/123"
```

**Output**:
```json
{
  "success": true,
  "message": "Game details retrieved successfully",
  "data": {
    "game": {
      "id": 123,
      "game_name": "Team Quiz",
      "total_teams": 2,
      "total_rounds": 1,
      "questions_per_round": 10,
      "game_mode": "category",
      "source_id": 1,
      "source_name": "Geography",
      "source_name_ar": "الجغرافيا",
      "completed_at": "2024-01-20 14:30:45",
      "created_at": "2024-01-20 14:15:30"
    },
    "teams": [
      {
        "team_id": 456,
        "team_name": "Team A",
        "team_position": 1,
        "total_score": 150,
        "correct_answers": 8,
        "total_answers": 10,
        "average_time": 12.5
      },
      {
        "team_id": 457,
        "team_name": "Team B",
        "team_position": 2,
        "total_score": 120,
        "correct_answers": 6,
        "total_answers": 10,
        "average_time": 14.2
      }
    ]
  }
}
```

### Admin Content Management Example

1. **Login as admin**:
```bash
curl -X POST "https://yourdomain.com/api/admin/login" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "your_password"
  }'
```

**Output**:
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "admin": {
      "id": 1,
      "username": "admin",
      "email": "admin@example.com",
      "full_name": "System Administrator",
      "role": "super_admin",
      "last_login": "2024-01-20 14:25:30"
    }
  }
}
```

2. **Create a new category**:
```bash
curl -X POST "https://yourdomain.com/api/categories" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Geography",
    "name_ar": "الجغرافيا",
    "description": "Geography questions",
    "description_ar": "أسئلة الجغرافيا",
    "difficulty": "medium",
    "timer_seconds": 30
  }'
```

**Output**:
```json
{
  "success": true,
  "message": "Category created successfully",
  "data": {
    "id": 3
  }
}
```

3. **Create questions for the category**:
```bash
curl -X POST "https://yourdomain.com/api/questions" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "question_text": "What is the capital of France?",
    "question_text_ar": "ما هي عاصمة فرنسا؟",
    "option_a": "London",
    "option_a_ar": "لندن",
    "option_b": "Paris",
    "option_b_ar": "باريس",
    "option_c": "Berlin",
    "option_c_ar": "برلين",
    "option_d": "Madrid",
    "option_d_ar": "مدريد",
    "correct_answer": "b",
    "explanation": "Paris is the capital of France.",
    "explanation_ar": "باريس هي عاصمة فرنسا.",
    "difficulty": "easy",
    "timer_seconds": 30,
    "category_id": 3
  }'
```

**Output**:
```json
{
  "success": true,
  "message": "Question created successfully",
  "data": {
    "id": 26
  }
}
```

4. **View statistics**:
```bash
curl -X GET "https://yourdomain.com/api/statistics/dashboard" \
  -H "Authorization: Bearer {token}"
```

**Output**:
```json
{
  "success": true,
  "message": "Dashboard statistics retrieved successfully",
  "data": {
    "counts": {
      "total_categories": 16,
      "total_packs": 8,
      "total_questions": 251,
      "total_games": 45,
      "total_admins": 3,
      "total_downloads": 350
    },
    "recent_activity": [
      {
        "date": "2024-01-20",
        "action": "CREATE",
        "target_type": "category",
        "count": 1
      },
      {
        "date": "2024-01-20",
        "action": "CREATE",
        "target_type": "question",
        "count": 1
      }
    ],
    "popular_categories": [
      {
        "name": "Geography",
        "name_ar": "الجغرافيا",
        "question_count": 1,
        "avg_difficulty": 1.0
      }
    ],
    "top_downloaded_packs": [
      {
        "name": "Science Pack",
        "name_ar": "حزمة العلوم",
        "download_count": 150,
        "question_count": 25
      }
    ]
  }
}
```

## Notes

1. All admin endpoints require authentication with a valid JWT token
2. Some admin functions require specific roles (admin, super_admin)
3. Pagination parameters are available on list endpoints
4. Input validation is performed on all endpoints with appropriate error messages
5. Always use HTTPS in production environments
6. Implement rate limiting for production use
7. Database backups and regular security updates are recommended
8. Monitor access logs for suspicious activity
9. The API supports both Arabic and English content for all text fields
10. Game sessions can be created from either categories or challenge packs
## Notes

1. All admin endpoints require authentication with a valid JWT token
2. Some admin functions require specific roles (admin, super_admin)
3. Pagination parameters are available on list endpoints
4. Input validation is performed on all endpoints with appropriate error messages
5. Always use HTTPS in production environments
6. Implement rate limiting for production use
7. Database backups and regular security updates are recommended
8. Monitor access logs for suspicious activity

## Support

For issues and questions, please check the documentation or contact support.