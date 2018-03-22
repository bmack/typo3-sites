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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Sites\Exception\SiteNotFoundException;

/**
 * Reads all available site configuration options, and puts them into Site objects.
 *
 * Is used for all places where to read / identify sites.
 */
class SiteReader
{
    /**
     * @var Site[]
     */
    protected $sites;

    /**
     * short-hand to quickly fetch a site based on a rootPageId
     * @var array
     */
    protected $mappingRootPageIdToIdentifier = [];

    /**
     * @param string $configurationPath
     */
    public function __construct(string $configurationPath)
    {
        $finder = new Finder();
        $finder->files()->depth(0)->name('config.yaml')->in($configurationPath . '/*');
        $loader = GeneralUtility::makeInstance(YamlFileLoader::class);
        $languageRecords = $this->getAllLanguageRecords();
        foreach ($finder as $fileInfo) {
            $configuration = $loader->load((string)$fileInfo);
            $identifier = basename($fileInfo->getPath());
            $rootPageId = (int)$configuration['site']['rootPageId'] ?? $configuration['site']['rootpageId'];
            $this->sites[$identifier] = new Site($identifier, $rootPageId, $configuration['site'], $languageRecords);
            $this->mappingRootPageIdToIdentifier[$rootPageId] = $identifier;
        }
    }

    /**
     * @return Site[]
     */
    public function getAllSites(): array
    {
        return $this->sites;
    }

    public function getBaseUris()
    {
        $baseUrls = [];
        foreach ($this->sites as $site) {
            /** @var SiteLanguage $language */
            foreach ($site->getLanguages() as $language) {
                $baseUrls[$language->getBase()] = $language;
                if ($language->getLanguageId() === 0) {
                    $baseUrls[$site->getBase()] = $language;
                }
            }
        }
        return $baseUrls;
    }

    /**
     * Find a site by given root page id
     *
     * @param int $rootPageId
     * @return Site
     * @throws SiteNotFoundException
     */
    public function getSiteByRootPageId(int $rootPageId): Site
    {
        if (isset($this->mappingRootPageIdToIdentifier[$rootPageId])) {
            return $this->sites[$this->mappingRootPageIdToIdentifier[$rootPageId]];
        }
        throw new SiteNotFoundException('No site found for root page id ' . $rootPageId, 1521668882);
    }

    public function getSiteLanguageByBase(string $uri)
    {
        $baseUris = $this->getBaseUris();
        $bestMatchedUri = null;
        foreach ($baseUris as $base => $language) {
            if (strpos($uri, $base) === 0 && strlen($bestMatchedUri ?? '') < strlen($base)) {
                $bestMatchedUri = $base;
            }
        }
        return $baseUris[$bestMatchedUri] ?? null;
    }

    /**
     * Find a site by given identifier
     *
     * @param string $identifier
     * @return Site
     * @throws SiteNotFoundException
     */
    public function getSiteByIdentifier(string $identifier): Site
    {
        if (isset($this->sites[$identifier])) {
            return $this->sites[$identifier];
        }
        throw new SiteNotFoundException('No site found for identifier ' . $identifier, 1521716628);
    }

    /**
     *
     */
    protected function getAllLanguageRecords(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_language');
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder->select('*')->from('sys_language')->orderBy('sorting')->execute();
        $languageRecords = [];
        while ($row = $statement->fetch()) {
            $languageRecords[(int)$row['uid']] = $row;
        }
        return $languageRecords;
    }

}