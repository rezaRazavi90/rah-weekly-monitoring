<?php

namespace App\Filament\Resources\Grades\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GradeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('نام پایه')     // متن نمایشی در فرم
                    ->required()             // بدون این، ذخیره نمی‌کند
                    ->maxLength(50),         // محدودیت طول رشته
            ]);
    }
}
