<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Sites\Site;

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
use TYPO3\CMS\Sites\Site\Entity\Site;

/**
 * Responsibility: Handles the format of the configuration (currently yaml), and the location of the file system folder
 * Reads all available site configuration options, and puts them into Site objects.
 *
 * @internal
 */
class SiteConfigurationManager
{
    /**
     * @var string
     */
    protected $configPath;

    /**
     * @param string $configPath
     */
    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    /**
     * return all site objects which have been found in the FS.
     *
     * @return Site[]
     */
    public function resolveAllExistingSites(): array
    {
        $finder = new Finder();
        $finder->files()->depth(0)->name('config.yaml')->in($this->configPath . '/*');
        $loader = GeneralUtility::makeInstance(YamlFileLoader::class);
        $sites = [];
        foreach ($finder as $fileInfo) {
            $configuration = $loader->load((string)$fileInfo);
            $identifier = basename($fileInfo->getPath());
            $rootPageId = (int)$configuration['site']['rootPageId'] ?? $configuration['site']['rootpageId'];
            $sites[$identifier] = new Site($identifier, $rootPageId, $configuration['site']);
        }
        return $sites;
    }
}