<?php
namespace Example;

/**
 * Hello.
 */
final class Hello extends AbstractHello implements HelloInterface
{
    use HelloTrait;

    public function greet($name)
    {
        echo "hello $name\n";
    }

    protected function overrideMethod()
    {
        echo "overridden\n";
    }

    public function abstractMethod()
    {
        echo "abstract implemented\n";
    }

    public function traitOverride()
    {
        echo "overridden\n";
    }

    public function parameterTest(
        $a,
        &$b,
        array $arr,
        callable $callback,
        HelloInterface $other,
        $c = 1,
        $d = 4.2,
        $e = 'test',
        $f = true,
        $g = self::HELLO_ABSTRACT
    ) {
        echo "body\n";
    }
}
