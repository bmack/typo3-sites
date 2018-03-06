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

/**
 * Transfer "row" from ['customData']['siteData'] to ['databaseRow']
 */
class FakeDatabaseEditRow implements FormDataProviderInterface
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

        if (!isset($result['customData']['siteData'])) {
            throw new \RuntimeException(
                'No side data found', 1520353598
            );
        }

        foreach ($result['customData']['siteData'] as $fieldName => $value) {
            // Flat values only - databaseRow is has no "tree"
            if (!is_array($value)) {
                $result['databaseRow'][$fieldName] = $fieldName;
            }
        }
        // Fake a pid
        $result['databaseRow']['pid'] = 0;
        return $result;
    }
}
