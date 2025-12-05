<?php

namespace App\Filament\Widgets;

use App\Models\ExamResult;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

class ProblematicSubjectsThisWeek extends TableWidget
{
    protected static ?string $heading = 'درس‌های مسئله‌دار این هفته';

    /**
     * تعریف جدول به سبک Filament 4
     */
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subject')
                    ->label('درس')
                    ->sortable(),

                Tables\Columns\TextColumn::make('grade')
                    ->label('پایه')
                    ->sortable(),

                Tables\Columns\TextColumn::make('avg_score')
                    ->label('میانگین')
                    ->sortable()
                    ->formatStateUsing(fn ($state) =>
                    number_format((float) $state, 1)
                    ),

                Tables\Columns\TextColumn::make('exam_count')
                    ->label('تعداد آزمون'),
            ])

            // دیتای جدول را دستی می‌سازیم
            ->records(fn () => $this->getRows())

            // حداکثر ۵ درس دارید، صفحه‌بندی لازم نیست
            ->paginated(false);
    }

    /**
     * ساخت سطرهای جدول برای «درس‌های مسئله‌دار»
     *
     * معیار: پایین‌ترین میانگین نمره در هفته جاری، حداکثر ۵ درس.
     */
    protected function getRows(): Collection
    {
        $today = now();
        $weekStart = $today->copy()->startOfWeek(Carbon::SATURDAY);
        $weekEnd   = $today->copy()->endOfWeek(Carbon::FRIDAY);

        $results = ExamResult::query()
            ->where('is_absent', false)
            ->whereNotNull('correct_answer_count')
            ->whereHas('exam', function ($q) use ($weekStart, $weekEnd) {
                $q->whereBetween('exam_date', [
                    $weekStart->toDateString(),
                    $weekEnd->toDateString(),
                ]);
            })
            ->with(['exam.subject.grade'])
            ->get()
            // فقط امتحان‌هایی که تعداد سؤال دارند
            ->filter(fn ($r) => $r->exam && $r->exam->total_question > 0);

        $grouped = $results
            ->groupBy(fn ($r) => $r->exam->subject_id)
            ->map(function (Collection $items, $subjectId) {
                $first   = $items->first();
                $subject = $first->exam->subject;
                $grade   = $subject->grade;

                $avgScore = $items->avg(function ($r) {
                    /** @var \App\Models\ExamResult $r */
                    return ($r->correct_answer_count / $r->exam->total_question) * 20;
                });

                return [
                    // 🔑 کلید یکتا برای هر درس
                    'key'        => $subjectId,

                    'subject'    => $subject->name ?? '-',
                    'grade'      => $grade->name ?? '-',
                    'avg_score'  => $avgScore,
                    'exam_count' => $items->count(),
                ];
            });

        // کمترین میانگین‌ها، حداکثر ۵ درس
        return $grouped
            ->sortBy('avg_score')
            ->take(5)
            ->values();
    }
}
