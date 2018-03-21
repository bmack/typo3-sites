<?php
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
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Resolve some specialities of the "site configuration"
 */
class SiteTcaSelectItems implements FormDataProviderInterface
{
    /**
     * Resolve select items for
     * * 'sys_site_language' -> 'typo3language'
     *
     * @param array $result
     * @return array
     * @throws \UnexpectedValueException
     */
    public function addData(array $result)
    {
        $table = $result['tableName'];
        if ($table !== 'sys_site_language') {
            return $result;
        }
        $locales = GeneralUtility::makeInstance(Locales::class);
        $languages = $locales->getLanguages();
        $items = [];
        foreach ($languages as $key => $label) {
            $items[] = [
                0 => $label,
                1 => $key,
            ];
        }
        $result['processedTca']['columns']['typo3Language']['config']['items'] = $items;
        return $result;
    }
}
