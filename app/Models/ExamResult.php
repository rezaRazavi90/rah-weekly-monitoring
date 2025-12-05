<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model
{
    protected $fillable = [
        'exam_id',
        'student_id',
        'correct_answer_count',
        'is_absent',
    ];
    protected $casts = [
        'is_absent' => 'boolean',   // ⬅️ این هم کمک می‌کنه توی خروجی و فرم منطقی باشه
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}

