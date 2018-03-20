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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Sites\Site\SiteReader;

/**
 * Identify the current request and resolve the site to it.
 * After that middleware, TSFE should be populated with
 * - language configuration
 * - site configuration
 */
class SiteResolver implements MiddlewareInterface
{
    /**
     *
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $reader = GeneralUtility::makeInstance(SiteReader::class, Environment::getConfigPath() . '/sites');
        // 1. Check if we have a _GET/_POST parameter for "id"
        // 2. Check if there is a site, if not, just don't do anything
        // First resolve the site
        $uri = $request->getUri();
        $language = $reader->getSiteLanguageByBase((string)$uri);
        if ($language) {
            $site = $language->getSite();
            $request = $request->withAttribute('site', $site);
            $request = $request->withAttribute('language', $language);
            // At this point, we later get further route modifiers
            // for bw-compat we update GLOBALS[TYPO3_REQUEST] and define stuff in TSFE.
            $GLOBALS['SERVER_REQUEST'] = $request;
        }
        return $handler->handle($request);
    }
}
