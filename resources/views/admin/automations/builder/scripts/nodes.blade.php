<script>
function automationBuilderNodes() {

    return {

        /*
        |--------------------------------------------------------------------------
        | Library Drag
        |--------------------------------------------------------------------------
        */

        startDrag(type) {

            /*
             * Only allow known node types.
             */
            if (
                !this.nodeDefinitions?.[type]
            ) {

                console.warn(
                    '[Automation Builder] Unknown node type:',
                    type
                );

                return;

            }


            this.draggedType =
                type;

            this.isDragging =
                true;

        },


        /*
        |--------------------------------------------------------------------------
        | End Library Drag
        |--------------------------------------------------------------------------
        */

        endDrag() {

            this.isDragging =
                false;

            this.draggedType =
                null;

        },


        /*
        |--------------------------------------------------------------------------
        | Drop Node
        |--------------------------------------------------------------------------
        */

        dropNode(event) {

            this.isDragging =
                false;


            /*
             * Nothing to drop.
             */
            if (
                !this.draggedType
            ) {

                return;

            }


            /*
             * Get canvas.
             */
            const canvas =
                this.$refs.canvas;


            if (!canvas) {

                console.warn(
                    '[Automation Builder] Canvas not found.'
                );


                this.draggedType =
                    null;


                return;

            }


            /*
             * Calculate drop position.
             */
            const rect =
                canvas.getBoundingClientRect();


            const x =
                event.clientX -
                rect.left;


            const y =
                event.clientY -
                rect.top;


            /*
             * Save type before clearing draggedType.
             */
            const type =
                this.draggedType;


            /*
             * Generate a temporary ID.
             *
             * This will be replaced by the backend
             * after the workflow is saved.
             */
            const nodeId =
                'temp_' +
                Date.now() +
                '_' +
                Math.random()
                    .toString(36)
                    .substring(2, 8);


            /*
             * Create node.
             *
             * IMPORTANT:
             *
             * Configuration is created directly on
             * the node object.
             *
             * There is no separate temporary config.
             */
            const node = {

                id:
                    nodeId,

                type:
                    type,

                name:
                    this.nodeLabel(type),

                description:
                    this.defaultDescription(type),

                enabled:
                    true,

                position: {

                    x:
                        Math.max(
                            20,
                            x - 128
                        ),

                    y:
                        Math.max(
                            20,
                            y - 54
                        ),

                },

                /*
                 * Use the main builder's single
                 * defaultConfig implementation.
                 */
                config:
                    this.defaultConfig(type),

            };


            /*
             * Make absolutely sure the configuration
             * has the correct structure.
             */
            this.ensureNodeConfig(
                node
            );


            /*
             * Add node to the reactive nodes array.
             */
            this.nodes.push(
                node
            );


            /*
             * Select the newly created node.
             */
            this.selectedNode =
                node.id;


            this.selectedConnection =
                null;


            /*
             * Clear drag state.
             */
            this.draggedType =
                null;


            /*
             * Mark workflow as changed.
             */
            this.markDirty?.();


            /*
             * Debug information.
             */
            console.log(
                '[Automation Builder] Node created:',
                {
                    id: node.id,
                    type: node.type,
                    config: node.config,
                }
            );

        },


        /*
        |--------------------------------------------------------------------------
        | Select Node
        |--------------------------------------------------------------------------
        */

        selectNode(id) {

            const node =
                this.getNode(id);


            /*
             * Node doesn't exist.
             */
            if (!node) {

                this.selectedNode =
                    null;

                this.selectedConnection =
                    null;


                console.warn(
                    '[Automation Builder] Cannot select node. Node not found:',
                    id
                );


                return;

            }


            /*
             * IMPORTANT:
             *
             * Ensure configuration BEFORE selecting
             * the node.
             *
             * This means returning to a node will use
             * the exact config stored inside nodes[].
             */
            this.ensureNodeConfig(
                node
            );


            /*
             * Select the actual node ID.
             */
            this.selectedNode =
                node.id;


            /*
             * A node selection always clears
             * connection selection.
             */
            this.selectedConnection =
                null;


            /*
             * Debug configuration.
             */
            console.log(
                '[Automation Builder] Node selected:',
                {
                    id: node.id,
                    type: node.type,
                    config: node.config,
                }
            );

        },


        /*
        |--------------------------------------------------------------------------
        | Clear Selection
        |--------------------------------------------------------------------------
        */

        clearSelection(
            event = null
        ) {

            /*
             * Don't clear selection while creating
             * a connection.
             */
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


            /*
             * Clear only when the user clicked the
             * actual canvas/background.
             */
            if (
                !event ||
                event.target === canvas ||
                event.target?.classList?.contains(
                    'bg-gray-50'
                )
            ) {

                this.selectedNode =
                    null;

                this.selectedConnection =
                    null;

            }

        },


        /*
        |--------------------------------------------------------------------------
        | Delete Node
        |--------------------------------------------------------------------------
        */

        deleteNode(id) {

            const nodeId =
                String(id);


            const node =
                this.getNode(id);


            /*
             * Node doesn't exist.
             */
            if (!node) {

                console.warn(
                    '[Automation Builder] Delete failed. Node not found:',
                    id
                );


                return;

            }


            /*
             * Cancel active connection if this node
             * is currently the connection source.
             */
            if (
                this.connectionDraft?.active &&
                String(
                    this.connectionDraft.sourceNodeId
                ) === nodeId
            ) {

                if (
                    typeof this.cancelConnection ===
                    'function'
                ) {

                    this.cancelConnection();

                }

            }


            /*
             * Remove node.
             */
            this.nodes =
                this.nodes.filter(
                    item =>
                        String(item.id) !==
                        nodeId
                );


            /*
             * Remove every connection attached
             * to the deleted node.
             */
            this.connections =
                this.connections.filter(
                    connection => {

                        const sourceId =
                            String(
                                connection.source_node_id
                            );


                        const targetId =
                            String(
                                connection.target_node_id
                            );


                        return (
                            sourceId !== nodeId &&
                            targetId !== nodeId
                        );

                    }
                );


            /*
             * Clear node selection if the deleted
             * node was selected.
             */
            if (
                this.selectedNode !== null &&
                String(
                    this.selectedNode
                ) === nodeId
            ) {

                this.selectedNode =
                    null;

            }


            /*
             * Clear connection selection.
             */
            this.selectedConnection =
                null;


            /*
             * Mark workflow dirty.
             */
            this.markDirty?.();


            /*
             * Debug.
             */
            console.log(
                '[Automation Builder] Node deleted:',
                nodeId
            );

        },


        /*
        |--------------------------------------------------------------------------
        | Get Node
        |--------------------------------------------------------------------------
        */

        getNode(id) {

            if (
                !Array.isArray(
                    this.nodes
                )
            ) {

                return null;

            }


            return this.nodes.find(
                node =>
                    String(node.id) ===
                    String(id)
            ) ?? null;

        },


        /*
        |--------------------------------------------------------------------------
        | Duplicate Node
        |--------------------------------------------------------------------------
        |
        | Useful for future "Duplicate node" functionality.
        | It performs a deep copy of the configuration so
        | buttons/media/etc. aren't shared between nodes.
        |
        */

        duplicateNode(id) {

            const source =
                this.getNode(id);


            if (!source) {

                console.warn(
                    '[Automation Builder] Cannot duplicate node. Node not found:',
                    id
                );


                return null;

            }


            /*
             * Deep clone the node.
             */
            const cloned =
                JSON.parse(
                    JSON.stringify(
                        source
                    )
                );


            /*
             * Give the clone a new temporary ID.
             */
            cloned.id =
                'temp_' +
                Date.now() +
                '_' +
                Math.random()
                    .toString(36)
                    .substring(2, 8);


            /*
             * Offset the duplicated node so it
             * doesn't sit exactly on top of the original.
             */
            cloned.position = {

                x:
                    Number(
                        source.position?.x ?? 100
                    ) + 40,

                y:
                    Number(
                        source.position?.y ?? 100
                    ) + 40,

            };


            /*
             * Make sure the cloned configuration
             * is valid.
             */
            this.ensureNodeConfig(
                cloned
            );


            /*
             * Add clone.
             */
            this.nodes.push(
                cloned
            );


            /*
             * Select clone.
             */
            this.selectedNode =
                cloned.id;

            this.selectedConnection =
                null;


            /*
             * Mark dirty.
             */
            this.markDirty?.();


            console.log(
                '[Automation Builder] Node duplicated:',
                cloned
            );


            return cloned;

        },


        /*
        |--------------------------------------------------------------------------
        | Get Node Configuration
        |--------------------------------------------------------------------------
        */

        getNodeConfig(id) {

    const node = this.getNode(id);

    if (!node) {
        return {};
    }

    this.ensureNodeConfig(node);

    return node.config;
},


        /*
        |--------------------------------------------------------------------------
        | Reset Node Configuration
        |--------------------------------------------------------------------------
        */

        resetNodeConfig(
            id
        ) {

            const node =
                this.getNode(id);


            if (!node) {

                return;

            }


            /*
             * Replace configuration with a fresh
             * deep copy of the node defaults.
             */
            node.config =
                this.defaultConfig(
                    node.type
                );


            /*
             * Mark workflow dirty.
             */
            this.markDirty?.();


            console.log(
                '[Automation Builder] Node configuration reset:',
                node.id
            );

        },


        /*
        |--------------------------------------------------------------------------
        | Node Label
        |--------------------------------------------------------------------------
        */

        nodeLabel(type) {

            return (
                this.nodeDefinitions?.[type]?.label ??
                type
            );

        },


        /*
        |--------------------------------------------------------------------------
        | Node Icon
        |--------------------------------------------------------------------------
        */

        nodeIcon(type) {

            return (
                this.nodeDefinitions?.[type]?.icon ??
                '●'
            );

        },


        /*
        |--------------------------------------------------------------------------
        | Node Colors
        |--------------------------------------------------------------------------
        */

        nodeColors(type) {

            return (
                this.nodeDefinitions?.[type]?.color ??
                'bg-gray-100'
            );

        },


        /*
        |--------------------------------------------------------------------------
        | Default Description
        |--------------------------------------------------------------------------
        */

        defaultDescription(type) {

            return (
                this.nodeDefinitions?.[type]?.description ??
                'Automation node'
            );

        },

    };

}
</script>