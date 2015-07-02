<?php
namespace Example;

/**
 * HelloTrait.
 */
trait HelloTrait
{
    public function say()
    {
        echo "hello\n";
    }

    public function traitOverride()
    {
        echo "override me\n";
    }
}
