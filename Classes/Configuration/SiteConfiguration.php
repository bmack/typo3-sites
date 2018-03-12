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
        foreach ($allSites as $site => $siteDetails) {
            if ($siteDetails['rootPageId'] === $rootPageId) {
                return $siteDetails;
            }
        }
        throw new \RuntimeException(
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
            $configuration = $loader->load((string)$fileInfo);
            // Make sub array count from 1 instead of 0 to have "valid uid's" for inline references
            foreach ($configuration['site'] as $fieldName => $fieldValue) {
                if (is_array($fieldValue)) {
                    $newArray = [ 0 => 0 ];
                    foreach ($fieldValue as $subField) {
                        $newArray[] = $subField;
                    }
                    unset($newArray[0]);
                    $configuration['site'][$fieldName] = $newArray;
                }
            }
            $identifier = basename($fileInfo->getPath());
            $sites[$identifier] = $configuration['site'];
        }
        return $sites;
    }
}
