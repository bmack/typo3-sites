<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Sites\Entity;

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
 * Identifies the entrypoint for a language configured for the language site.
 */
class SiteLanguage
{
    /**
     * @var Site
     */
    protected $site;

    /**
     * The language mapped to the sys_language DB entry.
     *
     * @var int
     */
    protected $languageId;

    /**
     * Locale, like 'de_CH' or 'en_GB'
     *
     * @var string
     */
    protected $locale;

    /**
     * The Base URL for this language
     *
     * @var string
     */
    protected $base;

    /**
     * Label to be used within TYPO3 to identify the language
     * @var string
     */
    protected $title;

    /**
     * The flag key (like "gb" or "fr") used to be used in TYPO3's Backend.
     * @var string
     */
    protected $flagIdentifier;

    /**
     * Prefix for TYPO3's language files
     * "default" for english, otherwise one of TYPO3's internal language keys.
     * Previously configured via TypoScript config.language = fr
     *
     * @var string
     */
    protected $labelFileLocale;

    /**
     * @var string
     */
    protected $fallbackType;
    /**
     * @var array
     */
    protected $fallbackLanguageIds = [];

    /**
     * Additional parameters configured for this site language
     * @var array
     */
    protected $attributes = [];

    /**
     * SiteLanguage constructor.
     * @param Site $site
     * @param int $languageId
     * @param string $locale
     * @param string $base
     * @param array $attributes
     */
    public function __construct(Site $site, int $languageId, string $locale, string $base, array $attributes)
    {
        $this->site = $site;
        $this->languageId = $languageId;
        $this->locale = $locale;
        $this->base = $base;
        $this->attributes = $attributes;
        $this->title = $attributes['title'] ?: 'Default';
        $this->flagIdentifier = $attributes['flag'] ?: 'gb';
        $this->labelFileLocale = $attributes['xlf'] ?: 'default';
        $this->fallbackType = $attributes['fallbackType'] ?: 'strict';
        $this->fallbackLanguageIds = $attributes['fallbacks'] ?: [];
    }

    /**
     * @return Site
     */
    public function getSite(): Site
    {
        return $this->site;
    }

    /**
     * @return int
     */
    public function getLanguageId(): int
    {
        return $this->languageId;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return string
     */
    public function getBase(): string
    {
        return $this->base;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getFlagIdentifier(): string
    {
        return $this->flagIdentifier;
    }

    /**
     * @return string
     */
    public function getLabelFileLocale(): string
    {
        return $this->labelFileLocale;
    }

    /**
     * @return string
     */
    public function getFallbackType(): string
    {
        return $this->fallbackType;
    }

    /**
     * @return array
     */
    public function getFallbackLanguageIds(): array
    {
        return $this->fallbackLanguageIds;
    }

}