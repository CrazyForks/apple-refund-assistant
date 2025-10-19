<?php

namespace App\Filament\Install;

use App\Utils\InstallUtil;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class InstallWizard extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.install-wizard';

    public ?array $data = [];
    public bool $isCompleted = false;
    public bool $isDatabaseTested = false;
    public string $databaseTestMessage = '';
    public bool $isInstalling = false;
    public int $installStep = 0;
    public string $installStepMessage = '';
    public array $commandLogs = [];
    public array $installSteps = [
        1 => 'Write .env configuration file',
        2 => 'Clear all caches',
        3 => 'Run database migrations',
        4 => 'Create admin account',
    ];

    public function mount(): void
    {
        if (config('app.installed')) {
            redirect('/admin');
            return;
        }

        // Load saved configuration from session or use defaults
        $savedConfig = session('install_wizard_config', []);

        // Use existing APP_KEY if available, otherwise generate a new one
        $key = $savedConfig['app_key'] ?? 'base64:' . base64_encode(Encrypter::generateKey(config('app.cipher')));
        $defaultConfig = [
            'app_name' => 'Apple Refund Assistant',
            'app_url' => request()->getSchemeAndHttpHost(),
            'app_env' => 'production',
            'app_debug' => false,
            'app_key' => $key,
            'app_timezone' => 'Asia/Shanghai',

            'db_connection' => 'sqlite',
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_database' => 'database/database.sqlite',
            'db_username' => 'root',
            'db_password' => '',
        ];

        // Merge saved config with defaults
        $config = array_merge($defaultConfig, $savedConfig);

        $this->form->fill($config);

        // Restore test status from session
        $this->isDatabaseTested = session('install_wizard_db_tested', false);
        $this->databaseTestMessage = session('install_wizard_db_message', '');
    }

    protected function saveConfigToSession(): void
    {
         // Get current form data
         $formData = $this->form->getState();

         // Merge with existing session data to preserve all configurations
         $existingConfig = session('install_wizard_config', []);
         $allConfig = array_merge($existingConfig, $formData);

         // Ensure app_key is always saved (it's dehydrated=false so not in form data)
         if (isset($this->data['app_key'])) {
             $allConfig['app_key'] = $this->data['app_key'];
         }

         // Save all configuration to session
         session(['install_wizard_config' => $allConfig]);
    }

    protected function saveTestStatusToSession(): void
    {
        session([
            'install_wizard_db_tested' => $this->isDatabaseTested,
            'install_wizard_db_message' => $this->databaseTestMessage,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make(__('Environment Check'))
                        ->icon('heroicon-o-shield-check')
                        ->description(__('Check system environment and permissions'))
                        ->components([
                            Section::make(__('System Environment Check'))
                                ->description(__('Ensure system meets installation requirements'))
                                ->schema([
                                    TextEntry::make('storage_permissions')
                                        ->label(__('Storage Directory Permissions'))
                                        ->placeholder(function () {
                                            $paths = [
                                                'storage' => storage_path(),
                                                'bootstrap/cache' => base_path('bootstrap/cache'),
                                            ];
                                            $results = [];
                                            foreach ($paths as $name => $path) {
                                                if (!is_dir($path)) {
                                                    $results[] = "❌ {$name}: " . __('Directory does not exist');
                                                } elseif (!is_writable($path)) {
                                                    $results[] = "❌ {$name}: " . __('No write permission');
                                                } else {
                                                    $results[] = "✅ {$name}: " . __('Permission normal');
                                                }
                                            }
                                            return Html::make(implode("<br>", $results));
                                        }),
                                ])
                                ->footerActions([
                                    Action::make('refreshCheck')
                                        ->label(__('Recheck'))
                                        ->icon('heroicon-o-arrow-path')
                                        ->color('gray')
                                        ->action(function () {
                                            // Force refresh the page to re-run checks
                                            $this->js('window.location.reload()');
                                        }),
                                ]),
                        ]),

                    Step::make(__('Application Configuration'))
                        ->icon('heroicon-o-cog-6-tooth')
                        ->description(__('Configure application basic information'))
                        ->afterValidation(function () {
                            $this->saveConfigToSession();
                        })
                        ->components([
                            Section::make(__('Basic Information'))
                                ->schema([
                                    TextInput::make('app_name')
                                        ->label(__('Application Name'))
                                        ->required()
                                        ->maxLength(255),

                                    TextInput::make('app_url')
                                        ->label(__('Application URL'))
                                        ->required()
                                        ->url(),

                                    Select::make('app_env')
                                        ->label(__('Runtime Environment'))
                                        ->required()
                                        ->native(false)
                                        ->options([
                                            'local' => __('Local') . ' (Local)',
                                            'development' => __('Development') . ' (Development)',
                                            'production' => __('Production') . ' (Production)',
                                        ]),

                                    Select::make('app_debug')
                                        ->label(__('Debug Mode'))
                                        ->required()
                                        ->native(false)
                                        ->boolean()
                                        ->helperText(__('Production environment recommends turning off debug mode')),

                                    Select::make('app_timezone')
                                        ->label(__('Application Timezone'))
                                        ->required()
                                        ->native(false)
                                        ->searchable()
                                        ->options([
                                            'Asia/Shanghai' => __('China Standard Time') . ' (Asia/Shanghai)',
                                            'Asia/Hong_Kong' => __('Hong Kong Time') . ' (Asia/Hong_Kong)',
                                            'Asia/Taipei' => __('Taipei Time') . ' (Asia/Taipei)',
                                            'Asia/Tokyo' => __('Tokyo Time') . ' (Asia/Tokyo)',
                                            'Asia/Seoul' => __('Seoul Time') . ' (Asia/Seoul)',
                                            'Asia/Singapore' => __('Singapore Time') . ' (Asia/Singapore)',
                                            'Asia/Bangkok' => __('Bangkok Time') . ' (Asia/Bangkok)',
                                            'Asia/Kuala_Lumpur' => __('Kuala Lumpur Time') . ' (Asia/Kuala_Lumpur)',
                                            'Asia/Jakarta' => __('Jakarta Time') . ' (Asia/Jakarta)',
                                            'UTC' => __('Coordinated Universal Time') . ' (UTC)',
                                            'America/New_York' => __('New York Time') . ' (America/New_York)',
                                            'America/Los_Angeles' => __('Los Angeles Time') . ' (America/Los_Angeles)',
                                            'Europe/London' => __('London Time') . ' (Europe/London)',
                                            'Europe/Paris' => __('Paris Time') . ' (Europe/Paris)',
                                            'Europe/Berlin' => __('Berlin Time') . ' (Europe/Berlin)',
                                            'Australia/Sydney' => __('Sydney Time') . ' (Australia/Sydney)',
                                        ])
                                        ->helperText(__('Select the timezone used by the application, affects log time and scheduled tasks')),
                                ])->columns(2),
                        ]),

                    Wizard\Step::make(__('Database Configuration'))
                        ->icon('heroicon-o-circle-stack')
                        ->description(__('Configure database connection'))
                        ->afterValidation(function () {
                            // Save database config to session
                            $this->saveConfigToSession();

                            if (!$this->isDatabaseTested) {
                                Notification::make()
                                    ->title(__('Please test database connection first'))
                                    ->body(__('Before proceeding to the next step, please click the "Test Database Connection" button to ensure the database configuration is correct'))
                                    ->warning()
                                    ->persistent()
                                    ->send();

                                $this->halt();
                            }
                        })
                        ->components([
                            Section::make(__('Database Settings'))
                                ->description(function (Get $get) {
                                    if ($get('db_connection') === 'sqlite') {
                                        return '⚠️ ' . __('Important reminder: If the specified SQLite database file already exists, the installation process may overwrite existing data. Please be sure to backup important database files first!');
                                    }
                                    return null;
                                })
                                ->schema([
                                    Select::make('db_connection')
                                        ->label(__('Database Type'))
                                        ->required()
                                        ->native(false)
                                        ->options([
                                            'sqlite' => 'SQLite',
                                            'mysql' => 'MySQL',
                                        ])
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            // Reset database test status when connection type changes
                                            $this->isDatabaseTested = false;
                                            $this->databaseTestMessage = '';
                                            $this->saveTestStatusToSession();

                                            if ($state === 'sqlite') {
                                                $set('db_database', 'database/database.sqlite');
                                            }

                                        }),

                                    TextInput::make('db_host')
                                        ->label(__('Database Host'))
                                        ->required(fn(Get $get) => $get('db_connection') === 'mysql')
                                        ->visible(fn(Get $get) => $get('db_connection') === 'mysql')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function () {
                                            $this->isDatabaseTested = false;
                                            $this->databaseTestMessage = '';
                                            $this->saveTestStatusToSession();
                                        }),

                                    TextInput::make('db_port')
                                        ->label(__('Database Port'))
                                        ->required(fn(Get $get) => $get('db_connection') === 'mysql')
                                        ->visible(fn(Get $get) => $get('db_connection') === 'mysql')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function () {
                                            $this->isDatabaseTested = false;
                                            $this->databaseTestMessage = '';
                                            $this->saveTestStatusToSession();
                                        }),

                                    TextInput::make('db_database')
                                        ->label(fn(Get $get) => $get('db_connection') === 'sqlite' ? __('Database File Path') : __('Database Name'))
                                        ->required()
                                        ->helperText(function (Get $get) {
                                            if ($get('db_connection') === 'sqlite') {
                                                return __('Relative to project root directory. ⚠️ If file already exists, please backup existing database file first!');
                                            }
                                            return '';
                                        })
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function () {
                                            $this->isDatabaseTested = false;
                                            $this->databaseTestMessage = '';
                                            $this->saveTestStatusToSession();
                                        }),

                                    TextInput::make('db_username')
                                        ->label(__('Database Username'))
                                        ->required(fn(Get $get) => $get('db_connection') === 'mysql')
                                        ->visible(fn(Get $get) => $get('db_connection') === 'mysql')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function () {
                                            $this->isDatabaseTested = false;
                                            $this->databaseTestMessage = '';
                                            $this->saveTestStatusToSession();
                                        }),

                                    TextInput::make('db_password')
                                        ->label(__('Database Password'))
                                        ->password()
                                        ->revealable()
                                        ->visible(fn(Get $get) => $get('db_connection') === 'mysql')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function () {
                                            $this->isDatabaseTested = false;
                                            $this->databaseTestMessage = '';
                                            $this->saveTestStatusToSession();
                                        }),
                                ])->columns(2),

                            Section::make(__('Connection Test'))
                                ->schema([
                                    TextEntry::make('db_test_status')
                                        ->label(function () {
                                            if ($this->databaseTestMessage) {
                                                return $this->databaseTestMessage;
                                            }
                                            if ($this->isDatabaseTested) {
                                                return '✅ ' . __('Database connection test passed');
                                            }
                                            return '⚠️ ' . __('Please click the button below to test database connection (must pass test to proceed to next step)');
                                        })
                                        ->color(function () {
                                            if ($this->isDatabaseTested) {
                                                return 'success';
                                            }
                                            if ($this->databaseTestMessage && !$this->isDatabaseTested) {
                                                return 'danger';
                                            }
                                            return 'warning';
                                        }),
                                ])
                                ->footerActions([
                                    Action::make('testDatabaseConnection')
                                        ->label(__('Test Database Connection'))
                                        ->icon('heroicon-o-signal')
                                        ->color(fn() => $this->isDatabaseTested ? 'success' : 'primary')
                                        ->action(function () {
                                            $this->testDatabaseConnection();
                                        }),
                                ]),
                        ]),

                    Wizard\Step::make(__('Confirm Configuration'))
                        ->icon('heroicon-o-clipboard-document-check')
                        ->description(__('Check all configuration information'))
                        ->components([
                            Section::make(__('.env File Preview'))
                                ->description(__('Save configuration properly'))
                                ->headerActions([
                                    Action::make('copyEnvContent')
                                        ->label(__('Copy Configuration'))
                                        ->icon('heroicon-o-clipboard-document')
                                        ->color('gray')
                                        ->action(function (Get $get) {
                                            $this->dispatch('copy-to-clipboard', content: $this->generateEnvPreview($get));
                                        }),
                                ])
                                ->schema([
                                    Textarea::make('env_preview')
                                        ->label('')
                                        ->disabled()
                                        ->extraInputAttributes(['class' => 'font-mono text-sm', 'style' => 'resize: vertical;'])
                                        ->placeholder(function (Get $get) {
                                            return $this->generateEnvPreview($get);
                                        })
                                        ->rows(20)
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Wizard\Step::make(__('Start Installation'))
                        ->icon('heroicon-o-rocket-launch')
                        ->description(__('Prepare to install system'))
                        ->components([
                            Section::make('🎉 ' . __('Installation Complete'))
                                ->description(__('Congratulations! System installation has been completed successfully.'))
                                ->schema([
                                    Html::make('<div class="text-center space-y-4">
                                           <div class="pt-4">
                                             <a href="/admin" target="_blank" style="color: #1e9fff;" class="text-blue-600 hover:text-blue-800 underline font-medium transition-colors">
                                                 ' . __('Access Admin Panel') . ' /admin
                                             </a>
                                         </div>
                                        <div class="space-y-2">
                                            <p><strong>' . __('Admin Account Information') . '：</strong></p>
                                            <p>' . __('Email') . ': <code>admin@dev.com</code></p>
                                            <p>' . __('Password') . ': <code>admin</code></p>
                                        </div>

                                        <div class="text-sm text-gray-600">
                                            <p>⚠️ ' . __('If you need to optimize performance and security, please execute the following commands') . '</p>
                                            <code>php artisan key:generate</code> <br>
                                            <code>php artisan optimize</code>
                                        </div>
                                    </div>')
                                ])
                                ->visible(fn() => $this->isCompleted),
                            Section::make(__('Installation Execution Log'))
                                ->schema([
                                    Textarea::make('command_logs')
                                        ->label('')
                                        ->disabled()
                                        ->extraInputAttributes([
                                            'class' => 'font-mono text-sm bg-gray-50',
                                            'style' => 'resize: vertical; min-height: 200px;',
                                            'readonly' => true
                                        ])
                                        ->placeholder(function () {
                                            if (empty($this->commandLogs)) {
                                                return __('Waiting for command execution...');
                                            }

                                            $logs = '';
                                            // Display logs in reverse order (newest at top)
                                            $reversedLogs = array_reverse($this->commandLogs);
                                            foreach ($reversedLogs as $log) {
                                                $color = match($log['type']) {
                                                    'success' => '🟢',
                                                    'error' => '🔴',
                                                    'warning' => '🟡',
                                                    default => '🔵'
                                                };
                                                $logs .= "[{$log['timestamp']}] {$color} {$log['message']}\n";
                                            }
                                            return $logs;
                                        })
                                        ->rows(10)
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ])
                    ->submitAction(view('filament.pages.install-wizard-submit-button'))
                    ->persistStepInQueryString()
                    ->skippable(false)
            ])
            ->statePath('data');
    }

    public function testDatabaseConnection(): void
    {
        try {
            // Only get form data, no validation
            $data = $this->data;

            if (!isset($data['db_connection'])) {
                throw new \Exception(__('Please select database type first'));
            }

            $connection = $data['db_connection'];

            if ($connection === 'sqlite') {
                if (!isset($data['db_database']) || empty($data['db_database'])) {
                    throw new \Exception(__('Please fill in database file path'));
                }

                $dbPath = base_path($data['db_database']);
                $dbDir = dirname($dbPath);

                if (!File::exists($dbDir)) {
                    File::makeDirectory($dbDir, 0755, true);
                }

                if (!File::exists($dbPath)) {
                    File::put($dbPath, '');
                }

                config(['database.connections.sqlite.database' => $dbPath]);
                DB::purge('sqlite');
                DB::connection('sqlite')->getPdo();
            } else {
                if (!isset($data['db_host']) || empty($data['db_host'])) {
                    throw new \Exception(__('Please fill in database host'));
                }
                if (!isset($data['db_database']) || empty($data['db_database'])) {
                    throw new \Exception(__('Please fill in database name'));
                }

                // Set MySQL connection configuration
                config([
                    'database.connections.mysql.host' => $data['db_host'],
                    'database.connections.mysql.port' => $data['db_port'] ?? '3306',
                    'database.connections.mysql.database' => $data['db_database'],
                    'database.connections.mysql.username' => $data['db_username'] ?? 'root',
                    'database.connections.mysql.password' => $data['db_password'] ?? '',
                    'database.connections.mysql.charset' => 'utf8mb4',
                    'database.connections.mysql.collation' => 'utf8mb4_unicode_ci',
                ]);

                // Clear connection cache
                DB::purge('mysql');

                // Try to connect and execute a simple query to ensure connection is valid
                $pdo = DB::connection('mysql')->getPdo();
                DB::connection('mysql')->select('SELECT 1');
            }

            $this->isDatabaseTested = true;
            $this->databaseTestMessage = '✅ ' . __('Database connection test successful, you can proceed to the next step');
            $this->saveTestStatusToSession();

        } catch (\Exception $e) {
            $this->isDatabaseTested = false;
            $this->databaseTestMessage = '❌ ' . __('Database connection failed') . '：' . $e->getMessage();
            $this->saveTestStatusToSession();
        }
    }

    protected function generateEnvContent(array $data): string
    {
        $lines = [];

        // Application Configuration
        $lines[] = '# Application Configuration';
        $lines[] = 'APP_NAME="' . $data['app_name'] . '"';
        $lines[] = 'APP_ENV=' . $data['app_env'];
        $lines[] = 'APP_KEY=' . config('app.key');
        $lines[] = 'APP_DEBUG=' . ($data['app_debug'] ? 'true' : 'false');
        $lines[] = 'APP_URL=' . $data['app_url'];
        $lines[] = 'APP_TIMEZONE=' . $data['app_timezone'];
        $lines[] = 'APP_INSTALLED_AT=' . Carbon::now()->unix();
        $lines[] = '';

        // Database Configuration
        $lines[] = '# Database Configuration';
        $lines[] = 'DB_CONNECTION=' . $data['db_connection'];

        if ($data['db_connection'] === 'mysql') {
            $lines[] = 'DB_HOST=' . $data['db_host'];
            $lines[] = 'DB_PORT=' . $data['db_port'];
            $lines[] = 'DB_DATABASE=' . $data['db_database'];
            $lines[] = 'DB_USERNAME=' . $data['db_username'];
            $lines[] = 'DB_PASSWORD=' . ($data['db_password'] ? '"' . $data['db_password'] . '"' : '');
        } else {
            $lines[] = 'DB_DATABASE=' . $data['db_database'];
        }
        $lines[] = '';

        // Cache & Session
        $lines[] = '# Cache, Session';
        $lines[] = 'CACHE_DRIVER=file';
        $lines[] = 'SESSION_DRIVER=file';
        $lines[] = '';

        return implode("\n", $lines);
    }

    protected function generateEnvPreview($get): string
    {
        $data = [
            'app_name' => $get('app_name'),
            'app_env' => $get('app_env'),
            'app_key' => $get('app_key'),
            'app_debug' => $get('app_debug'),
            'app_url' => $get('app_url'),
            'app_timezone' => $get('app_timezone'),
            'db_connection' => $get('db_connection'),
            'db_host' => $get('db_host'),
            'db_port' => $get('db_port'),
            'db_database' => $get('db_database'),
            'db_username' => $get('db_username'),
            'db_password' => $get('db_password'),
        ];

        return $this->generateEnvContent($data);
    }

    public function submit(): void
    {
        // Start installation process
        $this->isInstalling = true;
        $this->installStep = 0;
        $this->commandLogs = []; // Reset command logs

        // Trigger the first step
        $this->dispatch('start-installation');
    }

    protected function executeCommand(string $command, array $parameters = []): array
    {
        $startTime = microtime(true);
        $this->addCommandLog(__('Executing command') . ": php artisan {$command} " . implode(' ', $parameters), 'info');

        try {
            $exitCode = Artisan::call($command, $parameters);
            $output = Artisan::output();
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($exitCode === 0) {
                $this->addCommandLog("✅ " . __('Command executed successfully') . " (" . __('Duration') . ": {$duration}ms)", 'success');
                if (!empty(trim($output))) {
                    $this->addCommandLog(__('Output') . ": " . trim($output), 'info');
                }
            } else {
                $this->addCommandLog("❌ " . __('Command execution failed') . " (" . __('Exit code') . ": {$exitCode})", 'error');
                if (!empty(trim($output))) {
                    $this->addCommandLog(__('Error output') . ": " . trim($output), 'error');
                }
            }

            return [
                'success' => $exitCode === 0,
                'output' => $output,
                'exit_code' => $exitCode,
                'duration' => $duration
            ];
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->addCommandLog("❌ " . __('Command execution exception') . ": " . $e->getMessage(), 'error');
            $this->addCommandLog(__('Duration') . ": {$duration}ms", 'error');

            return [
                'success' => false,
                'output' => $e->getMessage(),
                'exit_code' => 1,
                'duration' => $duration
            ];
        }
    }

    protected function addCommandLog(string $message, string $type = 'info'): void
    {
        $this->commandLogs[] = [
            'timestamp' => now()->format('H:i:s'),
            'message' => $message,
            'type' => $type
        ];

        // Keep only last 50 log entries to prevent memory issues
        if (count($this->commandLogs) > 50) {
            $this->commandLogs = array_slice($this->commandLogs, -50);
        }
    }


    public function executeNextStep(): void
    {
        try {
            $data = $this->data;
            $currentStep = $this->installStep;
            $nextStep = $currentStep + 1;

            if ($nextStep > 4) {
                // All steps completed
                $this->isCompleted = true;
                $this->isInstalling = false;

                // Add completion log
                $this->addCommandLog("🎉 " . __('Installation complete!'), 'success');
                $this->addCommandLog(__('You can now access the admin panel'), 'success');

                // Clear installation session data
                session()->forget('install_wizard_config');
                session()->forget('install_wizard_db_tested');
                session()->forget('install_wizard_db_message');

                return;
            }

            $this->installStep = $nextStep;
            $this->installStepMessage = __($this->installSteps[$nextStep]);

            switch ($nextStep) {
                case 1: // Write .env file
                    $this->writeEnvFile($data);
                    break;

                case 2: // Clear all caches
                    // Clear all caches to ensure fresh configuration
                    $this->executeCommand('optimize:clear');
                    break;

                case 3: // Run migrations
                    $this->executeCommand('migrate', ['--force' => true]);
                    break;

                case 4: // Create admin user
                    $this->executeCommand('db:seed', ['--force' => true]);
                    break;
            }

        } catch (\Exception $e) {
            // Log error to logs
            $this->addCommandLog("❌ " . __('Installation failed') . ": " . $e->getMessage(), 'error');
            $this->addCommandLog(__('Failed step') . ": " . __($this->installSteps[$nextStep]), 'error');

            $this->isInstalling = false;
            $this->isCompleted = false;
            // Don't reset installStep, keep current step to display logs

            Notification::make()
                ->title('❌ ' . __('Installation failed'))
                ->body(__('Step') . ' ' . $nextStep . ' (' . __($this->installSteps[$nextStep]) . ') ' . __('failed') . '：' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }


    protected function writeEnvFile(array $data): void
    {
        $this->addCommandLog(__('Generating .env configuration file...'), 'info');

        $envPath = base_path('.env');
        $envContent = $this->generateEnvContent($data);

        File::put($envPath, $envContent);

        $this->addCommandLog("✅ " . __('.env file generation completed'), 'success');
        $this->addCommandLog(__('File path') . ": {$envPath}", 'info');
    }


    public static function canAccess(): bool
    {
        return InstallUtil::canInstall();
    }
}
