<?php
namespace StaticReflection;

/**
 * A lazy evaluated scalar expression.
 */
class ScalarExpression {
    /**
     * @var StaticReflectionFactory
     */
    protected $factory;

    /**
     * Each element is an array containing two elements:
     *   class/interface name and the constant name.
     *
     * @var array
     */
    protected $classes;

    /**
     * Values are the constant names.
     *
     * @var array
     */
    protected $constants;

    /**
     * PHP scalar expression with placeholder values.
     *
     * @var string
     */
    protected $expression;

    /**
     * @param StaticReflectionFactory $factory
     * @param string $expression
     * @param array $classes
     * @param array $constants
     */
    public function __construct($factory, $expression, $classes, $constants)
    {
        $this->factory = $factory;
        $this->expression = $expression;
        $this->classes = $classes;
        $this->constants = $constants;
    }

    /**
     * @return mixed Result of scalar expression.
     *   Result of scalar expression.
     *
     * @throws CompileError
     *   Thrown on invalid scalar expression.
     */
    public function evaluate()
    {
        $replacements = [];
        foreach ($this->classes as $i => $class) {
            list($classFqn, $constantName) = $class;
            $class = $this->factory->getClass($classFqn);
            $constantValue = $class->getConstant($constantName);
            $replacements['@' . $i] = var_export($constantValue, true);
        }
        foreach ($this->constants as $i => $constantName) {
            // @todo handle undefined constant.
            $replacements['`' . $i] = var_export(constant($constantName), true);
        }
        $snippet = strtr($this->expression, $replacements);
        $v = null;
        $ret = @eval('static $v = ' . $snippet . ';' . PHP_EOL);
        if ($ret === false) {
            throw new CompileError('Syntax error evaluating scalar expression "' . $snippet . '"');
        }
        return $v;
    }
}
