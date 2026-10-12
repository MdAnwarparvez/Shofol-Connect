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
        'status',
        'created_by',
    ];

    /**
     * Get the user who created this knowledge article.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
        if (!empty($module) && in_array($module, self::MODULES, true)) {
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
