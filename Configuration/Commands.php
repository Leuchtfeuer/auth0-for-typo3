<?php
declare(strict_types = 1);

/*
 * TODO: Update this when dropping TYPO3 9 LTS support
 *
 * As the console commands configuration has been migrated to Symfony service tags, the console command conguration file
 * Configuration/Commands.php has been marked as deprecated.
 */

return [
    'auth0:cleanupusers' => [
        'class' => \Bitmotion\Auth0\Command\CleanUpCommand::class,
    ],
];
