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
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Sites\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Sites\Form\FormDataGroup\SiteConfigurationFormDataGroup;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Lists all site root pages, and allows to configure a configuration for a site.
 */
class SiteConfigurationController
{
    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var ViewInterface
     */
    protected $view;

    /**
     * Injects the request object for the current request, and renders the overview of all sites
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function dispatchMainActions(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        $action = $request->getQueryParams()['action'] ?? $request->getParsedBody()['action'] ?? 'overview';
        $this->initializeView($action);

        $result = call_user_func_array([$this, $action . 'Action'], [$request]);
        if ($result instanceof ResponseInterface) {
            return $result;
        }
        $this->moduleTemplate->setContent($this->view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

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
     * Show all possible pages where a site configuration can be put, as well as all site configurations
     */
    protected function overviewAction()
    {
        $this->configureOverViewDocHeader();
        $unmappedSiteConfiguration = [];
        $allSiteConfiguration = GeneralUtility::makeInstance(SiteConfiguration::class)->getAllSites();
        $pages = $this->getAllSitePages();
        foreach ($allSiteConfiguration as $identifier => $siteConfiguration) {
            $rootpageId = (int)$siteConfiguration['rootpageId'];
            if (isset($pages[$rootpageId])) {
                $pages[$rootpageId]['siteidentifier'] = $identifier;
                $pages[$rootpageId]['siteconfiguration'] = $siteConfiguration;
            } else {
                $unmappedSiteConfiguration[$identifier] = $siteConfiguration;
            }
        }
        $this->view->assign('unmappedSiteConfiguration', $unmappedSiteConfiguration);
        $this->view->assign('pages', $pages);
    }

    /**
     * Shows a form to create a new site configuration, or edit an existing one.
     *
     * @param $request ServerRequestInterface
     */
    protected function editAction(ServerRequestInterface $request)
    {
        $this->configureEditViewDocHeader();
        $siteIdentifier = $request->getQueryParams()['site'] ?? null;
        if ($siteIdentifier) {
            $allSiteConfiguration = GeneralUtility::makeInstance(SiteConfiguration::class)->getAllSites();
            if (!isset($allSiteConfiguration[$siteIdentifier])) {
                // @todo throw an error;
            }

            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $returnUrl = $uriBuilder->buildUriFromRoute('site_configuration');
            $formDataGroup = GeneralUtility::makeInstance(SiteConfigurationFormDataGroup::class);
            $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
            $formDataCompilerInput = [
                'tableName' => 'sys_site',
                'vanillaUid' => 42, // @todo dummy for now, string identifier not good for mamuschka
                'command' => 'edit',
                'returnUrl' => (string)$returnUrl,
                'customData' => [
                    'siteData' => $allSiteConfiguration[$siteIdentifier],
                    'siteIdentifier' => $siteIdentifier,
                ],
            ];
            $formData = $formDataCompiler->compile($formDataCompilerInput);
            $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
            $formData['renderType'] = 'outerWrapContainer';
            $formResult = $nodeFactory->create($formData)->render();
            $formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);
            $formResultCompiler->mergeResult($formResult);
            $formResultCompiler->addCssFiles();
            $this->view->assign('returnUrl', $returnUrl);
            $this->view->assign('formEngineHtml', $formResult['html']);
            $this->view->assign('formEngineFooter', $formResultCompiler->printNeededJSFunctions());
        } else {
            // ?
        }
    }
    /**
     * Save incoming data from editAction and redirect to overview or edit
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function saveAction(ServerRequestInterface $request): ResponseInterface
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $overviewRoute = $uriBuilder->buildUriFromRoute('site_configuration', ['action' => 'overview']);
        $parsedBody = $request->getParsedBody();
        if (isset($parsedBody['closeDoc']) && (int)$parsedBody['closeDoc'] === 1) {
            // Closing means no save, just redirect to overview
            return new RedirectResponse($overviewRoute);
        }
        $isSave = $parsedBody['_savedok'] ?? false;
        $isSaveClose = $parsedBody['_saveandclosedok'] ?? false;
        if (!$isSave && !$isSaveClose) {
            throw new \RuntimeException('Either save or save and close', 1520370364);
        }

        // @todo throw if identifier not set?
        // @todo Store data here

        // @todo ugly
        $siteIdentifier = $parsedBody['data']['sys_site']['0']['identifier'] ?? null;
        $saveRoute = $uriBuilder->buildUriFromRoute('site_configuration', ['action' => 'edit', 'site' => $siteIdentifier]);

        if ($isSave) {
            return new RedirectResponse($saveRoute);
        }
        return new RedirectResponse($overviewRoute);
    }

    /**
     * @param string $templateName
     */
    protected function initializeView(string $templateName)
    {
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setTemplate($templateName);
        $this->view->setTemplateRootPaths(['EXT:sites/Resources/Private/Templates/SiteConfiguration']);
        $this->view->setPartialRootPaths(['EXT:sites/Resources/Private/Partials']);
        $this->view->setLayoutRootPaths(['EXT:sites/Resources/Private/Layouts']);
    }


    /**
     * Create document header buttons of "edit" action
     */
    protected function configureEditViewDocHeader(): void
    {
        $iconFactory = $this->moduleTemplate->getIconFactory();
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $lang = $this->getLanguageService();
        $closeButton = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setClasses('t3js-editform-close')
            ->setTitle($lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:rm.closeDoc'))
            ->setIcon($iconFactory->getIcon('actions-close', Icon::SIZE_SMALL));
        $saveButton = $buttonBar->makeInputButton()
            ->setTitle($lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:rm.saveDoc'))
            ->setName('_savedok')
            ->setValue('1')
            ->setForm('siteConfigurationController')
            ->setIcon($iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL));
        $saveAndCloseButton = $buttonBar->makeInputButton()
            ->setName('_saveandclosedok')
            ->setClasses('t3js-editform-submitButton')
            ->setValue('1')
            ->setForm('siteConfigurationController')
            ->setTitle($lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:rm.saveCloseDoc'))
            ->setIcon($iconFactory->getIcon('actions-document-save-close', Icon::SIZE_SMALL));
        $saveSplitButton = $buttonBar->makeSplitButton();
        $saveSplitButton->addItem($saveButton, true);
        $saveSplitButton->addItem($saveAndCloseButton);
        $buttonBar->addButton($closeButton);
        $buttonBar->addButton($saveSplitButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
    }

    /**
     * Create document header buttons of "overview" action
     */
    protected function configureOverViewDocHeader(): void
    {
        $iconFactory = $this->moduleTemplate->getIconFactory();
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $reloadButton = $buttonBar->makeLinkButton()
            ->setHref(GeneralUtility::getIndpEnv('REQUEST_URI'))
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);
        if ($this->getBackendUser()->mayMakeShortcut()) {
            $getVars = ['id', 'route'];
            $shortcutButton = $buttonBar->makeShortcutButton()
                ->setModuleName('site_configuration')
                ->setGetVariables($getVars);
            $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
        }
    }

    /**
     * @return array
     */
    protected function getAllSitePages(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $statement = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', 0),
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('pid', 0),
                    $queryBuilder->expr()->eq('is_siteroot', 1)
                )
            )
            ->orderBy('pid')
            ->addOrderBy('sorting')
            ->execute();

        $pages = [];
        while ($row = $statement->fetch()) {
            $row['rootline'] = BackendUtility::getRecordPath((int)$row['uid'], '', 100);
            $row['rootline'] = trim($row['rootline'], '/');
            $pages[(int)$row['uid']] = $row;
        }
        return $pages;
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
     * Gets result array from FormEngine and returns string with js modules
     * that need to be loaded and evaluated by JavaScript.
     *
     * @param array $result
     * @return array
     */
    public function createExecutableStringRepresentationOfRegisteredRequireJsModules(array $result): array
    {
        if (empty($result['requireJsModules'])) {
            return [];
        }
        $requireJs = [];
        foreach ($result['requireJsModules'] as $module) {
            $moduleName = null;
            $callback = null;
            if (is_string($module)) {
                // if $module is a string, no callback
                $moduleName = $module;
                $callback = null;
            } elseif (is_array($module)) {
                // if $module is an array, callback is possible
                foreach ($module as $key => $value) {
                    $moduleName = $key;
                    $callback = $value;
                    break;
                }
            }
            if ($moduleName !== null) {
                $inlineCodeKey = $moduleName;
                $javaScriptCode = 'require(["' . $moduleName . '"]';
                if ($callback !== null) {
                    $inlineCodeKey .= sha1($callback);
                    $javaScriptCode .= ', ' . $callback;
                }
                $javaScriptCode .= ');';
                $requireJs[] = '/*RequireJS-Module-' . $inlineCodeKey . '*/' . LF . $javaScriptCode;
            }
        }
        return $requireJs;
    }


    /**
     * Parse a language file and get a label/value array from it.
     *
     * @param string $file EXT:path/to/file
     * @return array Label/value array
     */
    protected function getLabelsFromLocalizationFile($file)
    {
        /** @var $languageFactory LocalizationFactory */
        $languageFactory = GeneralUtility::makeInstance(LocalizationFactory::class);
        $language = $GLOBALS['LANG']->lang;
        $localizationArray = $languageFactory->getParsedData($file, $language);
        if (is_array($localizationArray) && !empty($localizationArray)) {
            if (!empty($localizationArray[$language])) {
                $xlfLabelArray = $localizationArray['default'];
                ArrayUtility::mergeRecursiveWithOverrule($xlfLabelArray, $localizationArray[$language], true, false);
            } else {
                $xlfLabelArray = $localizationArray['default'];
            }
        } else {
            $xlfLabelArray = [];
        }
        $labelArray = [];
        foreach ($xlfLabelArray as $key => $value) {
            if (isset($value[0]['target'])) {
                $labelArray[$key] = $value[0]['target'];
            } else {
                $labelArray[$key] = '';
            }
        }
        return $labelArray;
    }


    /**
     * Resolve a CSS file position, possibly prefixed with 'EXT:'
     *
     * @param string $stylesheetFile Given file, possibly prefixed with EXT:
     * @return string Web root relative position to file
     */
    protected function getRelativePathToStylesheetFile(string $stylesheetFile): string
    {
        if (strpos($stylesheetFile, 'EXT:') === 0) {
            $stylesheetFile = GeneralUtility::getFileAbsFileName($stylesheetFile);
            $stylesheetFile = PathUtility::getRelativePathTo($stylesheetFile);
            $stylesheetFile = rtrim($stylesheetFile, '/');
        } else {
            $stylesheetFile = GeneralUtility::resolveBackPath($stylesheetFile);
        }
        $stylesheetFile = GeneralUtility::createVersionNumberedFilename($stylesheetFile);
        return PathUtility::getAbsoluteWebPath($stylesheetFile);
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

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
