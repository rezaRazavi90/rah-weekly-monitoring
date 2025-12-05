<?php

namespace App\Filament\Widgets;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Grade;
use App\Models\Student;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;

class GradeAverageByGradeWidget extends StatsOverviewWidget
{
    protected  ?string $heading = 'میانگین نمرات به تفکیک پایه';

    protected function getStats(): array
    {
        // همه نتایج همراه با نمره
        $results = ExamResult::query()
            ->where('is_absent', false)
            ->whereNotNull('correct_answer_count')
            ->with(['exam', 'student.grade'])
            ->get()
            ->filter(fn (ExamResult $result) => $result->exam && $result->exam->total_question > 0)
            ->map(function (ExamResult $result) {
                $score = ($result->correct_answer_count / $result->exam->total_question) * 20;

                return [
                    'grade_id' => $result->student?->grade_id,
                    'score'    => $score,
                ];
            })
            ->filter(fn ($row) => $row['grade_id'] !== null);

        /** @var Collection $grouped */
        $grouped = $results->groupBy('grade_id')->map(function (Collection $items) {
            return $items->avg('score');
        });

        $grades = Grade::query()
            ->whereIn('id', $grouped->keys())
            ->get()
            ->keyBy('id');

        $stats = [];

        foreach ($grouped as $gradeId => $avg) {
            $grade = $grades->get($gradeId);

            if (! $grade) {
                continue;
            }

            $color = $this->getColorForAverage($avg);

            $stats[] = Stat::make(
                'پایه ' . $grade->name,
                number_format($avg, 1) . ' / 20'
            )->color($color);
        }

        return $stats;
    }

    protected function getColorForAverage(float $avg): string
    {
        // 15 تا 20 سبز – 12 تا 15 نارنجی – زیر 12 قرمز
        if ($avg >= 15) {
            return 'success';
        }

        if ($avg >= 12) {
            return 'warning';
        }

        return 'danger';
    }
}
