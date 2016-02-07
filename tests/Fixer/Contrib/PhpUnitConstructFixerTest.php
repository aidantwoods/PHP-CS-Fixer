<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tests\Fixer\Contrib;

use PhpCsFixer\Fixer\Contrib\PhpUnitConstructFixer;
use PhpCsFixer\Test\AbstractFixerTestCase;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class PhpUnitConstructFixerTest extends AbstractFixerTestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Configured method "MyTest" cannot be fixed by this fixer.
     */
    public function testInvalidConfiguration()
    {
        /** @var $fixer PhpUnitConstructFixer */
        $fixer = $this->getFixer();
        $fixer->configure(array('MyTest' => 'test'));
    }

    /**
     * @dataProvider provideTestFixCases
     */
    public function testFix($expected, $input = null)
    {
        $fixer = $this->getFixer();

        $fixer->configure(array(
            'assertEquals' => true,
            'assertSame' => true,
            'assertNotEquals' => true,
            'assertNotSame' => true,
        ));
        $this->doTest($expected, $input, null, $fixer);

        $fixer->configure(array(
            'assertEquals' => false,
            'assertSame' => false,
            'assertNotEquals' => false,
            'assertNotSame' => false,
        ));
        $this->doTest($input ?: $expected, null, null, $fixer);

        foreach (array('assertSame', 'assertEquals', 'assertNotEquals', 'assertNotSame') as $method) {
            $config = array(
                'assertEquals' => false,
                'assertSame' => false,
                'assertNotEquals' => false,
                'assertNotSame' => false,
            );
            $config[$method] = true;

            $fixer->configure($config);
            $this->doTest(
                $expected,
                $input && false !== strpos($input, $method) ? $input : null,
                null,
                $fixer
            );
        }
    }

    public function provideTestFixCases()
    {
        $cases = array(
            array('<?php $sth->assertSame(true, $foo);'),
            array('<?php $this->assertSame($b, null);'),
            array(
                '<?php
    $this->assertTrue(
        $a,
        "foo" . $bar
    );',
                '<?php
    $this->assertSame(
        true,
        $a,
        "foo" . $bar
    );',
            ),
            array(
                '<?php $this->assertNull(/*bar*/ $a);',
                '<?php $this->assertSame(null /*foo*/, /*bar*/ $a);',
            ),
            array(
                '<?php $this->assertSame(null === $eventException ? $exception : $eventException, $event->getException());',
            ),
            array(
                '<?php $this->assertSame(null /*comment*/ === $eventException ? $exception : $eventException, $event->getException());',
            ),
        );

        return array_merge(
            $cases,
            $this->generateCases('<?php $this->assert%s%s($a); //%s %s', '<?php $this->assert%s(%s, $a); //%s %s'),
            $this->generateCases('<?php $this->assert%s%s($a, "%s", "%s");', '<?php $this->assert%s(%s, $a, "%s", "%s");')
        );
    }

    /**
     * @expectedException \PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException
     * @expectedExceptionMessage [php_unit_construct] Configured method "__TEST__" cannot be fixed by this fixer.
     */
    public function testInvalidConfig()
    {
        /** @var PhpUnitConstructFixer $fixer */
        $fixer = $this->getFixer();
        $fixer->configure(array('__TEST__' => 'abc'));
    }

    private function generateCases($expectedTemplate, $inputTemplate)
    {
        $cases = array();
        $functionTypes = array('Same' => true, 'NotSame' => false, 'Equals' => true, 'NotEquals' => false);
        foreach (array('true', 'false', 'null') as $type) {
            foreach ($functionTypes as $method => $positive) {
                $cases[] = array(
                    sprintf($expectedTemplate, $positive ? '' : 'Not', ucfirst($type), $method, $type),
                    sprintf($inputTemplate, $method, $type, $method, $type),
                );
            }
        }

        return $cases;
    }
}