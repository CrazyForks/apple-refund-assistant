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
        
        // Try to get uptime from different sources
        if (file_exists('/proc/uptime')) {
            // Linux/Docker: Read from /proc/uptime
            $uptimeContent = file_get_contents('/proc/uptime');
            if ($uptimeContent !== false) {
                $parts = explode(' ', $uptimeContent);
                $uptimeSeconds = (int) floor((float) $parts[0]);
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
        
        // If we got uptime seconds from /proc/uptime, calculate boot time
        if ($uptimeSeconds > 0) {
            return Carbon::now()->subSeconds($uptimeSeconds);
        }
        
        // Final fallback: use application start time
        if (defined('LARAVEL_START')) {
            return Carbon::createFromTimestamp(LARAVEL_START);
        }
        
        // Ultimate fallback: current request time
        return Carbon::createFromTimestamp($_SERVER['REQUEST_TIME'] ?? time());
    }
}


