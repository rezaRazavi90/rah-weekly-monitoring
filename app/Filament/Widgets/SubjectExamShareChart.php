<?php

namespace App\Filament\Widgets;

use App\Models\Exam;
use Filament\Widgets\ChartWidget;

class SubjectExamShareChart extends ChartWidget
{
    protected ?string $heading = 'سهم هر درس از کل آزمون‌های برگزارشده';

    protected function getData(): array
    {
        $exams = Exam::query()
            ->with('subject')
            ->get();

        $grouped = $exams->groupBy('subject_id')->map(function ($items) {
            $subject = $items->first()->subject;

            return [
                'subject' => $subject?->name ?? '-',
                'count'   => $items->count(),
            ];
        })->values();

        $labels = $grouped->pluck('subject')->toArray();
        $data   = $grouped->pluck('count')->toArray();

        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label' => 'تعداد آزمون',
                    'data'  => $data,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
