<?php
namespace StaticReflection;

use ReflectionException;

class StaticReflectionMethod extends \ReflectionMethod
{
    use StaticReflectionFunctionTrait;

    /**
     * @var StaticReflectionClass
     */
    protected $declaringClass;

    /**
     * @var int
     */
    protected $modifiers;

    /**
     * @var string
     */
    protected $methodName;

    /**
     * Constructs a StaticReflectionMethod.
     *
     * @param StaticReflectionClass $declaringClass
     * @param string $docComment
     * @param int $modifiers
     * @param bool $returnsReference
     * @param string $methodName
     */
    public function __construct($declaringClass, $docComment, $modifiers, $returnsReference, $methodName)
    {
        $this->declaringClass = $declaringClass;
        $this->docComment = $docComment;
        $this->modifiers = $modifiers;
        $this->returnsReference = $returnsReference;
        $this->methodName = $methodName;
    }

    /**
     * Create mixin copy.
     *
     * @param StaticReflectionClass $declaringClass
     * @param \ReflectionMethod $method
     * @param int $visibility
     * @param string $aliasName
     *
     * @return StaticReflectionMethod
     */
    public static function copy(StaticReflectionClass $declaringClass, \ReflectionMethod $method, $visibility = null, $aliasName = null)
    {
        $modifiers = $method->getModifiers();
        if ($visibility) {
            $modifiers ^= \ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED | \ReflectionMethod::IS_PRIVATE;
            $modifiers |= $visibility;
        }
        $methodName = $aliasName ?: $method->getName();
        $copy = new StaticReflectionMethod($declaringClass, $method->getDocComment(), $modifiers, $method->returnsReference(), $methodName);
        $copy->parameters = $method->getParameters();
        if ($method instanceof StaticReflectionMethod) {
            $copy->staticVariables = $method->staticVariables;
            $copy->evaluateStaticVars = $method->evaluateStaticVars;
        } else {
            $copy->staticVariables = $method->getStaticVariables();
            $copy->evaluateStaticVars = false;
        }
        return $copy;
    }

    /**
     * Export a reflection method.
     *
     * @see http://php.net/manual/en/reflectionmethod.export.php
     *
     * @param string $class
     *   The class name.
     * @param string $name
     *   The name of the method.
     * @param bool $return
     *   Setting to TRUE will return the export, as opposed to emitting it.
     *   Setting to FALSE (the default) will do the opposite.
     *
     * @return string
     *   If the *return* parameter is set to TRUE, then the export is returned
     *   as a string, otherwise NULL is returned.
     *
     * @throws ReflectionException
     */
    public static function export($class, $name, $return = false)
    {
        throw new ReflectionException('Method not implemented');
    }

    /**
     * Returns the string representation of the Reflection method object.
     *
     * @see http://php.net/manual/en/reflectionmethod.tostring.php
     *
     * @return string
     *   A string representation of this *ReflectionMethod* instance.
     */
    public function __toString()
    {
        return $this->declaringClass->getName() . '::' . $this->methodName . '()';
    }

    /**
     * Gets extension info.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.getextension.php
     *
     * @return \ReflectionExtension
     *   The extension information, as a *ReflectionExtension* object.
     */
    public function getExtension()
    {
        return $this->declaringClass->getExtension();
    }

    /**
     * Gets extension name.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.getextensionname.php
     *
     * @return string
     *   The extensions name.
     */
    public function getExtensionName()
    {
        return $this->declaringClass->getExtensionName();
    }

    /**
     * Gets file name.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.getfilename.php
     *
     * @return string
     *   The file name.
     */
    public function getFileName()
    {
        return $this->declaringClass->getFileName();
    }

    /**
     * Get method name.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.getname.php
     *
     * @return string
     *   The name of the method.
     */
    public function getName()
    {
        return $this->methodName;
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
        return '';
    }

    /**
     * Get method short name.
     *
     * @see http://php.net/manual/en/reflectionfunctionabstract.getshortname.php
     *
     * @return string
     *   The short name of the function.
     */
    public function getShortName()
    {
        return $this->methodName;
    }

    /**
     * Checks if method is public.
     *
     * @see http://php.net/manual/en/reflectionmethod.ispublic.php
     *
     * @return bool
     *   TRUE if the method is public, otherwise FALSE.
     */
    public function isPublic()
    {
        return ($this->modifiers & self::IS_PUBLIC) === self::IS_PUBLIC;
    }

    /**
     * Checks if method is private.
     *
     * @see http://php.net/manual/en/reflectionmethod.isprivate.php
     *
     * @return bool
     *   TRUE if the method is private, otherwise FALSE.
     */
    public function isPrivate()
    {
        return ($this->modifiers & self::IS_PRIVATE) === self::IS_PRIVATE;
    }

    /**
     * Checks if method is protected.
     *
     * @see http://php.net/manual/en/reflectionmethod.isprotected.php
     *
     * @return bool
     *   TRUE if the method is protected, otherwise FALSE.
     */
    public function isProtected()
    {
        return ($this->modifiers & self::IS_PROTECTED) === self::IS_PROTECTED;
    }

    /**
     * Checks if method is abstract.
     *
     * @see http://php.net/manual/en/reflectionmethod.isabstract.php
     *
     * @return bool
     *   TRUE if the method is abstract, otherwise FALSE.
     */
    public function isAbstract()
    {
        return ($this->modifiers & self::IS_ABSTRACT) === self::IS_ABSTRACT;
    }

    /**
     * Checks if method is final.
     *
     * @see http://php.net/manual/en/reflectionmethod.isfinal.php
     *
     * @return bool
     *   TRUE if the method is final, otherwise FALSE.
     */
    public function isFinal()
    {
        return ($this->modifiers & self::IS_FINAL) === self::IS_FINAL;
    }

    /**
     * Checks if method is static.
     *
     * @see http://php.net/manual/en/reflectionmethod.isstatic.php
     *
     * @return bool
     *   TRUE if the method is static, otherwise FALSE.
     */
    public function isStatic()
    {
        return ($this->modifiers & self::IS_STATIC) === self::IS_STATIC;
    }

    /**
     * Checks if method is a constructor.
     *
     * @see http://php.net/manual/en/reflectionmethod.isconstructor.php
     *
     * @return bool
     *   TRUE if the method is a constructor, otherwise FALSE.
     */
    public function isConstructor()
    {
        return $this->methodName === '__construct';
    }

    /**
     * Checks if method is a destructor.
     *
     * @see http://php.net/manual/en/reflectionmethod.isdestructor.php
     *
     * @return bool
     *   TRUE if the method is a destructor, otherwise FALSE.
     */
    public function isDestructor()
    {
        return $this->methodName === '__destruct';
    }

    /**
     * Returns a dynamically created closure for the method.
     *
     * @see http://php.net/manual/en/reflectionmethod.getclosure.php
     *
     * @param string $object
     * Forbidden for static methods, required for other methods.
     *
     * @return \Closure
     *   Returns NULL in case of an error.
     *
     * @throws ReflectionException
     */
    public function getClosure($object)
    {
        throw new ReflectionException('Method not implemented');
    }

    /**
     * Gets the method modifiers.
     *
     * @see http://php.net/manual/en/reflectionmethod.getmodifiers.php
     *
     * @return int
     *   A numeric representation of the modifiers.
     */
    public function getModifiers()
    {
        return $this->modifiers;
    }

    /**
     * Invoke method.
     *
     * @see http://php.net/manual/en/reflectionmethod.invoke.php
     *
     * @param object $object
     *   The object to invoke the method on. For static methods, pass null to
     *   this parameter.
     *
     * @param mixed ...$parameters
     *   Zero or more parameters to be passed to the method.
     *   It accepts a variable number of parameters which are passed to the method.
     *
     * @return mixed
     *   The method result.
     */
    public function invoke($object, $arg = null)
    {
        $parameters = array_slice(func_get_args(), 1);
        return call_user_func_array(array($object, $this->methodName), $parameters);
    }

    /**
     * Invoke args.
     *
     * @see http://php.net/manual/en/reflectionmethod.invokeargs.php
     *
     * @param object $object
     *   The object to invoke the method on. In case of static methods, you can
     *   pass null to this parameter.
     *
     * @param array $args
     * The parameters to be passed to the function, as an array.
     *
     * @return mixed
     *   The method result.
     */
    public function invokeArgs($object, array $args)
    {
        return call_user_func_array(array($object, $this->methodName), $args);
    }

    /**
     * Gets declaring class for the reflected method.
     *
     * @see http://php.net/manual/en/reflectionmethod.getdeclaringclass.php
     *
     * @return StaticReflectionClass
     *   A *ReflectionClass* object of the class that the reflected method is
     *   part of.
     */
    public function getDeclaringClass()
    {
        return $this->declaringClass;
    }

    /**
     * Gets the method prototype (if there is one).
     *
     * @see http://php.net/manual/en/reflectionmethod.getprototype.php
     *
     * @return \ReflectionMethod
     *   A *ReflectionMethod* instance of the method prototype.
     *
     * @throws ReflectionException
     */
    public function getPrototype()
    {
        // @todo find out what this is and if should implement.
        throw new ReflectionException('Method not implemented');
    }

    /**
     * Set method accessibility.
     *
     * @see http://php.net/manual/en/reflectionmethod.setaccessible.php
     *
     * @param bool $accessible
     *   TRUE to allow accessibility, or FALSE.
     *
     * @throws ReflectionException
     */
    public function setAccessible($accessible)
    {
        throw new ReflectionException('Method not implemented');
    }
}
