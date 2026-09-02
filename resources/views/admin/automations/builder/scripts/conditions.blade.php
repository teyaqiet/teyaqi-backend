<script>
function automationBuilderConditions() {

    return {

        /*
        |--------------------------------------------------------------------------
        | CONDITION REGISTRY
        |--------------------------------------------------------------------------
        */

        conditionRegistry: {

            /*
            |--------------------------------------------------------------------------
            | OPERATORS
            |--------------------------------------------------------------------------
            */

            operators: {

                number: [

                    {
                        value: 'equals',
                        label: 'Equals',
                    },

                    {
                        value: 'not_equals',
                        label: 'Not Equals',
                    },

                    {
                        value: 'greater_than',
                        label: 'Greater Than',
                    },

                    {
                        value: 'greater_than_or_equal',
                        label: 'Greater Than or Equal',
                    },

                    {
                        value: 'less_than',
                        label: 'Less Than',
                    },

                    {
                        value: 'less_than_or_equal',
                        label: 'Less Than or Equal',
                    },

                ],

                string: [

                    {
                        value: 'equals',
                        label: 'Equals',
                    },

                    {
                        value: 'not_equals',
                        label: 'Not Equals',
                    },

                    {
                        value: 'contains',
                        label: 'Contains',
                    },

                    {
                        value: 'not_contains',
                        label: 'Does Not Contain',
                    },

                    {
                        value: 'is_empty',
                        label: 'Is Empty',
                    },

                    {
                        value: 'is_not_empty',
                        label: 'Is Not Empty',
                    },

                ],

                boolean: [

                    {
                        value: 'equals',
                        label: 'Equals',
                    },

                    {
                        value: 'not_equals',
                        label: 'Not Equals',
                    },

                    {
                        value: 'is_true',
                        label: 'Is True',
                    },

                    {
                        value: 'is_false',
                        label: 'Is False',
                    },

                ],

            },


            /*
            |--------------------------------------------------------------------------
            | PLAYER FIELDS
            |--------------------------------------------------------------------------
            */

            player: [

                {
                    value: 'user_id',
                    label: 'User ID',
                    type: 'number',
                },

                {
                    value: 'name',
                    label: 'Player Name',
                    type: 'string',
                },

                {
                    value: 'username',
                    label: 'Username',
                    type: 'string',
                },

                {
                    value: 'telegram_id',
                    label: 'Telegram ID',
                    type: 'string',
                },

                {
                    value: 'level',
                    label: 'Level',
                    type: 'number',
                },

                {
                    value: 'total_xp',
                    label: 'Total XP',
                    type: 'number',
                },

                {
                    value: 'current_streak',
                    label: 'Current Streak',
                    type: 'number',
                },

                {
                    value: 'best_streak',
                    label: 'Best Streak',
                    type: 'number',
                },

                {
                    value: 'daily_lives',
                    label: 'Daily Lives',
                    type: 'number',
                },

            ],


            /*
            |--------------------------------------------------------------------------
            | TRIGGER FIELDS
            |--------------------------------------------------------------------------
            |
            | These MUST match the actual automation payload:
            |
            | trigger.type
            | trigger.data.*
            |
            */

            trigger: [

                /*
                |--------------------------------------------------------------------------
                | Trigger Type
                |--------------------------------------------------------------------------
                */

                {
                    value: 'trigger.type',
                    label: 'Trigger Type',
                    type: 'string',
                },


                /*
                |--------------------------------------------------------------------------
                | Game Completed
                |--------------------------------------------------------------------------
                */

                {
                    value: 'trigger.data.session_id',
                    label: 'Session ID',
                    type: 'number',
                },

                {
                    value: 'trigger.data.correct_answers',
                    label: 'Correct Answers',
                    type: 'number',
                },

                {
                    value: 'trigger.data.total_questions',
                    label: 'Total Questions',
                    type: 'number',
                },

                {
                    value: 'trigger.data.lives_lost',
                    label: 'Lives Lost',
                    type: 'number',
                },

                {
                    value: 'trigger.data.xp_earned',
                    label: 'XP Earned',
                    type: 'number',
                },

                {
                    value: 'trigger.data.perfect_bonus',
                    label: 'Perfect Bonus XP',
                    type: 'number',
                },


                /*
                |--------------------------------------------------------------------------
                | Level Up
                |--------------------------------------------------------------------------
                */

                {
                    value: 'trigger.data.previous_level',
                    label: 'Previous Level',
                    type: 'number',
                },

                {
                    value: 'trigger.data.new_level',
                    label: 'New Level',
                    type: 'number',
                },


                /*
                |--------------------------------------------------------------------------
                | Streak Reached
                |--------------------------------------------------------------------------
                */

                {
                    value: 'trigger.data.streak',
                    label: 'Streak Reached',
                    type: 'number',
                },


                /*
                |--------------------------------------------------------------------------
                | XP Milestone
                |--------------------------------------------------------------------------
                */

                {
                    value: 'trigger.data.previous_xp',
                    label: 'Previous XP',
                    type: 'number',
                },

                {
                    value: 'trigger.data.new_xp',
                    label: 'New XP',
                    type: 'number',
                },

                {
                    value: 'trigger.data.milestone',
                    label: 'XP Milestone',
                    type: 'number',
                },

            ],

        },


        /*
        |--------------------------------------------------------------------------
        | GET ALL CONDITION FIELDS
        |--------------------------------------------------------------------------
        */

        getConditionFields() {

            return [
                ...this.conditionRegistry.player,
                ...this.conditionRegistry.trigger,
            ];

        },


        /*
        |--------------------------------------------------------------------------
        | PLAYER FIELDS
        |--------------------------------------------------------------------------
        */

        getPlayerConditionFields() {

            return this.conditionRegistry.player;

        },


        /*
        |--------------------------------------------------------------------------
        | TRIGGER FIELDS
        |--------------------------------------------------------------------------
        */

        getTriggerConditionFields() {

            return this.conditionRegistry.trigger;

        },


        /*
        |--------------------------------------------------------------------------
        | FIND FIELD
        |--------------------------------------------------------------------------
        */

        getConditionField(fieldValue) {

            if (!fieldValue) {

                return null;

            }

            return this
                .getConditionFields()
                .find(
                    field =>
                        field.value === fieldValue
                ) ?? null;

        },


        /*
        |--------------------------------------------------------------------------
        | FIELD TYPE
        |--------------------------------------------------------------------------
        */

        getConditionFieldType(fieldValue = null) {

            const field =
                fieldValue ??
                this.getSelectedNodeConfig()?.field;

            if (!field) {

                return 'string';

            }

            return (
                this.getConditionField(field)?.type ??
                'string'
            );

        },


        /*
        |--------------------------------------------------------------------------
        | OPERATORS
        |--------------------------------------------------------------------------
        */

        getConditionOperators(fieldValue = null) {

            const type =
                this.getConditionFieldType(
                    fieldValue
                );

            return (
                this.conditionRegistry.operators[type] ??
                this.conditionRegistry.operators.string
            );

        },


        /*
        |--------------------------------------------------------------------------
        | OPERATOR LABEL
        |--------------------------------------------------------------------------
        */

        getConditionOperatorLabel(
            operatorValue = null,
            fieldValue = null
        ) {

            if (!operatorValue) {

                return 'Operator';

            }

            const field =
                fieldValue ??
                this.getSelectedNodeConfig()?.field;

            if (!field) {

                return 'Operator';

            }

            const operators =
                this.getConditionOperators(
                    field
                );

            return (
                operators.find(
                    operator =>
                        operator.value === operatorValue
                )?.label ??
                operatorValue
            );

        },


        /*
        |--------------------------------------------------------------------------
        | NODE-SPECIFIC OPERATOR LABEL
        |--------------------------------------------------------------------------
        */

        getConditionOperatorLabelForNode(
            node,
            operatorValue = null
        ) {

            if (!node || !operatorValue) {

                return 'Operator';

            }

            this.ensureNodeConfig(node);

            const field =
                node.config?.field;

            if (!field) {

                return 'Operator';

            }

            const operators =
                this.getConditionOperators(
                    field
                );

            return (
                operators.find(
                    operator =>
                        operator.value === operatorValue
                )?.label ??
                operatorValue
            );

        },


        /*
        |--------------------------------------------------------------------------
        | FIELD LABEL
        |--------------------------------------------------------------------------
        */

        getConditionFieldLabel(
            fieldValue = null
        ) {

            if (!fieldValue) {

                return 'Field';

            }

            return (
                this.getConditionField(
                    fieldValue
                )?.label ??
                fieldValue
            );

        },


        /*
        |--------------------------------------------------------------------------
        | FIELD DESCRIPTION
        |--------------------------------------------------------------------------
        */

        getConditionFieldDescription(
            fieldValue = null
        ) {

            const field =
                this.getConditionField(
                    fieldValue ??
                    this.getSelectedNodeConfig()?.field
                );

            if (!field) {

                return 'Choose a field to evaluate.';

            }

            return `${field.label} is evaluated as a ${field.type} value.`;

        },


        /*
        |--------------------------------------------------------------------------
        | VALUE PLACEHOLDER
        |--------------------------------------------------------------------------
        */

        getConditionValuePlaceholder() {

            const type =
                this.getConditionFieldType();

            if (type === 'number') {

                return 'Enter a number';

            }

            if (type === 'boolean') {

                return 'Select true or false';

            }

            if (type === 'date') {

                return 'Select a date';

            }

            return 'Enter a value';

        },


        /*
        |--------------------------------------------------------------------------
        | VALUE HELP
        |--------------------------------------------------------------------------
        */

        getConditionValueHelp() {

            const config =
                this.getSelectedNodeConfig();

            if (!config?.field) {

                return '';

            }

            if (
                [
                    'is_empty',
                    'is_not_empty',
                    'is_true',
                    'is_false',
                ].includes(
                    config.operator
                )
            ) {

                return 'This operator does not require a value.';

            }

            const type =
                this.getConditionFieldType(
                    config.field
                );

            if (type === 'number') {

                return 'Enter a numeric value to compare against.';

            }

            if (type === 'boolean') {

                return 'Choose whether the value should be true or false.';

            }

            return 'Enter the value that should be compared.';

        },


        /*
        |--------------------------------------------------------------------------
        | OPERATOR REQUIRES VALUE
        |--------------------------------------------------------------------------
        */

        conditionOperatorRequiresValue(
            operatorValue = null
        ) {

            const operator =
                operatorValue ??
                this.getSelectedNodeConfig()?.operator;

            return ![
                'is_empty',
                'is_not_empty',
                'is_true',
                'is_false',
            ].includes(
                operator
            );

        },


        /*
        |--------------------------------------------------------------------------
        | NUMERIC FIELD
        |--------------------------------------------------------------------------
        */

        isNumericConditionField(
            fieldValue = null
        ) {

            return (
                this.getConditionFieldType(
                    fieldValue
                ) === 'number'
            );

        },


        /*
        |--------------------------------------------------------------------------
        | STRING FIELD
        |--------------------------------------------------------------------------
        */

        isStringConditionField(
            fieldValue = null
        ) {

            return (
                this.getConditionFieldType(
                    fieldValue
                ) === 'string'
            );

        },


        /*
        |--------------------------------------------------------------------------
        | BOOLEAN FIELD
        |--------------------------------------------------------------------------
        */

        isBooleanConditionField(
            fieldValue = null
        ) {

            return (
                this.getConditionFieldType(
                    fieldValue
                ) === 'boolean'
            );

        },


        /*
        |--------------------------------------------------------------------------
        | CHANGE CONDITION FIELD
        |--------------------------------------------------------------------------
        */

        changeConditionField(fieldValue) {

            const node =
                this.getSelectedNode();

            if (!node) {

                return;

            }

            this.ensureNodeConfig(node);

            node.config.field =
                fieldValue || '';


            /*
            |--------------------------------------------------------------------------
            | No field selected
            |--------------------------------------------------------------------------
            */

            if (!fieldValue) {

                node.config.operator = '';

                node.config.value = '';

                node.config.case_sensitive = false;

                this.selectedNodeData =
                    node;

                this.markDirty();

                return;

            }


            /*
            |--------------------------------------------------------------------------
            | Get valid operators
            |--------------------------------------------------------------------------
            */

            const operators =
                this.getConditionOperators(
                    fieldValue
                );


            /*
            |--------------------------------------------------------------------------
            | Preserve operator if still valid
            |--------------------------------------------------------------------------
            */

            const currentOperator =
                node.config.operator;


            const operatorStillValid =
                operators.some(
                    operator =>
                        operator.value ===
                        currentOperator
                );


            /*
            |--------------------------------------------------------------------------
            | Otherwise use first valid operator
            |--------------------------------------------------------------------------
            */

            if (!operatorStillValid) {

                node.config.operator =
                    operators[0]?.value ??
                    '';

            }


            /*
            |--------------------------------------------------------------------------
            | Reset value
            |--------------------------------------------------------------------------
            */

            node.config.value = '';


            /*
            |--------------------------------------------------------------------------
            | Reset case sensitivity
            |--------------------------------------------------------------------------
            */

            if (
                this.getConditionFieldType(
                    fieldValue
                ) !== 'string'
            ) {

                node.config.case_sensitive =
                    false;

            }


            this.selectedNodeData =
                node;

            this.markDirty();


            console.log(
                '[Automation Builder] Condition field changed:',
                {
                    nodeId: node.id,
                    field: node.config.field,
                    fieldType:
                        this.getConditionFieldType(
                            fieldValue
                        ),
                    operator:
                        node.config.operator,
                    value:
                        node.config.value,
                }
            );

        },


        /*
        |--------------------------------------------------------------------------
        | CHANGE CONDITION OPERATOR
        |--------------------------------------------------------------------------
        */

        changeConditionOperator(
            operatorValue
        ) {

            const node =
                this.getSelectedNode();

            if (!node) {

                return;

            }

            this.ensureNodeConfig(node);


            const field =
                node.config.field;

            if (!field) {

                return;

            }


            const operators =
                this.getConditionOperators(
                    field
                );


            const valid =
                operators.some(
                    operator =>
                        operator.value ===
                        operatorValue
                );


            if (!valid) {

                return;

            }


            node.config.operator =
                operatorValue;


            /*
            |--------------------------------------------------------------------------
            | Operators without values
            |--------------------------------------------------------------------------
            */

            if (
                [
                    'is_empty',
                    'is_not_empty',
                    'is_true',
                    'is_false',
                ].includes(
                    operatorValue
                )
            ) {

                node.config.value = '';

            }


            this.selectedNodeData =
                node;

            this.markDirty();


            console.log(
                '[Automation Builder] Condition operator changed:',
                {
                    nodeId: node.id,
                    field: node.config.field,
                    operator: node.config.operator,
                    value: node.config.value,
                }
            );

        },


        /*
        |--------------------------------------------------------------------------
        | CHANGE CONDITION VALUE
        |--------------------------------------------------------------------------
        */

        changeConditionValue(value) {

            const node =
                this.getSelectedNode();

            if (!node) {

                return;

            }

            this.ensureNodeConfig(node);


            /*
            |--------------------------------------------------------------------------
            | Always store condition values as strings
            |--------------------------------------------------------------------------
            |
            | The backend can normalize numeric values when evaluating.
            |
            */

            node.config.value =
                value ?? '';


            this.selectedNodeData =
                node;

            this.markDirty();


            console.log(
                '[Automation Builder] Condition value changed:',
                {
                    nodeId: node.id,
                    field: node.config.field,
                    operator: node.config.operator,
                    value: node.config.value,
                }
            );

        },


        /*
        |--------------------------------------------------------------------------
        | CONDITION PREVIEW FIELD
        |--------------------------------------------------------------------------
        */

        conditionPreviewField(nodeId) {

            const node =
                this.getNode(nodeId);

            if (!node) {

                return 'Field';

            }

            this.ensureNodeConfig(node);

            return this.getConditionFieldLabel(
                node.config.field
            );

        },


        /*
        |--------------------------------------------------------------------------
        | CONDITION PREVIEW OPERATOR
        |--------------------------------------------------------------------------
        */

        conditionPreviewOperator(nodeId) {

            const node =
                this.getNode(nodeId);

            if (!node) {

                return 'Operator';

            }

            this.ensureNodeConfig(node);

            if (!node.config.field) {

                return 'Operator';

            }

            return this.getConditionOperatorLabelForNode(
                node,
                node.config.operator
            );

        },


        /*
        |--------------------------------------------------------------------------
        | CONDITION PREVIEW VALUE
        |--------------------------------------------------------------------------
        */

        conditionPreviewValue(nodeId) {

            const node =
                this.getNode(nodeId);

            if (!node) {

                return 'Value';

            }

            this.ensureNodeConfig(node);


            if (
                !this.conditionOperatorRequiresValue(
                    node.config.operator
                )
            ) {

                return '';

            }


            if (
                node.config.value === null ||
                typeof node.config.value === 'undefined' ||
                node.config.value === ''
            ) {

                return 'Value';

            }


            return String(
                node.config.value
            );

        },


        /*
        |--------------------------------------------------------------------------
        | CONDITION PREVIEW
        |--------------------------------------------------------------------------
        */

        conditionPreview(nodeId) {

            const node =
                this.getNode(nodeId);

            if (!node) {

                return 'Configure condition';

            }

            this.ensureNodeConfig(node);


            const field =
                this.conditionPreviewField(
                    nodeId
                );

            const operator =
                this.conditionPreviewOperator(
                    nodeId
                );

            const value =
                this.conditionPreviewValue(
                    nodeId
                );


            if (field === 'Field') {

                return 'Configure condition';

            }


            if (!value) {

                return `${field} ${operator}`;

            }


            return `${field} ${operator} ${value}`;

        },

    };

}
</script>