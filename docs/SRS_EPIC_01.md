# Software Requirements Specification (SRS)
## EPIC 01: Foundation & Knowledge Base

**Project**: Shofol Connect  
**Framework**: Laravel 12.x (PHP 8.2+)  
# Software Requirements Specification (SRS)
## EPIC 01: Foundation & Knowledge Base

**Project**: Shofol Connect  
**Framework**: Laravel 12.x (PHP 8.2+)  
**Document Version**: 1.3.0  
**Status**: Approved Baseline (Phases 1, 2, 3, & 4 Implemented)  
**Date**: October 2026  

---

### 1. Executive Summary & Project Overview

**Shofol Connect** is an enterprise operations and departmental knowledge management platform built with Laravel 12. The application centralizes institutional knowledge across dynamic enterprise business modules and module-linked knowledge categories, initially seeded with four foundational departments:
1. **HRM** (Human Resource Management): Leave policies, onboarding, employee benefits, PTO.
2. **Payroll**: Monthly disbursement cycles, statutory withholding (TDS), provident fund contributions.
3. **CRM** (Customer Relationship Management): Sales pipeline stages, BANT lead qualification, deal tracking.
4. **Accounting**: Customer invoice generation, payment terms, service fees, automated bank reconciliation.

The platform provides a secure web application featuring dual-role access control (`admin` and `user`), administrative knowledge management, dynamic module configuration (**SC-AI-11**), dynamic knowledge category management (**SC-AI-12**), dedicated FAQ management (**SC-AI-13**), user self-service dashboards, support ticketing, and an integrated **RAG-grounded AI Support Assistant** powered by the Google Gemini API (`gemini-3.8-flash`) with strict anti-hallucination guardrails and automated fallback policies.

---

### 2. Objectives & Scope

#### 2.1 Primary Objectives
- **Standardized Foundation**: Deliver a production-grade Laravel 12 architecture with Bootstrap 5 responsive styling, Vite bundling, and Pest automated testing.
- **Role-Based Access Control**: Secure administrative operations while providing authenticated users access to self-service resources.
- **Dynamic Module Management (SC-AI-11)**: Enable administrators to define, configure, sort, activate/deactivate, and safely manage business modules without hardcoding or code deployments.
- **Dynamic Category Management (SC-AI-12)**: Enable administrators to define, organize, and activate hierarchical topic categories strictly scoped to parent business modules, eliminating unvalidated free-text category sprawl.
- **Dedicated FAQ Management (SC-AI-13)**: Provide an administrative question-and-answer repository mapped to business modules and optional categories, seamlessly integrated into the RAG retrieval pipeline with automated deduplication.
- **Verified Knowledge Base**: Enable administrators to author, organize, search, update, and publish verified departmental policies.
- **RAG Grounding Touchpoint**: Provide structured, queryable knowledge context consumed by the AI Support Assistant and the fallback engine.
- **Auditability & Data Integrity**: Maintain creator attribution, status toggles, deletion referential integrity, and safe cascade handling across related entities.

#### 2.2 Scope Boundaries
* **In-Scope (EPIC 01 - Implemented Baseline, Phase 2, Phase 3, & Phase 4)**:
  - User authentication (registration, login, logout, password recovery, profile management).
  - RBAC with `admin` and `user` roles and `AdminMiddleware` protection.
  - User Dashboard (personal activity summary, conversation counts, ticket status).
  - Admin Dashboard (system-wide analytics, user counts, active articles, department breakdown).
  - Dynamic Shofol Module Management (**SC-AI-11**): `shofol_modules` table, Eloquent model, Admin CRUD under `/admin/modules`, status toggle, referential deletion protection, and idempotent seeder.
  - Dynamic Knowledge Category Management (**SC-AI-12**): `knowledge_categories` table, Eloquent model, Admin CRUD under `/admin/categories`, module-scoped uniqueness, status toggle, dynamic article dropdown filtering, and referential deletion protection.
  - Dedicated FAQ Management (**SC-AI-13**): `faqs` table, Eloquent model, Admin CRUD under `/admin/faqs`, module and category linkage, unique slug enforcement, dynamic dependent dropdown filtering, status toggle, idempotent seeder, and unified RAG retrieval integration.
  - Knowledge Article CRUD (dynamic module selection, module-filtered dynamic category selection, title, question, content, keywords, status).
  - Article and FAQ listings with pagination, keyword search, module filters, and status filters.
  - Backward compatibility: Preserving existing article category and module strings, allowing edits even if a category or module is deactivated.
  - Integration with RAG retrieval service (`KnowledgeRetrievalService`) and AI chat fallback.
* **Future Scope (Planned Extensions)**:
  - Public `/faqs` interactive accordion portal with instant search, category tabs, and helpfulness voting (`view_count`, `helpful_count`).

---

### 3. User Classes & System Actors

| Actor | Description | Privileges |
| :--- | :--- | :--- |
| **Guest** | Unauthenticated public visitor. | Can view public landing page (`/`), register a new account, or log in. |
| **Regular User** | Authenticated employee or enterprise user. | Can access `/dashboard`, use AI Support Chat (`/chat`), view their own conversation history (`/conversations`), submit and track support tickets (`/tickets`), and update personal profile (`/profile`). |
| **System Administrator** | Authenticated administrative personnel (`role = 'admin'`). | Possesses all Regular User privileges plus access to `/admin/dashboard`, full CRUD management of `/admin/modules`, `/admin/categories`, and `/admin/knowledge-articles`, and resolution of `/admin/tickets`. |
| **AI Assistant Subsystem** | Automated background service (`AIService` & `KnowledgeRetrievalService`). | Queries active knowledge articles, constructs grounded prompts, communicates with LLM provider, and formats policy fallbacks. |

---

### 4. Role Permissions Matrix

| Resource / Route | Guest | Regular User | Administrator |
| :--- | :---: | :---: | :---: |
| Public Welcome (`GET /`) | Allowed | Allowed | Allowed |
| Registration & Login (`/register`, `/login`) | Allowed | Redirected to Dashboard | Redirected to Admin Dashboard |
| User Dashboard (`GET /dashboard`) | Denied (302 Login) | Allowed | Allowed |
| User Profile (`/profile`) | Denied (302 Login) | Allowed (Own profile) | Allowed (Own profile) |
| AI Support Chat (`/chat`, `/chat/send`) | Denied (302 Login) | Allowed | Allowed |
| Conversation History (`/conversations`) | Denied (302 Login) | Allowed (Own records only) | Allowed (Own records only) |
| Support Tickets (`/tickets`, `/tickets/create`) | Denied (302 Login) | Allowed (Own tickets only) | Allowed |
| Admin Dashboard (`GET /admin/dashboard`) | Denied (302 Login) | Denied (403 Forbidden) | Allowed |
| Module Management List (`GET /admin/modules`) | Denied (302 Login) | Denied (403 Forbidden) | Allowed |
| Create/Store Module (`GET /POST /admin/modules`) | Denied (302 Login) | Denied (403 Forbidden) | Allowed |
| Edit/Update Module (`GET /PUT /admin/modules/{id}`) | Denied (302 Login) | Denied (403 Forbidden) | Allowed |
| Toggle Module Status (`PATCH /admin/modules/{id}/toggle`) | Denied (302 Login) | Denied (403 Forbidden) | Allowed |
| Delete Module (`DELETE /admin/modules/{id}`) | Denied (302 Login) | Denied (403 Forbidden) | Allowed (If unreferenced) |
| Category Management List (`GET /admin/categories`) | Denied (302 Login) | Denied (403 Forbidden) | Allowed |
| Create/Store Category (`GET /POST /admin/categories`) | Denied (302 Login) | Denied (403 Forbidden) | Allowed |
| Edit/Update Category (`GET /PUT /admin/categories/{id}`) | Denied (302 Login) | Denied (403 Forbidden) | Allowed |
| Toggle Category Status (`PATCH /admin/categories/{id}/toggle`) | Denied (302 Login) | Denied (403 Forbidden) | Allowed |
| Delete Category (`DELETE /admin/categories/{id}`) | Denied (302 Login) | Denied (403 Forbidden) | Allowed (If unreferenced) |
| FAQ Management List (`GET /admin/faqs`) | Denied (302 Login) | Denied (403 Forbidden) | Allowed |
| Create/Store FAQ (`GET /POST /admin/faqs`) | Denied (302 Login) | Denied (403 Forbidden) | Allowed |
| Edit/Update FAQ (`GET /PUT /admin/faqs/{id}`) | Denied (302 Login) | Denied (403 Forbidden) | Allowed |
| Toggle FAQ Status (`PATCH /admin/faqs/{id}/toggle`) | Denied (302 Login) | Denied (403 Forbidden) | Allowed |
| Delete FAQ (`DELETE /admin/faqs/{id}`) | Denied (302 Login) | Denied (403 Forbidden) | Allowed |
| Knowledge Base List (`GET /admin/knowledge-articles`) | Denied (302 Login) | Denied (403 Forbidden) | Allowed |
| Create Knowledge Article (`GET /POST /admin/knowledge-articles`) | Denied (302 Login) | Denied (403 Forbidden) | Allowed |
| View/Edit/Delete Article (`/admin/knowledge-articles/{id}`) | Denied (302 Login) | Denied (403 Forbidden) | Allowed |
| Admin Ticket Management (`/admin/tickets/{id}`) | Denied (302 Login) | Denied (403 Forbidden) | Allowed |

---

### 5. Functional Requirements

#### 5.1 Authentication & User Management (SC-AI-03 & SC-AI-04)
- **FR-AUTH-01**: The system shall allow new users to register with `name`, `email`, and `password` (hashed via Bcrypt/Argon2).
- **FR-AUTH-02**: The system shall default newly registered accounts to `role = 'user'`.
- **FR-AUTH-03**: The system shall authenticate users and redirect admins to `/admin/dashboard` and regular users to `/dashboard`.
- **FR-AUTH-04**: The system shall restrict all `/admin/*` routes to authenticated users with `role = 'admin'`, returning HTTP 403 for unauthorized users.
- **FR-AUTH-05**: The system shall allow authenticated users to update their name, email, and password, or securely delete their account.

#### 5.2 Dashboards & Reporting (SC-AI-05 & SC-AI-06)
- **FR-DASH-01**: The User Dashboard shall display personal metrics: conversation count, total tickets, open tickets, resolved tickets, recent chats, and recent tickets.
- **FR-DASH-02**: The Admin Dashboard shall aggregate real-time system metrics: total registered users, admin count, regular user count, total knowledge articles, active vs inactive articles, article distribution across active modules, total AI conversations, total chat messages, and tickets categorized by status and priority.

#### 5.3 Dynamic Shofol Module Management (SC-AI-11)
- **FR-MOD-01**: The system shall persist modules in `shofol_modules` with `id`, `name`, unique `code`, `icon`, `description`, `is_active`, `sort_order`, and timestamps.
- **FR-MOD-02**: The system shall provide an Eloquent model `App\Models\ShofolModule` with `scopeActive`, `scopeOrdered`, and relationship `knowledgeArticles()` mapping `code` to `knowledge_articles.module`.
- **FR-MOD-03**: The system shall provide an idempotent seeder (`ShofolModuleSeeder`) seeding default enterprise modules: `HRM`, `Payroll`, `CRM`, `Accounting`.
- **FR-MOD-04**: The system shall provide Admin CRUD operations at `/admin/modules` with unique name and code validation.
- **FR-MOD-05**: The system shall allow toggling activation status via `PATCH /admin/modules/{id}/toggle`.
- **FR-MOD-06**: When creating new knowledge articles, only active modules shall be selectable.
- **FR-MOD-07**: When updating existing knowledge articles, legacy or inactive modules assigned to that article shall remain valid to prevent data loss.
- **FR-MOD-08**: The system shall prevent deletion of any module if knowledge articles or FAQs reference it, returning a clear error notification.
- **FR-MOD-09**: If an administrator modifies a module's `code`, the system shall cascade the change to all referencing `knowledge_articles` to maintain data consistency.

#### 5.4 Dynamic Knowledge Category Management (SC-AI-12)
- **FR-CAT-01**: The system shall persist knowledge categories in `knowledge_categories` with `id`, `shofol_module_id` (FK to `shofol_modules` with cascade), `name`, `slug`, `description`, `is_active`, `sort_order`, and timestamps.
- **FR-CAT-02**: The system shall enforce uniqueness of category `name` and `slug` within each parent `shofol_module_id`.
- **FR-CAT-03**: The system shall provide an Eloquent model `App\Models\KnowledgeCategory` with relationships `shofolModule()` and `knowledgeArticles()`, and scopes `scopeActive()`, `scopeOrdered()`, and `scopeForModule()`.
- **FR-CAT-04**: The system shall provide an idempotent seeder (`KnowledgeCategorySeeder`) populating foundational categories across core modules (Leave Policy, Salary & Tax, Lead Management, Invoicing & Receivables, etc.).
- **FR-CAT-05**: The system shall provide Admin CRUD operations at `/admin/categories` with parent module filtering, search, and status filtering.
- **FR-CAT-06**: The system shall allow toggling category activation status via `PATCH /admin/categories/{id}/toggle`.
- **FR-CAT-07**: The knowledge article and FAQ create and edit forms shall dynamically filter category options based on the currently selected module.
- **FR-CAT-08**: The system shall validate that a newly assigned category belongs to the selected module when categories exist for that module.
- **FR-CAT-09**: When updating existing knowledge articles, legacy or inactive categories currently assigned to that article shall remain visible and selectable to prevent accidental data loss.
- **FR-CAT-10**: The system shall prevent deletion of any category if knowledge articles currently reference its name, returning an informative administrative error message.
- **FR-CAT-11**: If an administrator modifies a category's `name`, the system shall cascade the update to all referencing `knowledge_articles`.

#### 5.5 Knowledge Article Management (SC-AI-07, SC-AI-08, SC-AI-09, SC-AI-10)
- **FR-KB-01**: The system shall allow administrators to create a knowledge article specifying `module` (backed by active modules), `category` (dynamically filtered by module), `title`, `question` (optional), `content` (required policy body), `keywords` (optional comma-separated tokens), and `status` (`active` or `inactive`).
- **FR-KB-02**: The system shall automatically associate the creating administrator's ID with `created_by`.
- **FR-KB-03**: The system shall validate all inputs via `KnowledgeArticleRequest`:
  - `module`: Required, must be an active module code or current article's existing module.
  - `category`: Required, string, max 100 characters; must belong to the selected module or match current article's existing category.
  - `title`: Required, string, max 255 characters.
  - `question`: Nullable, string, max 1000 characters.
  - `content`: Required, string.
  - `keywords`: Nullable, string, max 255 characters.
  - `status`: Required, must be `active` or `inactive`.
- **FR-KB-04**: The system shall paginate article listings (10 per page) and retain query parameters during navigation.
- **FR-KB-05**: The system shall support keyword search filtering across `title`, `category`, `question`, `content`, and `keywords`.
- **FR-KB-06**: The system shall support combined filtering by `module` and `status`.
- **FR-KB-07**: The system shall allow administrators to view article details, edit all attributes, and delete articles with confirmation.
- **FR-KB-08**: Deleting an author user account shall set `knowledge_articles.created_by` to `NULL` without cascading deletion to the article.

#### 5.6 Dedicated FAQ Management (SC-AI-13)
- **FR-FAQ-01**: The system shall persist FAQs in `faqs` table with `id`, `shofol_module_id` (FK to `shofol_modules`), `knowledge_category_id` (nullable FK to `knowledge_categories`), `question`, `slug` (unique), `answer`, `keywords` (nullable), `is_active` (boolean, default true), `sort_order` (integer, default 0), `created_by` (nullable FK to `users`), and timestamps.
- **FR-FAQ-02**: The system shall provide an Eloquent model `App\Models\Faq` with relationships `shofolModule()`, `knowledgeCategory()`, `creator()`, and scopes `active()`, `ordered()`, `forModule()`, `forCategory()`, `forStatus()`, and `search()`.
- **FR-FAQ-03**: The system shall provide an idempotent seeder (`FaqSeeder`) populating verified FAQs grounded in core HRM, Payroll, CRM, and Accounting policies.
- **FR-FAQ-04**: The system shall provide Admin CRUD operations at `/admin/faqs` with pagination, text search across question/answer/keywords, and filtering by module, category, and status.
- **FR-FAQ-05**: The system shall allow administrators to toggle FAQ activation status via `PATCH /admin/faqs/{id}/toggle`.
- **FR-FAQ-06**: Dynamic module and category synchronization: Module selection is required; Category is optional. When selected, category must belong to the selected module. Create and edit forms shall dynamically update category dropdown options on module change.
- **FR-FAQ-07**: Safe update behavior: If an FAQ references a currently inactive category, editing the FAQ shall preserve the assigned category option to prevent accidental data modification.
- **FR-FAQ-08**: Integrity protection: Deleting a module referenced by an FAQ shall be blocked by application-level guard (`canBeDeleted()`). Deleting a category shall safely nullify the `knowledge_category_id` without deleting the FAQ (`nullOnDelete`).

#### 5.7 RAG Retrieval & Fallback Integration
- **FR-RAG-01**: `KnowledgeRetrievalService` shall query active records across both `KnowledgeArticle` and `Faq` where `is_active = true` / `status = 'active'`.
- **FR-RAG-02**: The retrieval service shall tokenize queries, calculate lexical relevance scores against `title`/`question`, `keywords`, `category`, `module`, and `content`/`answer`, and return ranked matches exceeding threshold 3.
- **FR-RAG-03**: The retrieval service shall automatically deduplicate matches when both an article and an FAQ address the same module/category topic with >=60% lexical overlap, prioritizing the highest-scoring source.
- **FR-RAG-04**: If upstream AI generation encounters HTTP 429 (quota exhausted), HTTP 503 (high demand), or timeout, the system shall safely deliver the verified policy excerpt from retrieved knowledge articles or FAQs with source metadata and support ticket escalation links.

---

### 6. Non-Functional Requirements

- **NFR-SEC-01 (Credential Security)**: API keys, database credentials, and session tokens must never be rendered in HTML, logged to files, or returned in JSON responses.
- **NFR-SEC-02 (SQL Injection Protection)**: All database operations must utilize Eloquent ORM or parameterized query bindings.
- **NFR-SEC-03 (Cross-Site Scripting & CSRF)**: All Blade outputs must be auto-escaped (`{{ }}` or `e()`). All POST/PUT/DELETE forms must validate CSRF tokens.
- **NFR-PERF-01 (Database Indexing)**: Indexes must exist on `knowledge_articles.module`, `knowledge_articles.status`, `shofol_modules.code`, `knowledge_categories.shofol_module_id`, `faqs.slug`, `faqs.shofol_module_id`, `faqs.knowledge_category_id`, and composite indexes to ensure sub-10ms query execution.
- **NFR-PERF-02 (Pagination)**: All listings must use database-level pagination (`LIMIT`/`OFFSET`) rather than in-memory slicing.
- **NFR-REL-01 (Transient Error Handling)**: Upstream HTTP requests must implement exponential backoff retries (2 attempts) for 429 and 503 responses, excluding 401/403/404.
- **NFR-MAINT-01 (Automated Test Coverage)**: Every controller route, authorization boundary, validation rule, and service fallback must be validated by Pest automated regression tests.

---

### 7. System Constraints & Assumptions

1. **Framework & Runtime**: Laravel 12 on PHP >= 8.2 with Composer 2.x and Node.js 18+.
2. **Dynamic Hierarchy**: Modules (`shofol_modules`), Categories (`knowledge_categories`), and FAQs (`faqs`) are dynamically managed with safe referential integrity.
3. **Database Compatibility**: Application queries must maintain strict ANSI SQL compatibility for seamless execution on both local SQLite and production MySQL 8.0+.
4. **AI Provider Constraints**: Upstream free-tier Google Gemini API imposes a limit of 20 requests per day per project for `gemini-3.8-flash`.

---

### 8. Acceptance Criteria

- [x] All 10 Knowledge Article admin CRUD routes functional and protected by `AdminMiddleware`.
- [x] All 8 Dynamic Module admin routes functional and protected by `AdminMiddleware`.
- [x] All 8 Dynamic Category admin routes functional and protected by `AdminMiddleware`.
- [x] All 8 Dedicated FAQ admin routes functional and protected by `AdminMiddleware`.
- [x] Regular users and guests receive HTTP 403 or redirect on all `/admin/*` routes.
- [x] Deletion of categories referenced by knowledge articles is blocked with descriptive feedback.
- [x] Category selection in Knowledge Article and FAQ forms dynamically filters based on the selected module.
- [x] Category validation ensures submitted category belongs to the chosen module while preserving legacy article/FAQ categories on edit.
- [x] Idempotent seeders create default modules, categories, and FAQs without duplication.
- [x] RAG retrieval accurately grounds policy responses for both knowledge articles and FAQs with automated deduplication.
- [x] Active FAQs are retrievable; inactive FAQs are excluded from normal RAG retrieval.
- [x] Full test suite passes without regressions.
