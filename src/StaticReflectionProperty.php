<?php
namespace StaticReflection;

use ReflectionException;

class StaticReflectionProperty extends \ReflectionProperty
{
    /**
     * @var StaticReflectionClass
     */
    protected $declaringClass;

    /**
     * @var string
     */
    protected $docComment;

    /**
     * @var int
     */
    protected $modifiers;

    /**
     * @var string
     */
    protected $propertyName;

    /**
     * @var bool
     */
    protected $hasDefaultValue;

    /**
     * @var mixed
     */
    protected $defaultValue;

    /**
     * Constructs a StaticReflectionProperty.
     *
     * @param StaticReflectionClass $declaringClass
     * @param string $docComment
     * @param int $modifiers
     * @param string $propertyName
     * @param bool $hasDefaultValue
     * @param mixed $defaultValue
     */
    public function __construct($declaringClass, $docComment, $modifiers, $propertyName, $hasDefaultValue, $defaultValue)
    {
        $this->declaringClass = $declaringClass;
        $this->docComment = $docComment;
        $this->modifiers = $modifiers;
        $this->propertyName = $propertyName;
        $this->hasDefaultValue = $hasDefaultValue;
        $this->defaultValue = $defaultValue;
    }

    /**
     * Export.
     *
     * @see http://php.net/manual/en/reflectionproperty.export.php
     *
     * @param mixed $class
     * @param string $name
     *   The property name.
     * @param bool $return
     *   Setting to TRUE will return the export,
     *   as opposed to emitting it. Setting to FALSE (the default) will do the opposite.
     *
     * @return string
     *
     * @throws ReflectionException
     */
    public static function export($class, $name, $return = null)
    {
        throw new ReflectionException('Method not implemented');
    }

    /**
     * To string.
     *
     * @see http://php.net/manual/en/reflectionproperty.tostring.php
     *
     * @return string
     */
    public function __toString()
    {
        // @todo
    }

    /**
     * Gets property name.
     *
     * @see http://php.net/manual/en/reflectionproperty.getname.php
     * @return string The name of the reflected property.
     */
    public function getName()
    {
        return $this->propertyName;
    }

    /**
     * Gets value.
     *
     * @see http://php.net/manual/en/reflectionproperty.getvalue.php
     *
     * @param object $object
     *   If the property is non-static an object must be provided to fetch the
     *   property from. If you want to fetch the default property without
     *   providing an object use *ReflectionClass::getDefaultProperties*
     *   instead.
     *
     * @return mixed
     *   The current value of the property.
     *
     * @throws ReflectionException
     */
    public function getValue($object = null)
    {
        if ($object) {
            return $object->{$this->propertyName};
        } elseif ($this->isStatic()) {
            $class = $this->getDeclaringClass()->getName();
            $propertyName = $this->propertyName;
            return $class::$propertyName;
        } else {
            // @todo
            return null;
        }
    }

    /**
     * Default property value.
     *
     * @return mixed
     *   The default value.
     */
    public function getDefaultValue()
    {
        if ($this->defaultValue instanceof ScalarExpression) {
            $this->defaultValue = $this->defaultValue->evaluate();
        }
        return $this->defaultValue;
    }

    /**
     * Set property value.
     *
     * @see http://php.net/manual/en/reflectionproperty.setvalue.php
     *
     * @param object $object
     *   If the property is non-static an object must be provided to change
     *   the property on. If the property is static this parameter is left
     *   out and only *value* needs to be provided.
     *
     * @param mixed $value
     *   The new value.
     *
     * @throws ReflectionException
     */
    public function setValue($object, $value = null)
    {
        throw new ReflectionException('Method not implemented');
    }

    /**
     * Checks if property is public.
     *
     * @see http://php.net/manual/en/reflectionproperty.ispublic.php
     *
     * @return bool
     *   TRUE if the property is public, FALSE otherwise.
     */
    public function isPublic()
    {
        return $this->modifiers & self::IS_PUBLIC;
    }

    /**
     * Checks if property is private.
     *
     * @see http://php.net/manual/en/reflectionproperty.isprivate.php
     *
     * @return bool
     *   TRUE if the property is private, FALSE otherwise.
     */
    public function isPrivate()
    {
        return $this->modifiers & self::IS_PRIVATE;
    }

    /**
     * Checks if property is protected.
     *
     * @see http://php.net/manual/en/reflectionproperty.isprotected.php
     *
     * @return bool
     *   TRUE if the property is protected, FALSE otherwise.
     */
    public function isProtected()
    {
        return $this->modifiers & self::IS_PROTECTED;
    }

    /**
     * Checks if property is static.
     *
     * @see http://php.net/manual/en/reflectionproperty.isstatic.php
     *
     * @return bool
     *   TRUE if the property is static, FALSE otherwise.
     */
    public function isStatic()
    {
        return $this->modifiers & self::IS_STATIC;
    }

    /**
     * Checks if default value.
     *
     * @see http://php.net/manual/en/reflectionproperty.isdefault.php
     *
     * @return bool
     *   TRUE if the property was declared at compile-time, or FALSE if
     *   it was created at run-time.
     */
    public function isDefault()
    {
        return $this->hasDefaultValue;
    }

    /**
     * Gets modifiers.
     *
     * @see http://php.net/manual/en/reflectionproperty.getmodifiers.php
     *
     * @return int
     *   A numeric representation of the modifiers.
     */
    public function getModifiers()
    {
        return $this->modifiers;
    }

    /**
     * Gets declaring class.
     *
     * @see http://php.net/manual/en/reflectionproperty.getdeclaringclass.php
     *
     * @return StaticReflectionClass
     *   A *ReflectionClass* object.
     */
    public function getDeclaringClass()
    {
        return $this->declaringClass;
    }

    /**
     * Gets doc comment.
     *
     * @see http://php.net/manual/en/reflectionproperty.getdoccomment.php
     *
     * @return string
     *   The doc comment.
     */
    public function getDocComment()
    {
        return $this->docComment;
    }

    /**
     * Set property accessibility.
     *
     * @see http://php.net/manual/en/reflectionproperty.setaccessible.php
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
