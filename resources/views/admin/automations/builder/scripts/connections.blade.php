<script>

function automationBuilderConnections() {

    return {

        handleConnectionHandlePointerDown(event) {

            event.preventDefault();
            event.stopPropagation();

        },


        startConnection(
            nodeId,
            sourceHandle,
            event
        ) {

            if (!event) return;

            event.preventDefault();
            event.stopPropagation();

            const node = this.getNode(nodeId);

            if (
                !node ||
                node.type === 'end' ||
                !['output', 'true', 'false'].includes(sourceHandle)
            ) {
                return;
            }

            this.selectedConnection = null;

            this.connectionDraft = {

                active: true,
                sourceNodeId: nodeId,
                sourceHandle: sourceHandle,
                mouseX: event.clientX,
                mouseY: event.clientY,

            };

            document.body.style.cursor = 'crosshair';

        },


        updateConnectionPreview(event) {

            if (!this.connectionDraft.active) {
                return;
            }

            this.connectionDraft.mouseX = event.clientX;
            this.connectionDraft.mouseY = event.clientY;

        },


        finishConnection(event) {

            if (!this.connectionDraft.active) {
                return;
            }

            if (!event) {
                this.cancelConnection();
                return;
            }

            event.preventDefault();

            const canvas = this.$refs.canvas;

            if (!canvas) {
                this.cancelConnection();
                return;
            }

            const handles =
                canvas.querySelectorAll(
                    '[data-automation-input]'
                );

            let nearest = null;
            let distance = Infinity;

            handles.forEach(handle => {

                const rect =
                    handle.getBoundingClientRect();

                if (!rect.width || !rect.height) {
                    return;
                }

                const x =
                    rect.left +
                    rect.width / 2;

                const y =
                    rect.top +
                    rect.height / 2;

                const d =
                    Math.hypot(
                        event.clientX - x,
                        event.clientY - y
                    );

                if (d < distance) {

                    distance = d;
                    nearest = handle;

                }

            });

            if (!nearest || distance > 60) {

                this.cancelConnection();
                return;

            }

            this.createConnection(
                this.connectionDraft.sourceNodeId,
                this.connectionDraft.sourceHandle,
                nearest.dataset.automationInput
            );

        },


        createConnection(
            sourceNodeId,
            sourceHandle,
            targetNodeId
        ) {

            const source = this.getNode(sourceNodeId);
            const target = this.getNode(targetNodeId);

            if (
                !source ||
                !target ||
                source.type === 'end' ||
                target.type === 'trigger' ||
                String(sourceNodeId) === String(targetNodeId)
            ) {

                this.cancelConnection();
                return;

            }

            const duplicate =
                this.connections.some(connection =>
                    String(connection.source_node_id) === String(sourceNodeId) &&
                    String(connection.target_node_id) === String(targetNodeId) &&
                    String(connection.source_handle ?? 'output') === String(sourceHandle)
                );

            if (duplicate) {

                this.cancelConnection();
                return;

            }

            this.connections =
                this.connections.filter(connection =>
                    !(
                        String(connection.target_node_id) ===
                        String(targetNodeId)
                    )
                );

            const connection = {

                id:
                    'temp_connection_' +
                    Date.now() +
                    '_' +
                    Math.random()
                        .toString(36)
                        .slice(2, 8),

                source_node_id: sourceNodeId,
                target_node_id: targetNodeId,
                source_handle: sourceHandle,
                target_handle: 'input',

            };

            this.connections.push(connection);

            this.selectedConnection = connection.id;

            this.markDirty();

            this.cancelConnection();

        },


        selectConnectionAt(event) {

            if (this.connectionDraft.active) {
                return;
            }

            const canvas = this.$refs.canvas;

            if (!canvas) {
                return;
            }

            const rect =
                canvas.getBoundingClientRect();

            const x =
                event.clientX - rect.left;

            const y =
                event.clientY - rect.top;

            let nearest = null;
            let distance = Infinity;

            this.connections.forEach(connection => {

                const source =
                    this.getConnectionPoint(
                        connection.source_node_id,
                        connection.source_handle ?? 'output'
                    );

                const target =
                    this.getConnectionPoint(
                        connection.target_node_id,
                        connection.target_handle ?? 'input'
                    );

                if (!source || !target) {
                    return;
                }

                const d =
                    this.distanceToBezier(
                        x,
                        y,
                        source.x,
                        source.y,
                        target.x,
                        target.y
                    );

                if (d < distance) {

                    distance = d;
                    nearest = connection;

                }

            });

            if (
                nearest &&
                distance <= 18
            ) {

                this.selectedConnection =
                    nearest.id;

                this.selectedNode = null;

            } else {

                this.selectedConnection = null;

            }

        },


        deleteConnection(id) {

            this.connections =
                this.connections.filter(
                    connection =>
                        String(connection.id) !==
                        String(id)
                );

            if (
                String(this.selectedConnection) ===
                String(id)
            ) {

                this.selectedConnection = null;

            }

            this.markDirty();

        },


        cancelConnection() {

            this.connectionDraft = {

                active: false,
                sourceNodeId: null,
                sourceHandle: null,
                mouseX: 0,
                mouseY: 0,

            };

            document.body.style.cursor = '';

        },


        getNode(id) {

            return this.nodes.find(
                node =>
                    String(node.id) ===
                    String(id)
            ) ?? null;

        },


        getConnectionPoint(
            nodeId,
            handle
        ) {

            const node =
                this.getNode(nodeId);

            if (!node) {
                return null;
            }

            const x =
                Number(node.position?.x ?? 0);

            const y =
                Number(node.position?.y ?? 0);

            const width = 256;
            const height = 108;

            if (handle === 'input') {

                return {
                    x,
                    y: y + height / 2,
                };

            }

            if (handle === 'true') {

                return {
                    x: x + width,
                    y: y + height * 0.35,
                };

            }

            if (handle === 'false') {

                return {
                    x: x + width,
                    y: y + height * 0.65,
                };

            }

            return {

                x: x + width,
                y: y + height / 2,

            };

        },


        connectionPath(connection) {

            const source =
                this.getConnectionPoint(
                    connection.source_node_id,
                    connection.source_handle ?? 'output'
                );

            const target =
                this.getConnectionPoint(
                    connection.target_node_id,
                    connection.target_handle ?? 'input'
                );

            if (!source || !target) {
                return '';
            }

            return this.buildBezierPath(
                source.x,
                source.y,
                target.x,
                target.y
            );

        },


        connectionsPath() {

            if (!Array.isArray(this.connections)) {
                return '';
            }

            return this.connections
                .map(connection =>
                    this.connectionPath(connection)
                )
                .filter(Boolean)
                .join(' ');

        },


        connectionPreviewPath() {

            if (!this.connectionDraft.active) {
                return '';
            }

            const source =
                this.getConnectionPoint(
                    this.connectionDraft.sourceNodeId,
                    this.connectionDraft.sourceHandle
                );

            const canvas =
                this.$refs.canvas;

            if (!source || !canvas) {
                return '';
            }

            const rect =
                canvas.getBoundingClientRect();

            return this.buildBezierPath(

                source.x,
                source.y,

                this.connectionDraft.mouseX - rect.left,
                this.connectionDraft.mouseY - rect.top

            );

        },


        buildBezierPath(
            x1,
            y1,
            x2,
            y2
        ) {

            const distance =
                Math.max(
                    70,
                    Math.abs(x2 - x1) * 0.45
                );

            return [
                `M ${x1} ${y1}`,
                `C ${x1 + distance} ${y1}`,
                `${x2 - distance} ${y2}`,
                `${x2} ${y2}`,
            ].join(' ');

        },


        distanceToBezier(
            px,
            py,
            x1,
            y1,
            x2,
            y2
        ) {

            const steps = 25;
            let min = Infinity;

            const distance =
                (a, b) =>
                    Math.hypot(
                        a.x - b.x,
                        a.y - b.y
                    );

            let previous = {
                x: x1,
                y: y1,
            };

            const curveX =
                t =>
                    Math.pow(1 - t, 3) * x1 +
                    3 * Math.pow(1 - t, 2) * t *
                        (x1 + Math.max(
                            70,
                            Math.abs(x2 - x1) * 0.45
                        )) +
                    3 * (1 - t) * Math.pow(t, 2) *
                        (x2 - Math.max(
                            70,
                            Math.abs(x2 - x1) * 0.45
                        )) +
                    Math.pow(t, 3) * x2;

            const curveY =
                t =>
                    Math.pow(1 - t, 3) * y1 +
                    3 * Math.pow(1 - t, 2) * t * y1 +
                    3 * (1 - t) * Math.pow(t, 2) * y2 +
                    Math.pow(t, 3) * y2;

            for (
                let i = 1;
                i <= steps;
                i++
            ) {

                const t =
                    i / steps;

                const current = {
                    x: curveX(t),
                    y: curveY(t),
                };

                min =
                    Math.min(
                        min,
                        distance(
                            {x: px, y: py},
                            {
                                x:
                                    (previous.x + current.x) / 2,
                                y:
                                    (previous.y + current.y) / 2,
                            }
                        )
                    );

                previous = current;

            }

            return min;

        },

    };

}

</script>