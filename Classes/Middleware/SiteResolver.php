<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Sites\Middleware;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Sites\Exception\SiteNotFoundException;
use TYPO3\CMS\Sites\Site\Entity\Site;
use TYPO3\CMS\Sites\Site\SiteFinder;
use TYPO3\CMS\Sites\Site\Entity\SiteLanguage;

/**
 * Identify the current request and resolve the site to it.
 * After that middleware, TSFE should be populated with
 * - language configuration
 * - site configuration
 *
 * Properties like config.sys_language_uid and config.language is then set for TypoScript.
 */
class SiteResolver implements MiddlewareInterface
{
    /**
     * Resolve the site/language information by checking the page ID or the URL.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $finder = GeneralUtility::makeInstance(SiteFinder::class);

        $site = null;
        $language = null;

        $pageId = $request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0;
        $languageId = $request->getQueryParams()['L'] ?? $request->getParsedBody()['L'] ?? null;

        // 1. Check if we have a _GET/_POST parameter for "id", then a site information can be resolved based.
        if ($pageId && $languageId) {
            // Loop over the whole rootline without permissions to get the actual site information
            try {
                $site = $finder->getSiteByPageId((int)$pageId);
                $language = $site->getLanguageById($languageId);
            } catch (SiteNotFoundException $e) {
            }
        } else {
            // 2. Check if there is a site language, if not, just don't do anything
            $language = $finder->getSiteLanguageByBase((string)$request->getUri());
            // @todo: use exception for getSiteLanguageByBase
            if ($language) {
                $site = $language->getSite();
            }
        }

        // Add language+site information to the PSR-7 request object.
        if ($language instanceof SiteLanguage && $site instanceof Site) {
            $request = $request->withAttribute('site', $site);
            $request = $request->withAttribute('language', $language);
            $queryParams = $request->getQueryParams();
            // necessary to calculate the proper hash base
            $queryParams['L'] = $language->getLanguageId();
            $request->withQueryParams($queryParams);
            $_GET['L'] = $queryParams['L'];
            // At this point, we later get further route modifiers
            // for bw-compat we update $GLOBALS[TYPO3_REQUEST] and define stuff in TSFE.
            $GLOBALS['TYPO3_REQUEST'] = $request;

            // Yes, hook into TSFE after TypoScript is parsed, baby.
            // Ensure that TYPO3 can deal with /en/ but keeps the original behaviour for deep links.
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc'][] = function($param) use ($language) {
                $param['config']['absRefPrefix'] = 'auto';
                $param['config']['language'] = $language->getTypo3Language();
                $param['config']['sys_language_uid'] = $language->getLanguageId();
                $param['config']['sys_language_mode'] = $language->getFallbackType();
            };
            // Ensure the language base is used for the hash base calculation as well, otherwise TypoScript and page-related rendering
            // is not cached properly as we don't have any language-specific conditions anymore
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['createHashBase'][] = function($params) use ($language) {
                $params['hashParameters']['sitebase'] = $language->getBase();
            };
        }
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['linkData-PostProc'][] = function($params) use ($request, $finder) {
            $pageId = (int)$params['args']['page']['uid'];
            try {
                $currentHost = $request->getUri()->getHost();
                $site = $finder->getSiteByPageId($pageId);
                $targetLanguageId = $GLOBALS['TSFE']->sys_language_uid;
                $targetUri = new Uri($params['LD']['totalURL']);
                if ($targetUri->getQuery()) {
                    $targetQueryParts = GeneralUtility::explodeUrl2Array($targetUri->getQuery(), true);
                    $targetLanguageId = (int)($targetQueryParts['L'] ?? $targetLanguageId);
                    $targetQueryParts['L'] = $targetLanguageId;
                    $targetUri = $targetUri->withQuery(http_build_query($targetQueryParts, '', '&', PHP_QUERY_RFC3986));
                    // todo: check the params, and exchange the base
                    // but take the relative path into account
                    #$targetSiteLanguage = $site->getLanguageById($targetLanguageId);
                    #$targetBase = $targetSiteLanguage->getBase();
                }
                $params['LD']['totalURL'] = (string)$targetUri;
            } catch (SiteNotFoundException $e) {
            }
        };
        return $handler->handle($request);
    }
}
