<x-filament-panels::page>
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
</x-filament-panels::page>

