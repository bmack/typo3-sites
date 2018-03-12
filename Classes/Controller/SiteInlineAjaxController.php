<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Sites\Controller;

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
use TYPO3\CMS\Backend\Controller\AbstractFormEngineAjaxController;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Sites\Form\FormDataGroup\SiteConfigurationFormDataGroup;

/**
 * FormEngine "edit" and "new" site configuration inline ajax related methods
 */
class SiteInlineAjaxController extends AbstractFormEngineAjaxController
{
    /**
     * Inline "create" new child
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function newInlineChildAction(ServerRequestInterface $request): ResponseInterface
    {
        $ajaxArguments = $request->getParsedBody()['ajax'] ?? $request->getQueryParams()['ajax'];
        $parentConfig = $this->extractSignedParentConfigFromRequest((string)$ajaxArguments['context']);
        $domObjectId = $ajaxArguments[0];
        $inlineFirstPid = $this->getInlineFirstPidFromDomObjectId($domObjectId);
        $childChildUid = null;
        if (isset($ajaxArguments[1]) && MathUtility::canBeInterpretedAsInteger($ajaxArguments[1])) {
            $childChildUid = (int)$ajaxArguments[1];
        }
        // Parse the DOM identifier, add the levels to the structure stack
        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $inlineStackProcessor->initializeByParsingDomObjectIdString($domObjectId);
        $inlineStackProcessor->injectAjaxConfiguration($parentConfig);
        $inlineTopMostParent = $inlineStackProcessor->getStructureLevel(0);
        // Parent, this table embeds the child table
        $parent = $inlineStackProcessor->getStructureLevel(-1);
        // Child, a record from this table should be rendered
        $child = $inlineStackProcessor->getUnstableStructure();
        if (MathUtility::canBeInterpretedAsInteger($child['uid'])) {
            // If uid comes in, it is the id of the record neighbor record "create after"
            $childVanillaUid = -1 * abs((int)$child['uid']);
        } else {
            // Else inline first Pid is the storage pid of new inline records
            $childVanillaUid = (int)$inlineFirstPid;
        }
        $childTableName = $parentConfig['foreign_table'];

        $formDataGroup = GeneralUtility::makeInstance(SiteConfigurationFormDataGroup::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $formDataCompilerInput = [
            'command' => 'new',
            'tableName' => $childTableName,
            'vanillaUid' => $childVanillaUid,
            'isInlineChild' => true,
            'inlineStructure' => $inlineStackProcessor->getStructure(),
            'inlineFirstPid' => $inlineFirstPid,
            'inlineParentUid' => $parent['uid'],
            'inlineParentTableName' => $parent['table'],
            'inlineParentFieldName' => $parent['field'],
            'inlineParentConfig' => $parentConfig,
            'inlineTopMostParentUid' => $inlineTopMostParent['uid'],
            'inlineTopMostParentTableName' => $inlineTopMostParent['table'],
            'inlineTopMostParentFieldName' => $inlineTopMostParent['field'],
        ];
        if ($childChildUid) {
            $formDataCompilerInput['inlineChildChildUid'] = $childChildUid;
        }
        $childData = $formDataCompiler->compile($formDataCompilerInput);

        if ($parentConfig['foreign_selector'] && $parentConfig['appearance']['useCombination']) {
            // We have a foreign_selector. So, we just created a new record on an intermediate table in $childData.
            // Now, if a valid id is given as second ajax parameter, the intermediate row should be connected to an
            // existing record of the child-child table specified by the given uid. If there is no such id, user
            // clicked on "created new" and a new child-child should be created, too.
            if ($childChildUid) {
                // Fetch existing child child
                $childData['databaseRow'][$parentConfig['foreign_selector']] = [
                    $childChildUid,
                ];
                $childData['combinationChild'] = $this->compileChildChild($childData, $parentConfig, $inlineStackProcessor->getStructure());
            } else {
                $formDataGroup = GeneralUtility::makeInstance(SiteConfigurationFormDataGroup::class);
                $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
                $formDataCompilerInput = [
                    'command' => 'new',
                    'tableName' => $childData['processedTca']['columns'][$parentConfig['foreign_selector']]['config']['foreign_table'],
                    'vanillaUid' => (int)$inlineFirstPid,
                    'isInlineChild' => true,
                    'isInlineAjaxOpeningContext' => true,
                    'inlineStructure' => $inlineStackProcessor->getStructure(),
                    'inlineFirstPid' => (int)$inlineFirstPid,
                ];
                $childData['combinationChild'] = $formDataCompiler->compile($formDataCompilerInput);
            }
        }

        $childData['inlineParentUid'] = (int)$parent['uid'];
        $childData['renderType'] = 'inlineRecordContainer';
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        $childResult = $nodeFactory->create($childData)->render();

        $jsonArray = [
            'data' => '',
            'stylesheetFiles' => [],
            'scriptCall' => [],
        ];

        // The HTML-object-id's prefix of the dynamically created record
        $objectName = $inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($inlineFirstPid);
        $objectPrefix = $objectName . '-' . $child['table'];
        $objectId = $objectPrefix . '-' . $childData['databaseRow']['uid'];
        $expandSingle = $parentConfig['appearance']['expandSingle'];
        if (!$child['uid']) {
            $jsonArray['scriptCall'][] = 'inline.domAddNewRecord(\'bottom\',' . GeneralUtility::quoteJSvalue($objectName . '_records') . ',' . GeneralUtility::quoteJSvalue($objectPrefix) . ',json.data);';
            $jsonArray['scriptCall'][] = 'inline.memorizeAddRecord(' . GeneralUtility::quoteJSvalue($objectPrefix) . ',' . GeneralUtility::quoteJSvalue($childData['databaseRow']['uid']) . ',null,' . GeneralUtility::quoteJSvalue($childChildUid) . ');';
        } else {
            $jsonArray['scriptCall'][] = 'inline.domAddNewRecord(\'after\',' . GeneralUtility::quoteJSvalue($domObjectId . '_div') . ',' . GeneralUtility::quoteJSvalue($objectPrefix) . ',json.data);';
            $jsonArray['scriptCall'][] = 'inline.memorizeAddRecord(' . GeneralUtility::quoteJSvalue($objectPrefix) . ',' . GeneralUtility::quoteJSvalue($childData['databaseRow']['uid']) . ',' . GeneralUtility::quoteJSvalue($child['uid']) . ',' . GeneralUtility::quoteJSvalue($childChildUid) . ');';
        }
        $jsonArray = $this->mergeChildResultIntoJsonResult($jsonArray, $childResult);
        if ($parentConfig['appearance']['useSortable']) {
            $inlineObjectName = $inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($inlineFirstPid);
            $jsonArray['scriptCall'][] = 'inline.createDragAndDropSorting(' . GeneralUtility::quoteJSvalue($inlineObjectName . '_records') . ');';
        }
        if (!$parentConfig['appearance']['collapseAll'] && $expandSingle) {
            $jsonArray['scriptCall'][] = 'inline.collapseAllRecords(' . GeneralUtility::quoteJSvalue($objectId) . ',' . GeneralUtility::quoteJSvalue($objectPrefix) . ',' . GeneralUtility::quoteJSvalue($childData['databaseRow']['uid']) . ');';
        }
        // Fade out and fade in the new record in the browser view to catch the user's eye
        $jsonArray['scriptCall'][] = 'inline.fadeOutFadeIn(' . GeneralUtility::quoteJSvalue($objectId . '_div') . ');';

        return new JsonResponse($jsonArray);
    }

    /**
     * Merge stuff from child array into json array.
     * This method is needed since ajax handling methods currently need to put scriptCalls before and after child code.
     *
     * @param array $jsonResult Given json result
     * @param array $childResult Given child result
     * @return array Merged json array
     */
    protected function mergeChildResultIntoJsonResult(array $jsonResult, array $childResult)
    {
        $jsonResult['data'] .= $childResult['html'];
        $jsonResult['stylesheetFiles'] = [];
        foreach ($childResult['stylesheetFiles'] as $stylesheetFile) {
            $jsonResult['stylesheetFiles'][] = $this->getRelativePathToStylesheetFile($stylesheetFile);
        }
        if (!empty($childResult['inlineData'])) {
            $jsonResult['scriptCall'][] = 'inline.addToDataArray(' . json_encode($childResult['inlineData']) . ');';
        }
        if (!empty($childResult['additionalJavaScriptSubmit'])) {
            $additionalJavaScriptSubmit = implode('', $childResult['additionalJavaScriptSubmit']);
            $additionalJavaScriptSubmit = str_replace([CR, LF], '', $additionalJavaScriptSubmit);
            $jsonResult['scriptCall'][] = 'TBE_EDITOR.addActionChecks("submit", "' . addslashes($additionalJavaScriptSubmit) . '");';
        }
        foreach ($childResult['additionalJavaScriptPost'] as $singleAdditionalJavaScriptPost) {
            $jsonResult['scriptCall'][] = $singleAdditionalJavaScriptPost;
        }
        if (!empty($childResult['additionalInlineLanguageLabelFiles'])) {
            $labels = [];
            foreach ($childResult['additionalInlineLanguageLabelFiles'] as $additionalInlineLanguageLabelFile) {
                ArrayUtility::mergeRecursiveWithOverrule(
                    $labels,
                    $this->getLabelsFromLocalizationFile($additionalInlineLanguageLabelFile)
                );
            }
            $javaScriptCode = [];
            $javaScriptCode[] = 'if (typeof TYPO3 === \'undefined\' || typeof TYPO3.lang === \'undefined\') {';
            $javaScriptCode[] = '   TYPO3.lang = {}';
            $javaScriptCode[] = '}';
            $javaScriptCode[] = 'var additionalInlineLanguageLabels = ' . json_encode($labels) . ';';
            $javaScriptCode[] = 'for (var attributeName in additionalInlineLanguageLabels) {';
            $javaScriptCode[] = '   if (typeof TYPO3.lang[attributeName] === \'undefined\') {';
            $javaScriptCode[] = '       TYPO3.lang[attributeName] = additionalInlineLanguageLabels[attributeName]';
            $javaScriptCode[] = '   }';
            $javaScriptCode[] = '}';

            $jsonResult['scriptCall'][] = implode(LF, $javaScriptCode);
        }
        $requireJsModule = $this->createExecutableStringRepresentationOfRegisteredRequireJsModules($childResult);
        $jsonResult['scriptCall'] = array_merge($requireJsModule, $jsonResult['scriptCall']);

        return $jsonResult;
    }

    /**
     * Inline ajax helper method.
     *
     * Validates the config that is transferred over the wire to provide the
     * correct TCA config for the parent table
     *
     * @param string $contextString
     * @throws \RuntimeException
     * @return array
     */
    protected function extractSignedParentConfigFromRequest(string $contextString): array
    {
        if ($contextString === '') {
            throw new \RuntimeException('Empty context string given', 1489751361);
        }
        $context = json_decode($contextString, true);
        if (empty($context['config'])) {
            throw new \RuntimeException('Empty context config section given', 1489751362);
        }
        if (!hash_equals(GeneralUtility::hmac(json_encode($context['config']), 'InlineContext'), $context['hmac'])) {
            throw new \RuntimeException('Hash does not validate', 1489751363);
        }
        return $context['config'];
    }

    /**
     * Get inlineFirstPid from a given objectId string
     *
     * @param string $domObjectId The id attribute of an element
     * @return int|null Pid or null
     */
    protected function getInlineFirstPidFromDomObjectId($domObjectId)
    {
        // Substitute FlexForm addition and make parsing a bit easier
        $domObjectId = str_replace('---', ':', $domObjectId);
        // The starting pattern of an object identifier (e.g. "data-<firstPidValue>-<anything>)
        $pattern = '/^data' . '-' . '(.+?)' . '-' . '(.+)$/';
        if (preg_match($pattern, $domObjectId, $match)) {
            return $match[1];
        }
        return null;
    }
}