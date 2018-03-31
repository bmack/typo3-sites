<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Sites\Site\Entity;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Sites\PageErrorHandler\FluidPageErrorHandler;
use TYPO3\CMS\Sites\PageErrorHandler\PageContentErrorHandler;
use TYPO3\CMS\Sites\PageErrorHandler\PageErrorHandlerInterface;

/**
 * Entity representing a single site with available languages
 */
class Site
{
    const ERRORHANDLER_TYPE_PAGE = 'Page';
    const ERRORHANDLER_TYPE_FLUID = 'Fluid';
    const ERRORHANDLER_TYPE_PHP = 'PHP';

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $base;

    /**
     * @var int
     */
    protected $rootPageId;

    /**
     * Any attributes for this site
     * @var array
     */
    protected $configuration;

    /**
     * @var SiteLanguage[]
     */
    protected $languages;

    /**
     * @var array
     */
    protected $errorHandlers;

    /**
     * Sets up a site object, and its languages and error handlers
     *
     * @param string $identifier
     * @param int $rootPageId
     * @param array $configuration
     */
    public function __construct(string $identifier, int $rootPageId, array $configuration)
    {
        $this->identifier = $identifier;
        $this->rootPageId = $rootPageId;
        $this->configuration = $configuration;
        $configuration['languages'] = $configuration['languages'] ?: [0 => [
            'languageId' => 0,
            'title' => 'Default',
            'typo3Language' => 'default',
            'flag' => 'us',
            'locale' => 'en_US.UTF-8',
            'iso-639-1' => 'en'
        ]];
        $this->base = $configuration['base'] ?? '';
        foreach ($configuration['languages'] as $languageConfiguration) {
            $languageUid = (int)$languageConfiguration['languageId'];
            $base = $languageConfiguration['base'] ?: '/';
            $baseParts = parse_url($base);
            if (!$baseParts['scheme']) {
                $base = rtrim($this->base, '/') . '/' . ltrim($base, '/');
            }
            $this->languages[$languageUid] = new SiteLanguage(
                $this,
                $languageUid,
                $languageConfiguration['locale'],
                $base,
                $languageConfiguration
            );
        }
        foreach ($configuration['errorHandling'] ?? [] as $errorHandlingConfiguration) {
            $code = $errorHandlingConfiguration['errorCode'];
            unset($errorHandlingConfiguration['errorCode']);
            $this->errorHandlers[(int)$code] = $errorHandlingConfiguration;
        }
    }

    /**
     * Gets the identifier of this site
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Returns the base URL of this site
     *
     * @return string
     */
    public function getBase(): string
    {
        return $this->base;
    }

    /**
     * Returns the root page ID of this site
     *
     * @return int
     */
    public function getRootPageId(): int
    {
        return $this->rootPageId;
    }

    /**
     * Returns all available langauges of this site
     *
     * @return SiteLanguage[]
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }

    /**
     * Returns a language of this site, given by the sys_language_uid
     * @todo: throw an error if the language does not exist in this site
     *
     * @param $languageId
     * @return null|SiteLanguage
     */
    public function getLanguageById($languageId): ?SiteLanguage
    {
        return $this->languages[$languageId];
    }

    /**
     * Returns a ready-to-use error handler, to be used within the ErrorController
     *
     * @param int $type
     * @return PageErrorHandlerInterface
     * @throws \RuntimeException
     */
    public function getErrorHandler(int $type): PageErrorHandlerInterface
    {
        $errorHandler = $this->errorHandlers[$type];
        switch ($errorHandler['errorHandler']) {
            case self::ERRORHANDLER_TYPE_FLUID:
                return new FluidPageErrorHandler($errorHandler);
            case self::ERRORHANDLER_TYPE_PAGE:
                return new PageContentErrorHandler($errorHandler);
            case self::ERRORHANDLER_TYPE_PHP:
                // Check if the interface is implemented
                $handler = GeneralUtility::makeInstance($errorHandler['errorPhpClassFQCN'])($errorHandler);
                if (!($handler instanceof PageErrorHandlerInterface)) {
                    // throw new exception
                }
                return $handler;
        }
        throw new \RuntimeException('Not implemented', 1522495914);
    }

    /**
     * Returns the whole configuration for this site
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * Returns a single configuration attribute
     *
     * @param string $attributeName
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function getAttribute(string $attributeName)
    {
        if (isset($this->configuration[$attributeName])) {
            return $this->configuration[$attributeName];
        }
        throw new \InvalidArgumentException(
            'Attribute ' . $attributeName . ' does not exist on site ' . $this->identifier . '.',
            1522495954
        );
    }
}