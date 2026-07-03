<?php
declare(strict_types=1);

use a9f\Fractor\Configuration\FractorConfiguration;
use a9f\FractorComposerJson\ChangePackageVersionComposerJsonFractor;
use a9f\FractorComposerJson\ValueObject\PackageAndVersion;
use a9f\Typo3Fractor\Set\Typo3LevelSetList;

return FractorConfiguration::configure()
    ->withPaths([
        __DIR__ . '/Configuration',
        __DIR__ . '/Resources',
        __DIR__ . '/composer.json',
    ])
    ->withSkip([
        __DIR__ . '/.Build',
    ])
    ->withSets([
        Typo3LevelSetList::UP_TO_TYPO3_14,
    ])
    ->withConfiguredRule(
        ChangePackageVersionComposerJsonFractor::class,
        [
            new PackageAndVersion('typo3/cms-core', '^14.0'),
            new PackageAndVersion('typo3/cms-form', '^14.0'),
        ]
    );