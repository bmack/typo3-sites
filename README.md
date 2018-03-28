# [FEATURE] Add Site Configuration as a new module

A new backend module allows for adding and modifying a configuration for sites
(= entrypoints to a website).

A site configuration has a unique (human-readable) identifier and the following
additional values:

- Rootpage ID
- The base path ("Base URL" / HTTP entry point, like https://www.mydomain.com/)
- The definition of language=0 (default language) of this pagetree
- Available Languages for this pagetree and their base path (https://www.mydomain.com/fr/)
- Language Configuration (fallback, strict etc)

A site configuration is stored in typo3conf/sites/site-identifier/config.yaml.

Done:
- Create a backend module
- Create edit/create functionality based on FormEngine
- Create a "Site" object containing all values of a site
- Create a PSR-15 middleware to find the correct site
- Create Links to the frontend from backend
- Tree-based view of sites in BE module
- FormEngine: Required fields
- FormEngine: Validate identifier

ToDo:
- Use site + language logic in frontend links
- Implement fallback for &L= parameter
- Build Error Handler functionality into ErrorController
- Add caching layer
- Handle fallback logic of fallbackTypes in Languages
- Create API to persist files into folder (also ensure that the folder always exists)
- Cleanup BE module (labels etc)
- Comment all code
- FormEngine: Cleanup "SiteConfigurationTCA" logic (do we need a ctrl section?)

Resolves: #?
Releases: master