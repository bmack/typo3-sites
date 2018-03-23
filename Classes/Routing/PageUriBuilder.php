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
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Sites\Exception\SiteNotFoundException;
use TYPO3\CMS\Sites\Site\SiteFinder;

/**
 * Responsible for generates URLs to pages which are NOT bound to any permissions or frontend restrictions.
 *
 * If a page is built with a site in the root line, the base of the site (+ language) is used
 * and the &L parameter is then dropped explicitly.
 *
 * @todo: check handling of MP parameter.
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
     * @var SiteFinder
     */
    protected $siteFinder;

    /**
     * PageUriBuilder constructor.
     */
    public function __construct()
    {
        $this->siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
    }

    /**
     * Main entrypoint for generating an Uri for a page.
     *
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
        // If something is found, use /en/?id=123&additionalParams
        $languageId = (int)($options['language'] ?? $queryParameters['L'] ?? 0);
        $siteLanguage = null;
        try {
            $site = $this->siteFinder->getSiteByPageId($pageId, $options['rootLine'] ?? null);
            if ($site) {
                // Resolve language (based on the options / query parameters, and remove it from GET variables,
                // as the language is determined by the language path
                unset($queryParameters['L']);
                $siteLanguage = $site->getLanguageById($languageId);
            }
        } catch (SiteNotFoundException $e) {
        }
        // Only if a language is configured for the site, build a new site URL.
        if ($siteLanguage) {
            $uri = new Uri($siteLanguage->getBase() . '?id=' . $pageId . http_build_query($queryParameters, '', '&', PHP_QUERY_RFC3986));
        } else {
            $queryParameters['L'] = $languageId;
            // If nothing is found, use index.php?id=123&additionalParams
            $uri = $this->buildLegacyUri($pageId, $queryParameters);
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
     * Create a link to a page
     *
     * @param int $pageId
     * @param array $queryParameters
     * @return Uri
     */
    protected function buildLegacyUri(int $pageId, array $queryParameters): Uri
    {
        $query = http_build_query($queryParameters, '', '&', PHP_QUERY_RFC3986);
        return new Uri(GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'index.php?id=' . $pageId . $query);
    }
}