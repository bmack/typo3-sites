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
use TYPO3\CMS\Sites\Configuration\SiteTcaConfiguration;

/**
 * Initialize processed TCA by reading FakeTCA from file
 *
 * @todo: Unused for now - we put that TCA into global scope at the moment
 */
class InitializeProcessedTca implements FormDataProviderInterface
{
    /**
     * Add processed TCA as copy from vanilla FakeTCA
     *
     * @param array $result
     * @return array
     * @throws \UnexpectedValueException
     */
    public function addData(array $result)
    {
        if (empty($result['processedTca'])) {
            $siteTcaConfiguration = GeneralUtility::makeInstance(SiteTcaConfiguration::class)->getTca();
            $result['processedTca'] = $siteTcaConfiguration[$result['tableName']];
        }
        if (!is_array($result['processedTca']['columns'])) {
            throw new \UnexpectedValueException(
                'No columns definition in fake TCA table ' . $result['tableName'],
                1520352433
            );
        }
        return $result;
    }
}
