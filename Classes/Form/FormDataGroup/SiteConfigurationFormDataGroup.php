<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Sites\Form\FormDataGroup;

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

use TYPO3\CMS\Backend\Form\FormDataGroup\OrderedProviderList;
use TYPO3\CMS\Backend\Form\FormDataGroupInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A data provider group for site and language configuration.
 *
 * This data group is for data fetched from sites yml files,
 * it is fed by "fake TCA" since there are no real db records.
 */
class SiteConfigurationFormDataGroup implements FormDataGroupInterface
{
    /**
     * Compile form data
     *
     * @param array $result Initialized result array
     * @return array Result filled with data
     * @throws \UnexpectedValueException
     */
    public function compile(array $result)
    {
        $orderedProviderList = GeneralUtility::makeInstance(OrderedProviderList::class);
        $orderedProviderList->setProviderList([
            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class => [],
            \TYPO3\CMS\Sites\Form\FormDataProvider\SiteDatabaseEditRow::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                ]
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class => [
                'depends' => [
                    \TYPO3\CMS\Sites\Form\FormDataProvider\SiteDatabaseEditRow::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseDefaultLanguagePageRow::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseDefaultLanguagePageRow::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEffectivePid::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageRootline::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEffectivePid::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageRootline::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEffectivePid::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\ParentPageTca::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca::class
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\ParentPageTca::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUniqueUidNewRow::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDateTimeFields::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUniqueUidNewRow::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDateTimeFields::class
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordOverrideValues::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordOverrideValues::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordOverrideValues::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageLanguageOverlayRows::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows::class
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseLanguageRows::class => [
                'depends' => [
                    // Language stuff depends on user ts, but it *may* also depend on new row defaults
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageLanguageOverlayRows::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseLanguageRows::class,
                    // As the ctrl.type can hold a nested key we need to resolve all relations
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaGroup::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsOverrides::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsOverrides::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessCommon::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessRecordTitle::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessCommon::class
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessPlaceholders::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessRecordTitle::class
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessShowitem::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessPlaceholders::class
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessCommon::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessRecordTitle::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessPlaceholders::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InlineOverrideChildTca::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessShowitem::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesShowitem::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordTypeValue::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesShowitem::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldLabels::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaText::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaText::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageRootline::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesShowitem::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class,
                ],
            ],
            \TYPO3\CMS\Sites\Form\FormDataProvider\SiteTcaInline::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInputPlaceholders::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineIsOnSymmetricSide::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInputPlaceholders::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRecordTitle::class => [
                'depends' => [
                    \TYPO3\CMS\Sites\Form\FormDataProvider\SiteTcaInline::class,
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineIsOnSymmetricSide::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\EvaluateDisplayConditions::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRecordTitle::class,
                ],
            ],
        ]);
        return $orderedProviderList->compile($result);
    }
}
