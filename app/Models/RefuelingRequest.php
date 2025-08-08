<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\RefuelingRequest
 *
 * @property int $id
 * @property string $no_do
 * @property string $nopol
 * @property string $distributor_name
 * @property string $status
 * @property int $created_by
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $rejection_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \App\Models\User $creator
 * @property-read \App\Models\User|null $approver
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|RefuelingRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RefuelingRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RefuelingRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder|RefuelingRequest whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RefuelingRequest whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RefuelingRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RefuelingRequest whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RefuelingRequest whereDistributorName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RefuelingRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RefuelingRequest whereNoDo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RefuelingRequest whereNopol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RefuelingRequest whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RefuelingRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RefuelingRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RefuelingRequest pending()
 * @method static \Illuminate\Database\Eloquent\Builder|RefuelingRequest approved()
 * @method static \Illuminate\Database\Eloquent\Builder|RefuelingRequest rejected()
 * @method static \Illuminate\Database\Eloquent\Builder|RefuelingRequest completed()
 * @method static \Database\Factories\RefuelingRequestFactory factory($count = null, $state = [])
 * 
 * @mixin \Eloquent
 */
class RefuelingRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'no_do',
        'nopol',
        'distributor_name',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who created this request.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved/rejected this request.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope a query to only include pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope a query to only include completed requests.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Check if the request can be edited.
     */
    public function canBeEdited(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the request can be approved/rejected.
     */
    public function canBeReviewed(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the request can be marked as completed.
     */
    public function canBeCompleted(): bool
    {
        return $this->status === 'approved';
    }
}