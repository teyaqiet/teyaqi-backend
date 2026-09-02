<?php


namespace App\Services\Automation;

use App\Services\Automation\Nodes\ConditionNode;
use App\Services\Automation\Nodes\DelayNode;
use App\Services\Automation\Nodes\EndNode;
use App\Services\Automation\Nodes\TelegramMessageNode;
use App\Services\Automation\Nodes\TriggerNode;
use RuntimeException;

class NodeRegistry
{
    /**
     * Automation node definitions.
     *
     * @var array<string, array>
     */
    protected array $nodes = [

        'trigger' => [
            'component' => 'trigger',
            'type' => 'trigger',
            'name' => 'Trigger',
            'description' => 'Starts an automation workflow.',
            'category' => 'trigger',
            'icon' => 'zap',
            'color' => 'orange',

            'handles' => [
                'inputs' => [],
                'outputs' => [
                    [
                        'id' => 'output',
                        'label' => 'Continue',
                    ],
                ],
            ],

            'config' => [

    'event' => [
        'type' => 'select',
        'label' => 'Trigger Event',
        'required' => true,
        'options' => [
            'streak_reached' => 'Streak Reached',
            'level_up' => 'Level Up',
            'xp_milestone' => 'XP Milestone',
            'lives_low' => 'Lives Low',
            'player_registered' => 'Player Registered',
        ],
    ],

    'description' => [
        'type' => 'textarea',
        'label' => 'Description',
        'required' => false,
        'placeholder' => 'Describe what should start this automation...',
    ],

    'enabled' => [
        'type' => 'boolean',
        'label' => 'Enabled',
        'required' => false,
        'default' => true,
    ],

],
        ],

        'condition' => [
            'component' => 'condition',
            'type' => 'action',
            'name' => 'Condition',
            'description' => 'Checks a value and creates branches.',
            'category' => 'logic',
            'icon' => 'git-branch',
            'color' => 'blue',

            'handles' => [
                'inputs' => [
                    [
                        'id' => 'input',
                        'label' => 'Input',
                    ],
                ],

                'outputs' => [
                    [
                        'id' => 'true',
                        'label' => 'True',
                    ],
                    [
                        'id' => 'false',
                        'label' => 'False',
                    ],
                ],
            ],

            'config' => [

    'field' => [
        'type' => 'select',
        'label' => 'Field',
        'required' => true,
        'options' => [
            'name' => 'Player Name',
            'xp' => 'Total XP',
            'streak' => 'Current Streak',
            'current_streak' => 'Current Streak',
            'best_streak' => 'Best Streak',
            'sr' => 'Skill Rating',
            'level' => 'Level',
            'lives' => 'Lives',
            'telegram_id' => 'Telegram ID',
            'username' => 'Username',
            'trigger.type' => 'Trigger Type',
            'trigger.data.streak' => 'Trigger Streak',
        ],
    ],

    'operator' => [
        'type' => 'select',
        'label' => 'Operator',
        'required' => true,
        'options' => [
            'equals' => 'Equals',
            'not_equals' => 'Not Equals',
            'greater_than' => 'Greater Than',
            'greater_than_or_equal' => 'Greater Than or Equal',
            'less_than' => 'Less Than',
            'less_than_or_equal' => 'Less Than or Equal',
            'contains' => 'Contains',
            'not_contains' => 'Does Not Contain',
            'is_empty' => 'Is Empty',
            'is_not_empty' => 'Is Not Empty',
        ],
    ],

    'value' => [
        'type' => 'text',
        'label' => 'Value',
        'required' => false,
    ],

    'case_sensitive' => [
        'type' => 'boolean',
        'label' => 'Case Sensitive',
        'required' => false,
        'default' => false,
    ],

],
        ],

        'telegram_message' => [
            'component' => 'telegram_message',
            'type' => 'action',
            'name' => 'Telegram Message',
            'description' => 'Sends a Telegram message to a player.',
            'category' => 'communication',
            'icon' => 'send',
            'color' => 'sky',

            'handles' => [
                'inputs' => [
                    [
                        'id' => 'input',
                        'label' => 'Input',
                    ],
                ],

                'outputs' => [
                    [
                        'id' => 'output',
                        'label' => 'Continue',
                    ],
                ],
            ],

            'config' => [

    'recipient' => [
        'type' => 'select',
        'label' => 'Recipient',
        'required' => true,
        'default' => 'context.telegram_id',
        'options' => [
            'context.telegram_id' => 'Current Player',
            'config.chat_id' => 'Custom Chat ID',
        ],
    ],

    'chat_id' => [
        'type' => 'text',
        'label' => 'Chat ID',
        'required' => false,
        'placeholder' => 'Telegram chat ID',
    ],

    'message' => [
        'type' => 'textarea',
        'label' => 'Message',
        'required' => true,
        'placeholder' => '🔥 Hey @{{name}}! You reached a @{{streak}} day streak!',
    ],

    'parse_mode' => [
        'type' => 'select',
        'label' => 'Parse Mode',
        'required' => false,
        'default' => 'HTML',
        'options' => [
            '' => 'Plain Text',
            'HTML' => 'HTML',
            'Markdown' => 'Markdown',
            'MarkdownV2' => 'Markdown V2',
        ],
    ],

    'disable_web_page_preview' => [
        'type' => 'boolean',
        'label' => 'Disable Link Preview',
        'required' => false,
        'default' => false,
    ],

    'disable_notification' => [
        'type' => 'boolean',
        'label' => 'Silent Notification',
        'required' => false,
        'default' => false,
    ],

],
        ],

        'delay' => [
            'component' => 'delay',
            'type' => 'action',
            'name' => 'Delay',
            'description' => 'Waits before continuing the workflow.',
            'category' => 'flow',
            'icon' => 'clock',
            'color' => 'purple',

            'handles' => [
                'inputs' => [
                    [
                        'id' => 'input',
                        'label' => 'Input',
                    ],
                ],

                'outputs' => [
                    [
                        'id' => 'output',
                        'label' => 'Continue',
                    ],
                ],
            ],

            'config' => [
                'duration' => [
                    'type' => 'number',
                    'label' => 'Duration',
                    'required' => true,
                    'default' => 1,
                ],

                'unit' => [
                    'type' => 'select',
                    'label' => 'Unit',
                    'required' => true,
                    'default' => 'seconds',
                    'options' => [
                        'seconds' => 'Seconds',
                        'minutes' => 'Minutes',
                        'hours' => 'Hours',
                    ],
                ],
            ],
        ],

        'end' => [
            'component' => 'end',
            'type' => 'end',
            'name' => 'End',
            'description' => 'Ends the automation workflow.',
            'category' => 'flow',
            'icon' => 'circle-stop',
            'color' => 'red',

            'handles' => [
                'inputs' => [
                    [
                        'id' => 'input',
                        'label' => 'Input',
                    ],
                ],

                'outputs' => [],
            ],

            'config' => [],
        ],
    ];


    /**
     * Actual handler classes.
     *
     * @var array<string, class-string>
     */
    protected array $handlers = [
        'trigger' => TriggerNode::class,
        'condition' => ConditionNode::class,
        'telegram_message' => TelegramMessageNode::class,
        'delay' => DelayNode::class,
        'end' => EndNode::class,
    ];


    /**
     * Get all node definitions.
     */
    public function all(): array
    {
        return $this->nodes;
    }


    /**
     * Get one node definition.
     */
    public function get(string $component): array
    {
        $this->validate($component);

        return $this->nodes[$component];
    }


    /**
     * Check whether a component exists.
     */
    public function has(string $component): bool
    {
        return isset($this->nodes[$component]);
    }


    /**
     * Get nodes by category.
     */
    public function category(string $category): array
    {
        return array_filter(
            $this->nodes,
            fn (array $node) =>
                ($node['category'] ?? null) === $category
        );
    }


    /**
     * Get component names.
     */
    public function components(): array
    {
        return array_keys($this->nodes);
    }


    /**
     * Validate a component.
     */
    public function validate(string $component): void
    {
        if (!$this->has($component)) {
            throw new RuntimeException(
                "Unknown automation component: {$component}"
            );
        }
    }


    /**
     * Resolve the actual node handler.
     */
    public function resolve(string $component): object
    {
        $component = trim($component);

        if ($component === '') {
            throw new RuntimeException(
                'Automation node component cannot be empty.'
            );
        }

        $this->validate($component);

        $handler = $this->handlers[$component] ?? null;

        if (!$handler) {
            throw new RuntimeException(
                "No handler registered for automation component: {$component}"
            );
        }

        return app($handler);
    }


    /**
     * Get handler class.
     */
    public function classFor(string $component): ?string
    {
        return $this->handlers[$component] ?? null;
    }


    /**
     * Register a node definition and handler.
     */
    public function register(
        string $component,
        array $definition,
        string $handler
    ): self {
        $component = trim($component);

        if ($component === '') {
            throw new RuntimeException(
                'Automation component name cannot be empty.'
            );
        }

        if (!class_exists($handler)) {
            throw new RuntimeException(
                "Automation handler class does not exist: {$handler}"
            );
        }

        $this->nodes[$component] = $definition;
        $this->handlers[$component] = $handler;

        return $this;
    }


    /**
     * Remove a component.
     */
    public function unregister(string $component): self
    {
        unset(
            $this->nodes[$component],
            $this->handlers[$component]
        );

        return $this;
    }
}