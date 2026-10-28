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
     * Boot model events for lifecycle safety and protection.
     */
    protected static function booted(): void
    {
        static::deleting(function (ShofolModule $module) {
            if (!$module->canBeDeleted()) {
                $descriptions = $module->getReferencingRecordDescriptions();
                $reason = implode(', ', $descriptions);
                throw new \RuntimeException("Cannot delete module '{$module->name}' ({$module->code}) because {$reason} currently referencing it.");
            }
        });
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
     * Check if the module can be safely deleted (no knowledge articles, categories, or FAQs referencing it).
     */
    public function canBeDeleted(): bool
    {
        return $this->knowledgeArticles()->count() === 0
            && $this->categories()->count() === 0
            && $this->faqs()->count() === 0;
    }

    /**
     * Get a descriptive list of related records blocking deletion.
     *
     * @return list<string>
     */
    public function getReferencingRecordDescriptions(): array
    {
        $parts = [];

        $articleCount = $this->knowledgeArticles()->count();
        if ($articleCount > 0) {
            $parts[] = "{$articleCount} knowledge " . ($articleCount === 1 ? 'article is' : 'articles are');
        }

        $categoryCount = $this->categories()->count();
        if ($categoryCount > 0) {
            $parts[] = "{$categoryCount} knowledge " . ($categoryCount === 1 ? 'category is' : 'categories are');
        }

        $faqCount = $this->faqs()->count();
        if ($faqCount > 0) {
            $parts[] = "{$faqCount} " . ($faqCount === 1 ? 'FAQ is' : 'FAQs are');
        }

        return $parts;
    }
}
