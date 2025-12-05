<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Imports\StudentsImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('importStudents')
                ->label('ایمپورت از اکسل')
                ->icon('heroicon-o-arrow-up-tray')
                ->modalHeading('ایمپورت دانش‌آموزها از فایل اکسل')
                ->modalSubmitActionLabel('شروع ایمپورت')
                ->schema([
                    FileUpload::make('file')
                        ->label('فایل اکسل دانش‌آموزان')
                        ->helperText("ستون‌های لازم در سطر اول: name, lastname, student_code, grade_id")
                        ->acceptedFileTypes([
                            // اکسل و CSV
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                            'text/plain',
                        ])
                        ->required()
                        ->storeFiles(false), // فایل رو دائمی ذخیره نمی‌کنیم، موقت می‌مونه
                ])
                ->action(function (array $data) {
                    $file = $data['file'];

                    $import = new StudentsImport();
                    $import->import($file);

                    $failures = $import->failures();
                    $errors   = $import->errors();

                    if ($failures->isEmpty() && empty($errors)) {
                        Notification::make()
                            ->title('ایمپورت با موفقیت انجام شد')
                            ->success()
                            ->send();

                        return;
                    }

                    $messages = [];

                    foreach ($failures as $failure) {
                        $messages[] = 'سطر ' . $failure->row() . ': ' . implode('، ', $failure->errors());
                    }

                    foreach ($errors as $error) {
                        $messages[] = (string) $error;
                    }

                    Notification::make()
                        ->title('ایمپورت انجام شد، اما چند خطا وجود دارد')
                        ->body(implode("\n", $messages))
                        ->warning()
                        ->send();
                }),

        ];
    }
}
