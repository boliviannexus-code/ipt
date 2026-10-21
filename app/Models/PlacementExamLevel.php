<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlacementExamLevel extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(PlacementExam::class, 'placement_exam_id');
    }
}
