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
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Sites\Configuration\SiteService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
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
        return new JsonResponse(['success' => true]);
    }

    /**
     * Show all possible pages where a site configuration can be put, as well as all site configurations
     */
    protected function overviewAction()
    {
        $this->configureOverViewDocHeader();
        $unmappedSiteConfiguration = [];
        $allSiteConfiguration = GeneralUtility::makeInstance(SiteService::class)->getAllSites();
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
            $allSiteConfiguration = GeneralUtility::makeInstance(SiteService::class)->getAllSites();
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
                'returnUrl' => $returnUrl,
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
            $formEngineFooter = $formResultCompiler->printNeededJSFunctions();
            // This hacks overrides "inline.makeAjaxCall()" to re-route inline open/new to this controller
            $formEngineFooter .= '
            <script type="text/javascript">
                  inline.makeAjaxCall = function(method, params, lock, context) {
                    var url = \'\', urlParams = \'\', options = {};
                    if (method && params && params.length && this.lockAjaxMethod(method, lock)) {
                      url = TYPO3.settings.ajaxUrls[\'record_inline_\' + method];
                      urlParams = \'\';
                      for (var i = 0; i < params.length; i++) {
                        urlParams += \'&ajax[\' + i + \']=\' + encodeURIComponent(params[i]);
                      }
                      if (context) {
                        urlParams += \'&ajax[context]=\' + encodeURIComponent(JSON.stringify(context));
                      }
                      options = {
                        type: \'POST\',
                        data: urlParams,
                        success: function(data, message, jqXHR) {
                          inline.isLoading = false;
                          inline.processAjaxResponse(method, jqXHR);
                          if (inline.progress) {
                            inline.progress.done();
                          }
                        },
                        error: function(jqXHR) {
                          inline.isLoading = false;
                          inline.showAjaxFailure(method, jqXHR);
                          if (inline.progress) {
                            inline.progress.done();
                          }
                        }
                      };
                      $.ajax(url, options);
                    }
                },
            </script>
            ';
            $this->view->assign('returnUrl', $returnUrl);
            $this->view->assign('formEngineHtml', $formResult['html']);
            $this->view->assign('formEngineFooter', $formEngineFooter);
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
