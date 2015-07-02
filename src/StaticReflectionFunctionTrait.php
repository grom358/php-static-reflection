<?php
namespace StaticReflection;

trait StaticReflectionFunctionTrait
{
    /**
     * @var string
     */
    protected $docComment = false;

    /**
     * @var bool
     */
    protected $returnsReference;

    /**
     * @var \ReflectionParameter[]
     */
    protected $parameters = [];

    /**
     * @var array
     */
    protected $staticVariables = [];

    /**
     * @var bool
     */
    protected $evaluateStaticVars = false;

    /**
     * @param \ReflectionParameter $parameter
     */
    public function addParameter(\ReflectionParameter $parameter)
    {
        $this->parameters[] = $parameter;
    }

    /**
     * @param string $variableName
     * @param mixed $value
     */
    public function addStaticVariable($variableName, $value)
    {
        if ($value instanceof ScalarExpression) {
            $this->evaluateStaticVars = true;
        }
        $this->staticVariables[$variableName] = $value;
    }

    /**
     * Checks if function in namespace.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.innamespace.php
     *
     * @return false
     */
    public function inNamespace()
    {
        return false;
    }

    /**
     * Checks if closure.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.isclosure.php
     *
     * @return false
     */
    public function isClosure()
    {
        return false;
    }

    /**
     * Checks if deprecated.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.isdeprecated.php
     *
     * @return bool
     *   TRUE if it's deprecated, otherwise FALSE
     */
    public function isDeprecated()
    {
        // @todo look at doc comment.
        return false;
    }

    /**
     * Checks if is internal.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.isinternal.php
     *
     * @return bool
     *   TRUE if it's internal, otherwise FALSE
     */
    public function isInternal()
    {
        // @todo look at doc comment.
        return false;
    }

    /**
     * Checks if user defined.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.isuserdefined.php
     *
     * @return bool
     *   TRUE if it's user-defined, otherwise false;
     */
    public function isUserDefined()
    {
        return true;
    }

    /**
     * Returns this pointer bound to closure.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.getclosurethis.php
     *
     * @return object $this pointer.
     *   Returns NULL in case of an error.
     */
    public function getClosureThis()
    {
        // @todo
        return null;
    }

    /**
     * Returns the scope associated to the closure.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.getclosurescopeclass.php
     *
     * @return mixed
     *   Returns the class on success or NULL on failure.
     */
    public function getClosureScopeClass()
    {
        // @todo
        return null;
    }

    /**
     * Gets doc comment.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.getdoccomment.php
     *
     * @return string
     *   The doc comment if it exists, otherwise FALSE.
     */
    public function getDocComment()
    {
        return $this->docComment;
    }

    /**
     * Gets end line number.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.getendline.php
     *
     * @return int
     *   The ending line number of the user defined function, or FALSE if unknown.
     */
    public function getEndLine()
    {
        // @todo
        return false;
    }

    /**
     * Gets number of parameters.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.getnumberofparameters.php
     *
     * @return int
     *   The number of parameters.
     */
    public function getNumberOfParameters()
    {
        return count($this->parameters);
    }

    /**
     * Gets number of required parameters.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.getnumberofrequiredparameters.php
     *
     * @return int
     *   The number of required parameters.
     */
    public function getNumberOfRequiredParameters()
    {
        // @todo
        return 0;
    }

    /**
     * Get parameters.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.getparameters.php
     *
     * @return \ReflectionParameter[]
     *   The parameters, as a ReflectionParameter objects.
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Gets starting line number.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.getstartline.php
     *
     * @return int
     *   The starting line number.
     */
    public function getStartLine()
    {
        // @todo
        return 0;
    }

    /**
     * Get static variables.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.getstaticvariables.php
     *
     * @return array
     *   An array of static variables.
     */
    public function getStaticVariables()
    {
        if ($this->evaluateStaticVars) {
            foreach ($this->staticVariables as $name => $value) {
                if ($value instanceof ScalarExpression) {
                    $this->staticVariables[$name] = $value->evaluate();
                }
            }
        }
        return $this->staticVariables;
    }

    /**
     * Checks if returns reference.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.returnsreference.php
     *
     * @return bool
     *   TRUE if it returns a reference, otherwise FALSE.
     */
    public function returnsReference()
    {
        return $this->returnsReference;
    }

    /**
     * Get *ReflectionExtension* object for the extension which defined the function.
     *
     * @see http://php.net/manual/en/reflectionclass.getextension.php
     *
     * @return \ReflectionExtension
     *   A *ReflectionExtension* object representing the extension which defined the function,
     *   or NULL for user-defined function.
     */
    public function getExtension()
    {
        return null;
    }

    /**
     * Gets the name of the extension which defined the function.
     *
     * @see http://php.net/manual/en/reflectionclass.getextensionname.php
     *
     * @return string
     *   The name of the extension which defined the class, or FALSE for user-defined function.
     */
    public function getExtensionName()
    {
        return false;
    }
}
