<?php
namespace Example;

/**
 * AbstractHello.
 */
abstract class AbstractHello
{
    const HELLO_ABSTRACT = 'abstract hello';

    public function baseMethod()
    {
        echo "base\n";
    }

    abstract public function abstractMethod();

    public static function staticMethod()
    {
        echo "static\n";
    }

    protected function overrideMethod()
    {
        echo "override me\n";
    }
}
