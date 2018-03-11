<?php
declare(strict_types=1);

namespace TYPO3\CMS\Sites\Tests\Unit\Converter;

use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Sites\Converter\SchemaFileToTca;

class SchemaToTcaTest extends TestCase
{

    /**
     * @test
     * @return void
     */
    public function foo()
    {
        $foo = new SchemaFileToTca();
        $result = $foo->convert();
        @file_put_contents(__DIR__ . '/Result.php', "<?php \r\n return "  . var_export($result, true) . ';');
    }
}
