<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProgramGradingScheme extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'program_id', 'version', 'passing_score', 'status', 'is_active', 'finalized_by', 'finalized_at'];

    protected function casts(): array
    {
        return ['version' => 'integer', 'passing_score' => 'decimal:2', 'is_active' => 'boolean', 'finalized_at' => 'immutable_datetime'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(ProgramGradingComponent::class)->orderBy('position');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function isFinalized(): bool
    {
        return $this->status === 'finalized';
    }
}
