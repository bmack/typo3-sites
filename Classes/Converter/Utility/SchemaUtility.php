<?php
declare(strict_types=1);

namespace TYPO3\CMS\Sites\Converter\Utility;


use JsonSchema\SchemaStorage;
use JsonSchema\Uri\UriResolver;
use JsonSchema\Uri\UriRetriever;

class SchemaUtility
{

    public static function convertRefToEntityName(string $name): string
    {
        $refResolver = new SchemaStorage(new UriRetriever(), new UriResolver());
        $resolved = $refResolver->resolveRef($name);
        return self::convertDefinitionNameToEntityName($resolved->items->{'$ref'});
    }

    public static function convertDefinitionNameToEntityName(string $referencedName): string
    {
        $matchCount = preg_match('/.*\#\/definitions\/([[:alnum:]]+)$/', $referencedName, $matches);
        if ($matchCount === 1) {
            $reference = $matches[1];
        }
        return $reference ?? '';
    }
}