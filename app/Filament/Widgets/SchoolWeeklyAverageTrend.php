<?php

namespace App\Filament\Widgets;

use App\Models\ExamResult;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class SchoolWeeklyAverageTrend extends ChartWidget
{
    protected ?string $heading = 'تغییرات میانگین مدرسه در سه هفتهٔ اخیر';

    protected function getData(): array
    {
        $today = now();

        $weeks = [
            'دو هفته قبل' => [
                'start' => $today->copy()->subWeeks(2)->startOfWeek(Carbon::SATURDAY),
                'end'   => $today->copy()->subWeeks(2)->endOfWeek(Carbon::FRIDAY),
            ],
            'هفته قبل'    => [
                'start' => $today->copy()->subWeek()->startOfWeek(Carbon::SATURDAY),
                'end'   => $today->copy()->subWeek()->endOfWeek(Carbon::FRIDAY),
            ],
            'هفته جاری'   => [
                'start' => $today->copy()->startOfWeek(Carbon::SATURDAY),
                'end'   => $today->copy()->endOfWeek(Carbon::FRIDAY),
            ],
        ];

        $labels = [];
        $values = [];

        foreach ($weeks as $label => $range) {
            $results = ExamResult::query()
                ->where('is_absent', false)
                ->whereNotNull('correct_answer_count')
                ->whereHas('exam', function ($q) use ($range) {
                    $q->whereBetween('exam_date', [
                        $range['start']->toDateString(),
                        $range['end']->toDateString(),
                    ]);
                })
                ->with('exam')
                ->get()
                ->filter(fn ($r) => $r->exam && $r->exam->total_question > 0);

            if ($results->isEmpty()) {
                $avg = null;
            } else {
                $avg = $results->avg(function ($r) {
                    return ($r->correct_answer_count / $r->exam->total_question) * 20;
                });
            }

            $labels[] = $label;
            $values[] = $avg ? round($avg, 1) : null;
        }

        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'           => 'میانگین نمره مدرسه',
                    'data'            => $values,
                    'borderWidth'     => 2,
                    'pointRadius'     => 3,
                    'tension'         => 0.3,
                    'spanGaps'        => true,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
