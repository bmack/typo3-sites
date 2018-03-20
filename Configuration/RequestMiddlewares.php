<?php
/**
 * Add a new middleware to resolve a site during frontend request processing
 */
return [
    'frontend' => [
        'typo3/cms-core/site' => [
            'target' => \TYPO3\CMS\Sites\Middleware\SiteResolver::class,
            'after' => [
                'typo3/cms-core/normalized-params-attribute',
                'typo3/cms-frontend/tsfe',
                'typo3/cms-frontend/authentication',
                'typo3/cms-frontend/backend-user-authentication',
            ],
            'before' => [
                'typo3/cms-frontend/page-resolver'
            ]
        ],
    ]
];
