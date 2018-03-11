<?php
declare(strict_types=1);

namespace TYPO3\CMS\Sites\Converter;


use TYPO3\CMS\Sites\Converter\Type\SimpleTypeConverter;

class SchemaToTca
{
    private $entities = [
        'language',
        'errorHandling'
    ];

    public function convertRootSchema(\stdClass $schema, string $entityName): array
    {
        $tca = [];
        foreach ($schema->definitions ?? [] as $name => $definition) {
            if (in_array($name, $this->entities, true)) {
                // convert to own tca
                $tca = array_merge($tca, $this->convertRootSchema($definition, $name));
            }
        }
        $tca[$entityName] = $this->convert($schema);
        return $tca;
    }

    private function convert($schema): array
    {
        $simpleTypeConverter = new SimpleTypeConverter();
        $tca = [];
        foreach ($schema->properties ?? [] as $name => $property) {
            $tca[$name] = $simpleTypeConverter->convert($property);
            if ($property->{'$ref'}) {
                // recursion
                $converter = new SchemaFileToTca($property->{'$ref'}, $name);
                $subTca = $converter->convert();
                foreach ($subTca[$name] ?? [] as $subPropName => $subProperty) {
                    $tca[$name . '_' . $subPropName] = $subProperty;
                }
            }
        }
        return $tca;
    }
}