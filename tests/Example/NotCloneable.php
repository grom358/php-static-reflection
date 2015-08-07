<?php
namespace Example;

class NotCloneable {
    private function __clone()
    {
        // Not cloneable.
    }
}
