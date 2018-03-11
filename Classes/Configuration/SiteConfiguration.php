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
    protected $sites = [];

    /**
     * @todo should be an opbject
     * @return array
     */
    public function getAllSites(): array
    {
        $this->resolveAllExistingConfigurations();
        return $this->sites;
    }

    protected function resolveAllExistingConfigurations()
    {
        $finder = new Finder();
        $finder->files()->depth(0)->name('config.yaml')->in(PATH_typo3conf . 'sites/*');
        $loader = GeneralUtility::makeInstance(YamlFileLoader::class);
        foreach ($finder as $fileInfo) {
            $configuration = $loader->load((string)$fileInfo);
            $identifier = basename($fileInfo->getPath());
            $this->sites[$identifier] = $configuration['site'];
        }
    }
}
