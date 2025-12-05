<?php

namespace App\Filament\Resources\Exams\Pages;

use App\Filament\Resources\Exams\ExamResource;
use App\Filament\Resources\Exams\RelationManagers\ExamResultsViewRelationManager;
use App\Models\Exam;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewExam extends ViewRecord
{
    protected static string $resource = ExamResource::class;

    /**
     * هنگام باز شدن صفحه، اگر آزمون ناقص باشد، یک نوتیفیکیشن قرمز نشان بده.
     */
    public function mount($record): void
    {
        parent::mount($record);

        /** @var Exam $exam */
        $exam = $this->record;

        $missing = $exam->missingResultsCount();

        if ($missing > 0) {
            Notification::make()
                ->title('هشدار مهم در مورد این آزمون')
                ->body("برای این آزمون، {$missing} دانش‌آموز هنوز هیچ نتیجه‌ای ندارند.")
                ->danger()
                ->persistent() // تا وقتی دستی بسته نشه، می‌مونه
                ->send();
        }
    }

    /**
     * زیرعنوان صفحه – متنی ولی واضح و قابل دید.
     */
    public function getSubheading(): ?string
    {
        /** @var Exam $exam */
        $exam = $this->record;

        $missing = $exam->missingResultsCount();

        if ($missing > 0) {
            // متن ساده است ولی با ایموجی و تاکید
            return "⚠ هشدار: برای این آزمون، {$missing} دانش‌آموز هنوز نتیجه‌ای ندارند.";
        }

        return null;
    }



    public function getRelationManagers(): array
    {
        return [
            ExamResultsViewRelationManager::class,
        ];
    }
}
