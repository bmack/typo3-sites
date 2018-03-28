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

/**
 * Renders the content of a page to be displayed (also in relation to language etc)
 */
class PageContentErrorHandler implements PageErrorHandlerInterface
{
    public function __construct(array $configuration)
    {
    }

    public function handlePageError(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface
    {
        return new HtmlResponse('FAIL VIA PAGE CONTENT');
    }

}