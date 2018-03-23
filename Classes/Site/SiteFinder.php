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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Sites\Exception\SiteNotFoundException;
use TYPO3\CMS\Sites\Site\Entity\Site;
use TYPO3\CMS\Sites\Site\Entity\SiteLanguage;

/**
 * Is used for all places where to read / identify sites and site languages.
 */
class SiteFinder
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
     */
    public function __construct()
    {
        $reader = GeneralUtility::makeInstance(SiteConfigurationManager::class, Environment::getConfigPath() . '/sites');
        $siteConfigurations = $reader->resolveAllExistingSites();
        foreach ($siteConfigurations as $identifier => $site) {
            $this->sites[$identifier] = $site;
            $this->mappingRootPageIdToIdentifier[$site->getRootPageId()] = $identifier;
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
}