<?php
// app/Filament/Resources/Exams/RelationManagers/ExamResultsViewRelationManager.php

namespace App\Filament\Resources\Exams\RelationManagers;

use App\Models\Exam;
use App\Models\Student;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExamResultsViewRelationManager extends RelationManager
{
    protected static string $relationship = 'examResults';

    protected static ?string $title = 'نمرات آزمون';

    protected function getTableQuery(): Builder
    {
        /** @var Exam $exam */
        $exam = $this->getOwnerRecord();

        $gradeId = $exam->subject?->grade_id;

        if (! $gradeId) {
            return Student::query()->whereRaw('1 = 0');
        }

        return Student::query()
            ->where('grade_id', $gradeId)
            ->with([
                'grade',
                'examResults' => function ($query) use ($exam) {
                    $query->where('exam_id', $exam->id);
                },
            ])
            ->orderBy('student_code');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student_code')
                    ->label('کد دانش‌آموز')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('last_name')
                    ->label('نام خانوادگی')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('نام')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('grade.name')
                    ->label('پایه')
                    ->badge()
                    ->sortable(),

                TextColumn::make('correct_answer_count')
                    ->label('تعداد پاسخ درست')
                    ->state(fn (Student $record) => optional($record->examResults->first())->correct_answer_count),

                IconColumn::make('is_absent')
                    ->label('غایب')
                    ->boolean()
                    ->state(fn (Student $record) => (bool) optional($record->examResults->first())->is_absent),

                TextColumn::make('status')
                    ->label('وضعیت')
                    ->state(function (Student $record): string {
                        $result = $record->examResults->first();

                        if (! $result) {
                            return 'بدون نتیجه';
                        }

                        if ($result->is_absent) {
                            return 'غایب';
                        }

                        return 'ثبت شده';
                    })
                    ->badge()
                    ->color(function (Student $record): string {
                        $result = $record->examResults->first();

                        if (! $result) {
                            return 'warning';
                        }

                        if ($result->is_absent) {
                            return 'danger';
                        }

                        return 'success';
                    }),
            ])

            ->filters([
                Filter::make('only_missing')
                    ->label('فقط بدون نتیجه‌ها')
                    ->query(function (Builder $query): Builder {
                        /** @var Exam $exam */
                        $exam = $this->getOwnerRecord();

                        return $query->whereDoesntHave('examResults', function (Builder $q) use ($exam) {
                            $q->where('exam_id', $exam->id);
                        });
                    }),
            ])

            // ⬅️ هیچ اکشنی تعریف نمی‌کنیم؛ فقط نمایش
            ->recordActions([]);
    }
}
