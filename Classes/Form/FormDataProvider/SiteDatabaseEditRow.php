<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Sites\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Sites\Configuration\SiteConfiguration;

/**
 * Transfer "row" from ['customData']['siteData'] to ['databaseRow']
 */
class SiteDatabaseEditRow implements FormDataProviderInterface
{
    /**
     * First level of ['customData']['siteData'] to ['databaseRow']
     *
     * @param array $result
     * @return array
     * @throws \RuntimeException
     */
    public function addData(array $result)
    {
        if ($result['command'] !== 'edit' || !empty($result['databaseRow'])) {
            return $result;
        }

        $tableName = $result['tableName'];
        $siteConfiguration = GeneralUtility::makeInstance(SiteConfiguration::class);
        if ($tableName === 'sys_site') {
            $siteConfigurationForPageUid = (int)$result['vanillaUid'];
            $rowData = $siteConfiguration->getByPageUid($siteConfigurationForPageUid);
            $result['databaseRow']['uid'] = $rowData['rootPageId'];
            $result['databaseRow']['identifier'] = $result['customData']['siteIdentifier'];
        } elseif ($tableName === 'sys_site_errorhandling') {
            $siteConfigurationForPageUid = (int)($result['inlineTopMostParentUid'] ?? $result['inlineParentUid']);
            $rowData = $siteConfiguration->getByPageUid($siteConfigurationForPageUid);
            $parentFieldName = $result['inlineParentFieldName'];
            if (!isset($rowData[$parentFieldName])) {
                throw new \RuntimeException(
                    'Field ' . $parentFieldName . ' not found',
                    1520886092
                );
            }
            // @todo Set to proper uid as soon as vanillaUid is correct
            $rowData = $rowData[$parentFieldName][$result['vanillaUid']];
            $result['databaseRow']['uid'] = $result['vanillaUid'];
        } else {
            throw new \RuntimeException(
                'Other tables not implemented', 1520886234
            );
        }

        foreach ($rowData as $fieldName => $value) {
            // Flat values only - databaseRow has no "tree"
            if (!is_array($value)) {
                $result['databaseRow'][$fieldName] = $value;
            }
        }
        // All "records" are on pid 0
        $result['databaseRow']['pid'] = 0;
        return $result;
    }
}
