<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'subject_id',
        'exam_date',
        'total_question',
        'title',
    ];
    protected $casts = [
        'exam_date' => 'date', // یا 'immutable_date' اگر دوست داری
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function examResults()
    {
        return $this->hasMany(ExamResult::class);
    }

    /**
     * تعداد دانش‌آموزهای این پایه که هنوز نتیجه برای این آزمون ندارند.
     */

    public function missingResultsCount(): int
    {
        $grade = $this->subject?->grade;

        if (! $grade) {
            return 0;
        }

        $totalStudents = $grade->students()->count();
        $withResults   = $this->examResults()->count();

        return max(0, $totalStudents - $withResults);
    }
}

