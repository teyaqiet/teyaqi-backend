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

        /*
         * -----------------------------
         * DEPLOYMENT LOCK
         * -----------------------------
         */

        deploymentLock: {
            locked: false,
            lock: null
        },

        /*
         * -----------------------------
         * MODALS
         * -----------------------------
         */

        showDeployModal: false,
        showPreflightModal: false,
        showDetailsModal: false,
        showMessageModal: false,

        /*
         * Rollback preview modal.
         */
        showRollbackModal: false,

        /*
         * Rollback state.
         */
        rollbackLoading: false,
        rollbackCreating: false,
        rollbackPreview: null,
        rollbackDeployment: null,

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

        /*
         * -----------------------------
         * INITIALIZATION
         * -----------------------------
         */

        async init() {
            await this.refresh();

            this.startPolling();
        },

        async refresh() {
            this.loading = true;

            try {
                await Promise.all([
                    this.loadOverview(),
                    this.loadHistory(),
                    this.loadLockStatus()
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

            const data =
                await this.parseResponse(response);

            if (!data.success) {
                throw new Error(
                    data.message ||
                    'Failed to load deployment overview.'
                );
            }

            const result =
                data.data || {};

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

                const result =
                    data.data || {};

                const active =
                    result.running_deployment ||
                    result.active_deployment ||
                    result.active ||
                    null;

                if (!active) {
                    if (
                        this.activeDeployment &&
                        [
                            'pending',
                            'running'
                        ].includes(
                            this.activeDeployment.status
                        )
                    ) {
                        if (
                            !this.deploymentLock.locked
                        ) {
                            this.activeDeployment = null;
                        }
                    }

                    return;
                }

                const oldStatus =
                    this.activeDeployment?.status ||
                    null;

                this.activeDeployment =
                    active;

                if (
                    oldStatus &&
                    [
                        'pending',
                        'running'
                    ].includes(oldStatus) &&
                    [
                        'completed',
                        'failed'
                    ].includes(active.status)
                ) {
                    if (
                        active.type === 'rollback'
                    ) {
                        this.handleRollbackFinished(
                            active
                        );
                    } else {
                        this.handleDeploymentFinished(
                            active
                        );
                    }
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
         * DEPLOYMENT LOCK
         * -----------------------------
         */

        async loadLockStatus() {
            try {
                const environment =
                    this.deploymentConfig.environment ||
                    'staging';

                const response = await fetch(
                    `{{ url('/api/admin/operations/deployments/lock-status') }}?environment=${encodeURIComponent(environment)}`,
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

                this.deploymentLock = {
                    locked:
                        data.data?.locked === true,

                    lock:
                        data.data?.lock || null
                };

            } catch (error) {
                console.error(
                    'Failed to load deployment lock:',
                    error
                );
            }
        },

        deploymentLockMessage() {
            const lock =
                this.deploymentLock.lock;

            if (!lock) {
                return '';
            }

            if (lock.deployment_id) {
                return `Deployment #${lock.deployment_id} is currently running in ${lock.environment || 'staging'}.`;
            }

            return `A deployment is currently running in ${lock.environment || 'staging'}.`;
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

                    await this.loadLockStatus();

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
            ) || this.deploymentLock.locked;
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

            if (
                [
                    'skipped',
                    'disabled'
                ].includes(status)
            ) {
                return 'skipped';
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

            if (status === 'skipped') {
                return 'border-gray-200 bg-gray-50';
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

            if (status === 'skipped') {
                return 'bg-gray-100 text-gray-400';
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

            if (status === 'skipped') {
                return 'bg-gray-300';
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

            if (status === 'skipped') {
                return 'text-gray-400';
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

                this.showMessage(
                    'warning',
                    'Deployment Already Running',
                    this.deploymentLockMessage() ||
                    'Another deployment is currently running.'
                );

                return;
            }

            this.showDeployModal = true;
        },

        closeDeployModal() {
            this.showDeployModal = false;
        },

        async beginDeployment() {
            this.closeDeployModal();

            await this.loadLockStatus();

            if (this.deploymentRunning) {

                this.showMessage(
                    'warning',
                    'Deployment Already Running',
                    this.deploymentLockMessage() ||
                    'Another deployment is currently running.'
                );

                return;
            }

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

            if (this.deploymentRunning) {

                this.closePreflightModal();

                this.showMessage(
                    'warning',
                    'Deployment Already Running',
                    this.deploymentLockMessage() ||
                    'Another deployment is currently running.'
                );

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

            await this.loadLockStatus();

            if (this.deploymentRunning) {

                this.showMessage(
                    'warning',
                    'Deployment Already Running',
                    this.deploymentLockMessage() ||
                    'Another deployment is currently running.'
                );

                return;
            }

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

                await this.loadOverview();

                await this.loadHistory();

                await this.loadLockStatus();

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

                await this.loadLockStatus();

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

            setTimeout(() => {
                window.location.reload();
            }, 1800);
        },

        /*
         * -----------------------------
         * ROLLBACK
         * -----------------------------
         */

        async openRollbackPreview(id) {
            /*
             * Close the deployment details modal
             * while showing the rollback preview.
             */
            this.showRollbackModal = true;

            this.rollbackLoading = true;

            this.rollbackPreview = null;

            this.rollbackDeployment = null;

            try {
                await this.loadLockStatus();

                /*
                 * Never allow rollback while another
                 * deployment or rollback is running.
                 */
                if (this.deploymentRunning) {
                    this.showRollbackModal = false;

                    this.showMessage(
                        'warning',
                        'Operation Already Running',
                        this.deploymentLockMessage() ||
                        'Another deployment or rollback is currently running.'
                    );

                    return;
                }

                const response = await fetch(
                    `{{ url('/api/admin/operations/rollbacks') }}/${id}/preview`,
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
                        'Unable to prepare rollback preview.'
                    );
                }

                this.rollbackDeployment = {
                    id
                };

                this.rollbackPreview =
                    data.data || {};

            } catch (error) {
                console.error(
                    'Rollback preview failed:',
                    error
                );

                this.showRollbackModal = false;

                this.showMessage(
                    'error',
                    'Rollback Preview Failed',
                    this.errorMessage(error)
                );

            } finally {
                this.rollbackLoading = false;
            }
        },

        closeRollbackModal() {
            if (this.rollbackCreating) {
                return;
            }

            this.showRollbackModal = false;

            this.rollbackPreview = null;

            this.rollbackDeployment = null;
        },

        rollbackCanProceed() {
            const preview =
                this.rollbackPreview;

            if (!preview) {
                return false;
            }

            if (preview.locked) {
                return false;
            }

            if (!preview.target_commit) {
                return false;
            }

            if (
                preview.current_commit ===
                preview.target_commit
            ) {
                return false;
            }

            return true;
        },

        confirmRollback() {
            if (
                !this.rollbackCanProceed()
            ) {
                return;
            }

            const preview =
                this.rollbackPreview;

            this.showRollbackModal = false;

            this.showConfirmation(
                'Rollback Deployment?',

                `Deployment #${preview.deployment_id} will be reverted from ${preview.current_commit_short || 'the current commit'} to ${preview.target_commit_short || 'the previous commit'}. This will change the application code and run the deployment pipeline again.`,

                () => this.createRollback(
                    preview.deployment_id
                )
            );
        },

        async createRollback(id) {
            this.closeMessageModal();

            await this.loadLockStatus();

            if (this.deploymentRunning) {

                this.showMessage(
                    'warning',
                    'Operation Already Running',
                    this.deploymentLockMessage() ||
                    'Another deployment or rollback is currently running.'
                );

                return;
            }

            this.rollbackCreating = true;

            this.reloadScheduled = false;

            try {
                const response = await fetch(
                    `{{ url('/api/admin/operations/rollbacks') }}/${id}/create`,
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

                        body: JSON.stringify({})
                    }
                );

                const data =
                    await this.parseResponse(response);

                if (!data.success) {
                    throw new Error(
                        data.message ||
                        'Failed to create rollback.'
                    );
                }

                /*
                 * The rollback itself becomes the
                 * active operation.
                 */
                this.activeDeployment =
                    data.data || null;

                this.previousDeploymentStatus =
                    null;

                await this.loadOverview();

                await this.loadHistory();

                await this.loadLockStatus();

                this.showMessage(
                    'success',
                    'Rollback Started',
                    data.message ||
                    'The rollback has been queued successfully.'
                );

            } catch (error) {
                console.error(
                    'Failed to create rollback:',
                    error
                );

                await this.loadLockStatus();

                this.showMessage(
                    'error',
                    'Rollback Failed',
                    this.errorMessage(error)
                );

            } finally {
                this.rollbackCreating = false;
            }
        },

        handleRollbackFinished(rollback) {
            if (this.reloadScheduled) {
                return;
            }

            this.reloadScheduled = true;

            const success =
                rollback.status ===
                'completed';

            this.showMessage(
                success
                    ? 'success'
                    : 'error',

                success
                    ? 'Rollback Successful'
                    : 'Rollback Failed',

                success
                    ? `Rollback #${rollback.id} completed successfully. The application has been restored to ${this.shortCommit(rollback.commit_hash)}.`
                    : (
                        rollback.error ||
                        `Rollback #${rollback.id} failed.`
                    ),

                {
                    confirmText: 'OK',
                    showCancel: false,
                    autoClose: false
                }
            );

            setTimeout(() => {
                window.location.reload();
            }, 1800);
        },

        shortCommit(commit) {
            if (!commit) {
                return '—';
            }

            return String(commit).substring(
                0,
                8
            );
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

                confirmText: 'Confirm',

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
