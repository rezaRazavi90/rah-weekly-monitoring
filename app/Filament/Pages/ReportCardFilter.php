<?php


namespace App\Filament\Pages;

use App\Models\Grade;
use App\Models\Student;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

class ReportCardFilter extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'کارنامه هفتگی';

    protected static string|null|\UnitEnum $navigationGroup = 'گزارش‌ها';

    protected static ?string $slug = 'report-cards';


    protected static ?string $title = 'کارنامه‌های هفتگی (فیلتر و گزارش‌گیری)'; // 👈 این رو اضافه کن

    protected string $view = 'filament.pages.report-card-filter';

    /**
     * وضعیت فرم
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'date_from' => now()->subWeek()->toDateString(),
            'date_to' => now()->toDateString(),
            'grade_id' => null,
            'student_ids' => [],
        ]);
    }

    /**
     * تعریف فرم فیلترها
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('فیلتر کارنامه هفتگی')
                    ->description('بازهٔ زمانی و دانش‌آموزان مورد نظر برای تولید کارنامه را انتخاب کنید.')
                    ->columns(1)
                    ->components([
                        Grid::make()
                            ->columns([
                                'default' => 1,
                                'md' => 3,
                            ])
                            ->components([
                                DatePicker::make('date_from')
                                    ->label('از تاریخ')
                                    ->required()
                                    ->native(false)
                                    ->closeOnDateSelection()
                                    ->jalali()
                                    ->displayFormat('Y-m-d'),

                                DatePicker::make('date_to')
                                    ->label('تا تاریخ')
                                    ->required()
                                    ->native(false)
                                    ->closeOnDateSelection()
                                    ->jalali()
                                    ->displayFormat('Y-m-d')
                                    ->after('date_from'),

                                Select::make('grade_id')
                                    ->label('پایه')
                                    ->options(fn () => \App\Models\Grade::query()
                                        ->orderBy('id')
                                        ->pluck('name', 'id'))
                                    ->searchable()
                                    ->native(false)
                                    ->placeholder('همه پایه‌ها')
                                    ->live(),
                            ]),

                        Select::make('student_ids')
                            ->label('دانش‌آموزان')
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->placeholder('اگر خالی بماند، همهٔ دانش‌آموزانِ فیلترشده در نظر گرفته می‌شوند')
                            ->options(function (callable $get) {
                                $gradeId = $get('grade_id');

                                $query = \App\Models\Student::query()
                                    ->orderBy('student_code');

                                if ($gradeId) {
                                    $query->where('grade_id', $gradeId);
                                }

                                return $query
                                    ->get()
                                    ->mapWithKeys(function (\App\Models\Student $student) {
                                        $label = $student->student_code
                                            . ' - '
                                            . $student->last_name . ' ' . $student->name;

                                        return [$student->id => $label];
                                    });
                            }),
                    ]),
            ])
            ->statePath('data');
    }



    /**
     * این متد با submit فرم صدا زده می‌شود.
     */
    public function generateReport()
    {
        $data = $this->form->getState();

        $dateFrom = $data['date_from'] ?? null;
        $dateTo = $data['date_to'] ?? null;
        $gradeId = $data['grade_id'] ?? null;
        $studentIdsInput = $data['student_ids'] ?? [];

        if (!$dateFrom || !$dateTo) {
            Notification::make()
                ->title('بازه تاریخ را مشخص کنید.')
                ->danger()
                ->send();

            return;
        }

        // پایه انتخاب شده یا همه پایه‌ها؟
        $studentsQuery = Student::query();

        if ($gradeId) {
            $studentsQuery->where('grade_id', $gradeId);
        }

        // اگر student_ids خالی باشد ⇒ همه دانش‌آموزان فیلترشده
        if (!empty($studentIdsInput)) {
            $studentsQuery->whereIn('id', $studentIdsInput);
        }

        $studentIds = $studentsQuery->pluck('id')->toArray();

        if (empty($studentIds)) {
            Notification::make()
                ->title('هیچ دانش‌آموزی با این شرایط پیدا نشد.')
                ->danger()
                ->send();

            return;
        }

        // پاس دادن اطلاعات به صفحه‌ی نتیجه از طریق query string
        $query = [
            'student_ids' => implode(',', $studentIds),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];

        // فرض بر این است که پنل شما "admin" است (آدرس‌ها شبیه /admin/...)
        $this->redirectRoute('filament.admin.pages.report-card-result', $query);
    }
}
