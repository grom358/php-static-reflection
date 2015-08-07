<?php
namespace StaticReflection;

/**
 * Factory for loading static reflection classes.
 */
class StaticReflectionFactory {
    /**
     * @var ClassFinderInterface
     */
    private $classFinder;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var StaticReflectionClass[]
     */
    private $classes;

    /**
     * @var StaticReflectionFunction[]
     */
    private $functions;

    /**
     * Construct StaticReflectionFactory.
     *
     * @param ClassFinderInterface $classFinder
     */
    public function __construct(ClassFinderInterface $classFinder)
    {
        $this->classFinder = $classFinder;
        $this->parser = new Parser($this);
        $this->classes = [];
        $this->functions = [];
    }

    /**
     * Get ReflectionClass.
     *
     * @param string $className
     *   Fully qualified class name.
     *
     * @return StaticReflectionClass
     *   Reflection class.
     *
     * @throws \ReflectionException
     *   Class not found.
     */
    public function getClass($className)
    {
        if (isset($this->classes[$className])) {
            return $this->classes[$className];
        }
        $filename = $this->classFinder->findClassFile($className);
        if ($filename === false) {
            throw new \ReflectionException("Class $className does not exist");
        }
        $this->parseFile($filename);
        if (!isset($this->classes[$className])) {
            throw new \ReflectionException("Class $className does not exist");
        }
        return $this->classes[$className];
    }

    /**
     * Unload StaticReflectionClass from factory cache.
     *
     * @param string $className
     *   Fully qualified class name.
     */
    public function unloadClass($className) {
        unset($this->classes[$className]);
    }

    /**
     * Parse file.
     *
     * @param string $filename
     *   Filename to parse.
     */
    public function parseFile($filename)
    {
        $this->parser->parseFile($filename);
        $this->classes += $this->parser->getClasses();
        $this->functions += $this->parser->getFunctions();
        $this->parser->clear();
    }

    /**
     * Get ReflectionFunction.
     *
     * There is no autoload mechanism for functions so call ::parseFile()
     * on file containing function first.
     *
     * @param string $functionName
     *   Fully qualified function name.
     *
     * @return StaticReflectionFunction
     *   Reflection function.
     *
     * @throws \ReflectionException
     *   Function not found.
     */
    public function getFunction($functionName)
    {
        if (!isset($this->functions[$functionName])) {
            throw new \ReflectionException("Function $functionName() does not exist");
        }
        return $this->functions[$functionName];
    }

    /**
     * Unload StaticReflectionFunction from factory cache.
     *
     * @param string $functionName
     *   Fully qualified function name.
     */
    public function unloadFunction($functionName) {
        unset($this->functions[$functionName]);
    }
}
