<?php
declare(strict_types=1);

namespace TYPO3\CMS\Sites\Converter;

use JsonSchema\SchemaStorage;
use JsonSchema\Uri\UriRetriever;
use JsonSchema\Uri\UriResolver;
use TYPO3\CMS\Sites\Converter\Type\SimpleTypeConverter;


class SchemaFileToTca
{

    private $name;
    private $schema;

    public function __construct($schemaFile = '', $name = 'site')
    {
        $schemaFile = $schemaFile ?: 'file://' . dirname(__DIR__, 2) .
                                     '/Configuration/Definitions/site.json';
        $refResolver = new SchemaStorage(new UriRetriever(), new UriResolver());
        $this->schema = $refResolver->resolveRef($schemaFile);
        $this->name = $name;
    }


    public function convert()
    {
        $schemaToTca = new SchemaToTca();
        $tca = $schemaToTca->convertRootSchema($this->schema, $this->name);
        return $tca;
    }
}