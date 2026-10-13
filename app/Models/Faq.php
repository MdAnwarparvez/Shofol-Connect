<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Faq extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'faqs';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'shofol_module_id',
        'knowledge_category_id',
        'question',
        'slug',
        'answer',
        'keywords',
        'embedding',
        'is_active',
        'sort_order',
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
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'shofol_module_id' => 'integer',
            'knowledge_category_id' => 'integer',
            'created_by' => 'integer',
            'embedding' => 'array',
        ];
    }

    /**
     * The Shofol business module this FAQ belongs to.
     */
    public function shofolModule(): BelongsTo
    {
        return $this->belongsTo(ShofolModule::class, 'shofol_module_id');
    }

    /**
     * Alias for shofolModule relationship.
     */
    public function module(): BelongsTo
    {
        return $this->shofolModule();
    }

    /**
     * The optional Knowledge category this FAQ is grouped under.
     */
    public function knowledgeCategory(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, 'knowledge_category_id');
    }

    /**
     * Alias for knowledgeCategory relationship.
     */
    public function category(): BelongsTo
    {
        return $this->knowledgeCategory();
    }

    /**
     * The administrator user who created this FAQ.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Accessor for title to ensure uniform interface with KnowledgeArticle in RAG retrieval.
     */
    public function getTitleAttribute(): string
    {
        return (string) $this->question;
    }

    /**
     * Accessor for content to ensure uniform interface with KnowledgeArticle in RAG retrieval.
     */
    public function getContentAttribute(): string
    {
        return (string) $this->answer;
    }

    /**
     * Accessor for module name string to ensure uniform interface with KnowledgeArticle in RAG retrieval.
     */
    public function getModuleAttribute(): string
    {
        return (string) ($this->shofolModule?->name ?? $this->shofolModule?->code ?? '');
    }

    /**
     * Accessor for category name string to ensure uniform interface with KnowledgeArticle in RAG retrieval.
     */
    public function getCategoryAttribute(): string
    {
        return (string) ($this->knowledgeCategory?->name ?? '');
    }

    /**
     * Scope to only include active FAQs.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order FAQs by module hierarchy first, sort_order ascending, then id ascending.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('shofol_module_id', 'asc')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc');
    }

    /**
     * Scope to filter FAQs by module ID or module code.
     */
    public function scopeForModule(Builder $query, int|string|null $module): Builder
    {
        if (empty($module) || $module === 'all') {
            return $query;
        }

        if (is_numeric($module)) {
            return $query->where('shofol_module_id', (int) $module);
        }

        return $query->whereHas('shofolModule', function (Builder $q) use ($module) {
            $q->where('code', $module)->orWhere('name', $module);
        });
    }

    /**
     * Scope to filter FAQs by category ID or category slug/name.
     */
    public function scopeForCategory(Builder $query, int|string|null $category): Builder
    {
        if (empty($category) || $category === 'all') {
            return $query;
        }

        if (is_numeric($category)) {
            return $query->where('knowledge_category_id', (int) $category);
        }

        return $query->whereHas('knowledgeCategory', function (Builder $q) use ($category) {
            $q->where('slug', $category)->orWhere('name', $category);
        });
    }

    /**
     * Scope to filter FAQs by status ('active', 'inactive', '1', '0').
     */
    public function scopeForStatus(Builder $query, ?string $status): Builder
    {
        if ($status === null || $status === '' || $status === 'all') {
            return $query;
        }

        $normalized = strtolower(trim((string) $status));

        if ($normalized === 'active' || $normalized === '1') {
            return $query->where('is_active', true);
        }

        if ($normalized === 'inactive' || $normalized === '0') {
            return $query->where('is_active', false);
        }

        return $query;
    }

    /**
     * Scope to search FAQs across question, answer, keywords, and slug.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search) || trim($search) === '') {
            return $query;
        }

        $term = trim($search);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('question', 'like', "%{$term}%")
              ->orWhere('answer', 'like', "%{$term}%")
              ->orWhere('keywords', 'like', "%{$term}%")
              ->orWhere('slug', 'like', "%{$term}%");
        });
    }

    /**
     * Boot the model. Automatically generate unique slug if not provided.
     */
    protected static function booted(): void
    {
        static::saving(function (Faq $faq) {
            if (empty($faq->slug)) {
                $baseSlug = Str::slug($faq->question);
                if (empty($baseSlug)) {
                    $baseSlug = 'faq-' . Str::random(8);
                }

                $slug = $baseSlug;
                $count = 1;
                while (static::where('slug', $slug)->where('id', '!=', $faq->id ?? 0)->exists()) {
                    $count++;
                    $slug = "{$baseSlug}-{$count}";
                }

                $faq->slug = $slug;
            }
        });

        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('kb_active_faqs');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('kb_active_faqs');
        });
    }
}
