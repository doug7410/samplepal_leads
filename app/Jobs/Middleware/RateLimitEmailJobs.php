<?php

namespace App\Jobs\Middleware;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RateLimitEmailJobs
{
    /**
     * The rate at which jobs are processed (in seconds).
     */
    protected int $throttleSeconds = 1;

    /**
     * Process the job.
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @return mixed
     */
    public function handle($job, $next)
    {
        $key = 'email_job_throttle';

        // Get the last execution time from cache
        $lastExecutionTime = Cache::get($key);
        $now = microtime(true);

        if ($lastExecutionTime) {
            $timeSinceLastExecution = $now - $lastExecutionTime;

            // If less than the throttle time has passed, wait for the remaining time
            if ($timeSinceLastExecution < $this->throttleSeconds) {
                $sleepTime = $this->throttleSeconds - $timeSinceLastExecution;
                Log::info("Rate limiting email job: sleeping for {$sleepTime} seconds");

                // Use usleep for more precise microsecond sleep
                usleep($sleepTime * 1000000);
            }
        }

        // Update the last execution time before processing the job
        Cache::put($key, microtime(true), 60);

        // Process the job
        return $next($job);
    }
}
