<x-filament-panels::page>
    @if ($isCompleted)
        <div class="text-center py-12">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-success-100 dark:bg-success-900/20">
                <svg class="h-6 w-6 text-success-600 dark:text-success-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">安装成功完成!</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">系统已成功安装并配置完成</p>
            <div class="mt-6">
                <a href="/admin/login" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700">
                    前往登录
                </a>
            </div>
        </div>
    @else
        <div x-data="{ 
            isDatabaseTested: @entangle('isDatabaseTested'),
            isInstalling: @entangle('isInstalling')
        }">
            <form wire:submit="submit">
                {{ $this->form }}
            </form>
            
            @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const checkButtons = () => {
                        const isDatabaseTested = @js($isDatabaseTested);
                        
                        // 查找当前步骤
                        const steps = document.querySelectorAll('[data-step]');
                        let currentStepIndex = -1;
                        
                        steps.forEach((step, index) => {
                            if (step.hasAttribute('data-current-step')) {
                                currentStepIndex = index;
                            }
                        });
                        
                        // 如果在数据库配置步骤（索引为1）
                        if (currentStepIndex === 1 && !isDatabaseTested) {
                            const nextButtons = document.querySelectorAll('button[type="button"]');
                            nextButtons.forEach(button => {
                                if (button.textContent.includes('Next') || button.textContent.includes('下一步')) {
                                    button.disabled = true;
                                    button.classList.add('opacity-50', 'cursor-not-allowed');
                                }
                            });
                        }
                    };
                    
                    // 初始检查
                    setTimeout(checkButtons, 100);
                    
                    // Livewire 更新后检查
                    Livewire.hook('commit', ({ component, commit, respond, succeed }) => {
                        succeed(() => {
                            setTimeout(checkButtons, 100);
                        });
                    });
                    
                    // Listen for copy to clipboard event
                    Livewire.on('copy-to-clipboard', (event) => {
                        const content = event.content;
                        navigator.clipboard.writeText(content).then(() => {
                            // Show success notification
                            window.$wireui?.notify({
                                title: '复制成功',
                                description: '配置内容已复制到剪贴板',
                                icon: 'success'
                            });
                            
                            // Fallback for Filament notification
                            if (window.Filament) {
                                new window.FilamentNotification()
                                    .title('复制成功')
                                    .success()
                                    .send();
                            }
                        }).catch(err => {
                            console.error('Failed to copy:', err);
                        });
                    });
                    
                    // Listen for installation start event
                    Livewire.on('start-installation', () => {
                        // Start executing steps
                        executeInstallationSteps();
                    });
                    
                    // Listen for installation completed event
                    Livewire.on('installation-completed', () => {
                        // Redirect after 3 seconds
                        setTimeout(() => {
                            window.location.href = '/admin/login';
                        }, 3000);
                    });
                    
                    // Execute installation steps one by one
                    function executeInstallationSteps() {
                        if (@this.installStep < 5 && @this.isInstalling) {
                            @this.executeNextStep().then(() => {
                                // Wait 800ms before next step to show progress
                                setTimeout(() => {
                                    executeInstallationSteps();
                                }, 800);
                            }).catch((error) => {
                                console.error('Installation step failed:', error);
                            });
                        }
                    }
                });
            </script>
            @endpush
        </div>
    @endif
</x-filament-panels::page>

