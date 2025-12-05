<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AdminDashboard extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'پیشخوان';

    protected static ?string $title = 'داشبورد مدیریت مدرسه';

    // این ویو به صورت خودکار توسط artisan ساخته شده
    protected string $view = 'filament.pages.admin-dashboard';

    /**
     * اگر خواستی داشبورد از منو حذف بشه، این می‌تونه false بشه.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    /**
     * چه ویجت‌هایی روی داشبورد نمایش داده بشن
     */
    public function getWidgets(): array
    {
        return [
            // ردیف اول: سه کارت میانگین پایه‌ها + ریسک کلی
            \App\Filament\Widgets\GradeAverageByGradeWidget::class,
            \App\Filament\Widgets\SchoolRiskOverviewWidget::class,

            // ردیف دوم: روند میانگین مدرسه + پراکندگی نمرات این هفته
            \App\Filament\Widgets\SchoolWeeklyAverageTrend::class,
            \App\Filament\Widgets\WeeklyScoreHistogram::class,

            // ردیف سوم: درس‌های مسئله‌دار + سهم دروس از آزمون‌ها
            \App\Filament\Widgets\ProblematicSubjectsThisWeek::class,
            \App\Filament\Widgets\SubjectExamShareChart::class,

            // ردیف چهارم: غیبت‌ها
            \App\Filament\Widgets\WeeklyAbsencesByWeekday::class,
            \App\Filament\Widgets\TopAbsentStudents::class,

            // ردیف پنجم: پیشرفت‌ها + آخرین آزمون‌ها
            \App\Filament\Widgets\TopImprovingStudents::class,
            \App\Filament\Widgets\RecentExamsTable::class,

            // ردیف آخر: “الان کجا باید توجه کنیم؟”
            \App\Filament\Widgets\AttentionNeededWidget::class,
        ];
    }

    /**
     * تعداد ستون‌ها در سایزهای مختلف
     */
    public function getColumns(): int|array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'lg' => 2,
            'xl' => 3,
        ];
    }
}
