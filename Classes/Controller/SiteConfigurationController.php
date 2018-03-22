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
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Sites\Site\Site;
use TYPO3\CMS\Sites\Site\SiteReader;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Sites\Configuration\SiteTcaConfiguration;
use TYPO3\CMS\Sites\Form\FormDataGroup\SiteFormDataGroup;
use TYPO3\CMS\Sites\Exception\SiteNotFoundException;
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
     * @var SiteReader
     */
    protected $siteReader;

    public function __construct()
    {
        $this->siteReader = GeneralUtility::makeInstance(SiteReader::class, Environment::getConfigPath() . '/sites');
    }

    /**
     * Main entry method dispatches to other actions - those method names that end with "Action".
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
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

    /**
     * Show all possible pages where a site configuration can be put, as well as all site configurations
     */
    protected function overviewAction()
    {
        $this->configureOverViewDocHeader();
        $unmappedSiteConfiguration = [];
        /** @var Site[] $allSites */
        $allSites = $this->siteReader->getAllSites();
        $pages = $this->getAllSitePages();
        foreach ($allSites as $identifier => $site) {
            $rootPageId = $site->getRootPageId();
            if (isset($pages[$rootPageId])) {
                $pages[$rootPageId]['siteIdentifier'] = $identifier;
                $pages[$rootPageId]['siteConfiguration'] = $site;
            } else {
                // @todo unused in view
                $unmappedSiteConfiguration[$identifier] = $site;
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

        // Brings "our" sys_site and friends TCA into global TCA
        // @todo: We might be able to get rid of that later
        $GLOBALS['TCA'] = array_merge($GLOBALS['TCA'], GeneralUtility::makeInstance(SiteTcaConfiguration::class)->getTca());

        $siteIdentifier = $request->getQueryParams()['site'] ?? null;
        $pageUid = (int)($request->getQueryParams()['pageUid'] ?? 0);

        if (empty($siteIdentifier) && empty($pageUid)) {
            throw new \RuntimeException('Either site identifier to edit a config or page uid to add new config must be set', 1521561148);
        }
        $isNewConfig = empty($siteIdentifier);

        $defaultValues = [];
        if ($isNewConfig) {
            $defaultValues['sys_site']['rootPageId'] = $pageUid;
        }

        $allSites = $this->siteReader->getAllSites();
        if (!$isNewConfig && !isset($allSites[$siteIdentifier])) {
            throw new \RuntimeException('Existing config for site ' . $siteIdentifier . ' not found', 1521561226);
        }

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $returnUrl = $uriBuilder->buildUriFromRoute('site_configuration');

        $formDataGroup = GeneralUtility::makeInstance(SiteFormDataGroup::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $formDataCompilerInput = [
            'tableName' => 'sys_site',
            'vanillaUid' => $isNewConfig ? $pageUid : $allSites[$siteIdentifier]->getRootPageId(),
            'command' => $isNewConfig ? 'new' : 'edit',
            'returnUrl' => (string)$returnUrl,
            'customData' => [
                'siteIdentifier' => $isNewConfig ? '' : $siteIdentifier,
            ],
            'defaultValues' => $defaultValues,
        ];
        $formData = $formDataCompiler->compile($formDataCompilerInput);
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        $formData['renderType'] = 'outerWrapContainer';
        $formResult = $nodeFactory->create($formData)->render();
        // Needed to be set for 'onChange="reload"' and reload on type change to work
        $formResult['doSaveFieldName'] = 'doSave';
        $formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);
        $formResultCompiler->mergeResult($formResult);
        $formResultCompiler->addCssFiles();
        // Always add rootPageId as additional field to have a reference for new records
        $this->view->assign('rootPageId', $isNewConfig ? $pageUid : $allSites[$siteIdentifier]->getRootPageId());
        $this->view->assign('returnUrl', $returnUrl);
        $this->view->assign('formEngineHtml', $formResult['html']);
        $this->view->assign('formEngineFooter', $formResultCompiler->printNeededJSFunctions());
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
        $siteTca = GeneralUtility::makeInstance(SiteTcaConfiguration::class)->getTca();

        $overviewRoute = $uriBuilder->buildUriFromRoute('site_configuration', ['action' => 'overview']);
        $parsedBody = $request->getParsedBody();
        if (isset($parsedBody['closeDoc']) && (int)$parsedBody['closeDoc'] === 1) {
            // Closing means no save, just redirect to overview
            return new RedirectResponse($overviewRoute);
        }
        $isSave = $parsedBody['_savedok'] ?? $parsedBody['doSave'] ?? false;
        $isSaveClose = $parsedBody['_saveandclosedok'] ?? false;
        if (!$isSave && !$isSaveClose) {
            throw new \RuntimeException('Either save or save and close', 1520370364);
        }

        if (!isset($parsedBody['data']['sys_site']) || !is_array($parsedBody['data']['sys_site'])) {
            throw new \RuntimeException('No sys_site data or sys_site identifier given', 1521030950);
        }

        $data = $parsedBody['data'];
        // This can be NEW... for new records
        $pageId = (int)key($data['sys_site']);
        $sysSiteRow = current($data['sys_site']);
        $siteIdentifier = $sysSiteRow['identifier'] ?? '';

        $isNewConfiguration = false;
        $currentIdentifier = '';
        try {
            $currentSite = $this->siteReader->getSiteByRootPageId($pageId);
            $currentSiteConfiguration = $currentSite->getConfiguration();
            $currentIdentifier = $currentSite->getIdentifier();
        } catch (SiteNotFoundException $e) {
            $isNewConfiguration = true;
            $pageId = (int)$parsedBody['rootPageId'];
            if (!$pageId > 0) {
                // Early validation of rootPageId - it must always be given and greater than 0
                throw new \RuntimeException('No root page id found', 1521719709);
            }
        }

        $siteIdentifier = $this->validateAndProcessIdentifier($isNewConfiguration, $siteIdentifier, $pageId);
        // Do not store or further process site identifier
        unset($sysSiteRow['identifier']);

        $sysSiteTca = $siteTca['sys_site'];
        $newSysSiteData = [];
        // Hard set rootPageId: This is TCA readOnly and not transmitted directly, but is also the "uid" of the sys_site record
        $newSysSiteData['site']['rootPageId'] = $pageId;
        foreach ($sysSiteRow as $fieldName => $fieldValue) {
            $type = $sysSiteTca['columns'][$fieldName]['config']['type'];
            if ($type === 'input') {
                $fieldValue = $this->validateAndProcessValue('sys_site', $isNewConfiguration, $fieldName, $fieldValue);
                $newSysSiteData['site'][$fieldName] = $fieldValue;
            } elseif ($type === 'inline') {
                $newSysSiteData['site'][$fieldName] = [];
                $childRowIds = GeneralUtility::trimExplode(',', $fieldValue, true);
                if (!isset($sysSiteTca['columns'][$fieldName]['config']['foreign_table'])) {
                    throw new \RuntimeException('No foreign_table found for inline type', 1521555037);
                }
                $foreignTable = $sysSiteTca['columns'][$fieldName]['config']['foreign_table'];
                foreach ($childRowIds as $childRowId) {
                    $childRowData = [];
                    if (!isset($data[$foreignTable][$childRowId])) {
                        if (!empty($currentSiteConfiguration[$fieldName][$childRowId])) {
                            // A collapsed inline record: Fetch data from existing config
                            $newSysSiteData['site'][$fieldName][] = $currentSiteConfiguration[$fieldName][$childRowId];
                            continue;
                        }
                        throw new \RuntimeException('No data found for table ' . $foreignTable . ' with id ' . $childRowId, 1521555177);
                    }
                    $childRow = $data[$foreignTable][$childRowId];
                    foreach ($childRow as $childFieldName => $childFieldValue) {
                        if ($childFieldName === 'pid') {
                            // pid is added by inline by default, but not relevant for yml storage
                            continue;
                        }
                        $type = $siteTca[$foreignTable]['columns'][$childFieldName]['config']['type'];
                        if ($type === 'input') {
                            $childRowData[$childFieldName] = $childFieldValue;
                        } elseif ($type === 'select') {
                            $childRowData[$childFieldName] = $childFieldValue;
                        } else {
                            throw new \RuntimeException('TCA type ' . $type . ' not implemented in site handling', 1521555340);
                        }
                    }
                    $newSysSiteData['site'][$fieldName][] = $childRowData;
                }
            } elseif ($type === 'select') {
                $newSysSiteData['site'][$fieldName] = (int)$fieldValue;
            } else {
                throw new \RuntimeException('TCA type ' . $type . ' not implemented in site handling', 1521032781);
            }
        }
        $yaml = Yaml::dump($newSysSiteData, 99, 2);

        if (!$isNewConfiguration && $currentIdentifier !== $siteIdentifier) {
            // @todo error handling / mkdir-deep?
            rename(PATH_site . 'typo3conf/sites/' . $currentIdentifier, PATH_site . 'typo3conf/sites/' . $siteIdentifier);
        } elseif ($isNewConfiguration) {
            // @todo error handling
            GeneralUtility::mkdir_deep(PATH_site . 'typo3conf/sites/' . $siteIdentifier);
        }
        // @todo error handling
        GeneralUtility::writeFile(PATH_site . 'typo3conf/sites/' . $siteIdentifier . '/config.yaml', $yaml);



        // @todo ugly
        $saveRoute = $uriBuilder->buildUriFromRoute('site_configuration', ['action' => 'edit', 'site' => $siteIdentifier]);

        if ($isSave) {
            return new RedirectResponse($saveRoute);
        }
        return new RedirectResponse($overviewRoute);
    }

    /**
     * Validation and processing of site identifier
     *
     * @param bool $isNew If true, we're dealing with a new record
     * @param string $identifier Given identifier to validate and process
     * @param integer $rootPageId Page uid this identifier is bound to
     * @return mixed Verified / modified value
     */
    protected function validateAndProcessIdentifier(bool $isNew, string $identifier, int $rootPageId)
    {
        // Normal "eval" processing of field first
        $identifier = $this->validateAndProcessValue('sys_site', $isNew, 'identifier', $identifier);
        if ($isNew) {
            // Verify no other site with this identifier exists. If so, find a new unique name as
            // identifier and show a flash message the identifier has been adapted
            try {
                $this->siteReader->getSiteByIdentifier($identifier);
                // Force this identifier to be unique
                $originalIdentifier = $identifier;
                $identifier = $identifier . '-' . str_replace('.', '', uniqid((string)mt_rand(), true));
                // @todo localize
                $message = 'Given site identifier ' . $originalIdentifier . ' already exists. It has been renamed to ' . $identifier . '.'
                    . ' Maybe you want to edit the site again and give it a better name.';
                $messageTitle = 'Renamed identifier';
                $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, $messageTitle, FlashMessage::WARNING, true);
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                $defaultFlashMessageQueue->enqueue($flashMessage);
            } catch (SiteNotFoundException $e) {
                // Do nothing, this new identifier is ok
            }
        } else {
            // If this is an existing config, the site for this identifier must have the same rootPageId, otherwise
            // a user tried to rename a site identifier to a different site that already exists. If so, we do not rename
            // the site and show a flash message
            try {
                $site = $this->siteReader->getSiteByIdentifier($identifier);
                if ($site->getRootPageId() !== $rootPageId) {
                    // Find original value and keep this
                    $origSite = $this->siteReader->getSiteByRootPageId($rootPageId);
                    $originalIdentifier = $identifier;
                    $identifier = $origSite->getIdentifier();
                    // @todo localize
                    $message = 'Given site identifier ' . $originalIdentifier . ' already exists and points to a different site.'
                        . ' The existing identifier ' . $identifier . ' for this site is kept.';
                    $messageTitle = 'Identifier not changed';
                    $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, $messageTitle, FlashMessage::WARNING, true);
                    $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                    $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                    $defaultFlashMessageQueue->enqueue($flashMessage);
                }
            } catch (SiteNotFoundException $e) {
                // User is renaming identifier which does not exist yet. Thas is ok
            }
        }
        return $identifier;
    }

    /**
     * Simple validation and processing method for incoming form field values
     *
     * @param string $tableName Table name
     * @param bool $isNew If true, we're dealing with a new record
     * @param string $fieldName Field name
     * @param mixed $fieldValue Incoming value from FormEngine
     * @return mixed Verified / modified value
     */
    protected function validateAndProcessValue(string $tableName, bool $isNew, string $fieldName, $fieldValue)
    {
        return $fieldValue;
    }

    /**
     * Delete an existing configuration
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function deleteAction(ServerRequestInterface $request): ResponseInterface
    {
        $siteIdentifier = $request->getQueryParams()['site'] ?? '';
        if (empty($siteIdentifier)) {
            throw new \RuntimeException('Not site identifier given', 1521565182);
        }
        // Verify site does exist, method throws if not
        $this->siteReader->getSiteByIdentifier($siteIdentifier);
        GeneralUtility::rmdir(PATH_site . 'typo3conf/sites/' . $siteIdentifier, true);
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $overviewRoute = $uriBuilder->buildUriFromRoute('site_configuration', ['action' => 'overview']);
        return new RedirectResponse($overviewRoute);
    }

    /**
     * @param string $templateName
     */
    protected function initializeView(string $templateName): void
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
            $row['rootline'] = BackendUtility::BEgetRootLine((int)$row['uid']);
            array_pop($row['rootline']);
            $row['rootline'] = array_reverse($row['rootline']);
            $i = 0;
            foreach ($row['rootline'] as &$record) {
                $record['margin'] = $i++ * 20;
            }
            $pages[(int)$row['uid']] = $row;
        }
        return $pages;
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
