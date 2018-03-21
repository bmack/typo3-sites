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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Sites\Site\SiteReader;

class BackendUriGenerationHook implements SingletonInterface
{
    /**
     * @param $previewUrl
     * @param $pageUid
     * @param $rootLine
     * @param $anchorSection
     * @param $viewScript
     * @param $additionalGetVars
     * @param $switchFocus
     * @return string
     */
    public function postProcess($previewUrl, $pageUid, $rootLine, $anchorSection, $viewScript, $additionalGetVars, $switchFocus): string
    {
        // Create a multi-dimensional array out of the additional get vars
        $additionalGetVars = GeneralUtility::explodeUrl2Array($additionalGetVars, true);
        // Check if the page (= its rootline) has a site attached, otherwise just keep the URL as is
        $siteReader = GeneralUtility::makeInstance(SiteReader::class, Environment::getConfigPath() . '/sites');
        $rootLine = $rootLine ?? BackendUtility::BEgetRootLine($pageUid);
        foreach ($rootLine as $pageInRootLine) {
            if ($pageInRootLine['uid'] > 0) {
                $site = $siteReader->getSiteByRootPageId($pageInRootLine['uid']);
                if ($site !== null) {
                    $uriBuilder = GeneralUtility::makeInstance(PageUriBuilder::class);
                    $previewUrl = (string)$uriBuilder->buildUri($pageUid, $additionalGetVars, $anchorSection, ['rootLine' => $rootLine], $uriBuilder::ABSOLUTE_URL);
                    break;
                }
            }
        }
        return $previewUrl;
    }
}