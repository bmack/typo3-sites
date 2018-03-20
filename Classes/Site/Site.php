<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Sites\Site;

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

/**
 * Entity representing a single Site with available languages
 */
class Site
{
    protected $identifier;

    protected $base;

    /**
     * @var int
     */
    protected $rootPageId;

    /**
     * Additional parameters configured for this site language
     * @var array
     */
    protected $parameters;

    /**
     * @var SiteLanguage[]
     */
    protected $languages;

    public function __construct(string $identifier, int $rootPageId, array $parameters, array $languageRecords = [])
    {
        $this->identifier = $identifier;
        $this->rootPageId = $rootPageId;
        $this->parameters = $parameters;
        $parameters['availableLanguages'] = $parameters['availableLanguages'] ?? [0 => ['language' => 0]];
        $this->base = $parameters['base'];
        foreach ($parameters['availableLanguages'] as $languageConfiguration) {
            $languageUid = (int)$languageConfiguration['language'];
            if ((int)$languageConfiguration['language'] === 0) {
                $languageConfiguration['locale'] = $parameters['defaultLocale'] ?? 'en_US';
                $languageConfiguration['title'] = $parameters['defaultLanguageLabel'] ?? 'Default';
                $languageConfiguration['flag'] = $parameters['defaultLanguageFlag'] ?? 'us';
                $languageConfiguration['xlf'] = $parameters['defaultLanguage'] ?? 'default';
                $languageConfiguration['iso639-1'] = $parameters['defaultLanguageIsoCode'] ?? 'en';
            } else {
                // @todo: what to do if no sys_language record was found?
                $languageConfiguration['title']    = $languageRecords[$languageUid]['title'];
                $languageConfiguration['flag']     = $languageRecords[$languageUid]['flag'];
                $languageConfiguration['iso639-1'] = $languageRecords[$languageUid]['language_isocode'];
            }
            $this->languages[$languageUid] = new SiteLanguage(
                $this,
                $languageUid,
                $languageConfiguration['locale'],
                $languageConfiguration['base'] ?: '/',
                $languageConfiguration
            );
        }
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getBase(): string
    {
        return $this->base;
    }

    public function getRootPageId(): int
    {
        return $this->rootPageId;
    }

    public function getLanguages(): array
    {
        return $this->languages;
    }

    public function getParameter($parameterName)
    {
        if (isset($this->parameters[$parameterName])) {
            return $this->parameters[$parameterName];
        }
        throw new \InvalidArgumentException('Parameter ' . $parameterName . ' does not exist on site ' . $this->identifier . '.');
    }

    public function getConfiguration(): array
    {
        return $this->parameters;
    }
}