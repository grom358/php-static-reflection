<?php
namespace StaticReflection;

use ReflectionException;

class StaticReflectionParameter extends \ReflectionParameter
{
    /**
     * @var StaticReflectionMethod
     */
    protected $declaringFunction;

    /**
     * @var int
     */
    protected $position;

    /**
     * @var string|StaticReflectionClass
     */
    protected $typeHint;

    /**
     * @var bool
     */
    protected $passByReference;

    /**
     * @var bool
     */
    protected $variadic;

    /**
     * @var string
     */
    protected $parameterName;

    /**
     * @var bool
     */
    protected $hasDefaultValue;

    /**
     * @var mixed
     */
    protected $defaultValue;

    /**
     * @var string
     */
    protected $defaultValueConstant;

    /**
     * Construct a StaticReflectionParameter.
     *
     * @param StaticReflectionMethod $declaringFunction
     * @param int $position
     * @param string|StaticReflectionClass $typeHint
     * @param bool $passByReference
     * @param bool $variadic
     * @param string $parameterName
     * @param bool $hasDefaultValue
     * @param mixed $defaultValue
     * @param string $defaultValueConstant
     */
    public function __construct($declaringFunction, $position, $typeHint, $passByReference, $variadic, $parameterName, $hasDefaultValue, $defaultValue, $defaultValueConstant) {
        $this->declaringFunction = $declaringFunction;
        $this->position = $position;
        $this->typeHint = $typeHint;
        $this->passByReference = $passByReference;
        $this->variadic = $variadic;
        $this->parameterName = $parameterName;
        $this->hasDefaultValue = $hasDefaultValue;
        $this->defaultValue = $defaultValue;
        $this->defaultValueConstant = $defaultValueConstant;
    }

    /**
     * Export.
     *
     * @see http://php.net/manual/en/reflectionparameter.export.php
     *
     * @param string $function
     *   The function name.
     * @param string $parameter
     *   The parameter name.
     * @param bool $return
     *   Setting to TRUE will return the export,
     *   as opposed to emitting it. Setting to FALSE (the default) will do the opposite.
     *
     * @return string
     *   The exported reflection.
     *
     * @throws ReflectionException
     */
    public static function export($function, $parameter, $return = null)
    {
        throw new ReflectionException('Method not implemented');
    }

    /**
     * To string.
     *
     * @see http://php.net/manual/en/reflectionparameter.tostring.php
     *
     * @return string
     */
    public function __toString()
    {
        // @todo
    }

    /**
     * Gets parameter name.
     *
     * @see http://php.net/manual/en/reflectionparameter.getname.php
     *
     * @return string
     *   The name of the reflected parameter.
     */
    public function getName()
    {
        return $this->parameterName;
    }

    /**
     * Checks if passed by reference.
     *
     * @see http://php.net/manual/en/reflectionparameter.ispassedbyreference.php
     *
     * @return bool
     *   TRUE if the parameter is passed in by reference, otherwise FALSE.
     */
    public function isPassedByReference()
    {
        return $this->passByReference;
    }

    /**
     * Returns whether this parameter can be passed by value.
     *
     * @see http://php.net/manual/en/reflectionparameter.canbepassedbyvalue.php
     *
     * @return bool
     *   TRUE if the parameter can be passed by value, FALSE otherwise.
     */
    public function canBePassedByValue()
    {
        return !$this->passByReference;
    }

    /**
     * Checks if the parameter is variadic.
     *
     * @see http://php.net/manual/en/reflectionparameter.isvariadic.php
     *
     * @return bool
     *   TRUE if the parameter is variadic, FALSE otherwise.
     */
    public function isVariadic()
    {
        return $this->variadic;
    }

    /**
     * Gets declaring function.
     *
     * @see http://php.net/manual/en/reflectionparameter.getdeclaringfunction.php
     *
     * @return StaticReflectionMethod
     *   The function where this parameter was declared.
     */
    public function getDeclaringFunction()
    {
        return $this->declaringFunction;
    }

    /**
     * Gets declaring class.
     *
     * @see http://php.net/manual/en/reflectionparameter.getdeclaringclass.php
     *
     * @return StaticReflectionClass
     *   The class where this parameter was declared.
     */
    public function getDeclaringClass()
    {
        return $this->declaringFunction->getDeclaringClass();
    }

    /**
     * Get class.
     *
     * @see http://php.net/manual/en/reflectionparameter.getclass.php
     *
     * @return StaticReflectionClass
     *   The class of type hint.
     */
    public function getClass()
    {
        return is_string($this->typeHint) ? NULL : $this->typeHint;
    }

    /**
     * Checks if parameter expects an array.
     *
     * @see http://php.net/manual/en/reflectionparameter.isarray.php
     *
     * @return bool
     *   TRUE if an array is expected, FALSE otherwise.
     */
    public function isArray()
    {
        return $this->typeHint === 'array';
    }

    /**
     * Returns whether parameter MUST be callable.
     *
     * @see http://php.net/manual/en/reflectionparameter.iscallable.php
     *
     * @return bool
     *   Returns TRUE if the parameter is callable, FALSE if it is not.
     */
    public function isCallable()
    {
        return $this->typeHint === 'callable';
    }

    /**
     * Checks if null is allowed.
     *
     * @see http://php.net/manual/en/reflectionparameter.allowsnull.php
     *
     * @return bool
     *   TRUE if NULL is allowed, otherwise FALSE
     */
    public function allowsNull()
    {
        return $this->typeHint === null;
    }

    /**
     * Gets parameter position.
     *
     * @see http://php.net/manual/en/reflectionparameter.getposition.php
     *
     * @return int
     *   The position of the parameter, left to right, starting at position 0.
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Checks if optional.
     *
     * @see http://php.net/manual/en/reflectionparameter.isoptional.php
     *
     * @return bool
     *   TRUE if the parameter is optional, otherwise FALSE
     */
    public function isOptional()
    {
        // @todo check if only parameters with default are optional.
        return $this->hasDefaultValue;
    }

    /**
     * Checks if a default value is available.
     *
     * @see http://php.net/manual/en/reflectionparameter.isdefaultvalueavailable.php
     *
     * @return bool
     *   TRUE if a default value is available, otherwise FALSE
     */
    public function isDefaultValueAvailable()
    {
        return $this->hasDefaultValue;
    }

    /**
     * Gets default parameter value.
     *
     * @see http://php.net/manual/en/reflectionparameter.getdefaultvalue.php
     *
     * @return mixed
     *   The parameters default value.
     */
    public function getDefaultValue()
    {
        if ($this->defaultValue instanceof ScalarExpression) {
            $this->defaultValue = $this->defaultValue->evaluate();
        }
        return $this->defaultValue;
    }

    /**
     * @return boolean
     */
    public function isDefaultValueConstant()
    {
        return $this->defaultValueConstant !== null;
    }

    /**
     * @return string
     */
    public function getDefaultValueConstantName()
    {
        return $this->defaultValueConstant;
    }
}
