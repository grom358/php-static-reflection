<?php
namespace StaticReflection;

use Composer\Autoload\ClassLoader;

/**
 * Uses composer autoloader to implement ClassFinderInterface.
 */
class ComposerFinder implements ClassFinderInterface {
    /**
     * @var ClassLoader
     */
    private $loader;

    /**
     * Construct ComposerFinder.
     *
     * @param ClassLoader $loader
     *   Composer class loader.
     */
    public function __construct(ClassLoader $loader)
    {
        $this->loader = $loader;
    }

    public function findClassFile($className)
    {
        return $this->loader->findFile($className);
    }
}
