<?php

namespace App\Services\Automation\Nodes;

use App\Models\AutomationExecution;
use App\Models\AutomationNode;
use App\Services\Automation\NodeResult;

class DelayNode
{
    public function handle(
        AutomationExecution $execution,
        AutomationNode $node,
        array $context
    ): NodeResult {

        $config =
            $node->config ?? [];

        if (!is_array($config)) {

            $config = [];

        }


        /*
        |--------------------------------------------------------------------------
        | Duration
        |--------------------------------------------------------------------------
        */

        $duration =
            (int) (
                $config['duration']
                ?? 1
            );


        if ($duration < 1) {

            $duration = 1;

        }


        /*
        |--------------------------------------------------------------------------
        | Unit
        |--------------------------------------------------------------------------
        */

        $unit =
            strtolower(
                (string) (
                    $config['unit']
                    ?? 'seconds'
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Convert To Seconds
        |--------------------------------------------------------------------------
        */

        $seconds =
            match ($unit) {

                'second',
                'seconds' =>
                    $duration,

                'minute',
                'minutes' =>
                    $duration * 60,

                'hour',
                'hours' =>
                    $duration * 60 * 60,

                'day',
                'days' =>
                    $duration * 60 * 60 * 24,

                default =>
                    $duration,
            };


        /*
        |--------------------------------------------------------------------------
        | Return Waiting Result
        |--------------------------------------------------------------------------
        */

        return NodeResult::wait([
            'delay' => [

                'duration' =>
                    $duration,

                'unit' =>
                    $unit,

                'seconds' =>
                    $seconds,

                'status' =>
                    'waiting',

            ],
        ]);
    }
}