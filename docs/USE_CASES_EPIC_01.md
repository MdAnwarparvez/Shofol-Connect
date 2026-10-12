# Use Cases Specification
## EPIC 01: Foundation & Knowledge Base

**Project**: Shofol Connect  
**Framework**: Laravel 12.x  
**Document Version**: 1.0.0  
**Status**: Approved Baseline  
**Date**: October 2026  

---

### 1. Actors

1. **Guest**: Unauthenticated visitor accessing public portal routes.
2. **Regular User**: Authenticated employee or client with `role = 'user'`.
3. **Administrator**: Authenticated administrative user with `role = 'admin'`.
4. **AI Assistant Subsystem**: Automated background services (`AIService`, `KnowledgeRetrievalService`, Google Gemini API).

---

### 2. Use Case Index

| Use Case ID | Use Case Name | Primary Actor | Category |
| :--- | :--- | :--- | :--- |
| **UC-FND-01** | User Self-Registration | Guest | Foundation & Auth |
| **UC-FND-02** | User Authentication & Role-Based Routing | Guest | Foundation & Auth |
| **UC-FND-03** | Password Reset Recovery | Guest | Foundation & Auth |
| **UC-FND-04** | User Profile Update & Management | Regular User / Admin | Foundation & Auth |
| **UC-FND-05** | View Personal User Dashboard | Regular User | Foundation / Dashboard |
| **UC-ADM-01** | View Admin Analytics Dashboard | Administrator | Foundation / Dashboard |
| **UC-ADM-02** | RBAC Route Guard Enforcement | Regular User / Guest | Security & RBAC |
| **UC-KB-01** | Browse & Filter Knowledge Articles | Administrator | Knowledge Base Management |
| **UC-KB-02** | Keyword Search Across Knowledge Base | Administrator | Knowledge Base Management |
| **UC-KB-03** | Create Verified Knowledge Article | Administrator | Knowledge Base Management |
| **UC-KB-04** | View Knowledge Article Details | Administrator | Knowledge Base Management |
| **UC-KB-05** | Update Knowledge Article Record | Administrator | Knowledge Base Management |
| **UC-KB-06** | Delete Knowledge Article Record | Administrator | Knowledge Base Management |
| **UC-AI-01** | RAG Knowledge Retrieval & AI Response | Regular User | AI Chat Integration |
| **UC-AI-02** | Upstream AI Quota/Outage Policy Fallback | Regular User / AI Subsystem | AI Chat Integration |
| **UC-TCK-01** | Escalate Chat Session to Support Ticket | Regular User | Support Ticketing |

---

### 3. Detailed Use Case Specifications

---

#### UC-FND-01: User Self-Registration
* **Primary Actor**: Guest
* **Preconditions**: Guest is not logged into the application.
* **Trigger**: Guest navigates to `/register` and submits registration details.
* **Main Success Flow**:
  1. Guest accesses the registration page.
  2. Guest inputs `name`, valid unique `email`, `password`, and `password_confirmation`.
  3. System validates input (string, unique email, password confirmation, minimum length).
  4. System creates new `User` record with `role = 'user'` (hashed password).
  5. System fires `Registered` event and logs the user into an active session.
  6. System redirects user to `/dashboard`.
* **Alternate / Error Flows**:
  * **3a. Validation Failure**:
    * System returns HTTP 422 with validation errors highlighted on the registration form.
  * **3b. Email Already Exists**:
    * System flags email field with "The email has already been taken."
* **Postconditions**: New user record created in `users` table; authenticated session initiated.

---

#### UC-FND-02: User Authentication & Role-Based Routing
* **Primary Actor**: Guest
* **Preconditions**: User has an existing account in the `users` table.
* **Trigger**: User navigates to `/login` and submits credentials.
* **Main Success Flow**:
  1. User enters registered email and password.
  2. System verifies credentials against the database.
  3. System regenerates the session ID to prevent session fixation.
  4. System inspects the user's `role` attribute:
     * If `role === 'admin'`: System redirects to `/admin/dashboard`.
     * If `role === 'user'`: System redirects to `/dashboard`.
* **Alternate / Error Flows**:
  * **2a. Invalid Credentials**:
    * System fails authentication, records failed attempt, and returns error "These credentials do not match our records."
  * **2b. Rate Limiting**:
    * If 5 consecutive failed attempts occur within 1 minute, system locks authentication for 60 seconds (HTTP 429).
* **Postconditions**: Authenticated session created; user redirected according to assigned role.

---

#### UC-FND-03: Password Reset Recovery
* **Primary Actor**: Guest
* **Preconditions**: User possesses a registered email address.
* **Trigger**: User selects "Forgot your password?" on `/login`.
* **Main Success Flow**:
  1. User inputs email on `/forgot-password`.
  2. System verifies email exists and generates a secure signed token in `password_reset_tokens`.
  3. System transmits password reset link.
  4. User accesses reset URL (`/reset-password/{token}`).
  5. User provides new password and confirmation.
  6. System validates token, updates `password` hash in `users`, and deletes the token.
  7. System redirects user to login with success flash status.
* **Alternate / Error Flows**:
  * **6a. Expired / Invalid Token**:
    * System displays "This password reset token is invalid."

---

#### UC-FND-04: User Profile Update & Management
* **Primary Actor**: Regular User or Administrator
* **Preconditions**: User is authenticated.
* **Trigger**: User navigates to `/profile`.
* **Main Success Flow**:
  1. User navigates to profile view.
  2. User modifies `name` or `email` and clicks Save.
  3. System validates changes via `ProfileUpdateRequest`.
  4. If email is altered, system resets `email_verified_at` to `null`.
  5. System saves changes and returns session flash status.
* **Alternate / Error Flows**:
  * **2a. Password Change**:
    * User submits `current_password`, `password`, and `password_confirmation`. System verifies current password before updating hash.
  * **2b. Account Deletion**:
    * User enters password to confirm account deletion. System invalidates session, deletes `User` record, and redirects to `/`.

---

#### UC-FND-05: View Personal User Dashboard
* **Primary Actor**: Regular User
* **Preconditions**: User is authenticated with `role = 'user'`.
* **Trigger**: User accesses `/dashboard`.
* **Main Success Flow**:
  1. System queries database isolating records belonging to `auth()->id()`:
     * Total conversations count.
     * Total support tickets count.
     * Open tickets count (`status IN ('Open', 'In Progress')`).
     * Resolved tickets count (`status IN ('Resolved', 'Closed')`).
     * Up to 4 recent conversations with message counts.
     * Up to 4 recent support tickets.
  2. System renders `resources/views/dashboard.blade.php`.
  3. User can click quick action cards to launch AI chat or submit a ticket.
* **Postconditions**: User views accurate, strictly isolated personal activity metrics.

---

#### UC-ADM-01: View Admin Analytics Dashboard
* **Primary Actor**: Administrator
* **Preconditions**: User is authenticated with `role = 'admin'`.
* **Trigger**: Admin accesses `/admin/dashboard`.
* **Main Success Flow**:
  1. System executes analytics aggregation queries across the platform:
     * User metrics: Total users, admin count, regular user count.
     * Knowledge Base metrics: Total articles, active count, inactive count, article counts per module (`HRM`, `Payroll`, `CRM`, `Accounting`).
     * AI Chat metrics: Total conversations, total user messages, total assistant messages.
     * Support Ticket metrics: Total tickets, open tickets, resolved tickets, priority counts (`High`, `Medium`, `Low`).
  2. System renders `resources/views/admin/dashboard.blade.php` displaying summary metric cards, distribution charts, and quick administration links.
* **Postconditions**: System-wide operations analytics presented to administrator.

---

#### UC-ADM-02: RBAC Route Guard Enforcement
* **Primary Actor**: Regular User or Guest
* **Preconditions**: User is unauthenticated OR authenticated with `role = 'user'`.
* **Trigger**: Actor attempts to access any `/admin/*` route (e.g. `/admin/knowledge-articles` or `/admin/dashboard`).
* **Main Success Flow (Security Rejection)**:
  1. Request passes into `AdminMiddleware`.
  2. If unauthenticated: Middleware redirects actor to `/login` (HTTP 302).
  3. If authenticated but `!$user->isAdmin()`: Middleware terminates request with HTTP 403 Forbidden ("Access denied. Admin only.").
* **Postconditions**: Administrative endpoints protected against unauthorized access.

---

#### UC-KB-01: Browse & Filter Knowledge Articles
* **Primary Actor**: Administrator
* **Preconditions**: Admin is authenticated.
* **Trigger**: Admin navigates to `/admin/knowledge-articles`.
* **Main Success Flow**:
  1. System executes `KnowledgeArticle::query()->with('creator')`.
  2. System applies active query parameters (`module`, `status`).
  3. System paginates records at 10 items per page with query string preservation.
  4. System computes summary pill badge counts for articles per department (`HRM`, `Payroll`, `CRM`, `Accounting`).
  5. System renders `resources/views/admin/knowledge-articles/index.blade.php`.
  6. Admin views paginated list with title, department badge, category, status pill, creator name, and action buttons.
* **Alternate / Error Flows**:
  * **2a. Empty Dataset**:
    * System displays "No knowledge articles found" with a prompt to create a new article.

---

#### UC-KB-02: Keyword Search Across Knowledge Base
* **Primary Actor**: Administrator
* **Preconditions**: Admin is viewing knowledge articles index.
* **Trigger**: Admin enters a query into the search field and submits.
* **Main Success Flow**:
  1. Request includes `?q={searchTerm}`.
  2. System invokes `scopeSearch` on `KnowledgeArticle`:
     * Matches substring against `title`, `category`, `question`, `content`, or `keywords`.
  3. System displays matching filtered articles while retaining the search term in pagination links.

---

#### UC-KB-03: Create Verified Knowledge Article
* **Primary Actor**: Administrator
* **Preconditions**: Admin is authenticated.
* **Trigger**: Admin clicks "Add New Article" (`/admin/knowledge-articles/create`).
* **Main Success Flow**:
  1. System renders article creation form with module options (`HRM`, `Payroll`, `CRM`, `Accounting`) and status options (`active`, `inactive`).
  2. Admin fills in `module`, `category`, `title`, optional `question`, `content`, optional `keywords`, and `status`.
  3. Admin clicks "Create Article".
  4. Request is validated by `KnowledgeArticleRequest`.
  5. System sets `created_by = auth()->id()`.
  6. System creates record in `knowledge_articles`.
  7. System redirects to `/admin/knowledge-articles` with flash message: `Knowledge article "{title}" was created successfully.`
* **Alternate / Error Flows**:
  * **4a. Validation Error**:
    * If required fields are missing or invalid module selected, system redirects back with errors and old input values.

---

#### UC-KB-04: View Knowledge Article Details
* **Primary Actor**: Administrator
* **Preconditions**: Knowledge article exists in the database.
* **Trigger**: Admin clicks "View" on an article row.
* **Main Success Flow**:
  1. System retrieves article eager-loading `creator`.
  2. System renders `resources/views/admin/knowledge-articles/show.blade.php`.
  3. View displays full policy text, primary question, module badge, category, keywords tags, creator name, creation date, and last updated timestamp.
* **Alternate / Error Flows**:
  * **1a. Record Not Found**:
    * System returns HTTP 404 ModelNotFoundException.

---

#### UC-KB-05: Update Knowledge Article Record
* **Primary Actor**: Administrator
* **Preconditions**: Knowledge article exists in the database.
* **Trigger**: Admin clicks "Edit" on an article row (`/admin/knowledge-articles/{id}/edit`).
* **Main Success Flow**:
  1. System displays edit form pre-populated with current article data.
  2. Admin modifies desired fields (e.g. updates content or changes status from `active` to `inactive`).
  3. Admin clicks "Save Changes" (`PUT /admin/knowledge-articles/{id}`).
  4. Request is validated via `KnowledgeArticleRequest`.
  5. System updates the article record in the database.
  6. System redirects to article details view with flash message: `Knowledge article "{title}" was updated successfully.`

---

#### UC-KB-06: Delete Knowledge Article Record
* **Primary Actor**: Administrator
* **Preconditions**: Knowledge article exists in the database.
* **Trigger**: Admin clicks "Delete" on an article row and confirms modal prompt.
* **Main Success Flow**:
  1. Form issues `DELETE /admin/knowledge-articles/{id}` with CSRF verification.
  2. System deletes record from `knowledge_articles`.
  3. System redirects to `/admin/knowledge-articles` with flash message: `Knowledge article "{title}" was deleted successfully.`
* **Postconditions**: Record removed from database. No orphaned dependencies created.

---

#### UC-AI-01: RAG Knowledge Retrieval & AI Response
* **Primary Actor**: Regular User
* **Secondary Actor**: AI Assistant Subsystem
* **Preconditions**: User is authenticated and submits a prompt on `/chat`.
* **Main Success Flow**:
  1. User submits question via AJAX to `POST /chat/send`.
  2. System stores user message in `chat_messages` table.
  3. `KnowledgeRetrievalService` retrieves active knowledge articles matching question tokens.
  4. Service builds anti-hallucination system prompt citing verified policy excerpts.
  5. `AIService` dispatches request to Gemini completions endpoint.
  6. LLM generates grounded response citing source metadata.
  7. System saves assistant response in `chat_messages` with JSON sources.
  8. User interface renders message bubble with verified source badges (`[Module] Title`).
* **Alternate / Error Flows**:
  * **3a. No Matching Knowledge Articles**:
    * System returns standard fallback without invoking the LLM to prevent hallucination.

---

#### UC-AI-02: Upstream AI Quota/Outage Policy Fallback
* **Primary Actor**: Regular User
* **Secondary Actor**: AI Assistant Subsystem
* **Preconditions**: Upstream Gemini API returns HTTP 429 (quota exhausted) or 503 (high demand).
* **Main Success Flow**:
  1. User submits query to `POST /chat/send`.
  2. `KnowledgeRetrievalService` successfully retrieves matching active knowledge articles.
  3. `AIService` calls LLM and captures HTTP 429 / 503 error.
  4. `buildPolicyFallbackAnswer` formats a friendly notice explaining upstream constraints.
  5. System appends the exact verified policy text from the retrieved knowledge article.
  6. System attaches source chips and sets `is_policy_fallback = true`.
  7. Client interface renders the `Official Knowledge Base Policy` badge, full verified policy content, and a direct "Create Support Ticket" button.
* **Postconditions**: User receives accurate, verified policy information without exposure of raw API errors or service disruption.

---

#### UC-TCK-01: Escalate Chat Session to Support Ticket
* **Primary Actor**: Regular User
* **Preconditions**: User is engaged in an active AI chat conversation.
* **Trigger**: User clicks "Create Support Ticket from this Inquiry" or "Contact Support".
* **Main Success Flow**:
  1. System opens ticket creation form (`GET /tickets/create?conversation_id={id}`).
  2. System pre-populates conversation context link.
  3. User provides subject, description, category, and priority.
  4. System saves record in `support_tickets` linking `user_id` and `conversation_id`.
  5. System redirects user to ticket details view.
* **Postconditions**: Support ticket created and linked to conversation for administrative resolution.
