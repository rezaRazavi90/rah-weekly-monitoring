<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('نام')
                    ->required()
                    ->maxLength(100),
                TextInput::make('last_name')
                    ->label('نام خانوادگی')
                    ->required()
                    ->maxLength(100),
                TextInput::make('student_code')
                    ->label('کد دانش‌آموز')
                    ->numeric()
                    ->required(),
                Select::make('grade_id')
                    ->label('پایه')
                    ->relationship('grade', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }
}
