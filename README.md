# Shofol Connect

[![Laravel 12](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat&logo=php)](https://php.net)
[![Google Gemini API](https://img.shields.io/badge/Google%20Gemini-API%20Integrated-4285F4?style=flat&logo=google)](https://ai.google.dev/)
[![Tests](https://img.shields.io/badge/Tests-Passing-brightgreen?style=flat&logo=github)](tests/)

**Shofol Connect** is a modern enterprise operations and knowledge management platform built on **Laravel 12**. It centralizes departmental knowledge across **HRM**, **Payroll**, **CRM**, and **Accounting**, paired with a **RAG-grounded AI Support Assistant** powered by the **Google Gemini API** (`gemini-3.8-flash`).

---

## Key Features

### 1. RAG-Grounded AI Support Assistant
* **Strict Anti-Hallucination Guardrails**: The assistant strictly answers questions using retrieved knowledge articles. If no authoritative documentation exists, it returns a standard fallback guidance message rather than hallucinating answers.
* **OpenAI-Compatible Gemini Integration**: Communicates with Google Gemini via its OpenAI-compatible completions endpoint (`https://generativelanguage.googleapis.com/v1beta/openai`).
* **Transient Fault Tolerance**: Built-in automated retries for transient HTTP `429` (rate limits) and `503` (high-demand spikes) errors, with strict non-retry policies for authentication and invalid model errors.
* **Credential Redaction**: Automated sanitization removes all API keys and secrets from error logs and exception traces before logging.
* **Source Citations**: Displays verified source badges (`[Module] Article Title`) for each grounded response.
* **Conversation Continuity**: Real-time conversation threads with automatic session naming and pagination.

### 2. Departmental Knowledge Base
* Comprehensive documentation repository categorized across four business modules:
  * **HRM**: Leave policies, onboarding, employee benefits, PTO.
  * **Payroll**: Disbursement cycles, statutory tax withholding (TDS), provident fund.
  * **CRM**: Sales pipeline stages, BANT lead qualification, deal status tracking.
  * **Accounting**: Invoice generation, reconciliation, Net 30 payment terms.
* Lexical tokenization, candidate filtering, and relevance ranking via `KnowledgeRetrievalService`.
* Administrative CRUD interface for knowledge article management.

### 3. Integrated Support Ticketing
* Seamless user escalation from AI chat sessions directly into formal support tickets.
* Conversation context auto-linking for support agents.
* Admin management dashboard with status tracking, priority assignment, and resolution notes.

### 4. Role-Based Access Control (RBAC) & Security
* Multi-role authorization (`admin` and `user`).
* Protected admin middleware for tickets and knowledge base management.
* Strict conversation ownership verification.
* Built-in authentication, email verification, and profile management via Laravel Breeze.

---

## Technology Stack

* **Backend**: [Laravel 12](https://laravel.com) (PHP 8.2+)
* **Database**: MySQL 8.0+ (supported) / SQLite (testing & local dev)
* **Frontend**: Blade Templates, Bootstrap 5, Vite, Bootstrap Icons
* **AI & LLM**: Google Gemini API (`gemini-3.8-flash`) via Laravel HTTP Client (Guzzle)
* **Testing**: Pest PHP / PHPUnit

---

## Installation & Setup

### Prerequisites
* **PHP** >= 8.2 (extensions: `bcmath`, `curl`, `mbstring`, `openssl`, `pdo`, `pdo_mysql` or `pdo_sqlite`, `tokenizer`, `xml`)
* **Composer** >= 2.x
* **Node.js** >= 18.x & **npm**
* **MySQL** >= 8.0 (or SQLite)

### Step 1: Clone Repository & Install Dependencies
```bash
git clone https://github.com/your-username/shofol-connect.git
cd shofol-connect

# Install backend dependencies
composer install

# Install frontend dependencies
npm install
```

### Step 2: Environment Configuration
Create your local environment file from the provided template:
```bash
cp .env.example .env
php artisan key:generate
```

Configure your `.env` settings:

#### Database Configuration (MySQL)
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shofol_connect
DB_USERNAME=your_mysql_username
DB_PASSWORD=your_mysql_password
```

#### AI Provider Configuration (Google Gemini)
Obtain an API key from [Google AI Studio](https://aistudio.google.com/):
```env
AI_PROVIDER=gemini
GEMINI_API_KEY=your_actual_gemini_api_key_here
AI_MODEL=gemini-3.8-flash
AI_BASE_URL=https://generativelanguage.googleapis.com/v1beta/openai
AI_TIMEOUT=60
AI_RETRY_TIMES=2
AI_RETRY_DELAY=1000
```

Use `OPENAI_API_KEY` with `AI_PROVIDER=openai` and `https://api.openai.com/v1`. `AI_API_KEY`, when set, overrides the provider-specific key. Changing `.env` values has no effect while Laravel's configuration cache is active; refresh it with `php artisan config:clear`, then rebuild it for production with `php artisan config:cache`.

> **Note**: Do not commit your `.env` file to version control. The `.gitignore` file is pre-configured to keep your credentials safe.

### Step 3: Run Database Migrations & Seeders
```bash
php artisan migrate --seed
```

This sets up database tables and seeds initial administrative accounts and foundational knowledge base articles.

**Default Seeded Accounts**:
| Role | Email | Password |
|---|---|---|
| **Admin** | `admin@shofolconnect.com` | `password` |
| **Regular User** | `user@shofolconnect.com` | `password` |

### Step 4: Build Assets & Start Development Server
```bash
# Build frontend assets
npm run build
# Or run live watcher: npm run dev

# Start Laravel development server
php artisan serve
```

Access the application in your browser at `http://localhost:8000`.

---

## Running Automated Tests

Shofol Connect comes with an extensive suite of automated tests covering authentication, RBAC, ticket management, conversation history, and mocked AI service workflows:

```bash
# Run all tests
php artisan test

# Run chat & AI feature tests only
php artisan test tests/Feature/Chat/AIChatTest.php
```

All AI tests use HTTP mocks (`Http::fake()`) to ensure consistent, isolated test runs without making live API calls or consuming quota.

---

## Project Structure Highlights

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/                  # Admin tickets and knowledge management
│   │   ├── ChatController.php      # AI assistant chat actions
│   │   ├── ConversationController.php # Conversation session management
│   │   └── TicketController.php    # User support ticket actions
│   └── Middleware/
│       └── AdminMiddleware.php     # Admin role verification
├── Models/
│   ├── ChatMessage.php             # Individual conversation messages
│   ├── Conversation.php            # Chat session threads
│   ├── KnowledgeArticle.php        # Departmental knowledge entries
│   ├── SupportTicket.php           # User & escalated support tickets
│   └── User.php                    # Application users & roles
└── Services/
    ├── AIService.php               # LLM integration, prompt assembly, retries, sanitization
    └── KnowledgeRetrievalService.php # Lexical tokenization & relevance ranking
```

---

## Security Best Practices

* **Never commit `.env` files**: All secret keys (`APP_KEY`, `GEMINI_API_KEY`, database passwords) must remain localized.
* **Strict error sanitization**: `AIService` automatically redacts sensitive API keys from application logs.
* **RAG guardrails**: Knowledge base queries only expose active, published articles.

---

## License

This project is open-source software licensed under the [MIT License](LICENSE).
