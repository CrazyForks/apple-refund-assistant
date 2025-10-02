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
        1 => '写入 .env 配置文件',
        2 => '清除所有缓存',
        3 => '执行数据库迁移',
        4 => '创建管理员账户',
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
            'app_locale' => 'zh',

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
                    Step::make('环境检查')
                        ->icon('heroicon-o-shield-check')
                        ->description('检查系统环境和权限')
                        ->components([
                            Section::make('系统环境检查')
                                ->description('确保系统满足安装要求')
                                ->schema([
                                    TextEntry::make('storage_permissions')
                                        ->label('存储目录权限')
                                        ->placeholder(function () {
                                            $paths = [
                                                'storage' => storage_path(),
                                                'bootstrap/cache' => base_path('bootstrap/cache'),
                                            ];
                                            $results = [];
                                            foreach ($paths as $name => $path) {
                                                if (!is_dir($path)) {
                                                    $results[] = "❌ {$name}: 目录不存在";
                                                } elseif (!is_writable($path)) {
                                                    $results[] = "❌ {$name}: 无写入权限";
                                                } else {
                                                    $results[] = "✅ {$name}: 权限正常";
                                                }
                                            }
                                            return Html::make(implode("<br>", $results));
                                        }),
                                ])
                                ->footerActions([
                                    Action::make('refreshCheck')
                                        ->label('重新检查')
                                        ->icon('heroicon-o-arrow-path')
                                        ->color('gray')
                                        ->action(function () {
                                            // Force refresh the page to re-run checks
                                            $this->js('window.location.reload()');
                                        }),
                                ]),
                        ]),

                    Step::make('应用配置')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->description('配置应用基本信息')
                        ->afterValidation(function () {
                            $this->saveConfigToSession();
                        })
                        ->components([
                            Section::make('基本信息')
                                ->schema([
                                    TextInput::make('app_name')
                                        ->label('应用名称')
                                        ->required()
                                        ->maxLength(255),

                                    TextInput::make('app_url')
                                        ->label('应用 URL')
                                        ->required()
                                        ->url(),

                                    Select::make('app_env')
                                        ->label('运行环境')
                                        ->required()
                                        ->native(false)
                                        ->options([
                                            'local' => '本地 (Local)',
                                            'development' => '开发 (Development)',
                                            'production' => '生产 (Production)',
                                        ]),

                                    Select::make('app_debug')
                                        ->label('调试模式')
                                        ->required()
                                        ->native(false)
                                        ->boolean()
                                        ->helperText('生产环境建议关闭调试模式'),

                                    Select::make('app_timezone')
                                        ->label('应用时区')
                                        ->required()
                                        ->native(false)
                                        ->searchable()
                                        ->options([
                                            'Asia/Shanghai' => '中国标准时间 (Asia/Shanghai)',
                                            'Asia/Hong_Kong' => '中国香港时间 (Asia/Hong_Kong)',
                                            'Asia/Taipei' => '中国台北时间 (Asia/Taipei)',
                                            'Asia/Tokyo' => '东京时间 (Asia/Tokyo)',
                                            'Asia/Seoul' => '首尔时间 (Asia/Seoul)',
                                            'Asia/Singapore' => '新加坡时间 (Asia/Singapore)',
                                            'Asia/Bangkok' => '曼谷时间 (Asia/Bangkok)',
                                            'Asia/Kuala_Lumpur' => '吉隆坡时间 (Asia/Kuala_Lumpur)',
                                            'Asia/Jakarta' => '雅加达时间 (Asia/Jakarta)',
                                            'UTC' => '协调世界时 (UTC)',
                                            'America/New_York' => '纽约时间 (America/New_York)',
                                            'America/Los_Angeles' => '洛杉矶时间 (America/Los_Angeles)',
                                            'Europe/London' => '伦敦时间 (Europe/London)',
                                            'Europe/Paris' => '巴黎时间 (Europe/Paris)',
                                            'Europe/Berlin' => '柏林时间 (Europe/Berlin)',
                                            'Australia/Sydney' => '悉尼时间 (Australia/Sydney)',
                                        ])
                                        ->helperText('选择应用使用的时区，影响日志时间和定时任务'),

                                    Select::make('app_locale')
                                        ->label('应用语言')
                                        ->required()
                                        ->native(false)
                                        ->options([
                                            'zh' => '简体中文 (zh)',
                                            'en' => 'English (en)',
                                        ])
                                        ->helperText('选择应用界面显示语言'),
                                ])->columns(2),
                        ]),

                    Wizard\Step::make('数据库配置')
                        ->icon('heroicon-o-circle-stack')
                        ->description('配置数据库连接')
                        ->afterValidation(function () {
                            // Save database config to session
                            $this->saveConfigToSession();

                            if (!$this->isDatabaseTested) {
                                Notification::make()
                                    ->title('请先测试数据库连接')
                                    ->body('在进入下一步之前，请点击"测试数据库连接"按钮确保数据库配置正确')
                                    ->warning()
                                    ->persistent()
                                    ->send();

                                $this->halt();
                            }
                        })
                        ->components([
                            Section::make('数据库设置')
                                ->description(function (Get $get) {
                                    if ($get('db_connection') === 'sqlite') {
                                        return '⚠️ 重要提醒：如果指定的 SQLite 数据库文件已存在，安装过程可能会覆盖现有数据。请务必先备份好重要的数据库文件！';
                                    }
                                    return null;
                                })
                                ->schema([
                                    Select::make('db_connection')
                                        ->label('数据库类型')
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
                                        ->label('数据库主机')
                                        ->required(fn(Get $get) => $get('db_connection') === 'mysql')
                                        ->visible(fn(Get $get) => $get('db_connection') === 'mysql')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function () {
                                            $this->isDatabaseTested = false;
                                            $this->databaseTestMessage = '';
                                            $this->saveTestStatusToSession();
                                        }),

                                    TextInput::make('db_port')
                                        ->label('数据库端口')
                                        ->required(fn(Get $get) => $get('db_connection') === 'mysql')
                                        ->visible(fn(Get $get) => $get('db_connection') === 'mysql')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function () {
                                            $this->isDatabaseTested = false;
                                            $this->databaseTestMessage = '';
                                            $this->saveTestStatusToSession();
                                        }),

                                    TextInput::make('db_database')
                                        ->label(fn(Get $get) => $get('db_connection') === 'sqlite' ? '数据库文件路径' : '数据库名称')
                                        ->required()
                                        ->helperText(function (Get $get) {
                                            if ($get('db_connection') === 'sqlite') {
                                                return '相对于项目根目录。⚠️ 如果文件已存在，请先备份好现有数据库文件！';
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
                                        ->label('数据库用户名')
                                        ->required(fn(Get $get) => $get('db_connection') === 'mysql')
                                        ->visible(fn(Get $get) => $get('db_connection') === 'mysql')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function () {
                                            $this->isDatabaseTested = false;
                                            $this->databaseTestMessage = '';
                                            $this->saveTestStatusToSession();
                                        }),

                                    TextInput::make('db_password')
                                        ->label('数据库密码')
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

                            Section::make('连接测试')
                                ->schema([
                                    TextEntry::make('db_test_status')
                                        ->label(function () {
                                            if ($this->databaseTestMessage) {
                                                return $this->databaseTestMessage;
                                            }
                                            if ($this->isDatabaseTested) {
                                                return '✅ 数据库连接已测试通过';
                                            }
                                            return '⚠️ 请点击下方按钮测试数据库连接（必须测试通过才能进入下一步）';
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
                                        ->label('测试数据库连接')
                                        ->icon('heroicon-o-signal')
                                        ->color(fn() => $this->isDatabaseTested ? 'success' : 'primary')
                                        ->action(function () {
                                            $this->testDatabaseConnection();
                                        }),
                                ]),
                        ]),

                    Wizard\Step::make('确认配置')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->description('检查所有配置信息')
                        ->components([
                            Section::make('.env 文件预览')
                                ->description('保存好配置')
                                ->headerActions([
                                    Action::make('copyEnvContent')
                                        ->label('复制配置')
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

                    Wizard\Step::make('开始安装')
                        ->icon('heroicon-o-rocket-launch')
                        ->description('准备安装系统')
                        ->components([
                            Section::make('🎉 安装完成')
                                ->description('恭喜！系统安装已成功完成。')
                                ->schema([
                                    Html::make('<div class="text-center space-y-4">
                                          <div class="pt-4">
                                            <a href="/admin" target="_blank" style="color: #1e9fff;" class="text-blue-600 hover:text-blue-800 underline font-medium transition-colors">
                                                访问管理后台 /admin
                                            </a>
                                        </div>
                                        <div class="space-y-2">
                                            <p><strong>管理员账户信息：</strong></p>
                                            <p>邮箱: <code>admin@dev.com</code></p>
                                            <p>密码: <code>admin</code></p>
                                        </div>

                                        <div class="text-sm text-gray-600">
                                            <p>⚠️ 如果需要优化性能安全问题,请执行以下命令</p>
                                            <code>php artisan key:generate</code> <br>
                                            <code>php artisan optimize</code>
                                        </div>
                                    </div>')
                                ])
                                ->visible(fn() => $this->isCompleted),
                            Section::make('安装执行日志')
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
                                                return '等待命令执行...';
                                            }

                                            $logs = '';
                                            // 倒序显示日志（最新的在上面）
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
            // 只获取表单数据，不进行验证
            $data = $this->data;

            if (!isset($data['db_connection'])) {
                throw new \Exception('请先选择数据库类型');
            }

            $connection = $data['db_connection'];

            if ($connection === 'sqlite') {
                if (!isset($data['db_database']) || empty($data['db_database'])) {
                    throw new \Exception('请填写数据库文件路径');
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
                    throw new \Exception('请填写数据库主机');
                }
                if (!isset($data['db_database']) || empty($data['db_database'])) {
                    throw new \Exception('请填写数据库名称');
                }

                // 设置 MySQL 连接配置
                config([
                    'database.connections.mysql.host' => $data['db_host'],
                    'database.connections.mysql.port' => $data['db_port'] ?? '3306',
                    'database.connections.mysql.database' => $data['db_database'],
                    'database.connections.mysql.username' => $data['db_username'] ?? 'root',
                    'database.connections.mysql.password' => $data['db_password'] ?? '',
                    'database.connections.mysql.charset' => 'utf8mb4',
                    'database.connections.mysql.collation' => 'utf8mb4_unicode_ci',
                ]);

                // 清除连接缓存
                DB::purge('mysql');

                // 尝试连接并执行一个简单查询来确保连接有效
                $pdo = DB::connection('mysql')->getPdo();
                DB::connection('mysql')->select('SELECT 1');
            }

            $this->isDatabaseTested = true;
            $this->databaseTestMessage = '✅ 数据库连接测试成功，可以继续下一步';
            $this->saveTestStatusToSession();

        } catch (\Exception $e) {
            $this->isDatabaseTested = false;
            $this->databaseTestMessage = '❌ 数据库连接失败：' . $e->getMessage();
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
        $lines[] = 'APP_LOCALE=' . $data['app_locale'];
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
            'app_locale' => $get('app_locale'),
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
        $this->addCommandLog("执行命令: php artisan {$command} " . implode(' ', $parameters), 'info');

        try {
            $exitCode = Artisan::call($command, $parameters);
            $output = Artisan::output();
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($exitCode === 0) {
                $this->addCommandLog("✅ 命令执行成功 (耗时: {$duration}ms)", 'success');
                if (!empty(trim($output))) {
                    $this->addCommandLog("输出: " . trim($output), 'info');
                }
            } else {
                $this->addCommandLog("❌ 命令执行失败 (退出码: {$exitCode})", 'error');
                if (!empty(trim($output))) {
                    $this->addCommandLog("错误输出: " . trim($output), 'error');
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
            $this->addCommandLog("❌ 命令执行异常: " . $e->getMessage(), 'error');
            $this->addCommandLog("耗时: {$duration}ms", 'error');

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
                $this->addCommandLog("🎉 安装完成！", 'success');
                $this->addCommandLog("您现在可以访问管理后台了", 'success');

                // Clear installation session data
                session()->forget('install_wizard_config');
                session()->forget('install_wizard_db_tested');
                session()->forget('install_wizard_db_message');

                return;
            }

            $this->installStep = $nextStep;
            $this->installStepMessage = $this->installSteps[$nextStep];

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
            // 记录错误到日志中
            $this->addCommandLog("❌ 安装失败: " . $e->getMessage(), 'error');
            $this->addCommandLog("失败步骤: " . $this->installSteps[$nextStep], 'error');

            $this->isInstalling = false;
            $this->isCompleted = false;
            // 不要重置 installStep，保持当前步骤以显示日志

            Notification::make()
                ->title('❌ 安装失败')
                ->body('步骤 ' . $nextStep . ' (' . $this->installSteps[$nextStep] . ') 失败：' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }


    protected function writeEnvFile(array $data): void
    {
        $this->addCommandLog("生成 .env 配置文件...", 'info');

        $envPath = base_path('.env');
        $envContent = $this->generateEnvContent($data);

        File::put($envPath, $envContent);

        $this->addCommandLog("✅ .env 文件生成完成", 'success');
        $this->addCommandLog("文件路径: {$envPath}", 'info');
    }


    public static function canAccess(): bool
    {
        return InstallUtil::canInstall();
    }
}
