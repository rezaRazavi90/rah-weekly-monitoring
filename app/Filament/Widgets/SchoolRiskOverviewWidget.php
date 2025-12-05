<?php

namespace App\Filament\Widgets;

use App\Models\ExamResult;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;

class SchoolRiskOverviewWidget extends StatsOverviewWidget
{
    protected  ?string $heading = 'نمای کلی ریسک آموزشی';

    protected function getStats(): array
    {
        $today = now();
        $start = $today->copy()->subWeeks(2)->startOfWeek(Carbon::SATURDAY);
        $end   = $today->copy()->endOfWeek(Carbon::FRIDAY);

        $results = ExamResult::query()
            ->whereHas('exam', function ($q) use ($start, $end) {
                $q->whereBetween('exam_date', [
                    $start->toDateString(),
                    $end->toDateString(),
                ]);
            })
            ->with(['exam', 'student'])
            ->get()
            ->filter(fn ($r) => $r->exam && $r->exam->total_question > 0);

        $valid = $results->where('is_absent', false)->whereNotNull('correct_answer_count');

        $avgSchool = $valid->isEmpty()
            ? null
            : $valid->avg(fn ($r) => ($r->correct_answer_count / $r->exam->total_question) * 20);

        $failRate = $valid->isEmpty()
            ? null
            : ($valid->filter(function ($r) {
                    $score = ($r->correct_answer_count / $r->exam->total_question) * 20;
                    return $score < 10;
                })->count() / $valid->count()) * 100;

        // تعداد دانش‌آموزانی که در بازه حداقل ۳ غیبت در آزمون‌ها داشته‌اند
        /** @var Collection $highAbsentees */
        $highAbsentees = $results
            ->where('is_absent', true)
            ->groupBy('student_id')
            ->filter(fn (Collection $items) => $items->count() >= 3);

        [$label, $color] = $this->computeRiskLevel($avgSchool, $failRate, $highAbsentees->count());

        $mainStat = Stat::make('سطح ریسک مدرسه', $label)
            ->color($color)
            ->description(
                'میانگین: ' . ($avgSchool ? number_format($avgSchool, 1) : '-') .
                '، نرخ آزمون‌های زیر ۱۰: ' . ($failRate !== null ? number_format($failRate, 0) . '%' : '-') .
                '، دانش‌آموزان پرغیبت: ' . $highAbsentees->count()
            );

        return [
            $mainStat,
        ];
    }

    protected function computeRiskLevel(?float $avg, ?float $failRate, int $highAbsenteeCount): array
    {
        // خیلی ساده و قابل درک:
        // سبز: متوسط بالا، شکست کم، غیبت خاص نه
        if (
            $avg !== null && $failRate !== null &&
            $avg >= 15 && $failRate < 20 && $highAbsenteeCount === 0
        ) {
            return ['کم (وضعیت پایدار)', 'success'];
        }

        // قرمز: متوسط خیلی پایین یا شکست خیلی زیاد
        if (
            ($avg !== null && $avg < 12) ||
            ($failRate !== null && $failRate > 40) ||
            $highAbsenteeCount > 5
        ) {
            return ['بالا (نیازمند اقدام فوری)', 'danger'];
        }

        // بقیه‌ی حالات: زرد
        return ['متوسط (نیازمند مراقبت)', 'warning'];
    }
}
