<?php

return [

    'main' => [

        /*
        |--------------------------------------------------------------------------
        | DASHBOARD
        |--------------------------------------------------------------------------
        */

        [
            'label' => 'Dashboard',
            'icon' => 'ik ik-home',
            'route' => 'admin.dashboard',
            'active' => 'admin',
            'children' => [

                [
                    'label' => 'Overview',
                    'icon' => 'ik ik-home',
                    'route' => 'admin.dashboard',
                    'active' => 'admin',
                ],

                [
                    'label' => 'Analytics',
                    'icon' => 'ik ik-trending-up',
                    'route' => 'admin.analytics.index',
                    'active' => 'admin/analytics*',
                ],

            ],
        ],


        /*
        |--------------------------------------------------------------------------
        | GAME
        |--------------------------------------------------------------------------
        */

        [
            'heading' => 'Game',
        ],

        [
            'label' => 'Game Sessions',
            'icon' => 'ik ik-play-circle',
            'route' => 'admin.game-sessions.index',
            'active' => 'admin/game-sessions*',
        ],

        [
            'label' => 'Challenges',
            'icon' => 'ik ik-target',
            'route' => 'admin.challenges.index',
            'active' => 'admin/challenges*',
        ],

        [
            'label' => 'Questions',
            'icon' => 'ik ik-help-circle',
            'route' => 'admin.questions.index',
            'active' => 'admin/questions*',
        ],

        [
            'label' => 'Categories',
            'icon' => 'ik ik-grid',
            'route' => 'admin.categories.index',
            'active' => 'admin/categories*',
        ],

        [
            'label' => 'Topics',
            'icon' => 'ik ik-layers',
            'route' => 'admin.topics.index',
            'active' => 'admin/topics*',
        ],


        /*
        |--------------------------------------------------------------------------
        | PLAYERS
        |--------------------------------------------------------------------------
        */

        [
            'heading' => 'Players',
        ],

        [
            'label' => 'All Players',
            'icon' => 'ik ik-users',
            'route' => 'admin.users.index',
            'active' => 'admin/users*',
        ],


        /*
        |--------------------------------------------------------------------------
        | AUTOMATION & COMMUNICATION
        |--------------------------------------------------------------------------
        */

        [
            'heading' => 'Automation',
        ],


        /*
        | Automations
        */

        [
            'label' => 'Automations',
            'icon' => 'ik ik-cpu',
            'route' => 'admin.automations.index',
            'active' => 'admin/automations*',
            'children' => [

                [
                    'label' => 'All Automations',
                    'icon' => 'ik ik-list',
                    'route' => 'admin.automations.index',
                    'active' => 'admin/automations',
                ],

                [
                    'label' => 'Create Automation',
                    'icon' => 'ik ik-plus-circle',
                    'route' => 'admin.automations.create',
                    'active' => 'admin/automations/create',
                ],

            ],
        ],


        /*
        | Broadcasts
        */

        [
            'label' => 'Broadcasts',
            'icon' => 'ik ik-send',
            'route' => 'admin.broadcasts.index',
            'active' => 'admin/broadcasts*',
            'children' => [

                [
                    'label' => 'All Broadcasts',
                    'icon' => 'ik ik-list',
                    'route' => 'admin.broadcasts.index',
                    'active' => 'admin/broadcasts',
                ],

                [
                    'label' => 'Create Broadcast',
                    'icon' => 'ik ik-plus-circle',
                    'route' => 'admin.broadcasts.create',
                    'active' => 'admin/broadcasts/create',
                ],

            ],
        ],


        /*
        |--------------------------------------------------------------------------
        | OPERATIONS
        |--------------------------------------------------------------------------
        */

        [
            'heading' => 'Operations',
        ],


        [
            'label' => 'Operations Center',
            'icon' => 'ik ik-cpu',
            'route' => 'admin.operations.index',
            'active' => 'admin/operations*',
            'children' => [

                /*
                | Overview
                */

                [
                    'label' => 'Overview',
                    'icon' => 'ik ik-home',
                    'route' => 'admin.operations.index',
                    'active' => 'admin/operations',
                ],


                /*
                | System Information
                */

                [
                    'label' => 'System Information',
                    'icon' => 'ik ik-monitor',
                    'route' => 'admin.operations.system',
                    'active' => 'admin/operations/system',
                ],


                /*
                | Deployments
                */

                [
                    'label' => 'Deployments',
                    'icon' => 'ik ik-upload-cloud',
                    'route' => 'admin.operations.deployments',
                    'active' => 'admin/operations/deployments*',
                ],


                /*
                | Queue Monitor
                */

                [
                    'label' => 'Queue Monitor',
                    'icon' => 'ik ik-layers',
                    'route' => 'admin.operations.queue',
                    'active' => 'admin/operations/queue*',
                ],


                /*
                | System Logs
                */

                [
                    'label' => 'System Logs',
                    'icon' => 'ik ik-file-text',
                    'route' => 'admin.operations.logs',
                    'active' => 'admin/operations/logs*',
                ],


                /*
                | Audit Logs
                */

                [
                    'label' => 'Audit Logs',
                    'icon' => 'ik ik-activity',
                    'route' => 'admin.operations.audit-logs',
                    'active' => 'admin/operations/audit-logs*',
                ],


                /*
                | Database
                */

                [
                    'label' => 'Database',
                    'icon' => 'ik ik-database',
                    'route' => 'admin.operations.database',
                    'active' => 'admin/operations/database*',
                ],


                /*
                | Backups
                */

                [
                    'label' => 'Backups',
                    'icon' => 'ik ik-archive',
                    'route' => 'admin.operations.backups',
                    'active' => 'admin/operations/backups*',
                ],


                /*
                | Cache
                */

                [
                    'label' => 'Cache',
                    'icon' => 'ik ik-zap',
                    'route' => 'admin.operations.cache',
                    'active' => 'admin/operations/cache*',
                ],


                /*
                | Processes
                */

                [
                    'label' => 'Processes',
                    'icon' => 'ik ik-list',
                    'route' => 'admin.operations.processes',
                    'active' => 'admin/operations/processes*',
                ],

                 /*
                | Alerts
                */

                [
                    'label' => 'Alerts',
                    'icon' => 'ik ik-bell',
                    'route' => 'admin.operations.alerts',
                    'active' => 'admin/operations/alerts*',
                ],

                

            ],
        ],


        /*
        |--------------------------------------------------------------------------
        | SYSTEM
        |--------------------------------------------------------------------------
        */

        [
            'heading' => 'System',
        ],


        /*
        | My Profile
        */

        [
            'label' => 'My Profile',
            'icon' => 'ik ik-user',
            'route' => 'admin.profile.index',
            'active' => 'admin/profile*',
        ],


        /*
        | System Administration
        */

        [
            'label' => 'Administration',
            'icon' => 'ik ik-settings',
            'route' => 'admin.admin-users.index',
            'active' => 'admin/(admin-users|roles|permissions|activity-logs|system/api-logs)*',
            'children' => [

                [
                    'label' => 'Admin Users',
                    'icon' => 'ik ik-users',
                    'route' => 'admin.admin-users.index',
                    'active' => 'admin/admin-users*',
                ],

                [
                    'label' => 'Roles',
                    'icon' => 'ik ik-shield',
                    'route' => 'admin.roles.index',
                    'active' => 'admin/roles*',
                ],

                [
                    'label' => 'Permissions',
                    'icon' => 'ik ik-lock',
                    'route' => 'admin.permissions.index',
                    'active' => 'admin/permissions*',
                ],

                [
                    'label' => 'Activity Logs',
                    'icon' => 'ik ik-activity',
                    'route' => 'admin.activity-logs.index',
                    'active' => 'admin/activity-logs*',
                ],

                [
                    'label' => 'API Logs',
                    'icon' => 'ik ik-code',
                    'route' => 'admin.system.api-logs.index',
                    'active' => 'admin/system/api-logs*',
                ],

            ],
        ],


        /*
        | Settings
        */

        [
            'label' => 'Settings',
            'icon' => 'ik ik-sliders',
            'route' => 'admin.settings.index',
            'active' => 'admin/settings*',
        ],

    ],

];