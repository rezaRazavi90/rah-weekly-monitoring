<?php

namespace App\Filament\Widgets;

use App\Models\ExamResult;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

class TopAbsentStudents extends TableWidget
{
    protected static ?string $heading = 'دانش‌آموزان با بیشترین غیبت (کل داده‌ها)';

    /**
     * تعریف جدول به سبک Filament 4
     */
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student_code')
                    ->label('کد')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('نام')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('grade')
                    ->label('پایه')
                    ->sortable(),

                Tables\Columns\TextColumn::make('absent_count')
                    ->label('تعداد غیبت'),
            ])

            // رکوردها را خودمان می‌سازیم (Collection از آرایه‌ها)
            ->records(fn () => $this->getRows())

            // لیست کوچیکه، صفحه‌بندی لازم نیست
            ->paginated(false);
    }

    /**
     * ساخت سطرهای جدول (هر سطر: یک دانش‌آموز + تعداد غیبت)
     */
    protected function getRows(): Collection
    {
        return ExamResult::query()
            ->where('is_absent', true)
            ->with('student.grade')
            ->get()
            ->groupBy('student_id')
            ->map(function (Collection $items, $studentId) {
                $student = $items->first()->student;

                return [
                    // 🔑 کلید یکتا برای هر ردیف (خیلی مهم برای Filament)
                    'key'          => $studentId,

                    'student_id'   => $student?->id,
                    'student_code' => $student?->student_code ?? '-',
                    'name'         => $student
                        ? ($student->last_name . ' ' . $student->name)
                        : '-',
                    'grade'        => $student?->grade?->name ?? '-',
                    'absent_count' => $items->count(),
                ];
            })
            ->sortByDesc('absent_count')
            ->take(10)
            ->values();
    }
}
