<?php
namespace StaticReflection;

use ReflectionException;

class StaticReflectionFunction extends \ReflectionFunction
{
    use StaticReflectionFunctionTrait;

    /**
     * @var string
     */
    protected $functionName;

    /**
     * Constructs a StaticReflectionFunction.
     *
     * @param string $docComment
     * @param bool $returnsReference
     * @param string $functionName
     */
    public function __construct($docComment, $returnsReference, $functionName)
    {
        $this->docComment = $docComment;
        $this->returnsReference = $returnsReference;
        $this->functionName = $functionName;
    }

    /**
     * Get function name.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.getname.php
     *
     * @return string
     *   The name of the function.
     */
    public function getName()
    {
        return $this->functionName;
    }

    /**
     * Gets namespace name.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.getnamespacename.php
     *
     * @return string
     *   The namespace name.
     */
    public function getNamespaceName()
    {
        $parts = explode('\\', $this->functionName);
        $n = count($parts);
        if ($n === 1) {
            return '';
        }
        unset($parts[$n - 1]);
        return implode('\\', $parts);
    }

    /**
     * Get function short name.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.getshortname.php
     *
     * @return string
     *   The short name of the function.
     */
    public function getShortName()
    {
        $parts = explode('\\', $this->functionName);
        return end($parts);
    }

    /**
     * To string.
     *
     * @see http://php.net/manual/en/reflectionfunction.tostring.php
     *
     * @return string
     *   *ReflectionFunction::export*-like output for the function.
     */
    public function __toString()
    {
        // @todo
        return '';
    }

    /**
     * Exports function.
     *
     * @see http://php.net/manual/en/reflectionfunction.export.php
     *
     * @param string $name
     *   The reflection to export.
     * @param string $return
     *   Setting to TRUE will return the export,
     *   as opposed to emitting it. Setting to FALSE (the default) will do the opposite.
     *
     * @return string
     *   If the *return* parameter
     *   is set to TRUE, then the export is returned as a string,
     *   otherwise NULL is returned.
     *
     * @throws ReflectionException
     */
    public static function export($name, $return = null)
    {
        throw new ReflectionException('Method not implemented');
    }

    /**
     * Checks if function is disabled.
     *
     * @see http://php.net/manual/en/reflectionfunction.isdisabled.php
     *
     * @return bool
     *   TRUE if it's disable, otherwise FALSE.
     */
    public function isDisabled()
    {
        // @todo
        return false;
    }

    /**
     * Invokes function.
     *
     * @see http://php.net/manual/en/reflectionfunction.invoke.php
     *
     * @param string ...$args
     *   The passed in argument list. It accepts a variable number of
     *   arguments which are passed to the function much like
     *   call_user_func is.
     *
     * @return mixed
     */
    public function invoke($args = null)
    {
        $args = func_get_args();
        return call_user_func_array($this->functionName, $args);
    }

    /**
     * Invokes function args.
     *
     * @see http://php.net/manual/en/reflectionfunction.invokeargs.php
     *
     * @param array $args
     *   The passed arguments to the function as an array, much like
     *   *call_user_func_array* works.
     *
     * @return mixed
     *   The result of the invoked function
     */
    public function invokeArgs(array $args)
    {
        return call_user_func_array($this->functionName, $args);
    }

    /**
     * Returns a dynamically created closure for the function.
     *
     * @see http://php.net/manual/en/reflectionfunction.getclosure.php
     *
     * @return \Closure
     */
    public function getClosure()
    {
        // @todo
        return null;
    }
}
