<?php

namespace App\Filament\Widgets;

use App\Models\ExamResult;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

class TopImprovingStudents extends TableWidget
{
    protected static ?string $heading = 'دانش‌آموزان با بیشترین پیشرفت (هفته جاری نسبت به هفته قبل)';

    /**
     * تعریف جدول در سبک Filament 4
     */
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student_code')
                    ->label('کد'),

                Tables\Columns\TextColumn::make('name')
                    ->label('نام'),

                Tables\Columns\TextColumn::make('grade')
                    ->label('پایه'),

                Tables\Columns\TextColumn::make('prev_avg')
                    ->label('هفته قبل')
                    ->formatStateUsing(fn ($state) =>
                    $state !== null ? number_format((float) $state, 1) : '-'
                    ),

                Tables\Columns\TextColumn::make('current_avg')
                    ->label('هفته جاری')
                    ->formatStateUsing(fn ($state) =>
                    $state !== null ? number_format((float) $state, 1) : '-'
                    ),

                Tables\Columns\TextColumn::make('diff')
                    ->label('بهبود')
                    ->formatStateUsing(function ($state) {
                        if ($state === null) {
                            return '-';
                        }

                        $value = (float) $state;

                        return ($value >= 0 ? '+' : '') . number_format($value, 1);
                    }),
            ])

            // رکوردها را خودمان به‌صورت Collection از آرایه‌ها می‌سازیم
            ->records(fn () => $this->getRows())

            // این جدول کوچک است، نیاز به صفحه‌بندی نیست
            ->paginated(false);
    }

    /**
     * ساخت آرایه‌ی سطرها برای جدول
     *
     * هر سطر باید یک key یکتا داشته باشد تا Filament غر نزند.
     */
    protected function getRows(): Collection
    {
        $today = now();

        // هفته جاری: از شنبه تا جمعه (مدرسه تو پنج‌شنبه/جمعه امتحان ندارد، مشکلی نیست)
        $currentStart = $today->copy()->startOfWeek(Carbon::SATURDAY);
        $currentEnd   = $today->copy()->endOfWeek(Carbon::FRIDAY);

        // هفته قبل: یک هفته قبلِ بازه‌ی بالا
        $prevStart = $currentStart->copy()->subWeek();
        $prevEnd   = $currentEnd->copy()->subWeek();

        // میانگین‌های دانش‌آموزان در هر بازه
        $current = $this->getStudentAveragesForRange($currentStart, $currentEnd);
        $prev    = $this->getStudentAveragesForRange($prevStart, $prevEnd);

        // همه‌ی دانش‌آموزانی که در یکی از دو بازه نتیجه دارند
        $students = $current->keys()
            ->merge($prev->keys())
            ->unique();

        $rows = $students->map(function ($studentId) use ($current, $prev) {
            // فقط کسانی که در هر دو هفته امتحان داده‌اند
            if (! isset($current[$studentId], $prev[$studentId])) {
                return null;
            }

            $c = $current[$studentId]; // ['student' => Student, 'avg' => ...]
            $p = $prev[$studentId];

            $diff = $c['avg'] - $p['avg'];

            // فقط پیشرفت مثبت را نشان بده
            if ($diff <= 0) {
                return null;
            }

            $student = $c['student'];

            return [
                // 🔑 کلید یکتا برای هر سطر (خیلی مهم برای Filament)
                'key'          => $student->id,

                'student_id'   => $student->id,
                'student_code' => $student->student_code,
                'name'         => $student->last_name . ' ' . $student->name,
                'grade'        => $student->grade?->name ?? '-',
                'prev_avg'     => $p['avg'],
                'current_avg'  => $c['avg'],
                'diff'         => $diff,
            ];
        })->filter(); // null ها را حذف می‌کنیم

        // مرتب‌سازی بر اساس بیشترین پیشرفت و محدود به ۱۰ نفر
        return $rows
            ->sortByDesc('diff')
            ->take(10)
            ->values();
    }

    /**
     * محاسبه‌ی میانگین نمره‌ی هر دانش‌آموز در یک بازه‌ی تاریخی
     *
     * خروجی: Collection به شکل [student_id => ['student' => Student, 'avg' => float]]
     */
    protected function getStudentAveragesForRange(Carbon $start, Carbon $end): Collection
    {
        $results = ExamResult::query()
            ->where('is_absent', false)
            ->whereNotNull('correct_answer_count')
            ->whereHas('exam', function ($q) use ($start, $end) {
                $q->whereBetween('exam_date', [
                    $start->toDateString(),
                    $end->toDateString(),
                ]);
            })
            ->with(['exam', 'student.grade'])
            ->get()
            // مطمئن شو امتحان تعداد سؤال دارد
            ->filter(fn ($r) => $r->exam && $r->exam->total_question > 0);

        return $results
            ->groupBy('student_id')
            ->map(function (Collection $items) {
                $student = $items->first()->student;

                $avg = $items->avg(function ($r) {
                    /** @var \App\Models\ExamResult $r */
                    return ($r->correct_answer_count / $r->exam->total_question) * 20;
                });

                return [
                    'student' => $student,
                    'avg'     => $avg,
                ];
            });
    }
}
