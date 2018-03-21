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

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generates pages to
 */
class PageUriBuilder
{
    /**
     * Generates an absolute URL
     */
    const ABSOLUTE_URL = 'url';

    /**
     * Generates an absolute path
     */
    const ABSOLUTE_PATH = 'path';

    /**
     * @var SiteReader
     */
    protected $siteReader;

    /**
     * PageUriBuilder constructor.
     */
    public function __construct()
    {
        $this->siteReader = GeneralUtility::makeInstance(SiteReader::class, Environment::getConfigPath() . '/sites');
    }

    /**
     * @param $pageId
     * @param $queryParameters
     * @param $options ['language' => 123, ]
     * @param string $referenceType
     * @return UriInterface
     */
    public function buildUri(int $pageId, array $queryParameters = [], $options = [], $referenceType = self::ABSOLUTE_PATH): UriInterface
    {
        // Resolve site
        $site = $this->getSiteForPage($pageId);
        // If something is found, use /en/?id=123&additionalParams
        if ($site) {
            // Resolve language (based on the options / query parameters
            $site->getLanguages();
        } else {
            // If nothing is found, use index.php?id=123&additionalParams
            return $this->buildLegacyUri($pageId, $queryParameters, $options);
        }
    }

    protected function buildLegacyUri(int $pageId, array $queryParameters, $options)
    {
        // Resolve the previewDomain if in BE
        // Use typolink functionality in FE
    }

    protected function getSiteForPage(int $pageId)
    {
        $fullRootLine = BackendUtility::BEgetRootline($pageId);
        foreach ($fullRootLine as $pageRecord) {
            $site = $this->siteReader->getSiteByRootPageId((int)$pageRecord['uid']);
            if ($site !== null) {
                return $site;
            }
        }
        return null;
    }
}