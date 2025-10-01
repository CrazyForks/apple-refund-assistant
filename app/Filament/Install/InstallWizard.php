<?php

namespace App\Filament\Install;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Notifications\Notification;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis as RedisFacade;

class InstallWizard extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.install-wizard';
    
    public ?array $data = [];
    public bool $isCompleted = false;
    public bool $isDatabaseTested = false;
    public bool $isInstalling = false;
    public int $installStep = 0;
    public string $installStepMessage = '';
    public array $installSteps = [
        1 => '写入 .env 配置文件',
        2 => '清除配置缓存',
        3 => '执行数据库迁移',
        4 => '创建管理员账户',
        5 => '完成安装设置',
    ];

    public function mount(): void
    {
        if (config('app.installed')) {
            redirect('/admin');
            return;
        }

        // Use existing APP_KEY if available, otherwise generate a new one
        $existingKey = config('app.key');
        if (empty($existingKey) || $existingKey === 'base64:' || strlen($existingKey) < 20) {
            $key = 'base64:'.base64_encode(
                Encrypter::generateKey(config('app.cipher'))
            );
        } else {
            $key = $existingKey;
        }
        
        $this->form->fill([
            'app_name' => 'Apple Refund Assistant',
            'app_url' => request()->getSchemeAndHttpHost(),
            'app_env' => 'production',
            'app_debug' => false,
            'app_key' => $key,

            'db_connection' => 'sqlite',
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_database' => 'database/database.sqlite',
            'db_username' => 'root',
            'db_password' => '',

            'queue_connection' => 'sync',

            'redis_host' => '127.0.0.1',
            'redis_port' => '6379',
            'redis_password' => '',
            'redis_database' => '0',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('应用配置')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->description('配置应用基本信息')
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

                                    TextInput::make('app_key')
                                        ->label('应用密钥 (APP_KEY)')
                                        ->placeholder('将自动生成')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->helperText('如果为空，将自动生成新密钥'),
                                ])->columns(2),
                        ]),

                    Wizard\Step::make('数据库配置')
                        ->icon('heroicon-o-circle-stack')
                        ->description('配置数据库连接')
                        ->afterValidation(function () {
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
                                            if ($state === 'sqlite') {
                                                $set('db_database', 'database/database.sqlite');
                                            }
                                        }),

                                    TextInput::make('db_host')
                                        ->label('数据库主机')
                                        ->required(fn(Get $get) => $get('db_connection') === 'mysql')
                                        ->visible(fn(Get $get) => $get('db_connection') === 'mysql'),

                                    TextInput::make('db_port')
                                        ->label('数据库端口')
                                        ->required(fn(Get $get) => $get('db_connection') === 'mysql')
                                        ->visible(fn(Get $get) => $get('db_connection') === 'mysql'),

                                    TextInput::make('db_database')
                                        ->label(fn(Get $get) => $get('db_connection') === 'sqlite' ? '数据库文件路径' : '数据库名称')
                                        ->required()
                                        ->helperText(fn(Get $get) => $get('db_connection') === 'sqlite' ? '相对于项目根目录' : ''),

                                    TextInput::make('db_username')
                                        ->label('数据库用户名')
                                        ->required(fn(Get $get) => $get('db_connection') === 'mysql')
                                        ->visible(fn(Get $get) => $get('db_connection') === 'mysql'),

                                    TextInput::make('db_password')
                                        ->label('数据库密码')
                                        ->password()
                                        ->revealable()
                                        ->visible(fn(Get $get) => $get('db_connection') === 'mysql'),
                                ])->columns(2),

                            Section::make('连接测试')
                                ->schema([
                                    TextEntry::make('db_test_status')
                                        ->label('测试状态')
                                        ->placeholder(function () {
                                            if ($this->isDatabaseTested) {
                                                return '✅ 数据库连接已测试通过';
                                            }
                                            return '⚠️ 请点击下方按钮测试数据库连接（必须测试通过才能进入下一步）';
                                        })
                                        ->color(fn () => $this->isDatabaseTested ? 'success' : 'warning'),
                                ])
                                ->footerActions([
                                    Action::make('testDatabaseConnection')
                                        ->label('测试数据库连接')
                                        ->icon('heroicon-o-signal')
                                        ->color(fn () => $this->isDatabaseTested ? 'success' : 'primary')
                                        ->action(function () {
                                            $this->testDatabaseConnection();
                                        }),
                                ])
                                ->description(function () {
                                    if (!$this->isDatabaseTested) {
                                        return '⚠️ 必须先测试数据库连接才能继续';
                                    }
                                    return null;
                                }),
                        ]),

                    Wizard\Step::make('队列配置')
                        ->icon('heroicon-o-queue-list')
                        ->description('选择队列驱动')
                        ->components([
                            Section::make('队列驱动')
                                ->description('缓存和会话驱动固定使用 File')
                                ->schema([
                                    Select::make('queue_connection')
                                        ->label('队列驱动')
                                        ->required()
                                        ->native(false)
                                        ->options([
                                            'null' => 'Null (不处理)',
                                            'sync' => 'Sync (同步处理)',
                                            'redis' => 'Redis',
                                        ])
                                        ->helperText('Null: 任务不会被执行；Sync: 同步立即执行；Redis: 使用 Redis 队列')
                                        ->reactive()
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Redis 配置')
                                ->schema([
                                    TextInput::make('redis_host')
                                        ->label('Redis 主机')
                                        ->required(),

                                    TextInput::make('redis_port')
                                        ->label('Redis 端口')
                                        ->required(),

                                    TextInput::make('redis_password')
                                        ->label('Redis 密码')
                                        ->password()
                                        ->revealable(),

                                    TextInput::make('redis_database')
                                        ->label('Redis 数据库索引')
                                        ->required()
                                        ->numeric(),
                                ])->columns(2)
                                ->visible(fn(Get $get) => $get('queue_connection') === 'redis')
                                ->footerActions([
                                    Action::make('testRedisConnection')
                                        ->label('测试 Redis 连接')
                                        ->icon('heroicon-o-signal')
                                        ->action(function () {
                                            $this->testRedisConnection();
                                        }),
                                ]),
                        ]),

                    Wizard\Step::make('确认配置')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->description('检查所有配置信息')
                        ->components([
                            Section::make('.env 文件预览')
                                ->description('保存好配置')
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
                            Section::make('管理员账户信息')
                                ->description('系统将自动创建以下管理员账户')
                                ->schema([
                                    TextEntry::make('default_admin_info')
                                        ->label('')
                                        ->placeholder('邮箱: admin@dev.com' . "\n" . '密码: admin' . "\n\n" . '⚠️ 安装完成后请立即修改默认密码')
                                        ->columnSpanFull(),
                                ])
                                ->visible(fn() => !$this->isInstalling && !$this->isCompleted),
                            
                            Section::make('安装步骤')
                                ->description('点击下方"开始安装"按钮后，系统将自动执行以下步骤')
                                ->schema([
                                    TextEntry::make('install_steps_preview')
                                        ->label('')
                                        ->placeholder(
                                            '1️⃣ 写入 .env 配置文件' . "\n" .
                                            '2️⃣ 清除配置缓存' . "\n" .
                                            '3️⃣ 执行数据库迁移' . "\n" .
                                            '4️⃣ 创建管理员账户' . "\n" .
                                            '5️⃣ 完成安装设置'
                                        )
                                        ->columnSpanFull(),
                                ])
                                ->visible(fn() => !$this->isInstalling && !$this->isCompleted),
                            
                            Section::make('安装进度')
                                ->description(fn() => $this->isCompleted ? '✅ 安装已完成！' : '正在安装中，请稍候...')
                                ->schema([
                                    TextEntry::make('install_progress')
                                        ->label('')
                                        ->placeholder(function () {
                                            if ($this->isCompleted) {
                                                return '🎉 系统安装成功！' . "\n\n" . 
                                                       '管理员账户：admin@dev.com / admin' . "\n\n" .
                                                       '即将跳转到登录页面...';
                                            }
                                            
                                            $progress = '';
                                            foreach ($this->installSteps as $step => $message) {
                                                if ($step < $this->installStep) {
                                                    $progress .= '✅ ' . $message . "\n";
                                                } elseif ($step === $this->installStep) {
                                                    $progress .= '⏳ ' . $message . '...' . "\n";
                                                } else {
                                                    $progress .= '⏸️ ' . $message . "\n";
                                                }
                                            }
                                            return $progress;
                                        })
                                        ->columnSpanFull()
                                        ->extraAttributes(['class' => 'text-lg']),
                                ])
                                ->visible(fn() => $this->isInstalling || $this->isCompleted),
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

            Notification::make()
                ->title('数据库连接成功')
                ->body('数据库连接测试通过，可以继续下一步')
                ->success()
                ->send();

        } catch (\Exception $e) {
            $this->isDatabaseTested = false;
            
            Notification::make()
                ->title('数据库连接失败')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function testRedisConnection(): void
    {
        try {
            // 只获取表单数据，不进行验证
            $data = $this->data;
            
            if (!isset($data['redis_host']) || empty($data['redis_host'])) {
                throw new \Exception('请填写 Redis 主机地址');
            }
            if (!isset($data['redis_port']) || empty($data['redis_port'])) {
                throw new \Exception('请填写 Redis 端口');
            }
            
            config([
                'database.redis.default.host' => $data['redis_host'],
                'database.redis.default.port' => $data['redis_port'],
                'database.redis.default.password' => $data['redis_password'] ?: null,
                'database.redis.default.database' => $data['redis_database'] ?? '0',
            ]);

            RedisFacade::connection()->ping();

            Notification::make()
                ->title('Redis 连接成功')
                ->body('Redis 连接测试通过')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Redis 连接失败')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    protected function generateEnvPreview($get): string
    {
        $lines = [];
        
        // Application
        $lines[] = '# Application Configuration';
        $lines[] = 'APP_NAME="' . $get('app_name') . '"';
        $lines[] = 'APP_ENV=' . $get('app_env');
        $lines[] = 'APP_KEY=' . $get('app_key');
        $lines[] = 'APP_DEBUG=' . ($get('app_debug') ? 'true' : 'false');
        $lines[] = 'APP_URL=' . $get('app_url');
        $lines[] = 'APP_INSTALLED=true';
        $lines[] = '';
        
        // Database
        $lines[] = '# Database Configuration';
        $lines[] = 'DB_CONNECTION=' . $get('db_connection');
        
        if ($get('db_connection') === 'mysql') {
            $lines[] = 'DB_HOST=' . $get('db_host');
            $lines[] = 'DB_PORT=' . $get('db_port');
            $lines[] = 'DB_DATABASE=' . $get('db_database');
            $lines[] = 'DB_USERNAME=' . $get('db_username');
            $lines[] = 'DB_PASSWORD=' . ($get('db_password') ? '"' . $get('db_password') . '"' : '');
        } else {
            $lines[] = 'DB_DATABASE=' . $get('db_database');
        }
        $lines[] = '';
        
        // Cache & Session & Queue
        $lines[] = '# Cache, Session & Queue Configuration';
        $lines[] = 'CACHE_DRIVER=file';
        $lines[] = 'SESSION_DRIVER=file';
        $lines[] = 'QUEUE_CONNECTION=' . $get('queue_connection');
        $lines[] = '';
        
        // Redis (if needed)
        if ($get('queue_connection') === 'redis') {
            $lines[] = '# Redis Configuration';
            $lines[] = 'REDIS_HOST=' . $get('redis_host');
            $lines[] = 'REDIS_PORT=' . $get('redis_port');
            $lines[] = 'REDIS_PASSWORD=' . ($get('redis_password') ? '"' . $get('redis_password') . '"' : '');
            $lines[] = 'REDIS_DB=' . $get('redis_database');
            $lines[] = '';
        }
        
        return implode("\n", $lines);
    }

    public function submit(): void
    {
        // Start installation process
        $this->isInstalling = true;
        $this->installStep = 0;
        
        // Trigger the first step
        $this->dispatch('start-installation');
    }

    public function executeNextStep(): void
    {
        try {
            $data = $this->data;
            $currentStep = $this->installStep;
            $nextStep = $currentStep + 1;

            if ($nextStep > 5) {
                // All steps completed
                $this->isCompleted = true;
                $this->isInstalling = false;
                
                // Redirect after a short delay
                $this->dispatch('installation-completed');
                return;
            }

            $this->installStep = $nextStep;
            $this->installStepMessage = $this->installSteps[$nextStep];

            switch ($nextStep) {
                case 1: // Write .env file
                    // Use the APP_KEY from form data (which is already set in runtime)
                    $appKey = $data['app_key'] ?? config('app.key');
                    if (empty($appKey) || $appKey === 'base64:') {
                        $appKey = 'base64:'.base64_encode(
                            Encrypter::generateKey(config('app.cipher'))
                        );
                    }
                    
                    // Set the APP_KEY in runtime config BEFORE writing to .env
                    config(['app.key' => $appKey]);
                    $this->writeEnvFile($data, $appKey);
                    break;

                case 2: // Clear config cache (skipped)
                    // Skip config:clear to avoid reloading APP_KEY and invalidating session
                    break;

                case 3: // Run migrations
                    $this->setDatabaseConfig($data);
                    Artisan::call('migrate', ['--force' => true]);
                    break;

                case 4: // Create admin user
                    Artisan::call('make:filament-user', [
                        '--name' => 'Admin',
                        '--email' => 'admin@dev.com',
                        '--password' => 'admin',
                    ]);
                    break;

                case 5: // Finalize installation
                    $this->updateEnvValue('APP_INSTALLED', 'true');
                    break;
            }

        } catch (\Exception $e) {
            $this->isInstalling = false;
            $this->isCompleted = false;
            $this->installStep = 0;
            
            Notification::make()
                ->title('❌ 安装失败')
                ->body('步骤 ' . $this->installStep . ' 失败：' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    protected function writeEnvFile(array $data, string $appKey): void
    {
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        if (!File::exists($envPath) && File::exists($envExamplePath)) {
            File::copy($envExamplePath, $envPath);
        }

        $envplaceholder = File::exists($envPath) ? File::get($envPath) : '';

        $envplaceholder = $this->replaceEnvValue($envplaceholder, 'APP_NAME', $data['app_name']);
        $envplaceholder = $this->replaceEnvValue($envplaceholder, 'APP_ENV', $data['app_env']);
        $envplaceholder = $this->replaceEnvValue($envplaceholder, 'APP_KEY', $appKey);
        $envplaceholder = $this->replaceEnvValue($envplaceholder, 'APP_DEBUG', $data['app_debug'] ? 'true' : 'false');
        $envplaceholder = $this->replaceEnvValue($envplaceholder, 'APP_URL', $data['app_url']);

        $envplaceholder = $this->replaceEnvValue($envplaceholder, 'DB_CONNECTION', $data['db_connection']);

        if ($data['db_connection'] === 'mysql') {
            $envplaceholder = $this->replaceEnvValue($envplaceholder, 'DB_HOST', $data['db_host']);
            $envplaceholder = $this->replaceEnvValue($envplaceholder, 'DB_PORT', $data['db_port']);
            $envplaceholder = $this->replaceEnvValue($envplaceholder, 'DB_DATABASE', $data['db_database']);
            $envplaceholder = $this->replaceEnvValue($envplaceholder, 'DB_USERNAME', $data['db_username']);
            $envplaceholder = $this->replaceEnvValue($envplaceholder, 'DB_PASSWORD', $data['db_password']);
        } else {
            $envplaceholder = $this->replaceEnvValue($envplaceholder, 'DB_DATABASE', $data['db_database']);
        }

        $envplaceholder = $this->replaceEnvValue($envplaceholder, 'CACHE_DRIVER', 'file');
        $envplaceholder = $this->replaceEnvValue($envplaceholder, 'SESSION_DRIVER', 'file');
        $envplaceholder = $this->replaceEnvValue($envplaceholder, 'QUEUE_CONNECTION', $data['queue_connection']);

        if ($data['queue_connection'] === 'redis') {
            $envplaceholder = $this->replaceEnvValue($envplaceholder, 'REDIS_HOST', $data['redis_host']);
            $envplaceholder = $this->replaceEnvValue($envplaceholder, 'REDIS_PORT', $data['redis_port']);
            $envplaceholder = $this->replaceEnvValue($envplaceholder, 'REDIS_PASSWORD', $data['redis_password'] ?? '');
            $envplaceholder = $this->replaceEnvValue($envplaceholder, 'REDIS_DB', $data['redis_database']);
        }

        File::put($envPath, $envplaceholder);
    }

    protected function replaceEnvValue(string $placeholder, string $key, string $value): string
    {
        $pattern = "/^{$key}=.*/m";
        $replacement = "{$key}=" . $this->formatEnvValue($value);

        if (preg_match($pattern, $placeholder)) {
            return preg_replace($pattern, $replacement, $placeholder);
        }

        return $placeholder . "\n" . $replacement;
    }

    protected function formatEnvValue(string $value): string
    {
        if (preg_match('/\s/', $value) || empty($value)) {
            return '"' . $value . '"';
        }

        return $value;
    }

    protected function updateEnvValue(string $key, string $value): void
    {
        $envPath = base_path('.env');

        if (File::exists($envPath)) {
            $envplaceholder = File::get($envPath);
            $envplaceholder = $this->replaceEnvValue($envplaceholder, $key, $value);
            File::put($envPath, $envplaceholder);
        }
    }

    protected function setDatabaseConfig(array $data): void
    {
        if ($data['db_connection'] === 'sqlite') {
            $dbPath = base_path($data['db_database']);
            config(['database.connections.sqlite.database' => $dbPath]);
            config(['database.default' => 'sqlite']);
            DB::purge('sqlite');
        } else {
            config([
                'database.connections.mysql.host' => $data['db_host'],
                'database.connections.mysql.port' => $data['db_port'] ?? '3306',
                'database.connections.mysql.database' => $data['db_database'],
                'database.connections.mysql.username' => $data['db_username'] ?? 'root',
                'database.connections.mysql.password' => $data['db_password'] ?? '',
            ]);
            config(['database.default' => 'mysql']);
            DB::purge('mysql');
        }
    }

    public function getMaxplaceholderWidth(): Width
    {
        return Width::SevenExtraLarge;
    }

    public static function canAccess(): bool
    {
        return !config('app.installed');
    }
}
