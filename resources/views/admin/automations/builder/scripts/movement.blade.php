<script>
function automationBuilderMovement() {

    return {

        /*
        |--------------------------------------------------------------------------
        | Start Node Move
        |--------------------------------------------------------------------------
        */

        startNodeMove(nodeId, event) {

            /*
            |--------------------------------------------------------------------------
            | Don't move while connecting
            |--------------------------------------------------------------------------
            */

            if (this.connectionDraft?.active) {
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Primary pointer only
            |--------------------------------------------------------------------------
            */

            if (
                event.button !== undefined &&
                event.button !== 0
            ) {
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Don't drag from interactive controls
            |--------------------------------------------------------------------------
            */

            const target =
                event.target?.closest?.(
                    'button, input, textarea, select, option, a'
                );

            if (target) {
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Don't drag from connection handles
            |--------------------------------------------------------------------------
            */

            const handle =
                event.target?.closest?.(
                    '[data-automation-handle], [data-automation-input]'
                );

            if (handle) {
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Get node
            |--------------------------------------------------------------------------
            */

            const node =
                this.getNode(nodeId);

            if (!node) {
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Get canvas
            |--------------------------------------------------------------------------
            */

            const canvas =
                this.$refs.canvas;

            if (!canvas) {
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Calculate pointer position
            |--------------------------------------------------------------------------
            */

            const rect =
                canvas.getBoundingClientRect();

            const pointerX =
                event.clientX -
                rect.left;

            const pointerY =
                event.clientY -
                rect.top;


            /*
            |--------------------------------------------------------------------------
            | Start drag
            |--------------------------------------------------------------------------
            */

            this.nodeDrag = {

                active: true,

                nodeId: nodeId,

                offsetX:
                    pointerX -
                    Number(node.position.x),

                offsetY:
                    pointerY -
                    Number(node.position.y),

            };


            /*
            |--------------------------------------------------------------------------
            | Select node
            |--------------------------------------------------------------------------
            */

            this.selectedNode = nodeId;

            this.selectedConnection = null;


            /*
            |--------------------------------------------------------------------------
            | Pointer capture
            |--------------------------------------------------------------------------
            */

            try {

                if (
                    event.currentTarget &&
                    typeof event.currentTarget.setPointerCapture ===
                        'function' &&
                    event.pointerId !== undefined
                ) {

                    event.currentTarget.setPointerCapture(
                        event.pointerId
                    );

                }

            } catch (_) {
                // Ignore pointer capture errors.
            }


            /*
            |--------------------------------------------------------------------------
            | Prevent browser text selection
            |--------------------------------------------------------------------------
            */

            event.preventDefault();

        },


        /*
        |--------------------------------------------------------------------------
        | Move Node
        |--------------------------------------------------------------------------
        */

        moveNode(event) {

            if (!this.nodeDrag?.active) {
                return;
            }


            const node =
                this.getNode(
                    this.nodeDrag.nodeId
                );

            const canvas =
                this.$refs.canvas;


            if (
                !node ||
                !canvas
            ) {
                return;
            }


            const rect =
                canvas.getBoundingClientRect();


            const pointerX =
                event.clientX -
                rect.left;

            const pointerY =
                event.clientY -
                rect.top;


            node.position.x =
                Math.max(
                    20,
                    pointerX -
                    this.nodeDrag.offsetX
                );


            node.position.y =
                Math.max(
                    20,
                    pointerY -
                    this.nodeDrag.offsetY
                );


            /*
            |--------------------------------------------------------------------------
            | Dirty state
            |--------------------------------------------------------------------------
            */

            this.markDirty?.();

        },


        /*
        |--------------------------------------------------------------------------
        | Finish Node Move
        |--------------------------------------------------------------------------
        */

        finishNodeMove() {

            if (!this.nodeDrag?.active) {
                return;
            }


            this.nodeDrag = {

                active: false,

                nodeId: null,

                offsetX: 0,

                offsetY: 0,

            };

        },

    };

}
</script>