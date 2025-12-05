<?php

namespace App\Filament\Widgets;

use App\Models\ExamResult;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class WeeklyAbsencesByWeekday extends ChartWidget
{
    protected ?string $heading = 'غیبت‌ها به تفکیک روز هفته (۳۰ روز اخیر)';

    protected function getData(): array
    {
        $from = now()->subDays(30);

        $results = ExamResult::query()
            ->where('is_absent', true)
            ->whereHas('exam', function ($q) use ($from) {
                $q->where('exam_date', '>=', $from->toDateString());
            })
            ->with('exam')
            ->get();

        // ۰=یکشنبه در Carbon، ولی ما بر اساس exam_date->dayOfWeek می‌ریم و خودمون map می‌کنیم
        $labels = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];
        $counts = array_fill(0, 7, 0);

        foreach ($results as $result) {
            if (! $result->exam) {
                continue;
            }

            $date = Carbon::parse($result->exam->exam_date);
            $dayIndex = $date->dayOfWeek; // 0–6

            $counts[$dayIndex]++;
        }

        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label' => 'تعداد غیبت در آزمون‌ها',
                    'data'  => $counts,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
