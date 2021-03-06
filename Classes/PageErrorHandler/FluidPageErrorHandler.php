<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Sites\PageErrorHandler;

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
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3Fluid\Fluid\View\TemplateView;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * An error handler that renders a fluid template.
 * This is typically configured via the "Sites configuration" module in the backend.
 */
class FluidPageErrorHandler implements PageErrorHandlerInterface
{
    /**
     * @var ViewInterface
     */
    protected $view;

    public function __construct(array $configuration)
    {
        $this->view = new TemplateView();
        // @todo: make this work
        $this->view->render(PATH_site . str_replace('.html', '', $configuration['errorFluidTemplate']));
    }

    public function handlePageError(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface
    {
        $this->view->assignMultiple([
            'request' => $request,
            'message' => $message,
            'reasons' => $reasons
        ]);
        return new HtmlResponse($this->view->render());
    }
}