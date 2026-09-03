<script>
function operationsDeployments() {
    return {
        loading: false,
        historyLoading: false,

        overview: {},

        deploymentConfig: {
            environment: 'staging',
            branch: 'main',
            pipeline: {}
        },

        activeDeployment: null,
        history: [],

        showDeployModal: false,
        showPreflightModal: false,
        showDetailsModal: false,
        showMessageModal: false,

        selectedDeployment: null,

        preflightLoading: false,
        preflightResult: null,

        messageModal: {
            type: 'info',
            title: '',
            message: '',
            confirmText: 'OK',
            cancelText: 'Cancel',
            showCancel: false,
            onConfirm: null
        },

        reloadScheduled: false,
        pollingTimer: null,

        pipelineSteps: [
            'git',
            'composer',
            'npm',
            'build',
            'migrations',
            'optimize',
            'queue_restart',
            'health_check'
        ],

        stepLabels: {
            git: 'Git',
            composer: 'Composer',
            npm: 'NPM',
            build: 'Frontend Build',
            migrations: 'Database Migrations',
            optimize: 'Optimize',
            queue_restart: 'Queue Restart',
            health_check: 'Health Check'
        },

        previousDeploymentStatus: null,

        async init() {
            await this.refresh();

            this.startPolling();
        },

        async refresh() {
            this.loading = true;

            try {
                await Promise.all([
                    this.loadOverview(),
                    this.loadHistory()
                ]);

                await this.loadActiveDeployment();
            } catch (error) {
                console.error(
                    'Failed to refresh deployments:',
                    error
                );

                this.showMessage(
                    'error',
                    'Refresh Failed',
                    this.errorMessage(error)
                );
            } finally {
                this.loading = false;
            }
        },

        /*
         * -----------------------------
         * OVERVIEW
         * -----------------------------
         */

        async loadOverview() {
            const response = await fetch(
                '{{ url('/api/admin/operations/deployments') }}',
                {
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            const data = await this.parseResponse(response);

            if (!data.success) {
                throw new Error(
                    data.message ||
                    'Failed to load deployment overview.'
                );
            }

            const result = data.data || {};

            /*
             * Keep the API response structure intact.
             *
             * Example:
             *
             * overview.statistics.total
             * overview.statistics.successful
             * overview.statistics.failed
             * overview.statistics.running
             */
            this.overview = result;

            this.deploymentConfig = {
                environment:
                    result.environment ||
                    '{{ config('operations.deployments.environment', 'staging') }}',

                branch:
                    result.branch ||
                    '{{ config('operations.deployments.branch', 'main') }}',

                pipeline:
                    result.pipeline || {}
            };

            /*
             * The API uses running_deployment.
             */
            if (result.running_deployment) {
                this.activeDeployment =
                    result.running_deployment;
            }
        },

        /*
         * -----------------------------
         * ACTIVE DEPLOYMENT
         * -----------------------------
         */

        async loadActiveDeployment() {
            try {
                const response = await fetch(
                    '{{ url('/api/admin/operations/deployments') }}',
                    {
                        headers: {
                            'Accept': 'application/json'
                        }
                    }
                );

                const data =
                    await this.parseResponse(response);

                if (!data.success) {
                    return;
                }

                const result = data.data || {};

                /*
                 * Support the current API name first,
                 * while keeping backwards compatibility
                 * with possible older response names.
                 */
                const active =
                    result.running_deployment ||
                    result.active_deployment ||
                    result.active ||
                    null;

                /*
                 * If there is no running deployment, clear
                 * the active deployment so the UI correctly
                 * becomes idle.
                 */
                if (!active) {
                    if (this.deploymentRunning) {
                        this.activeDeployment = null;
                    }

                    return;
                }

                const oldStatus =
                    this.activeDeployment?.status || null;

                this.activeDeployment = active;

                /*
                 * If a deployment transitions from
                 * pending/running to completed/failed,
                 * show the result and reload the page.
                 */
                if (
                    oldStatus &&
                    ['pending', 'running'].includes(oldStatus) &&
                    ['completed', 'failed'].includes(active.status)
                ) {
                    this.handleDeploymentFinished(active);
                }

            } catch (error) {
                console.error(
                    'Failed to load active deployment:',
                    error
                );
            }
        },

        /*
         * -----------------------------
         * HISTORY
         * -----------------------------
         */

        async loadHistory() {
            this.historyLoading = true;

            try {
                const response = await fetch(
                    '{{ url('/api/admin/operations/deployments/history') }}',
                    {
                        headers: {
                            'Accept': 'application/json'
                        }
                    }
                );

                const data =
                    await this.parseResponse(response);

                if (!data.success) {
                    throw new Error(
                        data.message ||
                        'Failed to load deployment history.'
                    );
                }

                this.history =
                    Array.isArray(data.data)
                        ? data.data
                        : (
                            data.data?.data ||
                            data.data?.deployments ||
                            []
                        );

            } catch (error) {
                console.error(
                    'Failed to load deployment history:',
                    error
                );

                this.history = [];

            } finally {
                this.historyLoading = false;
            }
        },

        /*
         * -----------------------------
         * POLLING
         * -----------------------------
         */

        startPolling() {
            this.stopPolling();

            this.pollingTimer =
                setInterval(async () => {

                    if (!this.deploymentRunning) {
                        return;
                    }

                    await this.loadActiveDeployment();

                    await this.loadOverview();

                }, 2000);
        },

        stopPolling() {
            if (this.pollingTimer) {
                clearInterval(
                    this.pollingTimer
                );

                this.pollingTimer = null;
            }
        },

        /*
         * -----------------------------
         * DEPLOYMENT STATE
         * -----------------------------
         */

        get deploymentRunning() {
            return [
                'pending',
                'running'
            ].includes(
                this.activeDeployment?.status
            );
        },

        get deploymentProgress() {
            if (!this.activeDeployment) {
                return 0;
            }

            const status =
                this.activeDeployment.status;

            if (status === 'completed') {
                return 100;
            }

            if (status === 'failed') {
                return this.completedStepCount() > 0
                    ? Math.round(
                        (
                            this.completedStepCount() /
                            this.pipelineSteps.length
                        ) * 100
                    )
                    : 0;
            }

            /*
             * We don't currently receive exact command
             * percentage progress from the backend.
             *
             * Therefore the running step contributes
             * half a step visually.
             */
            const completed =
                this.completedStepCount();

            const running =
                this.runningStep();

            const progress =
                completed +
                (running ? 0.5 : 0);

            return Math.min(
                99,
                Math.round(
                    (
                        progress /
                        this.pipelineSteps.length
                    ) * 100
                )
            );
        },

        get outputLines() {
            if (!this.activeDeployment?.output) {
                return 0;
            }

            return this.activeDeployment.output
                .split('\n')
                .filter(
                    line => line.trim() !== ''
                )
                .length;
        },

        completedStepCount() {
            return this.pipelineSteps.filter(
                step =>
                    this.stepStatus(step) ===
                    'completed'
            ).length;
        },

        runningStep() {
            return this.pipelineSteps.find(
                step =>
                    this.stepStatus(step) ===
                    'running'
            );
        },

        /*
         * -----------------------------
         * PIPELINE STATUS
         * -----------------------------
         */

        stepStatus(step) {
            if (!this.activeDeployment) {
                return 'pending';
            }

            /*
             * Support several possible backend
             * pipeline structures.
             */
            const pipeline =
                this.activeDeployment.pipeline ||
                this.activeDeployment.steps ||
                this.activeDeployment.metadata?.pipeline ||
                {};

            const value =
                pipeline[step];

            if (typeof value === 'string') {
                return this.normalizeStepStatus(
                    value
                );
            }

            if (
                typeof value === 'object' &&
                value !== null
            ) {
                return this.normalizeStepStatus(
                    value.status || 'pending'
                );
            }

            /*
             * Fallback to current step metadata.
             */
            const currentStep =
                this.activeDeployment.current_step ||
                this.activeDeployment.current_stage ||
                this.activeDeployment.metadata?.current_step ||
                null;

            if (currentStep === step) {

                if (
                    this.activeDeployment.status ===
                    'failed'
                ) {
                    return 'failed';
                }

                if (
                    this.activeDeployment.status ===
                    'running'
                ) {
                    return 'running';
                }
            }

            /*
             * Don't falsely mark unknown steps
             * as completed.
             */
            return 'pending';
        },

        normalizeStepStatus(status) {
            status =
                String(status || '')
                    .toLowerCase();

            if (
                [
                    'success',
                    'successful',
                    'done',
                    'completed'
                ].includes(status)
            ) {
                return 'completed';
            }

            if (
                [
                    'running',
                    'processing',
                    'in_progress'
                ].includes(status)
            ) {
                return 'running';
            }

            if (
                [
                    'failed',
                    'failure',
                    'error'
                ].includes(status)
            ) {
                return 'failed';
            }

            return 'pending';
        },

        stepProgress(step) {
            const status =
                this.stepStatus(step);

            if (status === 'completed') {
                return 100;
            }

            if (status === 'failed') {
                return 100;
            }

            if (status === 'running') {
                /*
                 * The CSS animation represents an
                 * indeterminate running state.
                 */
                return 100;
            }

            return 0;
        },

        pipelineStepClass(step) {
            const status =
                this.stepStatus(step);

            if (status === 'running') {
                return 'border-blue-200 bg-blue-50/40';
            }

            if (status === 'completed') {
                return 'border-green-200 bg-green-50/30';
            }

            if (status === 'failed') {
                return 'border-red-200 bg-red-50/30';
            }

            return 'border-gray-100 bg-white';
        },

        pipelineIconClass(step) {
            const status =
                this.stepStatus(step);

            if (status === 'running') {
                return 'bg-blue-100 text-blue-600';
            }

            if (status === 'completed') {
                return 'bg-green-100 text-green-600';
            }

            if (status === 'failed') {
                return 'bg-red-100 text-red-600';
            }

            return 'bg-gray-100 text-gray-500';
        },

        pipelineBarClass(step) {
            const status =
                this.stepStatus(step);

            if (status === 'running') {
                return 'bg-blue-500 animate-pulse';
            }

            if (status === 'completed') {
                return 'bg-green-500';
            }

            if (status === 'failed') {
                return 'bg-red-500';
            }

            return 'bg-gray-200';
        },

        stepStatusTextClass(step) {
            const status =
                this.stepStatus(step);

            if (status === 'running') {
                return 'text-blue-600';
            }

            if (status === 'completed') {
                return 'text-green-600';
            }

            if (status === 'failed') {
                return 'text-red-600';
            }

            return 'text-gray-400';
        },

        /*
         * -----------------------------
         * FORMATTING
         * -----------------------------
         */

        formatStatus(status) {
            if (!status) {
                return 'Pending';
            }

            return String(status)
                .replace(/[_-]/g, ' ')
                .replace(
                    /\b\w/g,
                    char => char.toUpperCase()
                );
        },

        deploymentStatusClass(status) {
            switch (status) {

                case 'completed':
                    return 'bg-green-50 text-green-700';

                case 'failed':
                    return 'bg-red-50 text-red-700';

                case 'running':
                    return 'bg-blue-50 text-blue-700';

                case 'pending':
                    return 'bg-yellow-50 text-yellow-700';

                default:
                    return 'bg-gray-100 text-gray-700';
            }
        },

        formatDuration(seconds) {
            if (
                seconds === null ||
                seconds === undefined ||
                seconds === ''
            ) {
                return '—';
            }

            seconds = Number(seconds);

            if (seconds < 60) {
                return `${seconds}s`;
            }

            const minutes =
                Math.floor(seconds / 60);

            const remaining =
                seconds % 60;

            if (minutes < 60) {
                return `${minutes}m ${remaining}s`;
            }

            const hours =
                Math.floor(minutes / 60);

            const remainingMinutes =
                minutes % 60;

            return `${hours}h ${remainingMinutes}m`;
        },

        formatDate(value) {
            if (!value) {
                return '—';
            }

            const date =
                new Date(value);

            if (
                Number.isNaN(
                    date.getTime()
                )
            ) {
                return value;
            }

            return new Intl.DateTimeFormat(
                undefined,
                {
                    dateStyle: 'medium',
                    timeStyle: 'short'
                }
            ).format(date);
        },

        /*
         * -----------------------------
         * DEPLOYMENT FLOW
         * -----------------------------
         */

        openDeployModal() {
            if (this.deploymentRunning) {
                return;
            }

            this.showDeployModal = true;
        },

        closeDeployModal() {
            this.showDeployModal = false;
        },

        async beginDeployment() {
            this.closeDeployModal();

            await this.runPreflight();
        },

        /*
         * -----------------------------
         * PRE-FLIGHT
         * -----------------------------
         */

        async runPreflight() {
            this.preflightLoading = true;

            this.preflightResult = null;

            this.showPreflightModal = true;

            try {
                const response = await fetch(
                    '{{ url('/api/admin/operations/deployments/preflight') }}',
                    {
                        headers: {
                            'Accept':
                                'application/json'
                        }
                    }
                );

                const data =
                    await this.parseResponse(response);

                if (!data.success) {
                    throw new Error(
                        data.message ||
                        'Pre-flight check failed.'
                    );
                }

                this.preflightResult =
                    data.data || {};

            } catch (error) {
                console.error(
                    'Pre-flight check failed:',
                    error
                );

                this.preflightResult = {
                    success: false,
                    error:
                        this.errorMessage(error)
                };

            } finally {
                this.preflightLoading = false;
            }
        },

        closePreflightModal() {
            if (this.preflightLoading) {
                return;
            }

            this.showPreflightModal = false;
        },

        continueDeployment() {
            if (this.preflightLoading) {
                return;
            }

            const failed =
                this.preflightResult?.success === false ||
                this.preflightResult?.passed === false;

            if (failed) {

                this.showMessage(
                    'error',
                    'Pre-flight Check Failed',
                    this.preflightResult?.error ||
                    'The server is not ready for deployment.'
                );

                return;
            }

            this.closePreflightModal();

            this.showConfirmation(
                'Deploy to Staging?',

                `This will deploy branch "${this.deploymentConfig.branch || 'main'}" to ${this.deploymentConfig.environment || 'staging'}.`,

                () => this.createDeployment()
            );
        },

        /*
         * -----------------------------
         * CREATE DEPLOYMENT
         * -----------------------------
         */

        async createDeployment() {
            this.closeMessageModal();

            this.loading = true;

            this.reloadScheduled = false;

            try {
                const response = await fetch(
                    '{{ url('/api/admin/operations/deployments/create') }}',
                    {
                        method: 'POST',

                        headers: {
                            'Accept':
                                'application/json',

                            'Content-Type':
                                'application/json',

                            'X-CSRF-TOKEN':
                                '{{ csrf_token() }}'
                        },

                        body: JSON.stringify({
                            environment:
                                this.deploymentConfig.environment ||
                                'staging',

                            branch:
                                this.deploymentConfig.branch ||
                                'main'
                        })
                    }
                );

                const data =
                    await this.parseResponse(response);

                if (!data.success) {
                    throw new Error(
                        data.message ||
                        'Failed to create deployment.'
                    );
                }

                this.previousDeploymentStatus =
                    null;

                this.activeDeployment =
                    data.data || null;

                /*
                 * Refresh statistics immediately.
                 */
                await this.loadOverview();

                await this.loadHistory();

                this.showMessage(
                    'success',
                    'Deployment Started',
                    data.message ||
                    'The deployment has been queued successfully.'
                );

            } catch (error) {
                console.error(
                    'Failed to create deployment:',
                    error
                );

                this.showMessage(
                    'error',
                    'Deployment Failed',
                    this.errorMessage(error)
                );

            } finally {
                this.loading = false;
            }
        },

        /*
         * -----------------------------
         * DEPLOYMENT COMPLETION
         * -----------------------------
         */

        handleDeploymentFinished(deployment) {
            if (this.reloadScheduled) {
                return;
            }

            this.reloadScheduled = true;

            const success =
                deployment.status ===
                'completed';

            this.showMessage(
                success
                    ? 'success'
                    : 'error',

                success
                    ? 'Deployment Successful'
                    : 'Deployment Failed',

                success
                    ? `Deployment #${deployment.id} completed successfully.`
                    : (
                        deployment.error ||
                        `Deployment #${deployment.id} failed.`
                    ),

                {
                    confirmText: 'OK',
                    showCancel: false,
                    autoClose: false
                }
            );

            /*
             * Give the user a moment to see the
             * success/failure message before
             * refreshing the page.
             */
            setTimeout(() => {
                window.location.reload();
            }, 1800);
        },

        /*
         * -----------------------------
         * DETAILS
         * -----------------------------
         */

        async showDeploymentDetails(id) {
            this.selectedDeployment = null;

            this.showDetailsModal = true;

            try {
                const response = await fetch(
                    `{{ url('/api/admin/operations/deployments') }}/${id}`,
                    {
                        headers: {
                            'Accept':
                                'application/json'
                        }
                    }
                );

                const data =
                    await this.parseResponse(response);

                if (!data.success) {
                    throw new Error(
                        data.message ||
                        'Failed to load deployment.'
                    );
                }

                this.selectedDeployment =
                    data.data || null;

            } catch (error) {
                console.error(
                    'Failed to load deployment details:',
                    error
                );

                this.showDetailsModal = false;

                this.showMessage(
                    'error',
                    'Unable to Load Deployment',
                    this.errorMessage(error)
                );
            }
        },

        closeDetailsModal() {
            this.showDetailsModal = false;

            this.selectedDeployment = null;
        },

        /*
         * -----------------------------
         * CUSTOM MODALS
         * -----------------------------
         */

        showConfirmation(
            title,
            message,
            onConfirm
        ) {
            this.messageModal = {
                type: 'warning',

                title,

                message,

                confirmText: 'Deploy',

                cancelText: 'Cancel',

                showCancel: true,

                onConfirm
            };

            this.showMessageModal = true;
        },

        showMessage(
            type,
            title,
            message,
            options = {}
        ) {
            this.messageModal = {
                type,

                title,

                message,

                confirmText:
                    options.confirmText ||
                    'OK',

                cancelText:
                    options.cancelText ||
                    'Cancel',

                showCancel:
                    options.showCancel ??
                    false,

                onConfirm:
                    options.onConfirm ||
                    null
            };

            this.showMessageModal = true;

            if (options.autoClose === true) {
                setTimeout(() => {
                    this.closeMessageModal();
                }, options.delay || 2500);
            }
        },

        confirmMessage() {
            const callback =
                this.messageModal.onConfirm;

            this.closeMessageModal();

            if (
                typeof callback ===
                'function'
            ) {
                callback();
            }
        },

        closeMessageModal() {
            this.showMessageModal = false;
        },

        messageIcon() {
            switch (
                this.messageModal.type
            ) {
                case 'success':
                    return 'check-circle';

                case 'error':
                    return 'x-circle';

                case 'warning':
                    return 'alert-triangle';

                default:
                    return 'info';
            }
        },

        messageIconClass() {
            switch (
                this.messageModal.type
            ) {
                case 'success':
                    return 'bg-green-100 text-green-600';

                case 'error':
                    return 'bg-red-100 text-red-600';

                case 'warning':
                    return 'bg-yellow-100 text-yellow-600';

                default:
                    return 'bg-blue-100 text-blue-600';
            }
        },

        /*
         * -----------------------------
         * RESPONSE HANDLING
         * -----------------------------
         */

        async parseResponse(response) {
            const text =
                await response.text();

            let data;

            try {
                data = text
                    ? JSON.parse(text)
                    : {};

            } catch {
                throw new Error(
                    `Server returned an invalid response (${response.status}).`
                );
            }

            if (!response.ok) {
                throw new Error(
                    data.message ||
                    `Request failed with status ${response.status}.`
                );
            }

            return data;
        },

        errorMessage(error) {
            return error?.message ||
                'Something went wrong. Please try again.';
        }
    };
}
</script>