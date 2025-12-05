<?php

namespace App\Filament\Resources\Exams\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;

class ExamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('subject_id')
                    ->label('درس')
                    ->relationship('subject', 'name') // از رابطه‌ی subject() در مدل Exam
                    ->searchable()
                    ->preload()
                    ->required(),

                DatePicker::make('exam_date')
                    ->label('تاریخ آزمون')
                    ->jalali()
                    ->required(),

                TextInput::make('total_question')
                    ->label('تعداد سؤال‌ها')
                    ->numeric()
                    ->minValue(1)
                    ->required(),

                TextInput::make('title')
                    ->label('عنوان آزمون')
                    ->maxLength(100)
                    ->nullable(),

                FileUpload::make('results_file')
                    ->label('فایل اکسل نتایج آزمون')
                    ->helperText('هدر سطر اول: student_code, correct_answer_count, is_absent (اختیاری)')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'text/csv',
                    ])
                    ->storeFiles(false)   // در دیسک ذخیره نشود، فقط موقت

            ]);
    }
}
