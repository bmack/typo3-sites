<?php
namespace Bmack\Sites\Controller;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Contains all necessary functionality for dealing with a backend module
 */
class BackendModuleController
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var ViewInterface
     */
    protected $view;

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function processRequest(ServerRequestInterface $request, ResponseInterface $response)
    {
        $action = $request->getParsedBody()['action'] ?? $request->getQueryParams()['action'] ?? 'index';

        $this->initializeView(ucfirst($action));
        $result = call_user_func_array([$this, $action . 'Action'], [$request]);
        if ($result === null) {
            $result = $this->view->render($action);
        }

        $response->getBody()->write($result);
        return $response;
    }

    /**
     * Returns a new standalone view, shorthand function
     *
     * @param string $action Which templateFile should be used.
     */
    protected function initializeView(string $action)
    {
        $className = static::class;
        $classNameParts = explode('\\', $className);
        $controllerName = array_pop($classNameParts);
        if (substr($controllerName, -10) === 'Controller') {
            $controllerName = substr($controllerName, 0, -10);
        }
        $this->view = GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);
        $this->view->setLayoutRootPaths(['EXT:sites/Resources/Private/Layouts']);
        $this->view->setPartialRootPaths(['EXT:sites/Resources/Private/Partials']);
        $this->view->setTemplateRootPaths(['EXT:sites/Resources/Private/Templates/' . $controllerName]);
        $this->view->setTemplate(ucfirst($action));
    }
}
