<script>
function automationBuilderSave() {

    return {

        /*
        |--------------------------------------------------------------------------
        | CSRF TOKEN
        |--------------------------------------------------------------------------
        */

        getCsrfToken() {

            return @json(csrf_token());

        },


        /*
        |--------------------------------------------------------------------------
        | GENERIC JSON REQUEST
        |--------------------------------------------------------------------------
        */

        async request(
            url,
            method = 'GET',
            data = null
        ) {

            const options = {

                method: method,

                credentials: 'same-origin',

                headers: {

                    'Accept':
                        'application/json',

                    'X-Requested-With':
                        'XMLHttpRequest',

                    'X-CSRF-TOKEN':
                        this.getCsrfToken(),

                },

            };


            /*
             * JSON request body.
             */
            if (data !== null) {

                options.headers[
                    'Content-Type'
                ] =
                    'application/json';


                options.body =
                    JSON.stringify(data);

            }


            const response =
                await fetch(
                    url,
                    options
                );


            const responseText =
                await response.text();


            let result = {};


            try {

                result =
                    responseText
                        ? JSON.parse(
                            responseText
                        )
                        : {};

            } catch (error) {

                console.error(
                    '[Automation] Invalid JSON response:',
                    responseText
                );


                result = {

                    message:
                        responseText ||
                        'Server returned an invalid response.',

                };

            }


            console.log(
                '[Automation] Request:',
                method,
                url,
                response.status,
                result
            );


            if (!response.ok) {

                const error =
                    new Error(
                        result.message ||
                        `Request failed (${response.status})`
                    );


                error.status =
                    response.status;


                error.response =
                    result;


                throw error;

            }


            return result;

        },


        /*
        |--------------------------------------------------------------------------
        | BUILD PERSISTABLE NODE DATA
        |--------------------------------------------------------------------------
        |
        | Creates the JSON that will be stored in the database.
        |
        | IMPORTANT:
        |
        | - File objects are NOT placed inside JSON.
        | - preview_url is browser-only.
        | - The actual File is uploaded separately through FormData.
        | - Laravel will populate media.source after storing the file.
        |
        */

        buildPersistableNodes() {

            if (
                !Array.isArray(this.nodes)
            ) {

                return [];

            }


            return this.nodes.map(
                originalNode => {

                    /*
                     * Deep clone the node.
                     */
                    const node =
                        JSON.parse(
                            JSON.stringify(
                                originalNode
                            )
                        );


                    /*
                     * Make sure config exists.
                     */
                    this.ensureNodeConfig(
                        node
                    );


                    /*
                     |--------------------------------------------------------------------------
                     | TELEGRAM MEDIA
                     |--------------------------------------------------------------------------
                     */

                    if (
                        node.type ===
                        'telegram_message'
                    ) {

                        /*
                         * Existing media configuration.
                         */
                        const media =
                            node.config.media &&
                            typeof node.config.media === 'object'
                                ? node.config.media
                                : {};


                        /*
                         * Build clean persisted media object.
                         *
                         * Do NOT include preview_url.
                         */
                        node.config.media = {

                            enabled:
                                Boolean(
                                    media.enabled
                                ),

                            type:
                                media.type ||
                                'photo',

                            source:
                                media.source ??
                                null,

                            caption:
                                media.caption ??
                                null,

                            file_name:
                                media.file_name ??
                                null,

                            file_type:
                                media.file_type ??
                                null,

                            file_size:
                                Number(
                                    media.file_size
                                ) || 0,

                        };

                    }


                    return node;

                }
            );

        },


        /*
        |--------------------------------------------------------------------------
        | BUILD MULTIPART FORM DATA
        |--------------------------------------------------------------------------
        |
        | Sends:
        |
        |   name
        |   description
        |   status
        |   nodes
        |   connections
        |   media_files[node_id]
        |
        */

        buildSaveFormData() {

            const formData =
                new FormData();


            /*
             |--------------------------------------------------------------------------
             | CLEAN NODE DATA
             |--------------------------------------------------------------------------
             */

            const persistableNodes =
                this.buildPersistableNodes();


            /*
             |--------------------------------------------------------------------------
             | AUTOMATION DETAILS
             |--------------------------------------------------------------------------
             */

            formData.append(
                'name',
                this.automation.name || ''
            );


            formData.append(
                'description',
                this.automation.description || ''
            );


            formData.append(
                'status',
                this.automation.status || ''
            );


            /*
             |--------------------------------------------------------------------------
             | NODES
             |--------------------------------------------------------------------------
             */

            formData.append(
                'nodes',
                JSON.stringify(
                    persistableNodes
                )
            );


            /*
             |--------------------------------------------------------------------------
             | CONNECTIONS
             |--------------------------------------------------------------------------
             */

            formData.append(
                'connections',
                JSON.stringify(
                    Array.isArray(
                        this.connections
                    )
                        ? this.connections
                        : []
                )
            );


            /*
             |--------------------------------------------------------------------------
             | UPLOADED MEDIA FILES
             |--------------------------------------------------------------------------
             |
             | Example:
             |
             | media_files[8] = File
             |
             */

            if (
                this.pendingMediaFiles &&
                typeof this.pendingMediaFiles === 'object'
            ) {

                Object.entries(
                    this.pendingMediaFiles
                ).forEach(
                    ([nodeId, file]) => {

                        /*
                         * Ignore invalid entries.
                         */
                        if (
                            !file ||
                            !(file instanceof File)
                        ) {

                            return;

                        }


                        /*
                         * Upload file.
                         */
                        formData.append(
                            `media_files[${nodeId}]`,
                            file,
                            file.name
                        );


                        console.log(
                            '[Automation] Attaching media file:',
                            {
                                nodeId:
                                    nodeId,

                                name:
                                    file.name,

                                type:
                                    file.type,

                                size:
                                    file.size,
                            }
                        );

                    }
                );

            }


            /*
             |--------------------------------------------------------------------------
             | DEBUG FORM DATA
             |--------------------------------------------------------------------------
             */

            console.log(
                '[Automation] FormData prepared:',
                {
                    nodes:
                        persistableNodes,

                    connections:
                        this.connections,

                    pendingMediaFiles:
                        this.pendingMediaFiles,
                }
            );


            return formData;

        },


        /*
        |--------------------------------------------------------------------------
        | SAVE WORKFLOW + MEDIA
        |--------------------------------------------------------------------------
        */

        async saveWorkflowRequest() {

            const formData =
                this.buildSaveFormData();


            /*
             * Laravel method spoofing.
             *
             * The actual HTTP request is POST because
             * multipart/form-data + PUT can be problematic
             * depending on the PHP/Laravel stack.
             */
            formData.append(
                '_method',
                'PUT'
            );


            /*
             |--------------------------------------------------------------------------
             | SINGLE REQUEST
             |--------------------------------------------------------------------------
             |
             | IMPORTANT:
             |
             | There must only be ONE fetch here.
             |
             */

            const response =
                await fetch(
                    `/admin/automations/${this.automation.id}`,
                    {

                        method:
                            'POST',

                        credentials:
                            'same-origin',

                        headers: {

                            'Accept':
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest',

                            'X-CSRF-TOKEN':
                                this.getCsrfToken(),

                        },

                        body:
                            formData,

                    }
                );


            /*
             |--------------------------------------------------------------------------
             | READ RESPONSE
             |--------------------------------------------------------------------------
             */

            const responseText =
                await response.text();


            let result = {};


            try {

                result =
                    responseText
                        ? JSON.parse(
                            responseText
                        )
                        : {};

            } catch (error) {

                console.error(
                    '[Automation] Invalid JSON response:',
                    responseText
                );


                result = {

                    message:
                        responseText ||
                        'Server returned an invalid response.',

                };

            }


            console.log(
                '[Automation] Multipart save response:',
                response.status,
                result
            );


            /*
             |--------------------------------------------------------------------------
             | HANDLE ERROR
             |--------------------------------------------------------------------------
             */

            if (!response.ok) {

                const error =
                    new Error(
                        result.message ||
                        `Save failed (${response.status})`
                    );


                error.status =
                    response.status;


                error.response =
                    result;


                throw error;

            }


            return result;

        },


        /*
        |--------------------------------------------------------------------------
        | SAVE
        |--------------------------------------------------------------------------
        */

        async saveWorkflow() {

            /*
             * Prevent duplicate actions.
             */
            if (
                this.saveState === 'saving' ||
                this.saveState === 'testing' ||
                this.saveState === 'activating'
            ) {

                return null;

            }


            console.log(
                '[Automation] Starting save...',
                {

                    id:
                        this.automation.id,

                    name:
                        this.automation.name,

                    status:
                        this.automation.status,

                    nodes:
                        this.nodes,

                    connections:
                        this.connections,

                    pendingMediaFiles:
                        this.pendingMediaFiles,

                }
            );


            this.saveState =
                'saving';


            try {

                /*
                 |--------------------------------------------------------------------------
                 | SAVE WORKFLOW + MEDIA
                 |--------------------------------------------------------------------------
                 */

                const result =
                    await this.saveWorkflowRequest();


                /*
                 |--------------------------------------------------------------------------
                 | REPLACE NODES WITH SERVER VERSION
                 |--------------------------------------------------------------------------
                 */

                if (
                    Array.isArray(
                        result.nodes
                    )
                ) {

                    /*
                     * Prevent the Alpine watcher from
                     * immediately marking the workflow dirty.
                     */
                    this._suppressDirty =
                        true;


                    this.nodes =
                        result.nodes;


                    /*
                     |--------------------------------------------------------------------------
                     | NORMALIZE SERVER NODES
                     |--------------------------------------------------------------------------
                     */

                    this.nodes.forEach(
                        node => {

                            this.ensureNodeConfig(
                                node
                            );


                            /*
                             |--------------------------------------------------------------------------
                             | TELEGRAM MEDIA
                             |--------------------------------------------------------------------------
                             */

                            if (
                                node.type ===
                                'telegram_message'
                            ) {

                                if (
                                    !node.config.media ||
                                    typeof node.config.media !==
                                    'object'
                                ) {

                                    node.config.media = {

                                        enabled:
                                            false,

                                        type:
                                            'photo',

                                        source:
                                            null,

                                        caption:
                                            null,

                                        file_name:
                                            null,

                                        file_type:
                                            null,

                                        file_size:
                                            0,

                                    };

                                }


                                /*
                                 * preview_url is UI-only.
                                 *
                                 * We do NOT save it to DB.
                                 *
                                 * But we can create an empty
                                 * property for the UI.
                                 */
                                if (
                                    typeof node.config.media
                                        .preview_url ===
                                    'undefined'
                                ) {

                                    node.config.media.preview_url =
                                        '';

                                }

                            }

                        }
                    );


                    /*
                     |--------------------------------------------------------------------------
                     | RECONNECT SELECTED NODE
                     |--------------------------------------------------------------------------
                     */

                    if (
                        this.selectedNode !== null
                    ) {

                        this.selectedNodeData =
                            this.getNode(
                                this.selectedNode
                            );

                    }


                    /*
                     |--------------------------------------------------------------------------
                     | RE-ENABLE DIRTY WATCHER
                     |--------------------------------------------------------------------------
                     */

                    this.$nextTick(
                        () => {

                            this._suppressDirty =
                                false;

                        }
                    );

                }


                /*
                 |--------------------------------------------------------------------------
                 | CONNECTIONS
                 |--------------------------------------------------------------------------
                 */

                if (
                    Array.isArray(
                        result.connections
                    )
                ) {

                    this.connections =
                        result.connections.map(
                            connection => ({

                                ...connection,

                                source_node_id:
                                    this.normalizeNodeId(
                                        connection.source_node_id
                                    ),

                                target_node_id:
                                    this.normalizeNodeId(
                                        connection.target_node_id
                                    ),

                            })
                        );

                }


                /*
                 |--------------------------------------------------------------------------
                 | AUTOMATION
                 |--------------------------------------------------------------------------
                 */

                if (
                    result.automation
                ) {

                    this.automation.id =
                        result.automation.id;


                    this.automation.name =
                        result.automation.name;


                    this.automation.status =
                        result.automation.status;


                    this.automation.description =
                        result.automation.description ??
                        '';

                }


                /*
                 |--------------------------------------------------------------------------
                 | CLEAR PENDING MEDIA FILES
                 |--------------------------------------------------------------------------
                 |
                 | At this point the server has successfully
                 | accepted the uploaded files.
                 |
                 */

                if (
                    this.pendingMediaFiles &&
                    typeof this.pendingMediaFiles === 'object'
                ) {

                    this.pendingMediaFiles =
                        {};

                }


                /*
                 |--------------------------------------------------------------------------
                 | SAVE STATE
                 |--------------------------------------------------------------------------
                 */

                this.saveState =
                    'saved';


                console.log(
                    '[Automation] SAVE SUCCESS',
                    result
                );


                return result;

            } catch (error) {

                console.error(
                    '[Automation] SAVE FAILED',
                    error
                );


                this.saveState =
                    'dirty';


                /*
                 |--------------------------------------------------------------------------
                 | VALIDATION ERRORS
                 |--------------------------------------------------------------------------
                 */

                if (
                    error.status === 422 &&
                    error.response?.validation
                ) {

                    this.validation =
                        error.response.validation;


                    this.validationVisible =
                        true;

                }


                return null;

            }

        },


        /*
        |--------------------------------------------------------------------------
        | VALIDATE
        |--------------------------------------------------------------------------
        */

        async validateAutomation() {

            try {

                const result =
                    await this.request(
                        `/admin/automations/${this.automation.id}/validate`,
                        'GET'
                    );


                this.validation = {

                    valid:
                        Boolean(
                            result.valid
                        ),

                    errors:
                        Array.isArray(
                            result.errors
                        )
                            ? result.errors
                            : [],

                    warnings:
                        Array.isArray(
                            result.warnings
                        )
                            ? result.warnings
                            : [],

                };


                this.validationVisible =
                    true;


                console.log(
                    '[Automation] VALIDATION',
                    this.validation
                );


                return this.validation;

            } catch (error) {

                console.error(
                    '[Automation] VALIDATION FAILED',
                    error
                );


                this.validation = {

                    valid:
                        false,

                    errors: [

                        {

                            code:
                                'validation.request.failed',

                            message:
                                error.message,

                        },

                    ],

                    warnings:
                        [],

                };


                this.validationVisible =
                    true;


                return this.validation;

            }

        },


        /*
        |--------------------------------------------------------------------------
        | TEST RUN
        |--------------------------------------------------------------------------
        */

        async testRun() {

            if (
                this.saveState === 'testing' ||
                this.saveState === 'saving' ||
                this.saveState === 'activating'
            ) {

                return null;

            }


            console.log(
                '[Automation] Test Run clicked'
            );


            /*
             |--------------------------------------------------------------------------
             | SAVE FIRST
             |--------------------------------------------------------------------------
             */

            if (
                this.saveState === 'dirty'
            ) {

                const saved =
                    await this.saveWorkflow();


                if (!saved) {

                    console.error(
                        '[Automation] Test aborted because save failed.'
                    );


                    return null;

                }

            }


            /*
             |--------------------------------------------------------------------------
             | VALIDATE
             |--------------------------------------------------------------------------
             */

            const validation =
                await this.validateAutomation();


            if (
                !validation.valid
            ) {

                console.warn(
                    '[Automation] Test aborted because validation failed.'
                );


                return null;

            }


            this.saveState =
                'testing';


            try {

                const result =
                    await this.request(
                        `/admin/automations/${this.automation.id}/test-run`,
                        'POST'
                    );


                console.log(
                    '[Automation] TEST SUCCESS',
                    result
                );


                this.saveState =
                    'saved';


                if (
                    result.execution_id
                ) {

                    window.location.href =
                        `/admin/automations/${this.automation.id}/executions`;

                }


                return result;

            } catch (error) {

                console.error(
                    '[Automation] TEST FAILED',
                    error
                );


                this.saveState =
                    'saved';


                return null;

            }

        },


        /*
        |--------------------------------------------------------------------------
        | ACTIVATE
        |--------------------------------------------------------------------------
        */

        async activateAutomation() {

            if (
                this.saveState ===
                'activating'
            ) {

                return null;

            }


            /*
             |--------------------------------------------------------------------------
             | SAVE FIRST
             |--------------------------------------------------------------------------
             */

            if (
                this.saveState ===
                'dirty'
            ) {

                const saved =
                    await this.saveWorkflow();


                if (!saved) {

                    return null;

                }

            }


            /*
             |--------------------------------------------------------------------------
             | VALIDATE
             |--------------------------------------------------------------------------
             */

            const validation =
                await this.validateAutomation();


            if (
                !validation.valid
            ) {

                return null;

            }


            this.saveState =
                'activating';


            try {

                const result =
                    await this.request(
                        `/admin/automations/${this.automation.id}/activate`,
                        'POST'
                    );


                this.automation.status =
                    result.status ||
                    'active';


                this.saveState =
                    'saved';


                return result;

            } catch (error) {

                console.error(
                    '[Automation] ACTIVATE FAILED',
                    error
                );


                this.saveState =
                    'dirty';


                return null;

            }

        },


        /*
        |--------------------------------------------------------------------------
        | PAUSE
        |--------------------------------------------------------------------------
        */

        async pauseAutomation() {

            try {

                const result =
                    await this.request(
                        `/admin/automations/${this.automation.id}/pause`,
                        'POST'
                    );


                this.automation.status =
                    result.status ||
                    'paused';


                this.saveState =
                    'saved';


                return result;

            } catch (error) {

                console.error(
                    '[Automation] PAUSE FAILED',
                    error
                );


                return null;

            }

        },


        /*
        |--------------------------------------------------------------------------
        | SAVE STATE
        |--------------------------------------------------------------------------
        */

        setSaveState(state) {

            this.saveState =
                state;

        },

    };

}
</script>