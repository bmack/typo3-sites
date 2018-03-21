<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Sites\Routing;

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
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Sites\Site\SiteReader;

/**
 * Generates links to pages
 *
 * @todo: MP
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
     * @param int $pageId
     * @param array $queryParameters
     * @param string $fragment
     * @param array $options ['language' => 123, 'rootLine' => etc.]
     * @param string $referenceType
     * @return UriInterface
     */
    public function buildUri(int $pageId, array $queryParameters = [], string $fragment = null, array $options = [], $referenceType = self::ABSOLUTE_PATH): UriInterface
    {
        // Resolve site
        $site = $this->getSiteForPage($pageId, $options['rootLine'] ?? null);
        // If something is found, use /en/?id=123&additionalParams
        if ($site) {
            $languageId = (int)($options['language'] ?? $queryParameters['L'] ?? 0);
            unset($queryParameters['L']);
            // Resolve language (based on the options / query parameters
            $siteLanguage = $site->getLanguageById($languageId);
            $uri = new Uri($siteLanguage->getBase() . '?id=' . $pageId . http_build_query($queryParameters, '', '&', PHP_QUERY_RFC3986));
        } else {
            // If nothing is found, use index.php?id=123&additionalParams
            $uri = $this->buildLegacyUri($pageId, $queryParameters, $options);
        }
        if ($fragment) {
            $uri = $uri->withFragment($fragment);
        }
        if ($referenceType === self::ABSOLUTE_PATH) {
            $uri = $uri->withScheme('')->withHost('')->withPort(null);
        }
        return $uri;
    }

    /**
     * @param int $pageId
     * @param array $queryParameters
     * @param array $options
     * @return Uri
     */
    protected function buildLegacyUri(int $pageId, array $queryParameters, array $options): Uri
    {
        $query = http_build_query($queryParameters, '', '&', PHP_QUERY_RFC3986);
        return new Uri(GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'index.php?id=' . $pageId . $query);
    }

    /**
     * @param int $pageId
     * @param array|null $fullRootLine
     * @return null|\TYPO3\CMS\Sites\Site\Site
     */
    protected function getSiteForPage(int $pageId, array $fullRootLine = null)
    {
        $fullRootLine = $fullRootLine !== null ? $fullRootLine : BackendUtility::BEgetRootline($pageId);
        foreach ($fullRootLine as $pageRecord) {
            $site = $this->siteReader->getSiteByRootPageId((int)$pageRecord['uid']);
            if ($site !== null) {
                return $site;
            }
        }
        return null;
    }
}