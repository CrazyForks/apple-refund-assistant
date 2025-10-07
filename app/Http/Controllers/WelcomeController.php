<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class WelcomeController extends Controller
{
    public function index()
    {
        $uptime = $this->getSystemUptime();
        
        return view('welcome', [
            'uptime' => $uptime,
        ]);
    }
    
    /**
     * Get system uptime information
     * 
     * @return array{days: int, hours: int, minutes: int, seconds: int, formatted: string}
     */
    private function getSystemUptime(): array
    {
        $bootTime = $this->getBootTime();
        $now = Carbon::now();
        
        // Calculate the difference
        $diff = $now->diff($bootTime);
        
        return [
            'days' => $diff->days,
            'hours' => $diff->h,
            'minutes' => $diff->i,
            'seconds' => $diff->s,
            'formatted' => $bootTime->diffForHumans($now, true),
        ];
    }
    
    /**
     * Get system boot time
     * 
     * @return Carbon
     */
    protected function getBootTime(): Carbon
    {
        // Cache the computed boot time to avoid repeated /proc reads (keyed per container)
        $cacheKey = 'system_boot_time_epoch_' . gethostname();
        $cached = Cache::get($cacheKey);
        if (is_int($cached) && $cached > 0) {
            return Carbon::createFromTimestamp($cached);
        }

        // Method 1: Calculate PID 1 start time via /proc (most reliable, no Docker CLI needed)
        $containerStartTime = $this->getContainerStartTimeFromProc();
        if ($containerStartTime) {
            Cache::put($cacheKey, $containerStartTime->getTimestamp(), Carbon::now()->addHour());
            return $containerStartTime;
        }

        // Method 2: Try to get container start time from environment variables
        $containerStartTime = $this->getContainerStartTimeFromEnv();
        if ($containerStartTime) {
            Cache::put($cacheKey, $containerStartTime->getTimestamp(), Carbon::now()->addHour());
            return $containerStartTime;
        }

        // Method 3: Try to calculate from /proc/uptime (note: this is host-level uptime, accuracy depends on btime correction)
        $containerStartTime = $this->getContainerStartTimeFromUptime();
        if ($containerStartTime) {
            Cache::put($cacheKey, $containerStartTime->getTimestamp(), Carbon::now()->addHour());
            return $containerStartTime;
        }

        // Method 4: Try to get from Docker API (usually not available inside container)
        $containerStartTime = $this->getContainerStartTimeFromDockerApi();
        if ($containerStartTime) {
            Cache::put($cacheKey, $containerStartTime->getTimestamp(), Carbon::now()->addHour());
            return $containerStartTime;
        }

        // Fallback to other system methods
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: Use WMI command
            try {
                exec('wmic os get lastbootuptime', $output);
                if (isset($output[1])) {
                    $bootTime = trim($output[1]);
                    $year = substr($bootTime, 0, 4);
                    $month = substr($bootTime, 4, 2);
                    $day = substr($bootTime, 6, 2);
                    $hour = substr($bootTime, 8, 2);
                    $minute = substr($bootTime, 10, 2);
                    $second = substr($bootTime, 12, 2);
                    
                    return Carbon::create($year, $month, $day, $hour, $minute, $second);
                }
            } catch (\Exception $e) {
                // Fallback will be handled below
            }
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            // macOS/BSD: Use sysctl
            try {
                exec('sysctl -n kern.boottime', $output);
                if (isset($output[0])) {
                    preg_match('/sec = (\d+)/', $output[0], $matches);
                    if (isset($matches[1])) {
                        return Carbon::createFromTimestamp((int) $matches[1]);
                    }
                }
            } catch (\Exception $e) {
                // Fallback will be handled below
            }
        }

        // Final fallback: use application start time
        if (defined('LARAVEL_START')) {
            Cache::put($cacheKey, LARAVEL_START, Carbon::now()->addHour());
            return Carbon::createFromTimestamp(LARAVEL_START);
        }
        
        // Ultimate fallback: current request time
        $fallback = (int) ($_SERVER['REQUEST_TIME'] ?? time());
        Cache::put($cacheKey, $fallback, Carbon::now()->addHour());
        return Carbon::createFromTimestamp($fallback);
    }

    protected function getContainerStartTimeFromProc(): ?Carbon
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            return null;
        }

        // 1) Read host boot time (seconds)
        $statContent = @file_get_contents('/proc/stat');
        if ($statContent === false) {
            return null;
        }
        if (!preg_match('/^btime\s+(\d+)/m', $statContent, $btimeMatch)) {
            return null;
        }
        $bootTimeEpoch = (int) $btimeMatch[1];

        // 2) Read PID 1 start time (clock ticks since boot)
        $pid1Stat = @file_get_contents('/proc/1/stat');
        if ($pid1Stat === false) {
            return null;
        }
        // comm field is in parentheses, need to locate the last closing parenthesis
        $parenPos = strrpos($pid1Stat, ')');
        if ($parenPos === false) {
            return null;
        }
        $after = substr($pid1Stat, $parenPos + 1);
        $fields = preg_split('/\s+/', trim($after));
        // Note: field 22 in /proc/[pid]/stat (1-based counting) is starttime
        // We split after the parenthesis, so the index should be 21-2 = 19? More stable to parse the whole line:
        // Here we use the traditional method: after splitting after parenthesis, the 20th element (index 19) is not always stable, so we parse the whole line:
        $allFields = preg_split('/\s+/', trim($pid1Stat));
        if (!$allFields || count($allFields) < 22) {
            return null;
        }
        $startTicks = (int) $allFields[21]; // Field 22 (0-based index 21)

        // 3) Get clock ticks per second
        $ticksPerSecond = 100;
        if (function_exists('posix_sysconf') && defined('_SC_CLK_TCK')) {
            $ticksPerSecond = (int) posix_sysconf(_SC_CLK_TCK) ?: 100;
        } elseif (is_executable('/usr/bin/getconf')) {
            $ticksOutput = @shell_exec('getconf CLK_TCK 2>/dev/null');
            if (is_string($ticksOutput) && trim($ticksOutput) !== '') {
                $ticksPerSecond = (int) trim($ticksOutput) ?: 100;
            }
        }

        if ($startTicks <= 0 || $ticksPerSecond <= 0) {
            return null;
        }

        $startSinceBootSeconds = (int) floor($startTicks / $ticksPerSecond);
        $startEpoch = $bootTimeEpoch + $startSinceBootSeconds;
        return Carbon::createFromTimestamp($startEpoch);
    }

    protected function getContainerStartTimeFromEnv(): ?Carbon
    {
        // Check for Docker-related environment variables
        $envVars = [
            'CONTAINER_START_TIME',
            'DOCKER_CONTAINER_START_TIME',
            'START_TIME'
        ];

        foreach ($envVars as $envVar) {
            $value = getenv($envVar);
            if ($value) {
                try {
                    return Carbon::parse($value);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return null;
    }

    protected function getContainerStartTimeFromUptime(): ?Carbon
    {
        if (!file_exists('/proc/uptime')) {
            return null;
        }

        $uptimeData = file_get_contents('/proc/uptime');
        if (!$uptimeData) {
            return null;
        }

        $parts = explode(' ', trim($uptimeData));
        if (count($parts) < 1) {
            return null;
        }

        $uptimeSeconds = (float) $parts[0];
        $currentTime = time();
        $startTime = $currentTime - $uptimeSeconds;

        return Carbon::createFromTimestamp($startTime);
    }

    protected function getContainerStartTimeFromDockerApi(): ?Carbon
    {
        // Try to get container info from Docker socket
        $containerId = gethostname();
        
        // Try to read /proc/self/cgroup to get container ID
        if (file_exists('/proc/self/cgroup')) {
            $cgroupData = file_get_contents('/proc/self/cgroup');
            if (preg_match('/docker\/([a-f0-9]{64})/', $cgroupData, $matches)) {
                $containerId = $matches[1];
            }
        }

        // Try to use docker inspect command
        $command = "docker inspect {$containerId} --format='{{.State.StartedAt}}' 2>/dev/null";
        $output = [];
        $result = @exec($command, $output, $exitCode);
        
        if ($exitCode === 0 && !empty($output[0])) {
            try {
                return Carbon::parse(trim($output[0]));
            } catch (\Exception $e) {
                // Ignore parsing errors
            }
        }

        return null;
    }
}


