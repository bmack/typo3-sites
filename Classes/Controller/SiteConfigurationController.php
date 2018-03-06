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
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Sites\Configuration\SiteService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Lists all siteroot pages, and allows to configure a configuration for a site.
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
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Initializes everything necessary for rendering
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Injects the request object for the current request, and renders the overview of all sites
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
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
     * Show all possible pages where a site configuration can be put, as well as all site configurations
     */
    protected function overviewAction()
    {
        $this->getButtons();
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
     */
    protected function editAction(ServerRequestInterface $request)
    {
        $siteIdentifier = $request->getQueryParams()['site'] ?? null;
        if ($siteIdentifier) {
            $allSiteConfiguration = GeneralUtility::makeInstance(SiteService::class)->getAllSites();
            if (!isset($allSiteConfiguration[$siteIdentifier])) {
                // throw an error;
            }
            $existingConfiguration = $allSiteConfiguration[$siteIdentifier];
        }
    }

    /**
     * Create document header buttons
     */
    protected function getButtons()
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        // Reload
        $reloadButton = $buttonBar->makeLinkButton()
            ->setHref(GeneralUtility::getIndpEnv('REQUEST_URI'))
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);

        // Shortcut
        $mayMakeShortcut = $this->getBackendUser()->mayMakeShortcut();
        if ($mayMakeShortcut) {
            $getVars = ['id', 'route'];
            $shortcutButton = $buttonBar->makeShortcutButton()
                ->setModuleName('site_configuration')
                ->setGetVariables($getVars);
            $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
        }
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
}
