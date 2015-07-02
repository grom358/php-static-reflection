<?php
namespace StaticReflection;

/**
 * Interface for finding file that class is defined in.
 */
interface ClassFinderInterface {
    /**
     * Finds the path to the file where the class is defined.
     *
     * @param string $className
     *   Fully qualified class name.
     * @return string|false
     *   The path if found, false otherwise.
     */
    public function findClassFile($className);
}
