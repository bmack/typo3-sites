<?php
declare(strict_types=1);

namespace TYPO3\CMS\Sites\Converter\Type;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Sites\Converter\Utility\SchemaUtility;

/**
 * Class SimpleTypeConverter
 * hacky-de-hack converter
 *
 * @todo min/max values (from pattern)
 * @todo integer / number
 * @package TYPO3\CMS\Sites\Converter\Type
 */
class SimpleTypeConverter
{
    /**
     * @todo make it clever
     * @var array
     */
    private $evalPatternMapping = [
        '^[a-z0-9-_]+$' => 'nospace,lower,trim',
        '^[a-z]{2}$' => 'lower',
    ];

    private $uriPatternMapping = [
        '^t3://' => [
            'blindLinkOptions' => 'record,mail,file,folder',
            'blindLinkFields' => 'class, target, params, rel, title',
        ],
    ];

    public function convert($schema): array
    {
        $tca = [];
        $tca['label'] = $schema->title ?? '';

        // Type Specific
        if (is_array($schema->enum)) {
            $tca['config'] = $this->handleEnum($schema);
        } else if ($schema->type === 'array') {
            $tca['config'] = $this->handleArray($schema);
        } else if ($schema->type === 'string') {
            $format = ($schema->format ?? '');
            if ($format === 'uri-reference' || $format === 'uri') {
                $tca['config'] = $this->handleUri($schema);
            }
            $tca['config']['type'] = 'input';
            if (is_array($schema->examples)) {
                $tca['config']['placeholder'] = array_shift($schema->examples);
            }
            $size = $this->getGuessedSize($schema);
            if ($size) {
                $tca['config']['size'] = $size;
            }
        } else if (null === $schema->type) {
            if (is_array($schema->oneOf)) {
                $tca['config'] = $this->handleReferences($schema->oneOf);
                $tca['config']['renderType'] = 'selectSingle';
                $tca['config']['size'] = 1;
                $tca['config']['min'] = 1;
                $tca['config']['max'] = 1;
            }
        }

        // Defaults
        if ($schema->default ?? false) {
            $tca['config']['default'] = $schema->default;
        }

        // Eval // size guessing
        if ($schema->pattern ?? false) {
            $eval = $this->evalPatternMapping[$schema->pattern] ?? '';

            if ($eval !== '') {
                $tca['config']['eval'] = $eval;
            }
        }

        return $tca;
    }

    private function getGuessedSize($schema): ?int
    {
        $size = 0;
        if (is_array($schema->examples)) {
            foreach ($schema->examples as $example) {
                $exampleLength = strlen($example);
                if ($exampleLength > $size) {
                    $size = $exampleLength;
                }
            }
        }
        return $size ?: null;
    }


    protected function handleArray($schema): array
    {
        // direct references are interpreted as IRRE
        if ($schema->items && $schema->items->{'$ref'}) {
            $tca['type'] = 'inline';
            $reference = SchemaUtility::convertDefinitionNameToEntityName($schema->items->{'$ref'});
            $tca['foreign_table'] = strtolower($reference);
            $tca['foreign_table_field'] = GeneralUtility::camelCaseToLowerCaseUnderscored(
                $reference
            );
        } else if ($schema->items && $schema->items->oneOf) {
            // oneOf references are handled as select
            $tca = $this->handleReferences($schema->items->oneOf);
            $tca['renderType'] = 'selectMultipleSideBySide';
        }
        return $tca ?? [];
    }

    protected function handleEnum($schema): array
    {
        $tca['type'] = 'select';
        $tca['renderType'] = 'selectSingle';
        foreach ($schema->enum as $value) {
            $tca['items'][] = [$value];
        }
        return $tca;
    }

    protected function handleReferences(array $oneOf): array
    {
        $linkedTable = '';
        $pointer = false;
        foreach ($oneOf as $itemType) {
            if ($itemType->format === 'json-pointer' || $itemType->type === 'integer') {
                $pointer = true;
            } else if ($itemType->{'$ref'}) {
                $linkedTable = $itemType->{'$ref'};
            }
        }
        if ($pointer && $linkedTable !== '') {
            $tca['type'] = 'select';
            $tca['foreign_table'] = SchemaUtility::convertRefToEntityName($linkedTable);
        }
        return $tca ?? [];
    }

    protected function handleUri($schema): array
    {
        $tca['renderType'] = 'inputLink';
        if ($schema->pattern ?? false) {
            $linkPopUpOptions = $this->uriPatternMapping[$schema->pattern] ?? '';
            if ($linkPopUpOptions !== '') {
                $tca['fieldControl']['linkPopup'] = [
                    'options' => $linkPopUpOptions,
                ];
            }
        }
        return $tca;
    }
}