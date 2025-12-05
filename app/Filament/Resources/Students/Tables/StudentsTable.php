<?php

namespace App\Filament\Resources\Students\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student_code')
                    ->label('کد')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('نام')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('last_name')
                    ->label('نام خانوادگی')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('grade.name')
                    ->label('پایه')
                    ->badge()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('ایجاد شد')
                    ->jalaliDate()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('بروزرسانی شد')
                    ->jalaliDate()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->label('ویرایش'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف انتخاب‌شده‌ها'),
                ]),
            ]);
    }
}
