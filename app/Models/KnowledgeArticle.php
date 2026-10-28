<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeArticle extends Model
{
    use HasFactory;

    /**
     * Supported modules in Shofol Connect.
     */
    public const MODULES = [
        'HRM',
        'Payroll',
        'CRM',
        'Accounting',
        'Inventory',
        'General',
        'POS',
        'Field-Automation',
    ];

    /**
     * Supported statuses.
     */
    public const STATUSES = [
        'active',
        'inactive',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'module',
        'category',
        'title',
        'question',
        'content',
        'keywords',
        'embedding',
        'status',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'embedding' => 'array',
        ];
    }

    /**
     * Boot model events for cache invalidation.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('kb_active_articles');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('kb_active_articles');
        });
    }

    /**
     * Get the user who created this knowledge article.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the dynamic Shofol module associated with this article by module code.
     */
    public function shofolModule(): BelongsTo
    {
        return $this->belongsTo(ShofolModule::class, 'module', 'code');
    }

    /**
     * Get the category record associated with this article by category name.
     */
    public function knowledgeCategory(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, 'category', 'name');
    }

    /**
     * Get available module choices for knowledge article forms.
     * Returns active database modules, falling back to legacy constants if unseeded,
     * and guarantees inclusion of the current article's existing module for safe edits.
     *
     * @param string|null $currentModule
     * @return list<string>
     */
    public static function getAvailableModules(?string $currentModule = null): array
    {
        $activeCodes = [];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('shofol_modules')) {
                $activeCodes = ShofolModule::active()->ordered()->pluck('code')->toArray();
            }
        } catch (\Throwable $e) {
            $activeCodes = self::MODULES;
        }

        if (empty($activeCodes)) {
            $activeCodes = self::MODULES;
        }

        if (!empty($currentModule) && !in_array($currentModule, $activeCodes, true)) {
            $activeCodes[] = $currentModule;
        }

        return array_values(array_unique($activeCodes));
    }

    /**
     * Get available category choices for knowledge article forms filtered by module.
     * Always ensures the article's existing category is included to prevent data loss.
     *
     * @param string|null $module
     * @param string|null $currentCategory
     * @return list<string>
     */
    public static function getAvailableCategories(?string $module = null, ?string $currentCategory = null): array
    {
        $categories = [];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('knowledge_categories')) {
                $query = KnowledgeCategory::query()->active()->ordered();
                if (!empty($module)) {
                    $query->forModule($module);
                }
                $categories = $query->pluck('name')->toArray();
            }
        } catch (\Throwable $e) {
            // Graceful fallback
        }

        if (!empty($currentCategory) && !in_array($currentCategory, $categories, true)) {
            $categories[] = $currentCategory;
        }

        return array_values(array_unique($categories));
    }

    /**
     * Get all active categories grouped by module code for dynamic frontend filtering.
     *
     * @return array<string, list<string>>
     */
    public static function getCategoriesGroupedByModule(): array
    {
        $grouped = [];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('knowledge_categories')) {
                $categories = KnowledgeCategory::with('shofolModule')->active()->ordered()->get();
                foreach ($categories as $cat) {
                    $code = $cat->shofolModule?->code ?? 'General';
                    $grouped[$code][] = $cat->name;
                }
            }
        } catch (\Throwable $e) {
            // Graceful fallback
        }

        return $grouped;
    }

    /**
     * Accessor alias: allow accessing content as answer.
     */
    public function getAnswerAttribute(): ?string
    {
        return $this->content;
    }

    /**
     * Mutator alias: allow setting answer as content.
     */
    public function setAnswerAttribute(?string $value): void
    {
        $this->attributes['content'] = $value;
    }

    /**
     * Scope for active articles.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by module.
     */
    public function scopeForModule(Builder $query, ?string $module): Builder
    {
        if (!empty($module)) {
            return $query->where('module', $module);
        }

        return $query;
    }

    /**
     * Scope to filter by status.
     */
    public function scopeForStatus(Builder $query, ?string $status): Builder
    {
        if (!empty($status) && in_array($status, self::STATUSES, true)) {
            return $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Scope for searching across title, question, content, category, and keywords.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('category', 'like', "%{$term}%")
              ->orWhere('question', 'like', "%{$term}%")
              ->orWhere('content', 'like', "%{$term}%")
              ->orWhere('keywords', 'like', "%{$term}%");
        });
    }
}
