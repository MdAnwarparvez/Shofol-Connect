<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShofolModule extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shofol_modules';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'icon',
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
     * Scope to only include active modules.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order modules by sort_order ascending, then name ascending.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    /**
     * Knowledge articles referencing this module by its code.
     */
    public function knowledgeArticles(): HasMany
    {
        return $this->hasMany(KnowledgeArticle::class, 'module', 'code');
    }

    /**
     * Alias for knowledge articles relationship.
     */
    public function articles(): HasMany
    {
        return $this->knowledgeArticles();
    }

    /**
     * Categories defined under this module.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(KnowledgeCategory::class, 'shofol_module_id')->ordered();
    }

    /**
     * FAQs defined under this module.
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class, 'shofol_module_id')->ordered();
    }

    /**
     * Check if the module can be safely deleted (no knowledge articles or FAQs referencing it).
     */
    public function canBeDeleted(): bool
    {
        return $this->knowledgeArticles()->count() === 0 && $this->faqs()->count() === 0;
    }
}
