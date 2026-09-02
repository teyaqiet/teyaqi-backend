<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use App\Models\AutomationConnection;
use App\Models\AutomationNode;
use App\Services\Automation\AutomationValidator;
use App\Services\Automation\Triggers\AutomationTriggerRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AutomationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $automations = Automation::query()
            ->withCount([
                'nodes',
                'executions',
            ])
            ->with([
                'executions' => fn ($query) =>
                    $query->latest()->limit(1),
            ])
            ->latest()
            ->paginate(20);

        $stats = [
            'total' => Automation::count(),
            'active' => Automation::where('status', 'active')->count(),
            'draft' => Automation::where('status', 'draft')->count(),
            'paused' => Automation::where('status', 'paused')->count(),
        ];

        return view(
            'admin.automations.index',
            compact('automations', 'stats')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        return view('admin.automations.create');
    }


    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                'in:draft,active,paused',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | New automations should normally begin as draft.
        |--------------------------------------------------------------------------
        */

        $status = $validated['status'];

        if ($status === 'active') {
            $status = 'draft';
        }

        $automation = new Automation();

        $automation->name =
            $validated['name'];

        $automation->description =
            $validated['description'] ?? null;

        $automation->status =
            $status;

        $automation->version =
            1;

        $automation->total_runs =
            0;

        $automation->successful_runs =
            0;

        $automation->failed_runs =
            0;

        $automation->save();

        return redirect()
            ->route(
                'admin.automations.builder',
                $automation
            )
            ->with(
                'success',
                'Automation created successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(Automation $automation)
    {
        return view(
            'admin.automations.edit',
            compact('automation')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update Details
    |--------------------------------------------------------------------------
    */

    public function updateDetails(
        Request $request,
        Automation $automation,
        AutomationValidator $validator
    ) {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                'in:draft,active,paused',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Validate before activation
        |--------------------------------------------------------------------------
        */

        if (
            $validated['status'] === 'active' &&
            $automation->status !== 'active'
        ) {
            $validation =
                $validator->validate(
                    $automation
                );

            if (!$validation['valid']) {
                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Automation cannot be activated until the validation errors are fixed.'
                    )
                    ->with(
                        'automation_validation',
                        $validation
                    );
            }
        }

        $automation->update([
            'name' =>
                $validated['name'],

            'description' =>
                $validated['description'] ?? null,

            'status' =>
                $validated['status'],
        ]);

        return redirect()
            ->route(
                'admin.automations.edit',
                $automation
            )
            ->with(
                'success',
                'Automation updated successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Builder
    |--------------------------------------------------------------------------
    */

    public function builder(
    Automation $automation,
    AutomationTriggerRegistry $triggerRegistry
) {
    $automation->load([
        'nodes',
        'connections',
    ]);

    $builderNodes = $automation->nodes
        ->map(function ($node) {

            $component = $node->component;

            $defaultName = match ($component) {
                'trigger' => 'Trigger',
                'condition' => 'Condition',
                'telegram_message' => 'Telegram Message',
                'delay' => 'Delay',
                'end' => 'End',
                default => 'Node',
            };

            $config = $node->config;

            if (is_string($config)) {
                $config = json_decode($config, true);
            }

            if (!is_array($config)) {
                $config = [];
            }

            return [
                'id' => $node->id,

                'type' => $component,

                'name' => $node->name ?: $defaultName,

                'description' => '',

                'enabled' => (bool) $node->enabled,

                'position' => [
                    'x' => (float) ($node->position_x ?? 300),
                    'y' => (float) ($node->position_y ?? 100),
                ],

                'config' => $config,
            ];
        })
        ->values();

    $builderConnections = $automation->connections
        ->map(function ($connection) {
            return [
                'id' => $connection->id,

                'source_node_id' =>
                    (int) $connection->source_node_id,

                'target_node_id' =>
                    (int) $connection->target_node_id,

                'source_handle' =>
                    $connection->source_handle ?? 'output',

                'target_handle' =>
                    $connection->target_handle ?? 'input',
            ];
        })
        ->values();

    /*
    |--------------------------------------------------------------------------
    | Available Automation Triggers
    |--------------------------------------------------------------------------
    */

    $automationTriggers = $triggerRegistry->all();

    return view(
        'admin.automations.builder',
        compact(
            'automation',
            'builderNodes',
            'builderConnections',
            'automationTriggers'
        )
    );
}


    /*
    |--------------------------------------------------------------------------
    | Update Workflow
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        Automation $automation,
        AutomationValidator $validator
    ) {
        /*
        |--------------------------------------------------------------------------
        | Multipart/FormData handling
        |--------------------------------------------------------------------------
        |
        | The frontend uploads Telegram media using FormData.
        |
        | Therefore nodes/connections arrive as JSON strings.
        | Decode them before Laravel validation.
        |
        */

        if ($request->has('nodes') && is_string($request->input('nodes'))) {

            $decodedNodes =
                json_decode(
                    $request->input('nodes'),
                    true
                );

            if (json_last_error() === JSON_ERROR_NONE) {
                $request->merge([
                    'nodes' => $decodedNodes,
                ]);
            }
        }

        if (
            $request->has('connections') &&
            is_string($request->input('connections'))
        ) {

            $decodedConnections =
                json_decode(
                    $request->input('connections'),
                    true
                );

            if (json_last_error() === JSON_ERROR_NONE) {
                $request->merge([
                    'connections' => $decodedConnections,
                ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                'in:draft,active,paused',
            ],

            'nodes' => [
                'required',
                'array',
            ],

            'nodes.*.id' => [
                'nullable',
            ],

            'nodes.*.type' => [
                'required',
                'string',
            ],

            'nodes.*.name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'nodes.*.enabled' => [
                'nullable',
                'boolean',
            ],

            'nodes.*.position.x' => [
                'required',
                'numeric',
            ],

            'nodes.*.position.y' => [
                'required',
                'numeric',
            ],

            'nodes.*.config' => [
                'nullable',
                'array',
            ],

            'connections' => [
                'required',
                'array',
            ],

            'connections.*.id' => [
                'nullable',
            ],

            'connections.*.source_node_id' => [
                'required',
            ],

            'connections.*.target_node_id' => [
                'required',
            ],

            'connections.*.source_handle' => [
                'nullable',
                'string',
                'max:100',
            ],

            'connections.*.target_handle' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);


        try {

            $result = DB::transaction(
                function () use (
                    $request,
                    $automation,
                    $validated,
                    $validator
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Update Automation
                    |--------------------------------------------------------------------------
                    */

                    $automation->update([
                        'name' =>
                            $validated['name'],

                        'description' =>
                            $validated['description']
                            ?? null,

                        'status' =>
                            $validated['status'],
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Save Nodes
                    |--------------------------------------------------------------------------
                    */

                    $nodeIdMap = [];

                    $savedNodeIds = [];


                    foreach (
                        $validated['nodes']
                        as $nodeData
                    ) {

                        $frontendId =
                            $nodeData['id']
                            ?? null;

                        $node = null;


                        /*
                        |--------------------------------------------------------------------------
                        | Existing node
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $frontendId !== null &&
                            is_numeric($frontendId)
                        ) {

                            $node =
                                AutomationNode::query()
                                    ->where(
                                        'automation_id',
                                        $automation->id
                                    )
                                    ->where(
                                        'id',
                                        $frontendId
                                    )
                                    ->first();
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | New node
                        |--------------------------------------------------------------------------
                        */

                        if (!$node) {

                            $node =
                                new AutomationNode();

                            $node->automation_id =
                                $automation->id;

                            $node->node_id =
                                (string) Str::uuid();
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Node data
                        |--------------------------------------------------------------------------
                        */

                        $node->type =
                            $nodeData['type'];

                        $node->component =
                            $nodeData['type'];

                        $node->name =
                            $nodeData['name']
                            ?? null;

                        /*
                         * IMPORTANT:
                         *
                         * Do NOT set $node->description.
                         *
                         * automation_nodes does not contain a
                         * description column.
                         */

                        $node->position_x =
                            $nodeData['position']['x'];

                        $node->position_y =
                            $nodeData['position']['y'];

                        $node->config =
                            $nodeData['config']
                            ?? [];

                        $node->enabled =
                            array_key_exists(
                                'enabled',
                                $nodeData
                            )
                                ? (bool) $nodeData['enabled']
                                : true;

                        $node->save();


                        /*
                        |--------------------------------------------------------------------------
                        | Frontend ID -> Database ID
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $frontendId !== null
                        ) {

                            $nodeIdMap[
                                (string) $frontendId
                            ] =
                                $node->id;
                        }

                        $savedNodeIds[] =
                            $node->id;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Delete Removed Nodes
                    |--------------------------------------------------------------------------
                    */

                    $nodeQuery =
                        AutomationNode::query()
                            ->where(
                                'automation_id',
                                $automation->id
                            );

                    if (
                        !empty($savedNodeIds)
                    ) {

                        $nodeQuery->whereNotIn(
                            'id',
                            $savedNodeIds
                        );
                    }

                    $nodeQuery->delete();


                    /*
                    |--------------------------------------------------------------------------
                    | Rebuild Connections
                    |--------------------------------------------------------------------------
                    */

                    AutomationConnection::query()
                        ->where(
                            'automation_id',
                            $automation->id
                        )
                        ->delete();


                    foreach (
                        $validated['connections']
                        as $connectionData
                    ) {

                        $sourceFrontendId =
                            (string)
                            $connectionData[
                                'source_node_id'
                            ];

                        $targetFrontendId =
                            (string)
                            $connectionData[
                                'target_node_id'
                            ];


                        $sourceNodeId =
                            $nodeIdMap[
                                $sourceFrontendId
                            ] ?? null;

                        $targetNodeId =
                            $nodeIdMap[
                                $targetFrontendId
                            ] ?? null;


                        /*
                        |--------------------------------------------------------------------------
                        | Broken Connection Protection
                        |--------------------------------------------------------------------------
                        */

                        if (
                            !$sourceNodeId ||
                            !$targetNodeId
                        ) {

                            throw new RuntimeException(
                                'One or more connections reference nodes that could not be resolved.'
                            );
                        }


                        AutomationConnection::create([
                            'automation_id' =>
                                $automation->id,

                            'source_node_id' =>
                                $sourceNodeId,

                            'target_node_id' =>
                                $targetNodeId,

                            'source_handle' =>
                                $connectionData[
                                    'source_handle'
                                ] ?? 'output',

                            'target_handle' =>
                                $connectionData[
                                    'target_handle'
                                ] ?? 'input',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Handle Uploaded Telegram Media
                    |--------------------------------------------------------------------------
                    |
                    | Files arrive as:
                    |
                    | media_files[8]
                    |
                    | where 8 is the frontend node ID.
                    |
                    */

                    $uploadedMedia =
                        $request->file('media_files', []);

                    if (
                        is_array($uploadedMedia)
                    ) {

                        foreach (
                            $uploadedMedia
                            as $frontendNodeId => $file
                        ) {

                            if (!$file) {
                                continue;
                            }

                            $databaseNodeId =
                                $nodeIdMap[
                                    (string) $frontendNodeId
                                ] ?? null;

                            if (!$databaseNodeId) {
                                continue;
                            }

                            $node =
                                AutomationNode::query()
                                    ->where(
                                        'automation_id',
                                        $automation->id
                                    )
                                    ->where(
                                        'id',
                                        $databaseNodeId
                                    )
                                    ->first();

                            if (!$node) {
                                continue;
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Store File
                            |--------------------------------------------------------------------------
                            */

                            $path =
                                $file->store(
                                    'automations/media',
                                    'public'
                                );


                            /*
                            |--------------------------------------------------------------------------
                            | Update Media Config
                            |--------------------------------------------------------------------------
                            */

                            $config =
                                $node->config;

                            if (
                                is_string($config)
                            ) {

                                $config =
                                    json_decode(
                                        $config,
                                        true
                                    );
                            }

                            if (
                                !is_array($config)
                            ) {

                                $config = [];
                            }


                            if (
                                !isset(
                                    $config['media']
                                ) ||
                                !is_array(
                                    $config['media']
                                )
                            ) {

                                $config['media'] = [];
                            }


                            $config['media']['enabled'] =
                                true;

                            $config['media']['source'] =
                                $path;

                            $config['media']['file_name'] =
                                $file->getClientOriginalName();

                            $config['media']['file_type'] =
                                $file->getClientMimeType();

                            $config['media']['file_size'] =
                                $file->getSize();

                            /*
                             * preview_url is deliberately NOT
                             * stored in the database.
                             */

                            unset(
                                $config['media']['preview_url']
                            );


                            $node->config =
                                $config;

                            $node->save();
                        }
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Reload Complete Workflow
                    |--------------------------------------------------------------------------
                    */

                    $automation->refresh();

                    $automation->load([
                        'nodes',
                        'connections',
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Validate If Activating
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $validated['status'] ===
                        'active'
                    ) {

                        $validation =
                            $validator->validate(
                                $automation
                            );

                        if (
                            !$validation['valid']
                        ) {

                            throw new RuntimeException(
                                json_encode(
                                    [
                                        'type' =>
                                            'automation_validation_failed',

                                        'validation' =>
                                            $validation,
                                    ],
                                    JSON_UNESCAPED_UNICODE
                                )
                            );
                        }
                    }


                    return $automation;
                }
            );


            /*
            |--------------------------------------------------------------------------
            | Return Normalized Builder Data
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' =>
                    true,

                'message' =>
                    'Automation saved successfully.',

                'automation' => [
                    'id' =>
                        $result->id,

                    'name' =>
                        $result->name,

                    'status' =>
                        $result->status,

                    'description' =>
                        $result->description,
                ],

                'nodes' =>
                    $result->nodes
                        ->map(function ($node) {

                            $config =
                                $node->config;

                            if (
                                is_string($config)
                            ) {

                                $config =
                                    json_decode(
                                        $config,
                                        true
                                    );
                            }

                            if (
                                !is_array($config)
                            ) {

                                $config = [];
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Media normalization
                            |--------------------------------------------------------------------------
                            */

                            if (
                                $node->component ===
                                'telegram_message'
                            ) {

                                if (
                                    !isset(
                                        $config['media']
                                    ) ||
                                    !is_array(
                                        $config['media']
                                    )
                                ) {

                                    $config['media'] = [
                                        'enabled' =>
                                            false,

                                        'type' =>
                                            'photo',

                                        'source' =>
                                            null,

                                        'caption' =>
                                            null,

                                        'file_name' =>
                                            null,

                                        'file_type' =>
                                            null,

                                        'file_size' =>
                                            0,

                                        'preview_url' =>
                                            '',
                                    ];

                                } else {

                                    $config['media'] = array_merge(
                                        [
                                            'enabled' =>
                                                false,

                                            'type' =>
                                                'photo',

                                            'source' =>
                                                null,

                                            'caption' =>
                                                null,

                                            'file_name' =>
                                                null,

                                            'file_type' =>
                                                null,

                                            'file_size' =>
                                                0,

                                            'preview_url' =>
                                                '',
                                        ],

                                        $config['media']
                                    );


                                    /*
                                     * Browser-only property.
                                     */

                                    $config['media']['preview_url'] =
                                        '';
                                }
                            }


                            return [
                                'id' =>
                                    $node->id,

                                'type' =>
                                    $node->component,

                                'name' =>
                                    $node->name,

                                /*
                                 * There is no description column.
                                 */

                                'description' =>
                                    '',

                                'enabled' =>
                                    (bool)
                                    $node->enabled,

                                'position' => [
                                    'x' =>
                                        (float)
                                        $node->position_x,

                                    'y' =>
                                        (float)
                                        $node->position_y,
                                ],

                                'config' =>
                                    $config,
                            ];
                        })
                        ->values(),

                'connections' =>
                    $result->connections
                        ->map(function ($connection) {

                            return [
                                'id' =>
                                    $connection->id,

                                'source_node_id' =>
                                    (int)
                                    $connection->source_node_id,

                                'target_node_id' =>
                                    (int)
                                    $connection->target_node_id,

                                'source_handle' =>
                                    $connection->source_handle
                                    ?? 'output',

                                'target_handle' =>
                                    $connection->target_handle
                                    ?? 'input',
                            ];
                        })
                        ->values(),
            ]);


        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Validation Failure
            |--------------------------------------------------------------------------
            */

            $message =
                $e->getMessage();

            $payload =
                null;


            if (
                str_starts_with(
                    $message,
                    '{"type":"automation_validation_failed"'
                )
            ) {

                $payload =
                    json_decode(
                        $message,
                        true
                    );
            }


            if (
                is_array($payload) &&
                ($payload['type'] ?? null) ===
                    'automation_validation_failed'
            ) {

                return response()->json([
                    'success' =>
                        false,

                    'message' =>
                        'Automation cannot be activated until the validation errors are fixed.',

                    'validation' =>
                        $payload['validation']
                        ?? [
                            'valid' =>
                                false,

                            'errors' =>
                                [],

                            'warnings' =>
                                [],
                        ],
                ], 422);
            }


            report($e);


            return response()->json([
                'success' =>
                    false,

                'message' =>
                    $e->getMessage(),
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Activate
    |--------------------------------------------------------------------------
    */

    public function activate(
        Automation $automation,
        AutomationValidator $validator
    ) {
        $validation =
            $validator->validate(
                $automation
            );

        if (!$validation['valid']) {

            return response()->json([
                'success' =>
                    false,

                'message' =>
                    'Automation cannot be activated until the validation errors are fixed.',

                'validation' =>
                    $validation,
            ], 422);
        }

        $automation->update([
            'status' =>
                'active',
        ]);

        return response()->json([
            'success' =>
                true,

            'message' =>
                'Automation activated successfully.',

            'status' =>
                'active',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Pause
    |--------------------------------------------------------------------------
    */

    public function pause(
        Automation $automation
    ) {
        $automation->update([
            'status' =>
                'paused',
        ]);

        return response()->json([
            'success' =>
                true,

            'message' =>
                'Automation paused successfully.',

            'status' =>
                'paused',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Validate
    |--------------------------------------------------------------------------
    */

    public function validate(
        Automation $automation,
        AutomationValidator $validator
    ): JsonResponse {
        return response()->json(
            $validator->validate(
                $automation
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Test Run
    |--------------------------------------------------------------------------
    */

    public function testRun(
        Automation $automation,
        \App\Services\Automation\AutomationEngine $engine
    ) {

        $context = [
            'source' =>
                'admin_test',

            'test_mode' =>
                true,

            'user_id' =>
                null,

            'name' =>
                'Test Player',

            'username' =>
                'test_player',

            'telegram_id' =>
                null,

            'xp' =>
                1500,

            'total_xp' =>
                1500,

            'streak' =>
                7,

            'current_streak' =>
                7,

            'best_streak' =>
                7,

            'sr' =>
                1200,

            'trigger' => [
                'type' =>
                    'manual_test',

                'data' => [
                    'source' =>
                        'admin_test',
                ],
            ],
        ];


        $triggerData = [
            'source' =>
                'admin_test',

            'test_mode' =>
                true,
        ];


        try {

            $execution =
                $engine->run(
                    $automation,

                    context:
                        $context,

                    triggerType:
                        'manual_test',

                    triggerData:
                        $triggerData
                );


            return response()->json([
                'success' =>
                    true,

                'execution_id' =>
                    $execution->execution_id,

                'status' =>
                    $execution->status,

                'message' =>
                    'Test run started successfully.',
            ]);


        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'success' =>
                    false,

                'message' =>
                    $e->getMessage(),
            ], 500);
        }
    }
}