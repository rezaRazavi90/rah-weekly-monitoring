<?php

namespace App\Filament\Resources\Exams\RelationManagers;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExamResultsRelationManager extends RelationManager
{
    /**
     * اسم رابطه روی مدل Exam مهم نیست، چون getTableQuery را override می‌کنیم،
     * ولی باید یک مقدار معتبر باشد.
     */
    protected static string $relationship = 'examResults';

    protected static ?string $title = 'نمرات آزمون';

    /**
     * رکوردهای جدول: دانش‌آموزانِ این پایه (نه exam_results)
     */
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
                    ->state(function (Student $record) {
                        $result = $record->examResults->first();
                        return $result?->correct_answer_count;
                    }),

                IconColumn::make('is_absent')
                    ->label('غایب')
                    ->boolean()
                    ->state(function (Student $record) {
                        $result = $record->examResults->first();
                        return (bool) ($result?->is_absent ?? false);
                    }),

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

            // فیلتر: فقط دانش‌آموزانِ بدون نتیجه (نه غایب، نه نمره)
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

            ->recordActions([
                // ۱) ثبت / ویرایش نمره (بدون غیبت)
                Action::make('edit_result')
                    ->label(function (Student $record): string {
                        $result = $record->examResults->first();
                        return $result?->is_absent || $result?->correct_answer_count === null
                            ? 'ثبت نمره'
                            : 'ویرایش نمره';
                    })
                    ->color('primary')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading(function (Student $record): string {
                        return sprintf(
                            'ثبت / ویرایش نمره برای %s %s',
                            $record->last_name,
                            $record->name,
                        );
                    })
                    ->form(function (): array {
                        return [
                            TextInput::make('correct_answer_count')
                                ->label('تعداد پاسخ درست')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->helperText('فقط عددی بین 0 و تعداد سؤال‌های آزمون.'),
                        ];
                    })
                    ->mountUsing(function (Action $action, Student $record): void {
                        $result = $record->examResults->first();

                        $action->fillForm([
                            'correct_answer_count' => $result && ! $result->is_absent
                                ? $result->correct_answer_count
                                : null,
                        ]);
                    })
                    ->action(function (Student $record, array $data): void {
                        /** @var Exam $exam */
                        $exam = $this->getOwnerRecord();

                        $raw = $data['correct_answer_count'] ?? null;

                        if (! is_numeric($raw)) {
                            Notification::make()
                                ->title('ثبت نمره انجام نشد.')
                                ->body('تعداد پاسخ درست باید عددی باشد.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $correct = (int) $raw;
                        $total   = (int) $exam->total_question;

                        if ($correct < 0 || $correct > $total) {
                            Notification::make()
                                ->title('ثبت نمره انجام نشد.')
                                ->body("تعداد پاسخ درست باید بین 0 و {$total} باشد.")
                                ->danger()
                                ->send();

                            return;
                        }

                        $existing = $record->examResults
                            ->firstWhere('exam_id', $exam->id);

                        if ($existing) {
                            $existing->update([
                                'correct_answer_count' => $correct,
                                'is_absent'            => false,
                            ]);
                        } else {
                            ExamResult::create([
                                'exam_id'              => $exam->id,
                                'student_id'           => $record->id,
                                'correct_answer_count' => $correct,
                                'is_absent'            => false,
                            ]);
                        }

                        // ریفرش رابطه برای جدول
                        $record->unsetRelation('examResults');
                        $record->load([
                            'examResults' => function ($q) use ($exam) {
                                $q->where('exam_id', $exam->id);
                            },
                        ]);

                        Notification::make()
                            ->title('نمره با موفقیت ثبت شد.')
                            ->success()
                            ->send();
                    }),

                // ۲) ثبت غیبت
                Action::make('mark_absent')
                    ->label('ثبت غیبت')
                    ->color('warning')
                    ->icon('heroicon-o-hand-raised')
                    ->requiresConfirmation()
                    ->modalHeading('ثبت غیبت')
                    ->modalDescription('برای این دانش‌آموز در این آزمون، وضعیت غیبت ثبت می‌شود و هر نمره‌ای که قبلاً بوده حذف خواهد شد.')
                    ->action(function (Student $record): void {
                        /** @var Exam $exam */
                        $exam = $this->getOwnerRecord();

                        $existing = $record->examResults
                            ->firstWhere('exam_id', $exam->id);

                        if ($existing) {
                            $existing->update([
                                'correct_answer_count' => null,
                                'is_absent'            => true,
                            ]);
                        } else {
                            ExamResult::create([
                                'exam_id'              => $exam->id,
                                'student_id'           => $record->id,
                                'correct_answer_count' => null,
                                'is_absent'            => true,
                            ]);
                        }

                        $record->unsetRelation('examResults');
                        $record->load([
                            'examResults' => function ($q) use ($exam) {
                                $q->where('exam_id', $exam->id);
                            },
                        ]);

                        Notification::make()
                            ->title('غیبت با موفقیت ثبت شد.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
