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
     * @param string $className
     *   Fully qualified class name.
     *
     * @return \ReflectionClass
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

    public function parseFile($filename)
    {
        $this->parser->parseFile($filename);
        $this->classes += $this->parser->getClasses();
        $this->parser->clear();
    }
}
