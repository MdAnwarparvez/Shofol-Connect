<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicket extends Model
{
    use HasFactory;

    /**
     * Supported ticket categories.
     */
    public const CATEGORIES = [
        'HRM',
        'Payroll',
        'CRM',
        'Accounting',
        'Technical',
        'Other',
    ];

    /**
     * Supported ticket priorities.
     */
    public const PRIORITIES = [
        'Low',
        'Medium',
        'High',
    ];

    /**
     * Supported ticket statuses.
     */
    public const STATUSES = [
        'Open',
        'In Progress',
        'Resolved',
        'Closed',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'assigned_to',
        'conversation_id',
        'subject',
        'description',
        'category',
        'priority',
        'status',
        'admin_response',
        'resolved_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * The user who submitted the ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The related chat conversation, if escalated from AI Assistant.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * The administrative staff member assigned to resolve this ticket.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Check if the ticket is currently active / open.
     */
    public function isOpen(): bool
    {
        return in_array($this->status, ['Open', 'In Progress'], true);
    }

    /**
     * Check if the ticket has been resolved or closed.
     */
    public function isResolved(): bool
    {
        return in_array($this->status, ['Resolved', 'Closed'], true);
    }

    /**
     * Scope for status filter.
     */
    public function scopeForStatus(Builder $query, ?string $status): Builder
    {
        if (!empty($status) && in_array($status, self::STATUSES, true)) {
            return $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Scope for category filter.
     */
    public function scopeForCategory(Builder $query, ?string $category): Builder
    {
        if (!empty($category) && in_array($category, self::CATEGORIES, true)) {
            return $query->where('category', $category);
        }

        return $query;
    }

    /**
     * Scope for priority filter.
     */
    public function scopeForPriority(Builder $query, ?string $priority): Builder
    {
        if (!empty($priority) && in_array($priority, self::PRIORITIES, true)) {
            return $query->where('priority', $priority);
        }

        return $query;
    }

    /**
     * Scope for assigned staff filter.
     */
    public function scopeForAssignedTo(Builder $query, mixed $assignedTo): Builder
    {
        if ($assignedTo !== null && $assignedTo !== '') {
            if ($assignedTo === 'unassigned') {
                return $query->whereNull('assigned_to');
            }
            return $query->where('assigned_to', $assignedTo);
        }

        return $query;
    }

    /**
     * Scope for searching tickets.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('subject', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('admin_response', 'like', "%{$term}%")
              ->orWhereHas('user', function (Builder $userQuery) use ($term) {
                  $userQuery->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");
              });
        });
    }

    /**
     * CSS badge class for status.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'Open' => 'bg-danger-subtle text-danger border border-danger-subtle',
            'In Progress' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'Resolved' => 'bg-success-subtle text-success border border-success-subtle',
            'Closed' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
            default => 'bg-secondary text-white',
        };
    }

    /**
     * CSS badge class for priority.
     */
    public function getPriorityBadgeClassAttribute(): string
    {
        return match ($this->priority) {
            'High' => 'bg-danger text-white',
            'Medium' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'Low' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
            default => 'bg-secondary text-white',
        };
    }
}
