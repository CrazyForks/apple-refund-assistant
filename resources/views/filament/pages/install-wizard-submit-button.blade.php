@if(!$this->isCompleted)
    <x-filament::button
        type="submit"
        size="lg"
    >
        {{ __('Start Installation') }}
    </x-filament::button>
@endif

