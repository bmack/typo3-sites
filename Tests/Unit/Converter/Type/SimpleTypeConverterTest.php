<?php
declare(strict_types=1);

namespace TYPO3\CMS\Sites\Tests\Unit\Converter\Type;

use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Sites\Converter\Type\SimpleTypeConverter;

class SimpleTypeConverterTest extends TestCase
{

    /**
     * @test
     * @return void
     */
    public function simpleSelectFromStringEnum(): void
    {
        $json = '{
          "type": "string",
          "title": "Fallback Type",
          "description": "",
          "enum": [
            "strict", "fallback"
          ]
        }';
        $dummy = \json_decode($json);

        $expectedTca =
            [
                'label' => 'Fallback Type',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        // @todo labels
                        ['strict'],
                        ['fallback'],
                    ],
                ],
            ];

        $stringConverter = new SimpleTypeConverter();
        $result = $stringConverter->convert($dummy);

        self::assertSame($expectedTca, $result);
    }

    /**
     * @test
     * @return void
     */
    public function convertUriReferenceFields(): void
    {
        $json = '{
          "type":"string",
          "format":"uri-reference",
          "title":"EntryPoint",
          "description":"",
          "default":"",
          "examples":["\/","https:\/\/example.com\/"]
      }
      ';
        $dummy = \json_decode($json);

        $expectedTca = [
            'label' => 'EntryPoint',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'placeholder' => '/',
                'size' => 20
            ],
        ];

        $stringConverter = new SimpleTypeConverter();
        $result = $stringConverter->convert($dummy);

        self::assertEquals($expectedTca, $result);
    }

    /**
     * @test
     * @return void
     */
    public function convertSimpleStringFields(): void
    {
        $json = '{"title":"Fluid Template","description":"(use SITES:syntax if you like)","type":"string"}';
        $dummy = \json_decode($json);

        $expectedTca = [
            'label' => 'Fluid Template',
            'config' => [
                'type' => 'input',
            ],
        ];

        $stringConverter = new SimpleTypeConverter();
        $result = $stringConverter->convert($dummy);

        self::assertSame($expectedTca, $result);
    }

    /**
     * @test
     * @return void
     */
    public function convertExamplesToPlaceholder(): void
    {
        $json = '{"title":"Fluid Template","description":"(use SITES:syntax if you like)","type":"string", "examples":["foo", "SITES:main-site/1234"]}';
        $dummy = \json_decode($json);

        $expectedTca = [
            'label' => 'Fluid Template',
            'config' => [
                'type' => 'input',
                'placeholder' => 'foo',
                'size' => '20'
            ],
        ];

        $stringConverter = new SimpleTypeConverter();
        $result = $stringConverter->convert($dummy);

        self::assertEquals($expectedTca, $result);
    }

    /**
     * @test
     * @return void
     */
    public function convertReferencesToSingleSelect(): void
    {
        $json = '
        {
          "title": "Language",
          "oneOf": [
            {
              "type": "integer"
            },
            {"$ref": "file:\/\/D:\\\CoreDev\\\Extensions\\\typo3-sites\/Configuration\/Definitions\/site.json#\/definitions/sys_language"}
          ]
        }
        ';
        $dummy = \json_decode($json);

        $expectedTca = [
            'label' => 'Language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'size' => 1,
                'min' => 1,
                'max' => 1
            ],
        ];

        $stringConverter = new SimpleTypeConverter();
        $result = $stringConverter->convert($dummy);

        self::assertEquals($expectedTca, $result);
    }

    /**
     * @test
     * @return void
     */
    public function convertUriReferencePagesOnly(): void
    {
        $json = '{
          "title": "Show content from page",
          "description": "",
          "type": "string",
          "format": "uri",
          "pattern": "^t3://"
        }';
        $dummy = \json_decode($json);

        $expectedTca = [
            'label' => 'Show content from page',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'fieldControl' => [
                    'linkPopup' => [
                        'options' => [
                            'blindLinkOptions' => 'record,mail,file,folder',
                            'blindLinkFields' => 'class, target, params, rel, title',
                        ],
                    ],
                ],
            ],
        ];

        $stringConverter = new SimpleTypeConverter();
        $result = $stringConverter->convert($dummy);

        self::assertEquals($expectedTca, $result);
    }

    /**
     * @test
     * @return void
     */
    public function convertWithEval(): void
    {
        $json = '{
          "title": "Site Identifier",
          "description": "",
          "type": "string",
          "pattern": "^[a-z0-9-_]+$"
        }';
        $dummy = \json_decode($json);

        $expectedTca = [
            'label' => 'Site Identifier',
            'config' => [
                'type' => 'input',
                'eval' => 'nospace,lower,trim',
            ],
        ];

        $stringConverter = new SimpleTypeConverter();
        $result = $stringConverter->convert($dummy);

        self::assertSame($expectedTca, $result);
    }

    /**
     * @test
     * @return void
     */
    public function convertWithSizeFromExamples(): void
    {
        $json = '{
          "title": "Site Identifier",
          "description": "",
          "type": "string",
          "pattern": "^[a-z0-9-_]+$",
          "examples": [
            "main-site", "my-super-project_landingpage-super8"
          ]
        }';
        $dummy = \json_decode($json);

        $expectedTca = [
            'label' => 'Site Identifier',
            'config' => [
                'type' => 'input',
                'placeholder' => 'main-site',
                'eval' => 'nospace,lower,trim',
                'size' => 35
            ],
        ];

        $stringConverter = new SimpleTypeConverter();
        $result = $stringConverter->convert($dummy);

        self::assertEquals($expectedTca, $result);
    }

    /**
     * @test
     * @return void
     */
    public function convertSetsDefault(): void
    {
        $json = '{
          "type": "string",
          "title": "Fallback Type",
          "description": "",
          "default": "strict",
          "enum": [
            "strict", "fallback"
          ]
        }';
        $dummy = \json_decode($json);

        $stringConverter = new SimpleTypeConverter();
        $result = $stringConverter->convert($dummy);

        self::assertSame('strict', $result['config']['default']);
    }

    /**
     * @test
     * @return void
     */
    public function convertHandlesArraysWithRefItemsAsInline(): void
    {
        $json = '{
            "title": "Error Handling",
            "description": "",
            "type": "array",
            "uniqueItems": true,
            "items": {
                "$ref": "file:\/\/D:\\\CoreDev\\\Extensions\\\typo3-sites\/Configuration\/Definitions\/site.json#\/definitions\/errorHandling"
            }
        }';

        $dummy = \GuzzleHttp\json_decode($json);

        $expectedTca = [
            'label' => 'Error Handling',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'errorhandling',
                'foreign_table_field' => 'error_handling',
            ],
        ];

        $stringConverter = new SimpleTypeConverter();
        $result = $stringConverter->convert($dummy);

        self::assertSame($expectedTca, $result);
    }

    /**
     * @test
     * @return void
     */
    public function convertHandlesArraysWithOneOfItems(): void
    {
        $json = '{
            "title": "Fallbacks",
            "description": "",
            "type": "array",
            "uniqueItems": true,
            "items": {
                "oneOf": [{
                    "type": "string",
                    "format": "json-pointer"
                }, {
                    "$ref": "file:\/\/D:\\\CoreDev\\\Extensions\\\typo3-sites\/Configuration\/Definitions\/site.json#\/properties\/availableLanguages"
                }]
            }
        }';

        $dummy = \json_decode($json);

        $expectedTca = [
            'label' => 'Fallbacks',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'language'
                // @todo foreign_table_where
            ],
        ];

        $stringConverter = new SimpleTypeConverter();
        $result = $stringConverter->convert($dummy);

        self::assertEquals($expectedTca, $result);
    }
}
