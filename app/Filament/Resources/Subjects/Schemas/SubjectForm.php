<?php

namespace App\Filament\Resources\Subjects\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;

class SubjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('نام درس')
                    ->required()
                    ->maxLength(100),

                Select::make('grade_id')
                    ->label('پایه')
                    ->relationship('grade', 'name') // از relation مدل Subject استفاده می‌کند
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }
}
