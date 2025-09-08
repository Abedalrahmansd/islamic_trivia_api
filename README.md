# Islamic Trivia Game API

A complete, bilingual (English/Arabic) REST API for an Islamic Trivia Game supporting both web and mobile platforms. This API provides comprehensive game management, content administration, and analytics capabilities.

![API Version](https://img.shields.io/badge/Version-1.0.0-blue.svg)
![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![MySQL Version](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg)
![License](https://img.shields.io/badge/License-MIT-green.svg)

## ✨ Features

- **Complete CRUD Operations** - Manage categories, challenge packs, questions, and game sessions
- **Admin Authentication & Authorization** - JWT-based secure admin access with role management
- **Bilingual Support** - Full English/Arabic content support throughout the API
- **Game Session Management** - Complete game flow from creation to results tracking
- **Statistics & Analytics** - Comprehensive dashboard and reporting capabilities
- **AI Content Generation** - AI-powered question and content generation (integration ready)
- **Security Best Practices** - Prepared statements, input validation, CORS, and rate limiting ready
- **Comprehensive Logging** - Detailed admin action logging and system monitoring

## 🚀 Quick Start

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (for dependency management)

### Installation

1. **Upload files to your server**
   ```bash
   git clone https://github.com/yourusername/islamic-trivia-api.git
   cd islamic-trivia-api
   ```

2. **Create MySQL database and import schema**
   ```sql
   mysql -u yourusername -p yourdatabase < database.sql
   ```

3. **Configure environment variables**
   ```bash
   cp .env.example .env
   ```
   Edit `.env` with your configuration:
   ```env
   DB_HOST=localhost
   DB_NAME=islamic_trivia_game
   DB_USER=your_username
   DB_PASS=your_password
   JWT_SECRET=your-super-secret-jwt-key-here
   APP_URL=https://yourdomain.com
   APP_ENV=production
   ```

4. **Set proper file permissions**
   ```bash
   chmod 755 directories/
   chmod 644 files/
   ```

5. **Configure web server**
   - Point your web server to the public directory
   - Enable mod_rewrite for Apache
   - Configure Nginx with proper rewrite rules

### Docker Installation (Alternative)

```bash
docker-compose up -d
docker exec -it trivia-app composer install
docker exec -it trivia-app php database/seed.php
```

## 🔧 Configuration

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_HOST` | Database host | `localhost` |
| `DB_NAME` | Database name | `islamic_trivia_game` |
| `DB_USER` | Database user | - |
| `DB_PASS` | Database password | - |
| `JWT_SECRET` | JWT secret key | - |
| `APP_URL` | Application URL | `http://localhost` |
| `APP_ENV` | Application environment | `production` |
| `AI_API_KEY` | AI service API key | - |

### Security Checklist

- [ ] Change default database credentials
- [ ] Set strong JWT secret key
- [ ] Implement HTTPS in production
- [ ] Configure rate limiting
- [ ] Set up proper error logging
- [ ] Configure firewall rules
- [ ] Enable regular security updates
- [ ] Use strong admin passwords
- [ ] Implement database backups
- [ ] Monitor access logs regularly
- [ ] Validate and sanitize all inputs

## 📚 API Documentation

### Base URL & Authentication

**Base URL**: `https://yourdomain.com/api/`

**Authentication**: Admin endpoints require JWT token in Authorization header:
```
Authorization: Bearer {your_jwt_token}
```

### Public Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/categories` | GET | List all categories |
| `/categories/{id}` | GET | Get specific category |
| `/challenge-packs` | GET | List all challenge packs |
| `/challenge-packs/{id}` | GET | Get specific challenge pack |
| `/challenge-packs/download/{id}` | GET | Download pack with questions |
| `/questions/random` | GET | Get random questions |
| `/games` | POST | Create new game session |
| `/games/save` | POST | Save game results |
| `/games/{id}` | GET | Get game results |

### Admin Endpoints (Authentication Required)

| Endpoint | Method | Description | Required Role |
|----------|--------|-------------|---------------|
| `/admin/login` | POST | Admin login | - |
| `/admin/logout` | POST | Admin logout | Any admin |
| `/admin/profile` | GET | Get admin profile | Any admin |
| `/admin/profile` | PUT | Update admin profile | Any admin |
| `/admin/logs` | GET | Get admin action logs | Admin |
| `/admin/ai-generate` | POST | Generate content with AI | Admin |
| `/admin/create` | POST | Create new admin | Super Admin |
| `/categories` | POST | Create category | Admin |
| `/categories/{id}` | PUT | Update category | Admin |
| `/categories/{id}` | DELETE | Delete category | Admin |
| `/challenge-packs` | POST | Create challenge pack | Admin |
| `/challenge-packs/{id}` | PUT | Update challenge pack | Admin |
| `/challenge-packs/{id}` | DELETE | Delete challenge pack | Admin |
| `/questions` | POST | Create question | Admin |
| `/questions/{id}` | PUT | Update question | Admin |
| `/questions/{id}` | DELETE | Delete question | Admin |

### Statistics Endpoints (Admin Only)

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/statistics/dashboard` | GET | Dashboard statistics |
| `/statistics/categories` | GET | Category statistics |
| `/statistics/packs` | GET | Challenge pack statistics |
| `/statistics/questions` | GET | Question statistics |
| `/statistics/games` | GET | Game statistics |
| `/statistics/general` | GET | General statistics |

## 🎮 API Usage Examples

### Game Flow Example

1. **Get random questions for a category**
```bash
curl -X GET "https://yourdomain.com/api/questions/random?category_id=1&limit=10"
```

2. **Create a game session**
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

3. **Save game results**
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

### Admin Content Management

1. **Admin login**
```bash
curl -X POST "https://yourdomain.com/api/admin/login" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "your_password"
  }'
```

2. **Create a new category**
```bash
curl -X POST "https://yourdomain.com/api/categories" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Islamic History",
    "name_ar": "التاريخ الإسلامي",
    "description": "Questions about Islamic history",
    "description_ar": "أسئلة حول التاريخ الإسلامي",
    "difficulty": "medium",
    "timer_seconds": 30
  }'
```

3. **Create questions for the category**
```bash
curl -X POST "https://yourdomain.com/api/questions" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "question_text": "Who was the first caliph of Islam?",
    "question_text_ar": "من كان أول خليفة للإسلام؟",
    "option_a": "Abu Bakr",
    "option_a_ar": "أبو بكر",
    "option_b": "Umar",
    "option_b_ar": "عمر",
    "option_c": "Uthman",
    "option_c_ar": "عثمان",
    "option_d": "Ali",
    "option_d_ar": "علي",
    "correct_answer": "a",
    "explanation": "Abu Bakr was the first caliph after Prophet Muhammad.",
    "explanation_ar": "كان أبو بكر أول خليفة بعد النبي محمد.",
    "difficulty": "easy",
    "timer_seconds": 30,
    "category_id": 1
  }'
```

## 📊 Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {
    // Response data
  }
}
```

### Paginated Response
```json
{
  "success": true,
  "message": "Data retrieved successfully",
  "data": {
    "items": [
      // Array of items
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 10,
      "total": 100,
      "total_pages": 10
    }
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "code": "ERROR_CODE",
  "data": null
}
```

## 🔐 Error Codes

| Code | Description | HTTP Status |
|------|-------------|-------------|
| `VALIDATION_ERROR` | Input validation failed | 400 |
| `INVALID_CREDENTIALS` | Invalid login credentials | 401 |
| `UNAUTHORIZED` | Authentication required | 401 |
| `FORBIDDEN` | Insufficient permissions | 403 |
| `NOT_FOUND` | Resource not found | 404 |
| `INTERNAL_ERROR` | Server error | 500 |

## 🗃️ Database Schema

### Main Tables
- `categories` - Question categories with difficulty levels
- `challenge_packs` - Downloadable question packs
- `questions` - Questions with bilingual content
- `games` - Game session records
- `game_teams` - Teams participating in games
- `game_results` - Individual question results
- `admins` - Administrator accounts
- `admin_logs` - Admin action audit trail

## 🤝 Contributing

We welcome contributions! Please feel free to submit pull requests, report bugs, or suggest new features.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support, please open an issue in the GitHub repository or contact us at support@yourdomain.com.

## 🙏 Acknowledgments

- Islamic scholars and content contributors
- Open source community for various libraries and tools
- AI service providers for content generation capabilities

---

**Note**: This API is designed for educational purposes. Always ensure proper content review by qualified Islamic scholars before deploying for public use.

For the complete API documentation with detailed request/response examples, please refer to the [API Documentation](docs/API.md) file.