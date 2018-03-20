<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Sites\Configuration;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Sites\SiteConfigurationNotFoundException;

/**
 * @todo: is this the right place?
 */
class SiteConfiguration
{
    /**
     * @return array
     */
    public function getAllSites(): array
    {
        return $this->resolveAllExistingConfigurations();
    }

    public function getByPageUid(int $rootPageId): array
    {
        $allSites = $this->resolveAllExistingConfigurations();
        foreach ($allSites as $siteIdentifier => $siteDetails) {
            if ($siteDetails['rootPageId'] === $rootPageId) {
                return $siteDetails;
            }
        }
        throw new SiteConfigurationNotFoundException(
            'No site configuration for root page uid ' . $rootPageId . ' found.',
            1520884750
        );
    }

    protected function resolveAllExistingConfigurations(): array
    {
        $sites = [];
        $finder = new Finder();
        $finder->files()->depth(0)->name('config.yaml')->in(PATH_typo3conf . 'sites/*');
        $loader = GeneralUtility::makeInstance(YamlFileLoader::class);
        foreach ($finder as $fileInfo) {
            $configuration = $loader->load(str_replace('\\', '/', (string)$fileInfo));
            // Make sub array count from 1 instead of 0 to have "valid uid's" for inline references
            foreach ($configuration['site'] as $fieldName => $fieldValue) {
                if (is_array($fieldValue)) {
                    \array_unshift($configuration['site'][$fieldName], [0 => 0]);
                    unset($configuration['site'][$fieldName][0]);
                }
            }
            $siteIdentifier = basename($fileInfo->getPath());
            $configuration['site']['siteIdentifier'] = $siteIdentifier;
            if (empty($configuration['site']['rootPageId'])) {
                throw new \RuntimeException(
                    'Invalid site configuration found, rootPageId must be set for identifier ' . $siteIdentifier,
                    1521569721
                );
            }
            $sites[$siteIdentifier] = $configuration['site'];
        }
        return $sites;
    }
}
