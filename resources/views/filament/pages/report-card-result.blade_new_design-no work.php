<x-filament::page>
    <style>
        @page {
            size: A5 portrait;
            margin: 8mm;
        }

        /* گرید دو نمودار پایین (میله‌ای + عنکبوتی) */
        .rc-subcharts {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }

        /* فاصله نمودار خطی در حالت عادی (نمایش در پنل) */
        .rc-line-chart {
            margin-top: 1rem;
            margin-bottom: 1rem;
        }

        @media print {
            html,
            body {
                background: #ffffff !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            /* برای اینکه position:absolute روی ریشه درست عمل کند */
            body {
                position: relative !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* همه‌چیز مخفی، فقط ریشهٔ چاپ دیده شود */
            body * {
                visibility: hidden !important;
            }

            .rc-print-root,
            .rc-print-root * {
                visibility: visible !important;
            }

            /* ریشهٔ چاپ از بالای صفحه شروع شود و چیزی قبلش فضا نگیرد */
            .rc-print-root {
                position: absolute !important;
                top: 0;
                left: 0;
                right: 0;
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* هر کارنامه روی یک صفحهٔ A5 جدا */
            .rc-grid {
                display: block !important;
            }

            .rc-card {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box;
                page-break-after: always;
                break-after: page;
            }

            .rc-subcharts {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 0.75rem !important;
            }

            /* فاصله مخصوص نمودار خطی در چاپ */
            .rc-line-chart {
                margin-top: 8mm !important;
                margin-bottom: 8mm !important;
            }

            /* چیزهایی که فقط برای پنل هست، در چاپ نباشد */
            .no-print {
                display: none !important;
            }
        }

        .rc-container {
            direction: rtl;
        }

        /* در حالت عادی دو کارنامه کنار هم، صرفاً برای نمایش در پنل */
        .rc-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .rc-card {
            width: 50%;
            box-sizing: border-box;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            background: #ffffff;
        }

        .rc-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 0.9rem;
            margin-bottom: 0.75rem;
            border-radius: 0.9rem;
            background-color: #f5f5f5;
            border: 1px solid #bbbdc0;

        }

        .rc-header-avatar {
            width: 44px;
            height: 44px;
            border-radius: 9999px;
            background: linear-gradient(135deg, #e5e7eb, #d4d4d4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .rc-header-main {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
        }

        .rc-header-name {
            font-size: 1rem;
            font-weight: 700;
        }

        .rc-header-meta {
            font-size: 0.75rem;
            color: #4b5563;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .rc-header-meta span::before {
            content: "•";
            margin-left: 0.25rem;
            color: #9ca3af;
        }

        .rc-header-meta span:first-child::before {
            content: "";
            margin: 0;
        }


        .rc-title {
            text-align: center;
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            background-color: #000;
            color: #fff;
            padding: 8px 15px;
        }

        .rc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
            table-layout: fixed;
        }

        .rc-table th,
        .rc-table td {
            border: 1px solid #b3b3b3;
            padding: 0.25rem 0.35rem;
            text-align: center;
        }

        .rc-table th.rc-label,
        .rc-table td.rc-label {
            text-align: right;
            white-space: nowrap;
        }

        .rc-row-label {
            width: 16%;
        }

        .rc-inner-header {
            width: 100%;
            border-collapse: collapse;
        }

        .rc-inner-header td {
            border: none;
            padding: 0 0.35rem;
            text-align: right;
            white-space: nowrap;
        }

        .rc-card canvas {
            max-width: 100% !important;
        }
    </style>


    <div class="rc-print-root">
        {{-- نوار بالا (فقط برای نمایش در پنل، در چاپ مخفی می‌شود) --}}
        <div class="no-print mb-4 flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">پیش‌نمایش کارنامه هفتگی</h2>
                <p class="text-sm text-gray-600">
                    بازه تاریخ (شمسی):
                    <span class="font-semibold">{{ $dateFromJalali }}</span>
                    تا
                    <span class="font-semibold">{{ $dateToJalali }}</span>
                </p>
            </div>
            <div class="flex gap-2">
                <x-filament::button color="gray" tag="a" href="{{ route('filament.admin.pages.report-cards') }}">
                    بازگشت به فیلتر
                </x-filament::button>

                <x-filament::button color="primary" onclick="window.print()">
                    چاپ
                </x-filament::button>
            </div>

            {{-- Chart.js برای نمودارها (فقط یک‌بار در صفحه) --}}
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        </div>

        <div class="rc-container">
            <div class="rc-grid">
                @foreach ($students as $student)
                    <div class="rc-card">
                        {{-- عنوان کارنامه --}}
                        <div class="rc-title">
                            کارنامه هفتگی دانش آموز
                        </div>
                        {{-- هدر هویتی دانش‌آموز --}}
                        <div class="rc-header">
                            <div class="rc-header-avatar">
                                👤
                            </div>

                            <div class="rc-header-main">
                                <div class="rc-header-name">
                                    {{ $student->last_name }} {{ $student->name }}
                                </div>

                                <div class="rc-header-meta">
                <span>
                    پایه:
                    <strong>{{ $student->grade->name ?? '-' }}</strong>
                </span>
                                    <span>
                    بازه گزارش:
                    <strong>{{ $dateFromJalali }} تا {{ $dateToJalali }}</strong>
                </span>
                                </div>
                            </div>
                        </div>

                        @php
                            $current  = $currentStats[$student->id]  ?? null;
                            $previous = $previousStats[$student->id] ?? null;
                            $total    = $totalStats[$student->id]    ?? null;

                            $formatNumber = function ($value): string {
                                return $value !== null ? number_format((float) $value, 1) : '-';
                            };

                            $formatInt = function ($value): string {
                                return $value !== null ? (string) $value : '-';
                            };

                            $formatPercent = function ($value): string {
                                return $value !== null ? number_format((float) $value, 0) . '%' : '-';
                            };

                            $formatDiff = function ($value): string {
                                if ($value === null) {
                                    return '-';
                                }

                                $abs = number_format(abs((float) $value), 1);

                                return ($value >= 0 ? '+' : '-') . $abs;
                            };

                            $trend      = $trendData[$student->id]        ?? null;
                            $bar        = $subjectBarData[$student->id]   ?? null;
                            $radar      = $subjectRadarData[$student->id] ?? null;


                            // تابع کوتاه‌کنندهٔ لیبل (اولین کلمه + «…» در صورت طولانی بودن)
                            $shortenLabel = function (string $label): string {
                                $parts = explode(' ', $label);
                                $first = $parts[0] ?? $label;

                                return mb_strlen($first) > 5
                                    ? mb_substr($first, 0, 5) . '…'
                                    : $first;
                            };

                            // لیبل‌های کوتاه برای نمودار میله‌ای
                            $barShortLabels = [];
                            if ($bar && !empty($bar['labels'])) {
                                foreach ($bar['labels'] as $label) {
                                    $barShortLabels[] = $shortenLabel($label);
                                }
                            }

                            // لیبل‌های کوتاه برای نمودار عنکبوتی
                            $radarShortLabels = [];
                            if ($radar && !empty($radar['labels'])) {
                                foreach ($radar['labels'] as $label) {
                                    $radarShortLabels[] = $shortenLabel($label);
                                }
                            }

                        @endphp

                        <h2 style="text-align: center">خلاصه عملکرد کلی</h2>
                        {{-- جدول اصلی کارنامه --}}
                        <table class="rc-table">
                            <tbody>

                            {{-- عنوان شاخص‌ها --}}
                            <tr style="
                                    font-size: 0.8em;
                                    font-weight: bold;
                                    background-color: #dcdcdc;
                                ">
                                <th class="rc-label rc-row-label"></th>
                                <th>رتبه</th>
                                <th>معدل</th>
                                <th>معدل کلاس</th>
                                <th>فاصله از کلاس</th>
                                <th>آزمون موفق(%)</th>
                                <th>غایب</th>
                            </tr>

                            {{-- هفته جاری --}}
                            <tr>
                                <td class="rc-label rc-row-label" style="background-color: #dcdcdc;">هفته جاری</td>
                                <td>{{ $formatInt($current['rank']            ?? null) }}</td>
                                <td>{{ $formatNumber($current['avg']          ?? null) }}</td>
                                <td>{{ $formatNumber($current['class_avg']    ?? null) }}</td>
                                <td>{{ $formatDiff($current['diff_from_class'] ?? null) }}</td>
                                <td>{{ $formatPercent($current['success_percent'] ?? null) }}</td>
                                <td>{{ $formatInt($current['absent_count']    ?? null) }}</td>
                            </tr>

                            {{-- هفته قبل --}}
                            <tr>
                                <td class="rc-label rc-row-label" style="background-color: #dcdcdc;">هفته قبل</td>
                                <td>{{ $formatInt($previous['rank']            ?? null) }}</td>
                                <td>{{ $formatNumber($previous['avg']          ?? null) }}</td>
                                <td>{{ $formatNumber($previous['class_avg']    ?? null) }}</td>
                                <td>{{ $formatDiff($previous['diff_from_class'] ?? null) }}</td>
                                <td>{{ $formatPercent($previous['success_percent'] ?? null) }}</td>
                                <td>{{ $formatInt($previous['absent_count']    ?? null) }}</td>
                            </tr>

                            {{-- کل --}}
                            <tr>
                                <td class="rc-label rc-row-label" style="background-color: #dcdcdc;">کل</td>
                                <td>{{ $formatInt($total['rank']            ?? null) }}</td>
                                <td>{{ $formatNumber($total['avg']          ?? null) }}</td>
                                <td>{{ $formatNumber($total['class_avg']    ?? null) }}</td>
                                <td>{{ $formatDiff($total['diff_from_class'] ?? null) }}</td>
                                <td>{{ $formatPercent($total['success_percent'] ?? null) }}</td>
                                <td>{{ $formatInt($total['absent_count']    ?? null) }}</td>
                            </tr>
                            </tbody>
                        </table>

                        {{-- نمودار روند خطی --}}
                        @if($trend && !empty($trend['labels']))
                            <div class="rc-line-chart">
                                <div style="text-align:center; font-size:0.75rem; margin-bottom:0.15rem;">
                                    روند تغییر معدل <strong>دانش‌آموز</strong> (خط پررنگ) در مقایسه با <strong>میانگین کلاس</strong> (خط نقطه‌چین)
                                </div>
                                <div style="height: 180px;">
                                    <canvas id="trend-chart-{{ $student->id }}"></canvas>
                                </div>

                                <script>
                                    (function () {
                                        const ctx = document.getElementById('trend-chart-{{ $student->id }}');
                                        if (!ctx || typeof Chart === 'undefined') {
                                            return;
                                        }

                                        const data = {
                                            labels: @json($trend['labels']),
                                            datasets: [
                                                {
                                                    label: 'دانش‌آموز',
                                                    data: @json($trend['student']),
                                                    borderColor: '#000000',
                                                    borderWidth: 2.5,
                                                    pointRadius: 4,
                                                    pointBackgroundColor: '#000000',
                                                    pointBorderColor: '#000000',
                                                    fill: false,
                                                    tension: 0.3,
                                                },
                                                {
                                                    label: 'میانگین کلاس',
                                                    data: @json($trend['class']),
                                                    borderColor: '#999999',
                                                    borderWidth: 1.5,
                                                    borderDash: [4, 4],
                                                    pointRadius: 3,
                                                    pointBackgroundColor: '#ffffff',
                                                    pointBorderColor: '#999999',
                                                    fill: false,
                                                    tension: 0.3,
                                                },
                                            ],
                                        };

                                        new Chart(ctx, {
                                            type: 'line',
                                            data,
                                            options: {
                                                responsive: true,
                                                maintainAspectRatio: false,

                                                scales: {
                                                    y: {
                                                        beginAtZero: true,
                                                        max: 20,
                                                        grid: {
                                                            color: '#eeeeee',
                                                        },
                                                    },
                                                    x: {
                                                        grid: {
                                                            display: false,
                                                        },
                                                    },
                                                },
                                                plugins: {
                                                    legend: {
                                                        display: false
                                                    },
                                                }

                                            },
                                        });
                                    })();
                                </script>
                            </div>
                        @endif


                        {{-- نمودارهای دروس: میله‌ای + عنکبوتی در دو ستون --}}
                        <div class="mt-4 rc-subcharts">
                            {{-- نمودار میله‌ای دروس (هفته جاری / هفته قبل) --}}
                            @if($bar && !empty($bar['labels']))
                                <div>
                                    <div style="height: 220px;">
                                        <canvas id="subject-bar-{{ $student->id }}"></canvas>
                                    </div>

                                    <script>
                                        (function () {
                                            const ctx = document.getElementById('subject-bar-{{ $student->id }}');
                                            if (!ctx || typeof Chart === 'undefined') {
                                                return;
                                            }

                                            const data = {
                                                labels: @json($barShortLabels),
                                                datasets: [
                                                    {
                                                        label: 'هفته جاری',
                                                        data: @json($bar['current']),
                                                        backgroundColor: 'rgba(0, 0, 0, 0.55)',
                                                        borderWidth: 0,
                                                        barThickness: 6,      // 👉 میله باریک
                                                        maxBarThickness: 8,
                                                    },
                                                    {
                                                        label: 'هفته قبل',
                                                        data: @json($bar['previous']),
                                                        backgroundColor: 'rgba(0, 0, 0, 0.18)',
                                                        borderWidth: 0,
                                                        barThickness: 6,      // 👉 میله باریک
                                                        maxBarThickness: 8,
                                                    },
                                                ],
                                            };

                                            new Chart(ctx, {
                                                type: 'bar',
                                                data,
                                                options: {
                                                    responsive: true,
                                                    maintainAspectRatio: false,
                                                    plugins: {
                                                        legend: {
                                                            display: true,
                                                            position: 'bottom',
                                                            labels: {
                                                                boxWidth: 10,
                                                                font: {
                                                                    size: 9,
                                                                },
                                                            },
                                                        },
                                                    },
                                                    layout: {
                                                        padding: { left: 0, right: 0, top: 0, bottom: 0 },
                                                    },
                                                    scales: {
                                                        x: {
                                                            // 👉 فاصلهٔ بین دسته‌ها و میله‌ها جمع‌وجورتر
                                                            categoryPercentage: 0.6,
                                                            barPercentage: 0.5,
                                                            ticks: {
                                                                font: {
                                                                    size: 8,
                                                                },
                                                                maxRotation: 60,
                                                                minRotation: 60,
                                                            },
                                                            grid: {
                                                                display: false,
                                                            },
                                                        },
                                                        y: {
                                                            beginAtZero: true,
                                                            suggestedMax: 20,
                                                            ticks: {
                                                                font: {
                                                                    size: 8,
                                                                },
                                                            },
                                                        },
                                                    },
                                                },
                                            });
                                        })();
                                    </script>

                                </div>
                            @endif

                            {{-- نمودار عنکبوتی دروس (دانش‌آموز vs میانگین کلاس) --}}
                            @if($radar && !empty($radar['labels']))
                                <div>
                                    <div style="height: 220px;">
                                        <canvas id="subject-radar-{{ $student->id }}"></canvas>
                                    </div>

                                    <script>
                                        (function () {
                                            const ctx = document.getElementById('subject-radar-{{ $student->id }}');
                                            if (!ctx || typeof Chart === 'undefined') {
                                                return;
                                            }

                                            const data = {
                                                labels: @json($radarShortLabels), // 🔹 لیبل‌های کوتاه‌شده
                                                datasets: [
                                                    {
                                                        label: 'دانش‌آموز',
                                                        data: @json($radar['student']),
                                                        borderColor: 'rgba(0, 0, 0, 1)',
                                                        backgroundColor: 'rgba(0, 0, 0, 0.08)',
                                                        borderWidth: 2,
                                                        fill: true,
                                                    },
                                                    {
                                                        label: 'میانگین کلاس',
                                                        data: @json($radar['class']),
                                                        borderColor: 'rgba(0, 0, 0, 1)',
                                                        backgroundColor: 'rgba(0, 0, 0, 0)',
                                                        borderWidth: 1,
                                                        borderDash: [4, 3],
                                                        fill: false,
                                                    },
                                                ],
                                            };

                                            new Chart(ctx, {
                                                type: 'radar',
                                                data,
                                                options: {
                                                    responsive: true,
                                                    maintainAspectRatio: false,
                                                    plugins: {
                                                        legend: {
                                                            display: true,
                                                            position: 'bottom',
                                                            labels: {
                                                                font: {
                                                                    size: 9,
                                                                },
                                                            },
                                                        },
                                                    },
                                                    scales: {
                                                        r: {
                                                            beginAtZero: true,
                                                            suggestedMax: 20,
                                                            pointLabels: {
                                                                font: {
                                                                    size: 8, // 🔹 لیبل‌های دور نمودار ریزتر
                                                                },
                                                            },
                                                            ticks: {
                                                                display: false, // دایره‌های عددی وسط رو مخفی کن برای خلوت‌تر شدن
                                                            },
                                                        },
                                                    },
                                                },
                                            });
                                        })();
                                    </script>

                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament::page>
