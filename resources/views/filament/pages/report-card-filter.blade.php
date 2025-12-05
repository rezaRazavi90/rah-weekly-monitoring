<x-filament::page>
    <form wire:submit.prevent="generateReport" class="space-y-6">
        {{-- خود فرم (با Section و Grid که در PHP تعریف کردیم) --}}
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit">
                ایجاد کارنامه
            </x-filament::button>
        </div>
    </form>
</x-filament::page>
