<?php

namespace App\Filament\Widgets;

use App\Models\Exam;
use Filament\Tables;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentExamsTable extends TableWidget
{
    protected static ?string $heading = 'آخرین آزمون‌های برگزارشده';

    protected function getTableQuery(): ?Builder
    {
        return Exam::query()
            ->with(['subject.grade', 'examResults'])
            ->latest('exam_date');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('exam_date')
                ->label('تاریخ')
                ->date('Y-m-d')
                ->sortable(),
            Tables\Columns\TextColumn::make('subject.name')
                ->label('درس')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('subject.grade.name')
                ->label('پایه'),
            Tables\Columns\TextColumn::make('title')
                ->label('عنوان')
                ->limit(25),
            Tables\Columns\TextColumn::make('participants')
                ->label('شرکت‌کننده')
                ->getStateUsing(function (Exam $exam) {
                    return $exam->examResults()
                        ->where('is_absent', false)
                        ->whereNotNull('correct_answer_count')
                        ->count();
                }),
            Tables\Columns\TextColumn::make('avg_score')
                ->label('میانگین')
                ->getStateUsing(function (Exam $exam) {
                    $results = $exam->examResults()
                        ->where('is_absent', false)
                        ->whereNotNull('correct_answer_count')
                        ->get();

                    if ($results->isEmpty() || $exam->total_question <= 0) {
                        return '-';
                    }

                    $avg = $results->avg(function ($r) use ($exam) {
                        return ($r->correct_answer_count / $exam->total_question) * 20;
                    });

                    return number_format($avg, 1);
                }),
        ];
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [5, 10, 15];
    }

    protected function getDefaultTableRecordsPerPage(): int
    {
        return 10;
    }
}
