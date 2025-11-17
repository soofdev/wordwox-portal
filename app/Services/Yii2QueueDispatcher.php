<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class Yii2QueueDispatcher
{
    protected $connection = 'mysql';

    public function dispatch(string $jobClass, array $jobData = [])
    {
        $stringData = json_encode($jobData);
        $this->log("Dispatch Yii2 Job: {$jobClass}, {$stringData}");

        $now = Carbon::now();

        // Create a job object with the provided data
        $job = new \stdClass();
        foreach ($jobData as $key => $value) {
            $job->$key = $value;
        }

        // Serialize the job in Yii2's format
        $serializedJob = sprintf(
            'O:%d:"%s":%d:{%s}',
            strlen($jobClass),
            $jobClass,
            count((array)$job),
            implode('', array_map(
                function ($key, $value) {
                    return sprintf(
                        's:%d:"%s";%s',
                        strlen($key),
                        $key,
                        $this->serializeValue($value)
                    );
                },
                array_keys((array)$job),
                array_values((array)$job)
            ))
        );

        DB::connection($this->connection)->table('queue')->insert([
            'channel' => 'default',
            'job' => $serializedJob,
            'pushed_at' => $now->timestamp,
            'ttr' => 30
        ]);
    }

    private function serializeValue($value)
    {
        if (is_int($value)) {
            return "i:{$value};";
        } elseif (is_bool($value)) {
            return 'b:' . ($value ? '1' : '0') . ';';
        } elseif (is_string($value)) {
            return sprintf('s:%d:"%s";', strlen($value), $value);
        }
        // Add more types as needed
        return serialize($value);
    }

    private function log($message)
    {
        Log::info($message);
        // If running in console, output to console as well
        if (app()->runningInConsole()) {
            echo date('[Y-m-d H:i:s]') . " {$message}\n";
        }
    }
}