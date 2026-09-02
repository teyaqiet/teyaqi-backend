<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use App\Models\AutomationExecution;
use App\Services\Automation\AutomationEngine;
use Illuminate\Http\Request;
use Throwable;

class AutomationExecutionController extends Controller
{
    public function index(Automation $automation)
    {
        $executions = $automation->executions()
            ->latest()
            ->paginate(25);

        return view(
            'admin.automations.executions.index',
            compact('automation', 'executions')
        );
    }

    public function show(
        Automation $automation,
        AutomationExecution $execution
    ) {
        abort_unless(
            (int) $execution->automation_id === (int) $automation->id,
            404
        );

        $execution->load([
            'nodeExecutions.node',
        ]);

        return view(
            'admin.automations.executions.show',
            compact('automation', 'execution')
        );
    }

    public function retry(
        Request $request,
        Automation $automation,
        AutomationExecution $execution,
        AutomationEngine $engine
    ) {
        abort_unless(
            (int) $execution->automation_id === (int) $automation->id,
            404
        );

        if ($execution->status === 'running') {
            return back()->with(
                'error',
                'This execution is still running.'
            );
        }

        try {
            $context = is_array($execution->context)
                ? $execution->context
                : [];

            $triggerData = is_array($execution->trigger_data)
                ? $execution->trigger_data
                : [];

            $result = $engine->run(
                $automation,
                $context,
                $execution->trigger_type,
                $triggerData
            );

            return redirect()
                ->route(
                    'automations.executions.show',
                    [$automation, $result]
                )
                ->with(
                    'success',
                    'Automation execution retried successfully.'
                );

        } catch (Throwable $e) {
            report($e);

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }


    
}