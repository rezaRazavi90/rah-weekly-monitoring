<?php

namespace App\Filament\Widgets;

use App\Models\ExamResult;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class WeeklyScoreHistogram extends ChartWidget
{
    protected  ?string $heading = 'پراکندگی نمرات در هفتهٔ جاری';

    protected function getData(): array
    {
        $today = now();

        // شروع هفته از شنبه، پایان تا جمعه
        $weekStart = $today->copy()->startOfWeek(Carbon::SATURDAY);
        $weekEnd   = $today->copy()->endOfWeek(Carbon::FRIDAY);

        $results = ExamResult::query()
            ->where('is_absent', false)
            ->whereNotNull('correct_answer_count')
            ->whereHas('exam', function ($q) use ($weekStart, $weekEnd) {
                $q->whereBetween('exam_date', [$weekStart->toDateString(), $weekEnd->toDateString()]);
            })
            ->with('exam')
            ->get()
            ->filter(fn ($r) => $r->exam && $r->exam->total_question > 0);

        $buckets = [
            '0–10'  => 0,
            '10–15' => 0,
            '15–20' => 0,
        ];

        foreach ($results as $result) {
            $score = ($result->correct_answer_count / $result->exam->total_question) * 20;

            if ($score < 10) {
                $buckets['0–10']++;
            } elseif ($score < 15) {
                $buckets['10–15']++;
            } else {
                $buckets['15–20']++;
            }
        }

        return [
            'labels'   => array_keys($buckets),
            'datasets' => [
                [
                    'label' => 'تعداد آزمون‌ها',
                    'data'  => array_values($buckets),
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
