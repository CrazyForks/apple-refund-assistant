<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

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
    private function getBootTime(): Carbon
    {
        $uptimeSeconds = 0;

        // Prefer container boot time on Linux by combining btime and PID 1 start ticks
        if (PHP_OS_FAMILY === 'Linux') {
            try {
                $btime = null; // seconds since epoch when the host booted
                $statContent = @file_get_contents('/proc/stat');
                if ($statContent !== false) {
                    if (preg_match('/^btime\s+(\d+)/m', $statContent, $m)) {
                        $btime = (int) $m[1];
                    }
                }

                // Field 22 in /proc/1/stat is starttime (clock ticks since boot)
                $pid1Stat = @file_get_contents('/proc/1/stat');
                $startTicks = null;
                if ($pid1Stat !== false) {
                    // /proc/[pid]/stat has spaces inside comm (field 2), so split carefully
                    // Extract by using the last ')' then split remaining by space
                    $pos = strrpos($pid1Stat, ')');
                    if ($pos !== false) {
                        $after = trim(substr($pid1Stat, $pos + 1));
                        $fields = preg_split('/\s+/', $after);
                        // Field 22 overall -> in $after it's index 20 (0-based), since after contains fields starting from field 3
                        if (isset($fields[20])) {
                            $startTicks = (int) $fields[20];
                        }
                    }
                }

                // Determine clock ticks per second
                $clkTck = 100; // sensible default
                $output = [];
                @exec('getconf CLK_TCK', $output);
                if (!empty($output[0]) && ctype_digit(trim($output[0]))) {
                    $clkTck = (int) trim($output[0]);
                }

                if ($btime !== null && $startTicks !== null && $clkTck > 0) {
                    $containerStart = $btime + (int) floor($startTicks / $clkTck);
                    return Carbon::createFromTimestamp($containerStart);
                }

                // Fallback to /proc/uptime → host uptime (less preferred)
                $uptimeContent = @file_get_contents('/proc/uptime');
                if ($uptimeContent !== false) {
                    $parts = explode(' ', $uptimeContent);
                    $uptimeSeconds = (int) floor((float) $parts[0]);
                    if ($uptimeSeconds > 0) {
                        return Carbon::now()->subSeconds($uptimeSeconds);
                    }
                }
            } catch (\Throwable $e) {
                // Continue to next strategies
            }
        } elseif (PHP_OS_FAMILY === 'Windows') {
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
        } else {
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
            return Carbon::createFromTimestamp(LARAVEL_START);
        }
        
        // Ultimate fallback: current request time
        return Carbon::createFromTimestamp($_SERVER['REQUEST_TIME'] ?? time());
    }
}


