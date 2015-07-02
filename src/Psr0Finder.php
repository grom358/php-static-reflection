<?php
namespace StaticReflection;

/**
 * Finds classes using PSR-0 convention.
 */
class Psr0Finder implements ClassFinderInterface {
    /**
     * @var string
     */
    private $baseDir;

    public function __construct($baseDir) {
        $this->baseDir = $baseDir;
    }

    public function findClassFile($className)
    {
        $className = ltrim($className, '\\');
        $classPath = str_replace('\\', '/', $className);
        $fileName = $this->baseDir . '/' . $classPath . '.php';
        return file_exists($fileName) ? $fileName : false;
    }
}
