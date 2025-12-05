<?php

namespace App\Filament\Resources\Exams\Tables;

use App\Models\Exam;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject.name')
                    ->label('درس')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('subject.grade.name')
                    ->label('پایه')
                    ->badge()
                    ->sortable(),

                TextColumn::make('exam_date')
                    ->label('تاریخ آزمون')
                    ->jalaliDate()
                    ->sortable(),

                TextColumn::make('total_question')
                    ->label('تعداد سؤال')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('عنوان')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                // ⭐ ستون وضعیت نمرات + هشدار
                TextColumn::make('results_status')
                    ->label('وضعیت نمرات')
                    ->state(function (Exam $record): string {
                        $missing = $record->missingResultsCount();

                        return $missing === 0
                            ? 'کامل'
                            : "ناقص ({$missing} نفر بدون نتیجه)";
                    })
                    ->badge()
                    ->color(fn (Exam $record): string => $record->missingResultsCount() === 0 ? 'success' : 'warning'),
                TextColumn::make('created_at')
                    ->label('ایجاد شده')
                    ->jalaliDate()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('بروزرسانی شده')
                    ->jalaliDate()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('نمایش'),
                EditAction::make()->label('ویرایش'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف انتخاب‌شده‌ها'),
                ]),
            ]);
    }
}
