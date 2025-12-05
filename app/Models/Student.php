<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'name',
        'last_name',
        'student_code',
        'grade_id',
    ];

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function examResults()
    {
        return $this->hasMany(ExamResult::class);
    }
}

