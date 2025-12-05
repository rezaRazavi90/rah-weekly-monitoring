<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'name',
        'grade_id',
    ];

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }
}

