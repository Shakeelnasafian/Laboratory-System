<?php

return [
    'entries' => [
        [
            'type' => 'commit',
            'reference' => '1693846',
            'date' => 'March 06, 2026',
            'title' => 'Initial application skeleton',
            'summary' => 'The first commit established the Laravel and Livewire foundation of the laboratory system and introduced the core admin and lab modules.',
            'items' => [
                'Bootstrapped authentication, profile management, role and permission setup, and PDF reporting.',
                'Added core data models and migrations for labs, users, patients, tests, orders, results, and invoices.',
                'Created the first admin dashboard and lab dashboard flows with patients, orders, results, billing, settings, users, and test management screens.',
                'Added initial layouts, Blade components, route structure, and baseline automated auth tests.',
            ],
        ],
        [
            'type' => 'commit',
            'reference' => 'c98b62d',
            'date' => 'March 06, 2026',
            'title' => 'README expansion and event bug fix',
            'summary' => 'The second commit was a smaller cleanup pass focused on documentation and one behavioral fix in patient creation.',
            'items' => [
                'Expanded the README with fuller project setup and usage information.',
                'Removed the event-related issue from the frontend bootstrap flow in `resources/js/app.js`.',
                'Adjusted the lab patient create screen to handle the related bug fix.',
            ],
        ],
        [
            'type' => 'commit',
            'reference' => 'e22ee26',
            'date' => 'March 09, 2026',
            'title' => 'Workflow modules and release pipeline',
            'summary' => 'The third commit significantly expanded the operational scope of the product by adding sample handling, worklists, release queues, workflow services, and supporting schema.',
            'items' => [
                'Added collection, sample receiving, recollection, worklists, and release queue modules to the lab area.',
                'Introduced `Sample`, `ResultRevision`, and the `LabWorkflowService` to support workflow transitions and result history.',
                'Extended orders, order items, results, and reports to support due times, release states, and operational workflow data.',
                'Added workflow migrations, permission-manager resources, and dedicated workflow feature tests.',
            ],
        ],
        [
            'type' => 'workspace',
            'reference' => 'working tree',
            'date' => null, // resolved dynamically in ChangelogPage
            'title' => 'Current uncommitted feature rollout',
            'summary' => 'The current workspace adds demo-ready data, SPA shell improvements, sidebar redesign, responsive table fixes, and this changelog page itself.',
            'items' => [
                'Added demo lab and showcase seeders for laboratories, staff, patients, tests, orders, invoices, and result workflows.',
                'Converted internal navigation to Livewire SPA behavior and removed the Alpine duplication that was causing full-page reloads.',
                'Redesigned the admin and lab sidebars with mobile drawers, desktop mini mode, persisted state, and real SVG icons.',
                'Improved dashboard and table responsiveness across orders, samples, worklists, results, invoices, staff, and test catalog pages.',
                'Centralized custom UI color codes for workflow badges, action/link theme tokens, dashboard visuals, and PDF report styling in shared config.',
                'Added this in-app changelog page and linked it from both sidebars.',
            ],
        ],
        [
            'type' => 'commit',
            'reference' => 'single-screen',
            'date' => 'October 25, 2026',
            'title' => 'Orders for the front desk, Samples for the lab',
            'summary' => 'Orders now handle registration and money only; all lab work for an order happens on one sample page, so a single person can collect, enter results and print without switching screens.',
            'items' => [
                'New orders default to "Sample collected now" and open straight on the sample page.',
                'Samples is the lab work queue, with tabs per next step (collect, enter results, verify, release, completed) and a next-step button per sample.',
                'The sample page shows patient details, a progress bar, a "Collect sample" button and a results grid for every test, with High/Low flags suggested from the normal range.',
                '"Finalize report" saves, verifies and releases all results in one step; released reports can be amended and finalized again.',
                'The order page shows tests, prices, payment and cancellation, with "Receive balance" and a link to the sample.',
                'Dashboard moved to the top of the sidebar; the per-step collection, worklist, result and release screens moved under "Advanced workflow".',
            ],
        ],
    ],
];
