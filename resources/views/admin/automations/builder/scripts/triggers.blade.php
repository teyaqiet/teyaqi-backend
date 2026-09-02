<script>
function automationBuilderTriggers() {

    return {

        /*
        |--------------------------------------------------------------------------
        | AUTOMATION TRIGGER REGISTRY
        |--------------------------------------------------------------------------
        |
        | These are the events currently available to the automation builder.
        |
        */

        triggerRegistry: {


            /*
            |--------------------------------------------------------------------------
            | Game Completed
            |--------------------------------------------------------------------------
            */

            game_completed: {

                value: 'game_completed',

                label: 'Game Completed',

                description:
                    'Triggered when a player completes a game.',

                fields: [

                    {
                        value: 'session_id',
                        label: 'Session ID',
                        type: 'number',
                    },

                    {
                        value: 'correct_answers',
                        label: 'Correct Answers',
                        type: 'number',
                    },

                    {
                        value: 'total_questions',
                        label: 'Total Questions',
                        type: 'number',
                    },

                    {
                        value: 'lives_lost',
                        label: 'Lives Lost',
                        type: 'number',
                    },

                    {
                        value: 'xp_earned',
                        label: 'XP Earned',
                        type: 'number',
                    },

                    {
                        value: 'perfect_bonus',
                        label: 'Perfect Bonus',
                        type: 'number',
                    },

                ],

            },


            /*
            |--------------------------------------------------------------------------
            | Level Up
            |--------------------------------------------------------------------------
            */

            level_up: {

                value: 'level_up',

                label: 'Level Up',

                description:
                    'Triggered when a player reaches a new level.',

                fields: [

                    {
                        value: 'previous_level',
                        label: 'Previous Level',
                        type: 'number',
                    },

                    {
                        value: 'new_level',
                        label: 'New Level',
                        type: 'number',
                    },

                ],

            },


            /*
            |--------------------------------------------------------------------------
            | Streak Reached
            |--------------------------------------------------------------------------
            */

            streak_reached: {

                value: 'streak_reached',

                label: 'Streak Reached',

                description:
                    'Triggered when a player reaches a streak milestone.',

                fields: [

                    {
                        value: 'streak',
                        label: 'Reached Streak',
                        type: 'number',
                    },

                ],

            },


            /*
            |--------------------------------------------------------------------------
            | XP Milestone
            |--------------------------------------------------------------------------
            */

            xp_milestone: {

                value: 'xp_milestone',

                label: 'XP Milestone',

                description:
                    'Triggered when a player reaches an XP milestone.',

                fields: [

                    {
                        value: 'previous_xp',
                        label: 'Previous XP',
                        type: 'number',
                    },

                    {
                        value: 'new_xp',
                        label: 'New XP',
                        type: 'number',
                    },

                    {
                        value: 'milestone',
                        label: 'Milestone',
                        type: 'number',
                    },

                ],

            },


            /*
            |--------------------------------------------------------------------------
            | Player Login
            |--------------------------------------------------------------------------
            |
            | Daily / Retention event.
            |
            | Fields will be added after the Laravel event payload is defined.
            |
            */

            player_login: {

                value: 'player_login',

                label: 'Player Login',

                description:
                    'Triggered when a player logs into Teyaqi.',

                fields: [],

            },


            /*
            |--------------------------------------------------------------------------
            | Player Inactive
            |--------------------------------------------------------------------------
            |
            | Daily / Retention event.
            |
            | Fields will be added after the inactivity detection payload
            | is defined.
            |
            */

            player_inactive: {

                value: 'player_inactive',

                label: 'Player Inactive',

                description:
                    'Triggered when a player becomes inactive.',

                fields: [],

            },


            /*
            |--------------------------------------------------------------------------
            | Challenge Completed
            |--------------------------------------------------------------------------
            |
            | Daily / Retention / Engagement event.
            |
            | Fields will be added after the Laravel event payload is defined.
            |
            */

            challenge_completed: {

                value: 'challenge_completed',

                label: 'Challenge Completed',

                description:
                    'Triggered when a player completes a challenge.',

                fields: [],

            },


            /*
            |--------------------------------------------------------------------------
            | Daily Challenge Completed
            |--------------------------------------------------------------------------
            |
            | Daily / Retention event.
            |
            | Fields will be added after the Laravel event payload is defined.
            |
            */

            daily_challenge_completed: {

                value: 'daily_challenge_completed',

                label: 'Daily Challenge Completed',

                description:
                    'Triggered when a player completes the daily challenge.',

                fields: [],

            },

        },


        /*
        |--------------------------------------------------------------------------
        | Get Trigger Events
        |--------------------------------------------------------------------------
        */

        getAutomationTriggers() {

            return Object.values(
                this.triggerRegistry
            );

        },


        /*
        |--------------------------------------------------------------------------
        | Get Trigger Event
        |--------------------------------------------------------------------------
        */

        getAutomationTrigger(eventValue) {

            if (!eventValue) {

                return null;

            }

            return (
                this.triggerRegistry[eventValue] ??
                null
            );

        },


        /*
        |--------------------------------------------------------------------------
        | Trigger Label
        |--------------------------------------------------------------------------
        */

        getTriggerLabel(eventValue) {

            if (!eventValue) {

                return 'Select an event';

            }

            return (
                this.triggerRegistry[eventValue]?.label ??
                eventValue
            );

        },


        /*
        |--------------------------------------------------------------------------
        | Trigger Description
        |--------------------------------------------------------------------------
        */

        getTriggerDescription(eventValue) {

            if (!eventValue) {

                return 'Choose the event that starts this automation.';

            }

            return (
                this.triggerRegistry[eventValue]?.description ??
                ''
            );

        },


        /*
        |--------------------------------------------------------------------------
        | Trigger Fields
        |--------------------------------------------------------------------------
        */

        getTriggerFields(eventValue = null) {

            const event =
                eventValue ??
                this.getSelectedNodeConfig()?.event;

            if (!event) {

                return [];

            }

            return (
                this.triggerRegistry[event]?.fields ??
                []
            );

        },


        /*
        |--------------------------------------------------------------------------
        | Trigger Field
        |--------------------------------------------------------------------------
        */

        getTriggerField(
            eventValue,
            fieldValue
        ) {

            const fields =
                this.getTriggerFields(
                    eventValue
                );

            return (
                fields.find(
                    field =>
                        field.value ===
                        fieldValue
                ) ??
                null
            );

        },


        /*
        |--------------------------------------------------------------------------
        | Trigger Field Label
        |--------------------------------------------------------------------------
        */

        getTriggerFieldLabel(
            eventValue,
            fieldValue
        ) {

            if (!fieldValue) {

                return 'Field';

            }

            return (
                this.getTriggerField(
                    eventValue,
                    fieldValue
                )?.label ??
                fieldValue
            );

        },


        /*
        |--------------------------------------------------------------------------
        | Trigger Event Change
        |--------------------------------------------------------------------------
        */

        changeTriggerEvent(eventValue) {

            const node =
                this.getSelectedNode();

            if (!node) {

                return;

            }

            if (
                node.type !== 'trigger'
            ) {

                return;

            }

            this.ensureNodeConfig(node);


            /*
            |------------------------------------------------------------------
            | Set event
            |------------------------------------------------------------------
            */

            node.config.event =
                eventValue || '';


            /*
            |------------------------------------------------------------------
            | Clear old event-specific configuration
            |------------------------------------------------------------------
            */

            node.config.description =
                this.getTriggerDescription(
                    eventValue
                );


            /*
            |------------------------------------------------------------------
            | Keep selected node in sync
            |------------------------------------------------------------------
            */

            this.selectedNodeData =
                node;


            /*
            |------------------------------------------------------------------
            | Mark automation as changed
            |------------------------------------------------------------------
            */

            this.markDirty();


            console.log(
                '[Automation Builder] Trigger event changed:',
                {
                    nodeId: node.id,
                    event: node.config.event,
                }
            );

        },

    };

}
</script>