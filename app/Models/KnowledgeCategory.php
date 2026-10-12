<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class KnowledgeCategory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'knowledge_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'shofol_module_id',
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
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
        ];
    }

    /**
     * The Shofol module this category belongs to.
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
     * Knowledge articles referencing this category by name.
     */
    public function knowledgeArticles(): HasMany
    {
        return $this->hasMany(KnowledgeArticle::class, 'category', 'name');
    }

    /**
     * Alias for knowledgeArticles relationship.
     */
    public function articles(): HasMany
    {
        return $this->knowledgeArticles();
    }

    /**
     * FAQs defined under this category.
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class, 'knowledge_category_id')->ordered();
    }

    /**
     * Scope to only include active categories.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order categories by module hierarchy first, sort_order ascending, then name ascending.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('shofol_module_id', 'asc')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc');
    }

    /**
     * Scope to filter categories by module ID or module code.
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
            $q->where('code', $module);
        });
    }

    /**
     * Scope to search categories across name, slug, and description.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search) || trim($search) === '') {
            return $query;
        }

        $term = trim($search);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('slug', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /**
     * Scope to filter categories by publication status ('active', 'inactive', '1', '0').
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
     * Check if the category can be safely deleted (no knowledge articles referencing it).
     */
    public function canBeDeleted(): bool
    {
        return $this->knowledgeArticles()->count() === 0;
    }

    /**
     * Boot the model. Automatically generate slug if not provided.
     */
    protected static function booted(): void
    {
        static::saving(function (KnowledgeCategory $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
}
