<?php

namespace Tests\Unit\Utils;

use App\Utils\InstallUtil;
use Carbon\Carbon;
use Tests\TestCase;

class InstallUtilTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2024-01-01 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_can_install_returns_true_when_installed_at_is_null(): void
    {
        config(['app.installed_at' => null]);

        $result = InstallUtil::canInstall();

        $this->assertTrue($result);
    }

    public function test_can_install_returns_true_within_60_seconds(): void
    {
        // Set installed_at to 30 seconds ago
        $installedAt = Carbon::now()->subSeconds(30)->unix();
        config(['app.installed_at' => $installedAt]);

        $result = InstallUtil::canInstall();

        $this->assertTrue($result);
    }

    public function test_can_install_returns_false_at_exactly_60_seconds(): void
    {
        // Set installed_at to exactly 60 seconds ago
        $installedAt = Carbon::now()->subSeconds(60)->unix();
        config(['app.installed_at' => $installedAt]);

        $result = InstallUtil::canInstall();

        $this->assertFalse($result);
    }

    public function test_can_install_returns_false_after_60_seconds(): void
    {
        // Set installed_at to 61 seconds ago
        $installedAt = Carbon::now()->subSeconds(61)->unix();
        config(['app.installed_at' => $installedAt]);

        $result = InstallUtil::canInstall();

        $this->assertFalse($result);
    }

    public function test_can_install_returns_false_after_long_time(): void
    {
        // Set installed_at to 1 hour ago
        $installedAt = Carbon::now()->subHour()->unix();
        config(['app.installed_at' => $installedAt]);

        $result = InstallUtil::canInstall();

        $this->assertFalse($result);
    }

    public function test_can_install_returns_true_just_before_60_seconds(): void
    {
        // Set installed_at to 59 seconds ago
        $installedAt = Carbon::now()->subSeconds(59)->unix();
        config(['app.installed_at' => $installedAt]);

        $result = InstallUtil::canInstall();

        $this->assertTrue($result);
    }
}
