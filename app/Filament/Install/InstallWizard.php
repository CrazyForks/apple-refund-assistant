<?php

namespace App\Filament\Install;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Livewire\Attributes\Computed;

class InstallWizard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $panel = 'install';
    
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'app_name' => 'Apple Refund Assistant',
            'app_url' => 'http://localhost:8000',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('app_name')
                    ->label('应用名称')
                    ->required(),
                TextInput::make('app_url')
                    ->label('应用 URL (APP_URL)')
                    ->placeholder('例如: http://localhost:8000 或 https://myapp.com')
                    ->required()
                    ->url(),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        
        // 处理表单数据
        dd($data);
    }

    public function getHeading(): string
    {
        return __('Install Wizard');
    }

    public static function getNavigationLabel(): string
    {
        return __('Install Wizard');
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public static function canAccess(): bool
    {
        return !config('app.installed');
    }
}