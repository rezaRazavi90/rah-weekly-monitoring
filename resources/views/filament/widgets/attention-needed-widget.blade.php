<x-filament::widget>
    <x-filament::card heading="پایش سریع: چه کسانی الان نیاز به توجه دارند؟">
        <div class="space-y-4 text-sm" dir="rtl">

            {{-- بخش ۱: دانش‌آموزان با افت محسوس --}}
            <div>
                <h3 class="font-bold mb-2 text-red-700">دانش‌آموزان با افت قابل توجه</h3>

                @if(empty($this->studentsWithDrop))
                    <p class="text-gray-500 text-xs">
                        در این بازه، افت چشمگیری ثبت نشده است. ✔️
                    </p>
                @else
                    <ul class="space-y-1">
                        @foreach($this->studentsWithDrop as $row)
                            <li class="flex items-center justify-between">
                                <div>
                                    <span class="font-semibold">{{ $row['name'] }}</span>
                                    <span class="text-gray-500 text-xs"> (پایه {{ $row['grade'] }})</span>
                                </div>
                                <div class="text-xs text-red-700">
                                    {{ number_format($row['prev_avg'], 1) }} ⟶ {{ number_format($row['curr_avg'], 1) }}
                                    ({{ $row['diff'] }})
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- بخش ۲: پایه‌های نیازمند توجه --}}
            <div>
                <h3 class="font-bold mb-2 text-amber-700">پایه‌هایی که نیاز به مراقبت دارند</h3>

                @if(empty($this->weakGrades))
                    <p class="text-gray-500 text-xs">
                        میانگین پایه‌ها در این هفته در محدوده قابل قبول است.
                    </p>
                @else
                    <ul class="space-y-1">
                        @foreach($this->weakGrades as $row)
                            <li class="flex items-center justify-between">
                                <span>پایه {{ $row['grade'] }}</span>
                                <span class="text-xs">
                                    میانگین: {{ number_format($row['avg'], 1) }} / 20
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- بخش ۳: دروس مسئله‌دار این هفته --}}
            <div>
                <h3 class="font-bold mb-2 text-gray-800">دروس مسئله‌دار در این هفته</h3>

                @if(empty($this->problemSubjects))
                    <p class="text-gray-500 text-xs">
                        در این هفته، درس خاصی با افت شدید مشاهده نشده است.
                    </p>
                @else
                    <ul class="space-y-1">
                        @foreach($this->problemSubjects as $row)
                            <li class="flex items-center justify-between">
                                <span>{{ $row['subject'] }}</span>
                                <span class="text-xs">
                                    میانگین: {{ number_format($row['avg'], 1) }} / 20
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

        </div>
    </x-filament::card>
</x-filament::widget>
