<?php

namespace App\Filament\Pages;

use App\Models\ExamResult;
use App\Models\Student;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Morilog\Jalali\Jalalian;

class ReportCardResult extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = null;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'نمایش نتایج';
    protected static ?string $slug = 'report-card-result';

    protected string $view = 'filament.pages.report-card-result';

    public array $studentIds = [];

    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public ?string $dateFromJalali = null;
    public ?string $dateToJalali = null;

    /** @var \Illuminate\Support\Collection<int, \App\Models\Student> */
    public Collection $students;

    /** آمار سه بازه برای هر دانش‌آموز (کلید: student_id) */
    public array $currentStats = [];
    public array $previousStats = [];
    public array $totalStats = [];

    /** داده‌ی نمودار روند برای هر دانش‌آموز (کلید: student_id) */
    public array $trendData = [];

    /** داده‌ی نمودار میله‌ای برای هر دانش‌آموز (کلید: student_id) */
    public array $subjectBarData = [];

    /** داده‌ی نمودار عنکبوتی برای هر دانش‌آموز (کلید: student_id) */
    public array $subjectRadarData = [];

    public function mount(): void
    {
        $this->dateFrom = request()->query('date_from');
        $this->dateTo   = request()->query('date_to');

        $ids = request()->query('student_ids');
        $this->studentIds = $ids ? explode(',', $ids) : [];

        // دانش‌آموزان انتخاب‌شده (همراه با پایه)
        $this->students = Student::query()
            ->with('grade')
            ->whereIn('id', $this->studentIds)
            ->orderBy('grade_id')
            ->orderBy('student_code')
            ->get();

        // تبدیل بازه به شمسی (فقط ماه/روز)
        if ($this->dateFrom) {
            $this->dateFromJalali = Jalalian::fromCarbon(
                Carbon::parse($this->dateFrom)
            )->format('%m/%d');
        }

        if ($this->dateTo) {
            $this->dateToJalali = Jalalian::fromCarbon(
                Carbon::parse($this->dateTo)
            )->format('%m/%d');
        }

        // اگر بازه تاریخ نداریم یا دانش‌آموزی انتخاب نشده
        if (! $this->dateFrom || ! $this->dateTo || $this->students->isEmpty()) {
            $this->currentStats = [];
            $this->previousStats = [];
            $this->totalStats = [];
            $this->trendData = [];
            $this->subjectBarData = [];
            $this->subjectRadarData = [];

            return;
        }

        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $to   = Carbon::parse($this->dateTo)->endOfDay();

        // طول بازه فعلی (برای هفته قبل و برای پنجره‌های روند)
        $days = $from->diffInDays($to) + 1;

        // هفته قبل: بازه‌ای با همان طول، بلافاصله قبل از بازه فعلی
        $prevTo   = $from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

        // کل: از ابتدای سال تا انتهای بازه فعلی (اینجا: بدون محدودیت پایین)
        $totalFrom = null;
        $totalTo   = $to;

        $this->currentStats  = $this->buildStats($from, $to, $this->students);
        $this->previousStats = $this->buildStats($prevFrom, $prevTo, $this->students);
        $this->totalStats    = $this->buildStats($totalFrom, $totalTo, $this->students);

        // داده‌ی نمودار روند برای هر دانش‌آموز (چند پنجره‌ی هفتگی پشت سر هم)
        $this->trendData = $this->buildTrendData($from, $to, $days);

        // داده‌ی نمودار میله‌ای (هفته جاری / هفته قبل برای هر درس)
        $this->subjectBarData = $this->buildSubjectBarData($from, $to, $days);

        // داده‌ی نمودار عنکبوتی (معدل کل دانش‌آموز و میانگین کلاس در هر درس)
        $this->subjectRadarData = $this->buildSubjectRadarData($to);
    }

    /**
     * آمار یک بازه زمانی برای همه دانش‌آموزانِ انتخاب‌شده را حساب می‌کند.
     *
     * خروجی:
     * [
     *   student_id => [
     *      'grade_id'         => int,
     *      'avg'              => float|null,   // معدل دانش‌آموز
     *      'exam_count'       => int,          // تعداد آزمون‌های شرکت‌کرده (حضور)
     *      'success_count'    => int,          // آزمون‌های >= 15
     *      'absent_count'     => int,          // تعداد غیبت‌ها
     *      'rank'             => int|null,     // رتبه در پایه
     *      'class_avg'        => float|null,   // معدل کل پایه
     *      'diff_from_class'  => float|null,   // avg - class_avg
     *      'success_percent'  => float|null,   // درصد آزمون موفق
     *   ],
     *   ...
     * ]
     */
    protected function buildStats(?Carbon $from, ?Carbon $to, Collection $students): array
    {
        $gradeIds = $students->pluck('grade_id')->unique()->filter()->values();

        if ($gradeIds->isEmpty()) {
            // برای هر دانش‌آموز، آمار خالی برگردان
            $empty = [];
            foreach ($students as $student) {
                $empty[$student->id] = [
                    'grade_id'         => $student->grade_id,
                    'avg'              => null,
                    'exam_count'       => 0,
                    'success_count'    => 0,
                    'absent_count'     => 0,
                    'rank'             => null,
                    'class_avg'        => null,
                    'diff_from_class'  => null,
                    'success_percent'  => null,
                ];
            }

            return $empty;
        }

        $query = ExamResult::query()
            ->selectRaw('
                exam_results.student_id,
                students.grade_id,
                SUM(CASE WHEN exam_results.is_absent = 0 THEN (exam_results.correct_answer_count * 20.0 / exams.total_question) ELSE 0 END) as score_sum,
                SUM(CASE WHEN exam_results.is_absent = 0 THEN 1 ELSE 0 END) as exam_count,
                SUM(CASE WHEN exam_results.is_absent = 0 AND (exam_results.correct_answer_count * 20.0 / exams.total_question) >= 15 THEN 1 ELSE 0 END) as success_count,
                SUM(CASE WHEN exam_results.is_absent = 1 THEN 1 ELSE 0 END) as absent_count
            ')
            ->join('exams', 'exam_results.exam_id', '=', 'exams.id')
            ->join('students', 'exam_results.student_id', '=', 'students.id')
            ->whereIn('students.grade_id', $gradeIds);

        if ($from) {
            $query->whereDate('exams.exam_date', '>=', $from->toDateString());
        }

        if ($to) {
            $query->whereDate('exams.exam_date', '<=', $to->toDateString());
        }

        $rows = $query
            ->groupBy('exam_results.student_id', 'students.grade_id')
            ->get();

        $stats = [];
        $gradeAggregates = [];

        foreach ($rows as $row) {
            $examCount    = (int) $row->exam_count;
            $scoreSum     = (float) $row->score_sum;
            $successCount = (int) $row->success_count;
            $absentCount  = (int) $row->absent_count;
            $avg          = $examCount > 0 ? $scoreSum / max($examCount, 1) : null;

            $studentId = (int) $row->student_id;
            $gradeId   = (int) $row->grade_id;

            $stats[$studentId] = [
                'grade_id'         => $gradeId,
                'avg'              => $avg,
                'exam_count'       => $examCount,
                'success_count'    => $successCount,
                'absent_count'     => $absentCount,
                'rank'             => null,
                'class_avg'        => null,
                'diff_from_class'  => null,
                'success_percent'  => null,
            ];

            if (! isset($gradeAggregates[$gradeId])) {
                $gradeAggregates[$gradeId] = [
                    'students'   => [],
                    'score_sum'  => 0.0,
                    'exam_count' => 0,
                ];
            }

            $gradeAggregates[$gradeId]['students'][] = [
                'student_id' => $studentId,
                'avg'        => $avg,
            ];

            $gradeAggregates[$gradeId]['score_sum']  += $scoreSum;
            $gradeAggregates[$gradeId]['exam_count'] += $examCount;
        }

        // محاسبه معدل کلاس و رتبه‌ها در هر پایه
        foreach ($gradeAggregates as $gradeId => $agg) {
            $gradeExamCount = $agg['exam_count'];
            $classAvg = $gradeExamCount > 0
                ? $agg['score_sum'] / max($gradeExamCount, 1)
                : null;

            $studentsForGrade = $agg['students'];

            // مرتب‌سازی بر اساس معدل نزولی، null ها آخر
            usort($studentsForGrade, function (array $a, array $b): int {
                $aAvg = $a['avg'];
                $bAvg = $b['avg'];

                if ($aAvg === null && $bAvg === null) {
                    return 0;
                }
                if ($aAvg === null) {
                    return 1; // a بعد از b
                }
                if ($bAvg === null) {
                    return -1; // a قبل از b
                }

                if ($aAvg === $bAvg) {
                    return 0;
                }

                return $aAvg > $bAvg ? -1 : 1; // نزولی
            });

            $rank = 1;

            foreach ($studentsForGrade as $entry) {
                $studentId = $entry['student_id'];
                $avg       = $entry['avg'];

                if (! isset($stats[$studentId])) {
                    continue;
                }

                $stats[$studentId]['class_avg'] = $classAvg;

                if ($avg === null) {
                    $stats[$studentId]['rank'] = null;
                    $stats[$studentId]['diff_from_class'] = null;
                    continue;
                }

                $stats[$studentId]['rank'] = $rank++;
                $stats[$studentId]['diff_from_class'] = $classAvg !== null
                    ? ($avg - $classAvg)  // مثبت یعنی بالاتر از کلاس
                    : null;
            }
        }

        // درصد آزمون موفق برای هر دانش‌آموز
        foreach ($stats as $studentId => &$item) {
            if ($item['exam_count'] > 0) {
                $item['success_percent'] = $item['success_count'] > 0
                    ? ($item['success_count'] * 100.0 / $item['exam_count'])
                    : 0.0;
            } else {
                $item['success_percent'] = null;
            }
        }
        unset($item);

        // برای دانش‌آموزانی که اصلاً امتحان ندادند، رکورد خالی بسازیم
        foreach ($students as $student) {
            if (! isset($stats[$student->id])) {
                $stats[$student->id] = [
                    'grade_id'         => $student->grade_id,
                    'avg'              => null,
                    'exam_count'       => 0,
                    'success_count'    => 0,
                    'absent_count'     => 0,
                    'rank'             => null,
                    'class_avg'        => null,
                    'diff_from_class'  => null,
                    'success_percent'  => null,
                ];
            }
        }

        return $stats;
    }

    /**
     * روند هفتگی معدل خود دانش‌آموز و کلاس (چند نقطه) برای هر دانش‌آموز.
     */
    protected function buildTrendData(Carbon $from, Carbon $to, int $days): array
    {
        $trend = [];

        $maxWindows = 8;

        foreach ($this->students as $student) {
            $labels = [];
            $studentSeries = [];
            $classSeries = [];

            $curFrom = $from->copy();
            $curTo   = $to->copy();

            for ($i = 0; $i < $maxWindows; $i++) {
                $statsForWindow = $this->buildStats($curFrom, $curTo, collect([$student]));
                $stat = $statsForWindow[$student->id] ?? null;

                if (! $stat || ($stat['avg'] === null && $stat['class_avg'] === null)) {
                    $curTo = $curFrom->copy()->subDay()->endOfDay();
                    $curFrom = $curTo->copy()->subDays($days - 1)->startOfDay();
                    continue;
                }

                $labelJalali = Jalalian::fromCarbon($curTo)->format('%m/%d');

                $labels[]        = $labelJalali;
                $studentSeries[] = $stat['avg'];
                $classSeries[]   = $stat['class_avg'];

                $curTo = $curFrom->copy()->subDay()->endOfDay();
                $curFrom = $curTo->copy()->subDays($days - 1)->startOfDay();
            }

            $labels        = array_reverse($labels);
            $studentSeries = array_reverse($studentSeries);
            $classSeries   = array_reverse($classSeries);

            $trend[$student->id] = [
                'labels'  => $labels,
                'student' => $studentSeries,
                'class'   => $classSeries,
            ];
        }

        return $trend;
    }

    /**
     * آمار متوسط هر درس برای هر دانش‌آموز در یک بازه (برای نمودارها)
     * خروجی:
     * [
     *   student_id => [
     *      'subject name' => ['avg' => float|null],
     *      ...
     *   ],
     * ]
     */
    protected function buildSubjectWindowStats(?Carbon $from, ?Carbon $to, Collection $students): array
    {
        $studentIds = $students->pluck('id')->all();

        if (empty($studentIds)) {
            return [];
        }

        $query = ExamResult::query()
            ->selectRaw('
                exam_results.student_id,
                exams.subject_id,
                subjects.name as subject_name,
                SUM(CASE WHEN exam_results.is_absent = 0 THEN (exam_results.correct_answer_count * 20.0 / exams.total_question) ELSE 0 END) as score_sum,
                SUM(CASE WHEN exam_results.is_absent = 0 THEN 1 ELSE 0 END) as exam_count
            ')
            ->join('exams', 'exam_results.exam_id', '=', 'exams.id')
            ->join('subjects', 'exams.subject_id', '=', 'subjects.id')
            ->whereIn('exam_results.student_id', $studentIds);

        if ($from) {
            $query->whereDate('exams.exam_date', '>=', $from->toDateString());
        }

        if ($to) {
            $query->whereDate('exams.exam_date', '<=', $to->toDateString());
        }

        $rows = $query
            ->groupBy('exam_results.student_id', 'exams.subject_id', 'subjects.name')
            ->orderBy('subjects.name')
            ->get();

        $stats = [];

        foreach ($rows as $row) {
            $examCount = (int) $row->exam_count;
            $scoreSum  = (float) $row->score_sum;
            $avg       = $examCount > 0 ? $scoreSum / max($examCount, 1) : null;

            $studentId   = (int) $row->student_id;
            $subjectName = $row->subject_name;

            if (! isset($stats[$studentId])) {
                $stats[$studentId] = [];
            }

            $stats[$studentId][$subjectName] = [
                'avg' => $avg,
            ];
        }

        return $stats;
    }

    /**
     * داده‌ی نمودار میله‌ای: برای هر درس، معدل هفته جاری و هفته قبل.
     *
     * خروجی:
     * [
     *   student_id => [
     *      'labels'   => [ 'ریاضی ۷', 'علوم ۷', ... ],
     *      'current'  => [ 18.5, 15.2, ... ],
     *      'previous' => [ 17.0, null, ... ],
     *   ],
     * ]
     */
    protected function buildSubjectBarData(Carbon $from, Carbon $to, int $days): array
    {
        $prevTo   = $from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

        $currentStats  = $this->buildSubjectWindowStats($from, $to, $this->students);
        $previousStats = $this->buildSubjectWindowStats($prevFrom, $prevTo, $this->students);

        $barData = [];

        foreach ($this->students as $student) {
            $sid = $student->id;

            $curSubjects  = array_keys($currentStats[$sid] ?? []);
            $prevSubjects = array_keys($previousStats[$sid] ?? []);

            $labels = array_values(array_unique(array_merge($curSubjects, $prevSubjects)));
            sort($labels);

            if (empty($labels)) {
                $barData[$sid] = [
                    'labels'   => [],
                    'current'  => [],
                    'previous' => [],
                ];
                continue;
            }

            $currentValues  = [];
            $previousValues = [];

            foreach ($labels as $subjectName) {
                $currentValues[]  = $currentStats[$sid][$subjectName]['avg']  ?? null;
                $previousValues[] = $previousStats[$sid][$subjectName]['avg'] ?? null;
            }

            $barData[$sid] = [
                'labels'   => $labels,
                'current'  => $currentValues,
                'previous' => $previousValues,
            ];
        }

        return $barData;
    }

    /**
     * داده‌ی نمودار عنکبوتی: معدل کل دانش‌آموز در هر درس و میانگین کل کلاس در همان درس.
     *
     * خروجی:
     * [
     *   student_id => [
     *      'labels' => ['ریاضی ۷', 'علوم ۷', ...],
     *      'student' => [ 17.3, 14.8, ... ],
     *      'class'   => [ 15.0, 13.2, ... ],
     *   ],
     * ]
     */
    protected function buildSubjectRadarData(Carbon $to): array
    {
        $students      = $this->students;
        $gradeIds      = $students->pluck('grade_id')->unique()->filter()->values();
        $studentIds    = $students->pluck('id')->all();

        if ($gradeIds->isEmpty() || empty($studentIds)) {
            return [];
        }

        // ۱) معدل هر دانش‌آموز در هر درس (تا انتهای بازه)
        $studentStats = $this->buildSubjectWindowStats(null, $to, $students);

        // ۲) معدل کلاس در هر درس برای هر پایه
        $query = ExamResult::query()
            ->selectRaw('
                students.grade_id,
                exams.subject_id,
                subjects.name as subject_name,
                SUM(CASE WHEN exam_results.is_absent = 0 THEN (exam_results.correct_answer_count * 20.0 / exams.total_question) ELSE 0 END) as score_sum,
                SUM(CASE WHEN exam_results.is_absent = 0 THEN 1 ELSE 0 END) as exam_count
            ')
            ->join('exams', 'exam_results.exam_id', '=', 'exams.id')
            ->join('students', 'exam_results.student_id', '=', 'students.id')
            ->join('subjects', 'exams.subject_id', '=', 'subjects.id')
            ->whereIn('students.grade_id', $gradeIds)
            ->whereDate('exams.exam_date', '<=', $to->toDateString())
            ->groupBy('students.grade_id', 'exams.subject_id', 'subjects.name')
            ->orderBy('subjects.name');

        $rows = $query->get();

        $classStats = [];

        foreach ($rows as $row) {
            $gradeId     = (int) $row->grade_id;
            $subjectName = $row->subject_name;
            $examCount   = (int) $row->exam_count;
            $scoreSum    = (float) $row->score_sum;
            $avg         = $examCount > 0 ? $scoreSum / max($examCount, 1) : null;

            if (! isset($classStats[$gradeId])) {
                $classStats[$gradeId] = [];
            }

            $classStats[$gradeId][$subjectName] = $avg;
        }

        $radarData = [];

        foreach ($students as $student) {
            $sid     = $student->id;
            $gradeId = $student->grade_id;

            $stuSubjects = array_keys($studentStats[$sid] ?? []);

            // اگر خودش هیچ امتحانی نداشته، نمودار عنکبوتی لازم نیست
            if (empty($stuSubjects)) {
                $radarData[$sid] = [
                    'labels'  => [],
                    'student' => [],
                    'class'   => [],
                ];
                continue;
            }

            $labels = $stuSubjects;
            sort($labels);

            $studentValues = [];
            $classValues   = [];

            foreach ($labels as $subjectName) {
                $studentValues[] = $studentStats[$sid][$subjectName]['avg'] ?? null;
                $classValues[]   = $classStats[$gradeId][$subjectName]       ?? null;
            }

            $radarData[$sid] = [
                'labels'  => $labels,
                'student' => $studentValues,
                'class'   => $classValues,
            ];
        }

        return $radarData;
    }
}
