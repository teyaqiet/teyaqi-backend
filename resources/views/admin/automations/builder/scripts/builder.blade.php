<script>
function automationBuilder() {

    return {

        /*
        |--------------------------------------------------------------------------
        | Automation
        |--------------------------------------------------------------------------
        */

        automation: {
            id: @json($automation->id),
            name: @json($automation->name),
            status: @json($automation->status),
            description: @json($automation->description ?? ''),
        },


        /*
        |--------------------------------------------------------------------------
        | Builder Data
        |--------------------------------------------------------------------------
        */

        nodes: @json($builderNodes),
        connections: @json($builderConnections),


        /*
        |--------------------------------------------------------------------------
        | Temporary Uploads
        |--------------------------------------------------------------------------
        */

        pendingMediaFiles: {},


        /*
        |--------------------------------------------------------------------------
        | Selection
        |--------------------------------------------------------------------------
        */

        selectedNode: null,
        selectedNodeData: null,
        selectedConnection: null,


        /*
        |--------------------------------------------------------------------------
        | Dragging
        |--------------------------------------------------------------------------
        */

        draggedType: null,
        isDragging: false,


        /*
        |--------------------------------------------------------------------------
        | Save State
        |--------------------------------------------------------------------------
        */

        saveState: 'saved',

        _builderReady: false,
        _suppressDirty: false,


        /*
        |--------------------------------------------------------------------------
        | Node Drag
        |--------------------------------------------------------------------------
        */

        nodeDrag: {
            active: false,
            nodeId: null,
            offsetX: 0,
            offsetY: 0,
        },


        /*
        |--------------------------------------------------------------------------
        | Connection Draft
        |--------------------------------------------------------------------------
        */

        connectionDraft: {
            active: false,
            sourceNodeId: null,
            sourceHandle: null,
            mouseX: 0,
            mouseY: 0,
        },


        /*
        |--------------------------------------------------------------------------
        | Node Definitions
        |--------------------------------------------------------------------------
        */

        nodeDefinitions: {

            trigger: {
                label: 'Trigger',
                icon: '⚡',
                color: 'bg-yellow-50',
                description: 'Starts the automation workflow.',
            },

            condition: {
                label: 'Condition',
                icon: '◇',
                color: 'bg-purple-50',
                description: 'Checks whether a condition is true.',
            },

            telegram_message: {
                label: 'Telegram Message',
                icon: '✈',
                color: 'bg-blue-50',
                description: 'Sends a Telegram message.',
            },

            delay: {
                label: 'Delay',
                icon: '⏱',
                color: 'bg-orange-50',
                description: 'Waits before continuing.',
            },

            end: {
                label: 'End',
                icon: '■',
                color: 'bg-gray-100',
                description: 'Ends the workflow.',
            },

        },


        /*
        |--------------------------------------------------------------------------
        | Initialization
        |--------------------------------------------------------------------------
        */

        init() {

            this.normalizeData();

            this.registerPointerEvents();

            this.registerKeyboardEvents();

            this.registerNodeWatcher();

            this.$nextTick(() => {

                this._builderReady = true;

                this.saveState = 'saved';

            });

        },


        /*
        |--------------------------------------------------------------------------
        | Destroy
        |--------------------------------------------------------------------------
        */

        destroy() {

            this.removePointerEvents();

            this.removeKeyboardEvents();

            this.removeNodeWatcher();

            if (
                typeof this.cancelConnection === 'function'
            ) {

                this.cancelConnection();

            }


            /*
            |--------------------------------------------------------------------------
            | Revoke temporary media previews
            |--------------------------------------------------------------------------
            */

            this.nodes.forEach(node => {

                const preview =
                    node?.config?.media?.preview_url;

                if (!preview) {
                    return;
                }

                try {

                    URL.revokeObjectURL(preview);

                } catch (error) {

                    console.warn(
                        '[Automation Builder] Could not revoke preview URL.',
                        error
                    );

                }

            });

        },


        /*
        |--------------------------------------------------------------------------
        | Normalize Builder Data
        |--------------------------------------------------------------------------
        */

        normalizeData() {

            /*
            |--------------------------------------------------------------------------
            | Nodes
            |--------------------------------------------------------------------------
            */

            if (!Array.isArray(this.nodes)) {

                this.nodes = [];

            }


            this.nodes = this.nodes

                .filter(node =>
                    node &&
                    typeof node === 'object'
                )

                .map(node => {

                    /*
                    |------------------------------------------------------------------
                    | Position
                    |------------------------------------------------------------------
                    */

                    if (
                        !node.position ||
                        typeof node.position !== 'object'
                    ) {

                        node.position = {
                            x: 100,
                            y: 100,
                        };

                    }


                    node.position.x =
                        Number.isFinite(
                            Number(node.position.x)
                        )
                            ? Number(node.position.x)
                            : 100;


                    node.position.y =
                        Number.isFinite(
                            Number(node.position.y)
                        )
                            ? Number(node.position.y)
                            : 100;


                    /*
                    |------------------------------------------------------------------
                    | Enabled
                    |------------------------------------------------------------------
                    */

                    if (
                        typeof node.enabled === 'undefined'
                    ) {

                        node.enabled = true;

                    }


                    /*
                    |------------------------------------------------------------------
                    | Configuration
                    |------------------------------------------------------------------
                    */

                    this.ensureNodeConfig(node);


                    return node;

                });


            /*
            |--------------------------------------------------------------------------
            | Connections
            |--------------------------------------------------------------------------
            */

            if (!Array.isArray(this.connections)) {

                this.connections = [];

            }


            this.connections =
                this.connections

                    .filter(connection =>
                        connection &&
                        typeof connection === 'object'
                    )

                    .map(connection => ({

                        ...connection,

                        source_node_id:
                            this.normalizeNodeId(
                                connection.source_node_id
                            ),

                        target_node_id:
                            this.normalizeNodeId(
                                connection.target_node_id
                            ),

                        source_handle:
                            connection.source_handle ??
                            'output',

                        target_handle:
                            connection.target_handle ??
                            'input',

                    }));

        },


        /*
        |--------------------------------------------------------------------------
        | Normalize Node ID
        |--------------------------------------------------------------------------
        */

        normalizeNodeId(id) {

            if (
                id === null ||
                typeof id === 'undefined'
            ) {

                return id;

            }


            if (
                typeof id === 'string' &&
                id.startsWith('temp_')
            ) {

                return id;

            }


            if (
                typeof id === 'string' &&
                id.trim() !== '' &&
                !isNaN(id)
            ) {

                return Number(id);

            }


            return id;

        },


        /*
        |--------------------------------------------------------------------------
        | Parse Node Configuration
        |--------------------------------------------------------------------------
        */

        parseNodeConfig(config) {

            if (
                config &&
                typeof config === 'object' &&
                !Array.isArray(config)
            ) {

                return config;

            }


            if (typeof config === 'string') {

                try {

                    const parsed =
                        JSON.parse(config);

                    if (
                        parsed &&
                        typeof parsed === 'object' &&
                        !Array.isArray(parsed)
                    ) {

                        return parsed;

                    }

                } catch (error) {

                    console.warn(
                        '[Automation Builder] Failed to parse node config:',
                        error
                    );

                }

            }


            return {};

        },


        /*
        |--------------------------------------------------------------------------
        | Default Node Configuration
        |--------------------------------------------------------------------------
        */

        defaultConfig(type) {

            const configs = {

                trigger: {

                    event: '',
                    description: '',
                    enabled: true,

                },


                condition: {

                    field: '',
                    operator: '',
                    value: '',
                    case_sensitive: false,

                },


                telegram_message: {

                    recipient: 'context.telegram_id',

                    chat_id: '',

                    message: '',

                    message_type: 'text',

                    parse_mode: 'HTML',

                    media: {

                        enabled: false,
                        type: 'photo',
                        source: '',
                        caption: '',
                        file_name: '',
                        file_type: '',
                        file_size: 0,
                        preview_url: '',

                    },

                    disable_web_page_preview: false,

                    disable_notification: false,

                    buttons: [],

                },


                delay: {

                    duration: 1,
                    unit: 'minutes',

                },


                end: {

                    status: 'success',
                    message: '',

                },

            };


            if (!configs[type]) {

                return {};

            }


            return JSON.parse(
                JSON.stringify(
                    configs[type]
                )
            );

        },


        /*
        |--------------------------------------------------------------------------
        | Ensure Node Configuration
        |--------------------------------------------------------------------------
        */

        ensureNodeConfig(node) {

            if (!node) {

                return {};

            }


            const defaults =
                this.defaultConfig(
                    node.type
                );


            const existing =
                this.parseNodeConfig(
                    node.config
                );


            node.config = {

                ...defaults,

                ...existing,

            };


            /*
            |--------------------------------------------------------------------------
            | Condition
            |--------------------------------------------------------------------------
            */

            if (node.type === 'condition') {

                if (
                    node.config.field === null ||
                    typeof node.config.field === 'undefined'
                ) {

                    node.config.field = '';

                }


                if (
                    node.config.operator === null ||
                    typeof node.config.operator === 'undefined'
                ) {

                    node.config.operator = '';

                }


                if (
                    node.config.value === null ||
                    typeof node.config.value === 'undefined'
                ) {

                    node.config.value = '';

                }


                if (
                    typeof node.config.case_sensitive ===
                    'undefined'
                ) {

                    node.config.case_sensitive = false;

                }


                /*
                |------------------------------------------------------------------
                | Repair invalid operator
                |------------------------------------------------------------------
                */

                if (node.config.field) {

                    const field =
                        this.getConditionField(
                            node.config.field
                        );


                    if (field) {

                        const operators =
                            this.getConditionOperators(
                                field.value
                            );


                        const valid =
                            operators.some(
                                operator =>
                                    operator.value ===
                                    node.config.operator
                            );


                        if (!valid) {

                            node.config.operator =
                                operators[0]?.value ??
                                '';

                        }

                    }

                }

            }


            /*
            |--------------------------------------------------------------------------
            | Delay
            |--------------------------------------------------------------------------
            */

            if (node.type === 'delay') {

                const duration =
                    Number(
                        node.config.duration
                    );


                node.config.duration =
                    Number.isFinite(duration) &&
                    duration >= 1
                        ? Math.floor(duration)
                        : 1;


                const allowedUnits = [
                    'seconds',
                    'minutes',
                    'hours',
                    'days',
                ];


                if (
                    !allowedUnits.includes(
                        node.config.unit
                    )
                ) {

                    node.config.unit = 'minutes';

                }

            }


            /*
            |--------------------------------------------------------------------------
            | Telegram Message
            |--------------------------------------------------------------------------
            */

            if (
                node.type === 'telegram_message'
            ) {

                if (
                    !Array.isArray(
                        node.config.buttons
                    )
                ) {

                    node.config.buttons = [];

                }


                if (
                    !node.config.media ||
                    typeof node.config.media !== 'object' ||
                    Array.isArray(node.config.media)
                ) {

                    node.config.media = {

                        enabled: false,
                        type: 'photo',
                        source: '',
                        caption: '',
                        file_name: '',
                        file_type: '',
                        file_size: 0,
                        preview_url: '',

                    };

                }

            }


            return node.config;

        },


        /*
        |--------------------------------------------------------------------------
        | Node Helpers
        |--------------------------------------------------------------------------
        */

        getNode(id) {

            if (!Array.isArray(this.nodes)) {

                return null;

            }


            return this.nodes.find(
                node =>
                    String(node.id) ===
                    String(id)
            ) ?? null;

        },


        getSelectedNode() {

            if (
                this.selectedNode === null ||
                typeof this.selectedNode === 'undefined'
            ) {

                return null;

            }


            const node =
                this.getNode(
                    this.selectedNode
                );


            if (node) {

                this.ensureNodeConfig(node);

            }


            return node;

        },


        getSelectedNodeConfig() {

            const node =
                this.getSelectedNode();


            if (!node) {

                return {};

            }


            return this.ensureNodeConfig(
                node
            );

        },


        /*
        |--------------------------------------------------------------------------
        | Nested Configuration Helpers
        |--------------------------------------------------------------------------
        */

        setNestedValue(
            object,
            path,
            value
        ) {

            const keys =
                String(path).split('.');


            let current =
                object;


            for (
                let index = 0;
                index < keys.length - 1;
                index++
            ) {

                const key =
                    keys[index];


                if (
                    !current[key] ||
                    typeof current[key] !== 'object'
                ) {

                    current[key] =
                        /^\d+$/.test(
                            keys[index + 1]
                        )
                            ? []
                            : {};

                }


                current =
                    current[key];

            }


            current[
                keys[keys.length - 1]
            ] = value;

        },


        getNodeConfigValue(
            nodeId,
            key,
            fallback = ''
        ) {

            const node =
                this.getNode(
                    nodeId
                );


            if (!node) {

                return fallback;

            }


            const config =
                this.ensureNodeConfig(
                    node
                );


            const keys =
                String(key).split('.');


            let current =
                config;


            for (const part of keys) {

                if (
                    current === null ||
                    typeof current === 'undefined'
                ) {

                    return fallback;

                }


                current =
                    current[part];

            }


            return typeof current === 'undefined'
                ? fallback
                : current;

        },


        /*
        |--------------------------------------------------------------------------
        | Selection
        |--------------------------------------------------------------------------
        */

        selectNode(id) {

            this.selectedConnection = null;


            const node =
                this.getNode(id);


            if (!node) {

                this.selectedNode = null;

                this.selectedNodeData = null;

                return;

            }


            this.ensureNodeConfig(node);


            this.selectedNode =
                node.id;


            this.selectedNodeData =
                node;

        },


        clearSelection(event = null) {

            if (
                this.connectionDraft?.active
            ) {

                return;

            }


            const canvas =
                this.$refs.canvas;


            if (!canvas) {

                return;

            }


            if (
                !event ||
                event.target === canvas ||
                event.target?.classList?.contains(
                    'bg-gray-50'
                )
            ) {

                this.selectedNode = null;

                this.selectedNodeData = null;

                this.selectedConnection = null;

            }

        },


        getSelectedConnection() {

            if (
                this.selectedConnection === null
            ) {

                return null;

            }


            return this.connections.find(
                connection =>
                    String(connection.id) ===
                    String(this.selectedConnection)
            ) ?? null;

        },


        /*
        |--------------------------------------------------------------------------
        | Keyboard Events
        |--------------------------------------------------------------------------
        */

        registerKeyboardEvents() {

            this._keydownHandler =
                event => {

                    const tag =
                        event.target?.tagName
                            ?.toLowerCase();


                    if (
                        [
                            'input',
                            'textarea',
                            'select',
                        ].includes(tag)
                    ) {

                        return;

                    }


                    if (
                        event.target?.isContentEditable
                    ) {

                        return;

                    }


                    if (
                        ![
                            'Delete',
                            'Backspace',
                        ].includes(
                            event.key
                        )
                    ) {

                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Delete Connection
                    |--------------------------------------------------------------------------
                    */

                    if (
                        this.selectedConnection !== null
                    ) {

                        event.preventDefault();

                        if (
                            typeof this.deleteConnection ===
                            'function'
                        ) {

                            this.deleteConnection(
                                this.selectedConnection
                            );

                        }

                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Delete Node
                    |--------------------------------------------------------------------------
                    */

                    if (
                        this.selectedNode !== null
                    ) {

                        event.preventDefault();

                        if (
                            typeof this.removeNodeFromBuilder ===
                            'function'
                        ) {

                            this.removeNodeFromBuilder(
                                this.selectedNode
                            );

                        }

                    }

                };


            window.addEventListener(
                'keydown',
                this._keydownHandler
            );

        },


        removeKeyboardEvents() {

            if (
                this._keydownHandler
            ) {

                window.removeEventListener(
                    'keydown',
                    this._keydownHandler
                );


                this._keydownHandler = null;

            }

        },


        /*
        |--------------------------------------------------------------------------
        | Pointer Events
        |--------------------------------------------------------------------------
        */

        registerPointerEvents() {

            this._pointerMoveHandler =
                event => {

                    if (
                        this.nodeDrag.active
                    ) {

                        if (
                            typeof this.moveNode ===
                            'function'
                        ) {

                            this.moveNode(event);

                        }

                        return;

                    }


                    if (
                        this.connectionDraft.active
                    ) {

                        if (
                            typeof this.updateConnectionPreview ===
                            'function'
                        ) {

                            this.updateConnectionPreview(
                                event
                            );

                        }

                    }

                };


            this._pointerUpHandler =
                event => {

                    if (
                        this.nodeDrag.active
                    ) {

                        if (
                            typeof this.finishNodeMove ===
                            'function'
                        ) {

                            this.finishNodeMove();

                        }

                        return;

                    }


                    if (
                        this.connectionDraft.active
                    ) {

                        if (
                            typeof this.finishConnection ===
                            'function'
                        ) {

                            this.finishConnection(
                                event
                            );

                        }

                    }

                };


            window.addEventListener(
                'pointermove',
                this._pointerMoveHandler
            );


            window.addEventListener(
                'pointerup',
                this._pointerUpHandler
            );

        },


        removePointerEvents() {

            if (
                this._pointerMoveHandler
            ) {

                window.removeEventListener(
                    'pointermove',
                    this._pointerMoveHandler
                );


                this._pointerMoveHandler = null;

            }


            if (
                this._pointerUpHandler
            ) {

                window.removeEventListener(
                    'pointerup',
                    this._pointerUpHandler
                );


                this._pointerUpHandler = null;

            }

        },


        /*
        |--------------------------------------------------------------------------
        | Node Watcher
        |--------------------------------------------------------------------------
        */

        registerNodeWatcher() {

            if (
                typeof this.$watch !== 'function'
            ) {

                return;

            }


            this._nodesWatcher =
                this.$watch(
                    'nodes',
                    () => {

                        if (
                            !this._builderReady ||
                            this._suppressDirty
                        ) {

                            return;

                        }


                        this.markDirty();

                    },
                    {
                        deep: true,
                    }
                );

        },


        removeNodeWatcher() {

            if (
                typeof this._nodesWatcher ===
                'function'
            ) {

                this._nodesWatcher();

                this._nodesWatcher = null;

            }

        },


        /*
        |--------------------------------------------------------------------------
        | UI Helpers
        |--------------------------------------------------------------------------
        */

        nodeLabel(type) {

            return (
                this.nodeDefinitions?.[type]?.label ??
                type
            );

        },


        nodeIcon(type) {

            return (
                this.nodeDefinitions?.[type]?.icon ??
                '●'
            );

        },


        nodeColors(type) {

            return (
                this.nodeDefinitions?.[type]?.color ??
                'bg-gray-100'
            );

        },


        defaultDescription(type) {

            return (
                this.nodeDefinitions?.[type]?.description ??
                'Automation node'
            );

        },


        /*
        |--------------------------------------------------------------------------
        | Dirty State
        |--------------------------------------------------------------------------
        */

        markDirty() {

            if (
                this._suppressDirty
            ) {

                return;

            }


            if (
                this.saveState !== 'saving'
            ) {

                this.saveState = 'dirty';

            }

        },


        setSaveState(state) {

            this.saveState = state;

        },


        /*
        |--------------------------------------------------------------------------
        | Feature Modules
        |--------------------------------------------------------------------------
        |
        | Each module owns one specific area of the builder.
        |
        */

        ...automationBuilderNodes(),

        ...automationBuilderMovement(),

        ...automationBuilderConnections(),

        ...automationBuilderSave(),

        ...automationBuilderConditions(),

        ...automationBuilderMedia(),

    };

}
</script>