<?php
namespace StaticReflection;

use ReflectionException;

class StaticReflectionClass extends \ReflectionClass
{
    const TYPE_CLASS = 1;
    const TYPE_TRAIT = 2;
    const TYPE_INTERFACE = 3;

    /**
     * @var StaticReflectionFactory
     */
    private $factory;

    /**
     * Type being reflected.
     *
     * @var int
     */
    protected $type;

    /**
     * Filename class belongs to.
     *
     * @var string
     */
    protected $filename;

    /**
     * Class doc comment.
     *
     * @var string
     */
    protected $docComment;

    /**
     * Bitmask of modifiers.
     *
     * @var int
     */
    protected $modifiers;

    /**
     * Fully qualified class name.
     *
     * @var string
     */
    protected $classFqn;

    /**
     * Fully qualified class name.
     *
     * @var string
     */
    protected $parentClassFqn;

    /**
     * Fully qualified class name of parent.
     *
     * @var string
     */
    protected $extends;

    /**
     * Fully qualified interface names.
     *
     * @var string[]
     */
    protected $interfaceNames;

    /**
     * @var array
     */
    protected $constants;

    /**
     * @var \ReflectionProperty[]
     */
    protected $properties;

    /**
     * @var \ReflectionMethod[]
     */
    protected $methods;

    /**
     * @var string[]
     */
    protected $traitNames;

    /**
     * @var array
     */
    protected $aliasRules;

    /**
     * @var array
     */
    protected $precedenceRules;

    /**
     * @var array
     */
    protected $traitAliases;

    /**
     * @var bool
     */
    protected $resolvedClasses;

    /**
     * @var bool
     */
    protected $resolvedInterfaces;

    /**
     * Constructs a StaticReflectionClass.
     *
     * @internal Use StaticReflection::getClass() instead.
     *
     * @param StaticReflectionFactory $factory
     * @param int $type
     * @param string $filename
     * @param string $docComment
     * @param int $modifiers
     * @param string $classFqn
     * @param string[] $interfaceNames
     * @param string $parentClassFqn
     */
    public function __construct($factory, $type, $filename, $docComment, $modifiers, $classFqn, $interfaceNames, $parentClassFqn = null)
    {
        $this->factory = $factory;
        $this->type = $type;
        $this->filename = $filename;
        $this->docComment = $docComment;
        $this->modifiers = $modifiers;
        $this->classFqn = $classFqn;
        $this->interfaceNames = $interfaceNames;
        $this->parentClassFqn = $parentClassFqn;
        $this->constants = [];
        $this->properties = [];
        $this->methods = [];
        $this->traitNames = [];
        $this->aliasRules = [];
        $this->precedenceRules = [];
        $this->traitAliases = null;
        $this->resolvedClasses = false;
        $this->resolvedInterfaces = false;
    }

    public function addTraitName($traitName)
    {
        $this->traitNames[] = $traitName;
    }

    public function addAliasRule($traitName, $methodName, $visibility, $aliasName)
    {
        $this->aliasRules[] = [$traitName, $methodName, $visibility, $aliasName];
    }

    public function addPrecedenceRule($traitName, $methodName, $traits)
    {
        $this->precedenceRules[] = [$traitName, $methodName, $traits];
    }

    public function addConstant($constantName, $value)
    {
        $this->constants[$constantName] = $value;
    }

    /**
     * @param \ReflectionProperty $property
     */
    public function addProperty($property)
    {
        $this->properties[$property->getName()] = $property;
    }

    /**
     * @param \ReflectionMethod $method
     */
    public function addMethod($method)
    {
        $this->methods[$method->getName()] = $method;
    }

    protected function resolveClasses()
    {
        if ($parent = $this->getParentClass()) {
            foreach ($parent->getConstants() as $constantName => $constantValue) {
                if (!isset($this->constants[$constantName])) {
                    $this->constants[$constantName] = $constantValue;
                }
            }
            foreach ($parent->getProperties() as $property) {
                if ($property->isPrivate()) {
                    // Parent private properties are hidden with Reflection API.
                    continue;
                }
                $propertyName = $property->getName();
                if (!isset($this->properties[$propertyName])) {
                    $this->properties[$propertyName] = $property;
                } else {
                    // @todo check access level matches parent property.
                }
            }
        }
        // Apply trait precedence rules.
        $traitMethods = [];
        $resolvedConflict = [];
        $traits = $this->getTraits();
        foreach ($this->precedenceRules as $rule) {
            list($ownerTraitName, $methodName, $insteadOfTraitNames) = $rule;
            if (!isset($traits[$ownerTraitName])) {
                throw new CompileError(sprintf("Required trait %s wasn't added to %s", $ownerTraitName, $this->classFqn));
            } else {
                $trait = $traits[$ownerTraitName];
                if (!$trait->hasMethod($methodName)) {
                    throw new CompileError(sprintf("A precedence rule was defined for %s::%s but this method does not exist", $ownerTraitName, $methodName));
                } else {
                    $resolvedConflict[$ownerTraitName][$methodName] = $ownerTraitName;
                    foreach ($insteadOfTraitNames as $insteadOfTraitName) {
                        if (!isset($traits[$insteadOfTraitName])) {
                            throw new CompileError(sprintf("Required trait %s wasn't added to %s", $insteadOfTraitName, $this->classFqn));
                        } else {
                            $insteadOfTrait = $traits[$insteadOfTraitName];
                            if (!$insteadOfTrait->hasMethod($methodName)) {
                                throw new CompileError(sprintf("A precedence rule was defined for %s::%s but this method does not exist", $insteadOfTraitName, $methodName));
                            } else {
                                $resolvedConflict[$insteadOfTraitName][$methodName] = $ownerTraitName;
                            }
                        }
                    }
                    $traitMethods[$methodName] = $ownerTraitName;
                }
            }
        }
        foreach ($traits as $traitName => $trait) {
            foreach ($trait->getProperties() as $property) {
                $propertyName = $property->getName();
                if (!isset($this->properties[$propertyName])) {
                    $this->properties[$propertyName] = $property;
                } else {
                    // @todo check property is compatible
                }
            }

            foreach ($trait->getMethods() as $methodName => $method) {
                if (isset($resolvedConflict[$traitName][$methodName])) {
                    $mixin = $traitMethods[$methodName] === $traitName;
                } elseif (isset($traitMethods[$methodName])) {
                    throw new CompileError(sprintf("Trait method %s::%s has not been applied because it collides with %s::%s", $traitName, $methodName, $traitMethods[$methodName], $methodName));
                } else {
                    $traitMethods[$methodName] = $traitName;
                    $mixin = !isset($this->methods[$methodName]);
                }
                if ($mixin) {
                    // @todo check method against parent method
                    $this->methods[$methodName] = StaticReflectionMethod::copy($this, $method);
                }
            }
        }
        // Trait alias
        foreach ($this->aliasRules as $rule) {
            list($ownerTraitName, $methodName, $visibility, $aliasName) = $rule;
            if ($aliasName && isset($traitMethods[$aliasName])) {
                throw new CompileError(sprintf("Trait method %s has not been applied, because there are collisions with other trait methods on %s", $aliasName, $this->classFqn));
            }
            if (!$ownerTraitName) {
                if (!isset($traitMethods[$methodName])) {
                    throw new CompileError(sprintf("An alias was defined for %s but this method does not exist", $methodName));
                } else {
                    $ownerTraitName = $traitMethods[$methodName];
                    $method = $traits[$ownerTraitName]->getMethod($methodName);
                    $this->methods[$methodName] = StaticReflectionMethod::copy($this, $method, $visibility, $aliasName);
                }
            } else {
                if (!isset($traits[$ownerTraitName])) {
                    throw new CompileError(sprintf("Required trait %s wasn't added to %s", $ownerTraitName, $this->classFqn));
                } else {
                    $trait = $traits[$ownerTraitName];
                    if (!$trait->hasMethod($methodName)) {
                        throw new CompileError(sprintf("An alias was defined for %s::%s but this method does not exist", $ownerTraitName, $methodName));
                    } else {
                        $method = $traits[$ownerTraitName]->getMethod($methodName);
                        $this->methods[$methodName] = StaticReflectionMethod::copy($this, $method, $visibility, $aliasName);
                    }
                }
            }
        }
        if ($parent) {
            foreach ($parent->getMethods() as $method) {
                $methodName = $method->getName();
                if (!isset($this->methods[$methodName])) {
                    $this->methods[$methodName] = $method;
                } else {
                    // @todo check method against parent.
                }
            }
        }
        $this->resolvedClasses = true;
    }

    protected function resolveInterfaces()
    {
        foreach ($this->getInterfaces() as $interface) {
            foreach ($interface->getConstants() as $name => $value) {
                if (!isset($this->constants[$name])) {
                    $this->constants[$name] = $value;
                } else {
                    // @todo throw error
                }
            }
            // @todo validate interface methods.
        }
        $this->resolvedInterfaces = true;
    }

    /**
     * Exports a class.
     *
     * @see http://php.net/manual/en/reflectionclass.export.php
     *
     * @param mixed $argument
     *   The reflection to export.
     *
     * @param bool $return [optional]
     *   Setting to TRUE will return the export,
     *   as opposed to emitting it. Setting to FALSE (the default) will do the opposite.
     *
     * @return string
     *   If the <i>return</i> parameter
     *   is set to TRUE, then the export is returned as a string,
     *   otherwise NULL is returned.
     *
     * @throws ReflectionException
     */
    public static function export($argument, $return = false)
    {
        throw new ReflectionException('Method not implemented');
    }

    /**
     * Gets class name.
     *
     * @see http://php.net/manual/en/reflectionclass.getname.php
     *
     * @return string
     *   The class name.
     */
    public function getName()
    {
        return $this->classFqn;
    }

    /**
     * Checks if class is defined internally by an extension, or the core.
     *
     * @see http://php.net/manual/en/reflectionclass.isinternal.php
     *
     * @return false
     */
    public function isInternal()
    {
        return false;
    }

    /**
     * Checks if user defined.
     *
     * @see http://php.net/manual/en/reflectionclass.isuserdefined.php
     *
     * @return true
     */
    public function isUserDefined()
    {
        return true;
    }

    /**
     * Checks if the class is instantiable.
     *
     * @see http://php.net/manual/en/reflectionclass.isinstantiable.php
     *
     * @return bool
     *   TRUE on success or FALSE on failure.
     *
     * @throws ReflectionException
     */
    public function isInstantiable()
    {
        throw new ReflectionException('Method not implemented');
    }

    /**
     * Returns whether this class is cloneable.
     *
     * @see http://php.net/manual/en/reflectionclass.iscloneable.php
     *
     * @return bool
     *   TRUE if the class is cloneable, FALSE otherwise.
     *
     * @throws ReflectionException
     */
    public function isCloneable()
    {
        // @todo
        throw new ReflectionException('Method not implemented');
    }

    /**
     * Gets the filename of the file in which the class has been defined.
     *
     * @see http://php.net/manual/en/reflectionclass.getfilename.php
     *
     * @return string
     *   The filename of the file in which the class has been defined.
     */
    public function getFileName()
    {
        return $this->filename;
    }

    /**
     * Gets starting line number.
     *
     * @see http://php.net/manual/en/reflectionclass.getstartline.php
     *
     * @return int
     *   The starting line number, as an integer.
     */
    public function getStartLine()
    {
        // @todo
        return 0;
    }

    /**
     * Gets end line.
     *
     * @see http://php.net/manual/en/reflectionclass.getendline.php
     *
     * @return int
     *   The ending line number of the user defined class, or FALSE if unknown.
     */
    public function getEndLine()
    {
        // @todo
        return false;
    }

    /**
     * Gets doc comments.
     *
     * @see http://php.net/manual/en/reflectionclass.getdoccomment.php
     *
     * @return string
     *   The doc comment if it exists, otherwise FALSE.
     */
    public function getDocComment()
    {
        return $this->docComment;
    }

    /**
     * Gets the constructor of the class.
     *
     * @see http://php.net/manual/en/reflectionclass.getconstructor.php
     *
     * @return StaticReflectionMethod
     *   A *ReflectionMethod* object reflecting the class' constructor, or NULL if the class
     *   has no constructor.
     */
    public function getConstructor()
    {
        // @todo
        return null;
    }

    /**
     * Checks if method is defined.
     *
     * @see http://php.net/manual/en/reflectionclass.hasmethod.php
     *
     * @param string $name
     *   Name of the method being checked for.
     *
     * @return bool TRUE if it has the method, otherwise FALSE
     */
    public function hasMethod($name)
    {
        if (isset($this->methods[$name])) {
            return true;
        }
        if (!$this->resolvedClasses) {
            $this->resolveClasses();
        }
        return isset($this->methods[$name]);
    }

    /**
     * Gets a *ReflectionMethod* for a class method.
     *
     * @see http://php.net/manual/en/reflectionclass.getmethod.php
     *
     * @param string $name
     *   The method name to reflect.
     *
     * @return StaticReflectionMethod
     *   Class method.
     */
    public function getMethod($name)
    {
        return $this->hasMethod($name) ? $this->methods[$name] : null;
    }

    /**
     * Gets an array of methods.
     *
     * @see http://php.net/manual/en/reflectionclass.getmethods.php
     *
     * @param int $filter
     *   Filter the results to include only methods with certain attributes.
     *   Defaults to no filtering.
     *
     * @return StaticReflectionMethod[]
     *   An array of methods.
     */
    public function getMethods($filter = null)
    {
        if (!$this->resolvedClasses) {
            $this->resolveClasses();
        }
        if ($filter === null) {
            return $this->methods;
        }
        $methods = [];
        foreach ($this->methods as $name => $method) {
            if ($method->getModifiers() & $filter) {
                $methods[$name] = $method;
            }
        }
        return $methods;
    }

    /**
     * Checks if property is defined.
     *
     * @see http://php.net/manual/en/reflectionclass.hasproperty.php
     *
     * @param string $name
     *   Name of the property being checked for.
     *
     * @return bool
     *   TRUE if it has the property, otherwise FALSE
     */
    public function hasProperty($name)
    {
        if (!$this->resolvedClasses) {
            if (isset($this->properties[$name])) {
                return true;
            }
            $this->resolveClasses();
        }
        return isset($this->properties[$name]);
    }

    /**
     * Gets a *ReflectionProperty* for a class's property.
     *
     * @see http://php.net/manual/en/reflectionclass.getproperty.php
     *
     * @param string $name
     *   The property name.
     *
     * @return \ReflectionProperty
     *   A class property.
     */
    public function getProperty($name)
    {
        return $this->hasProperty($name) ? $this->properties[$name] : null;
    }

    /**
     * Gets properties.
     *
     * @see http://php.net/manual/en/reflectionclass.getproperties.php
     *
     * @param int $filter
     *   The optional filter, for filtering desired property types. It's
     *   configured using the ReflectionProperty constants, and defaults
     *   to all property types.
     *
     * @return \ReflectionProperty[]
     */
    public function getProperties($filter = null)
    {
        if (!$this->resolvedClasses) {
            $this->resolveClasses();
        }
        if ($filter === null) {
            return $this->properties;
        }
        $properties = [];
        foreach ($this->properties as $name => $property) {
            if ($property->getModifiers() & $filter) {
                $properties[$name] = $property;
            }
        }
        return $properties;
    }

    /**
     * Checks if constant is defined.
     *
     * @see http://php.net/manual/en/reflectionclass.hasconstant.php
     *
     * @param string $name
     *   The name of the constant being checked for.
     *
     * @return bool
     *   TRUE if the constant is defined, otherwise FALSE.
     */
    public function hasConstant($name)
    {
        if (!$this->resolvedInterfaces) {
            if (array_key_exists($name, $this->constants)) {
                return true;
            }
            $this->resolveInterfaces();
        }
        return isset($this->constants[$name]);
    }

    /**
     * Gets constants.
     *
     * @see http://php.net/manual/en/reflectionclass.getconstants.php
     *
     * @return array
     *   An array of constants. Constant name in key, constant value in value.
     */
    public function getConstants()
    {
        if (!$this->resolvedClasses) {
            $this->resolveClasses();
        }
        if (!$this->resolvedInterfaces) {
            $this->resolveInterfaces();
        }
        return $this->constants;
    }

    /**
     * Gets defined constant.
     *
     * @see http://php.net/manual/en/reflectionclass.getconstant.php
     *
     * @param string $name
     *   Name of the constant.
     *
     * @return mixed
     *   Value of the constant.
     */
    public function getConstant($name)
    {
        return $this->hasConstant($name) ? $this->constants[$name] : null;
    }

    /**
     * Gets the interfaces.
     *
     * @see http://php.net/manual/en/reflectionclass.getinterfaces.php
     *
     * @return StaticReflectionClass[]
     *   An associative array of interfaces, with keys as interface
     *   names and the array values as *StaticReflectionClass* objects.
     *
     * @throws ReflectionException
     */
    public function getInterfaces()
    {
        $interfaces = [];
        foreach ($this->interfaceNames as $interfaceName) {
            $interface = $this->factory->getClass($interfaceName);
            if (!$interface->isInterface()) {
                // @todo
            }
            $interfaces[$interfaceName] = $interface;
        }
        return $interfaces;
    }

    /**
     * Gets the interface names.
     *
     * @see http://php.net/manual/en/reflectionclass.getinterfacenames.php
     *
     * @return string[]
     *   Interface names implemented by class.
     */
    public function getInterfaceNames()
    {
        return $this->interfaceNames;
    }

    /**
     * Checks if the class is an interface
     *
     * @see http://php.net/manual/en/reflectionclass.isinterface.php
     *
     * @return bool
     *   TRUE if interface, FALSE otherwise.
     */
    public function isInterface()
    {
        return $this->type === self::TYPE_INTERFACE;
    }

    /**
     * Returns an array of traits used by this class.
     *
     * @see http://php.net/manual/en/reflectionclass.gettraits.php
     *
     * @return StaticReflectionClass[]
     *   An array with trait names in keys and instances of trait's
     *   *StaticReflectionClass* in values.
     *   Returns NULL in case of an error.
     *
     * @throws ReflectionException
     */
    public function getTraits()
    {
        $traits = [];
        foreach ($this->traitNames as $traitName) {
            $trait = $this->factory->getClass($traitName);
            if (!$trait->isTrait()) {
                // @todo
            }
            $traits[$traitName] = $trait;
        }
        return $traits;
    }

    /**
     * Returns an array of names of traits used by this class.
     *
     * @see http://php.net/manual/en/reflectionclass.gettraitnames.php
     *
     * @return array
     *   An array with trait names in values.
     *   Returns NULL in case of an error.
     *
     * @throws ReflectionException
     */
    public function getTraitNames()
    {
        return $this->traitNames;
    }

    /**
     * Returns an array of trait aliases.
     *
     * @see http://php.net/manual/en/reflectionclass.gettraitaliases.php
     *
     * @return array
     *   An array with new method names in keys and original names (in the
     *   format "TraitName::original") in values.
     *   Returns NULL in case of an error.
     */
    public function getTraitAliases()
    {
        if ($this->traitAliases !== null) {
            return $this->traitAliases;
        }
        $this->traitAliases = [];
        foreach ($this->aliasRules as $rule) {
            list($traitName, $methodName, $visibility, $aliasName) = $rule;
            if ($aliasName) {
                if ($traitName === null) {
                    // @todo
                } else {
                    $this->traitAliases[$aliasName] = $traitName . '::' . $methodName;
                }
            }
        }
        return $this->traitAliases;
    }

    /**
     * Returns whether this is a trait.
     *
     * @see http://php.net/manual/en/reflectionclass.istrait.php
     *
     * @return bool
     *   TRUE if trait, FALSE otherwise.
     */
    public function isTrait()
    {
        return $this->type === self::TYPE_TRAIT;
    }

    /**
     * Checks if class is abstract.
     *
     * @see http://php.net/manual/en/reflectionclass.isabstract.php
     *
     * @return bool
     *   TRUE if class abstract, FALSE otherwise.
     */
    public function isAbstract()
    {
        return ($this->modifiers & self::IS_EXPLICIT_ABSTRACT) === self::IS_EXPLICIT_ABSTRACT;
    }

    /**
     * Checks if class is final.
     *
     * @see http://php.net/manual/en/reflectionclass.isfinal.php
     *
     * @return bool
     *   TRUE if class final, FALSE otherwise.
     */
    public function isFinal()
    {
        return ($this->modifiers & self::IS_FINAL) === self::IS_FINAL;
    }

    /**
     * Gets modifiers.
     *
     * @see http://php.net/manual/en/reflectionclass.getmodifiers.php
     *
     * @return int
     *   Bitmask of modifier constants.
     */
    public function getModifiers()
    {
        return $this->modifiers;
    }

    /**
     * Checks if instance of class.
     *
     * @see http://php.net/manual/en/reflectionclass.isinstance.php
     *
     * @param object $object
     *   The object being compared to.
     *
     * @return bool
     *   TRUE if object is an instance of the reflected class.
     *
     * @throws ReflectionException
     */
    public function isInstance($object)
    {
        // @todo
        throw new ReflectionException('Method not implemented');
    }

    /**
     * Creates a new class instance from given arguments.
     *
     * @see http://php.net/manual/en/reflectionclass.newinstance.php
     *
     * @param mixed ..$args
     *   Accepts a variable number of arguments which are passed to the class
     *   constructor, much like *call_user_func*.
     * @return object
     *
     * @throws ReflectionException
     */
    public function newInstance($args)
    {
        // @todo
        throw new ReflectionException('Method not implemented');
    }

    /**
     * Creates a new class instance without invoking the constructor.
     *
     * @see http://php.net/manual/en/reflectionclass.newinstancewithoutconstructor.php
     *
     * @return object
     *   Instance of class.
     *
     * @throws ReflectionException
     */
    public function newInstanceWithoutConstructor()
    {
        throw new ReflectionException('Method not implemented');
    }

    /**
     * Creates a new class instance from given arguments.
     *
     * @see http://php.net/manual/en/reflectionclass.newinstanceargs.php
     *
     * @param array $args
     *   The parameters to be passed to the class constructor as an array.
     *
     * @return object
     *   A new instance of the class.
     *
     * @throws ReflectionException
     */
    public function newInstanceArgs(array $args = null)
    {
        // @todo
        throw new ReflectionException('Method not implemented');
    }

    /**
     * Get parent class name.
     *
     * @return null|string
     *   Parent class name.
     */
    public function getParentClassName()
    {
        return $this->parentClassFqn;
    }

    /**
     * Get parent class.
     *
     * @see http://php.net/manual/en/reflectionclass.getparentclass.php
     *
     * @return StaticReflectionClass
     *   Parent class.
     */
    public function getParentClass()
    {
        return $this->parentClassFqn ? $this->factory->getClass($this->parentClassFqn) : false;
    }

    /**
     * Checks if a subclass.
     *
     * @see http://php.net/manual/en/reflectionclass.issubclassof.php
     *
     * @param string $class
     *   The class name being checked against.
     *
     * @return bool
     *   TRUE if subclass, FALSE otherwise.
     *
     * @throws ReflectionException
     */
    public function isSubclassOf($class)
    {
        // @todo
        throw new ReflectionException('Method not implemented');
    }

    /**
     * Gets static properties.
     *
     * @see http://php.net/manual/en/reflectionclass.getstaticproperties.php
     *
     * @return array
     *   The static properties, as an array.
     *
     * @throws ReflectionException
     */
    public function getStaticProperties()
    {
        // @todo
        throw new ReflectionException('Method not implemented');
    }

    /**
     * Gets static property value.
     *
     * @see http://php.net/manual/en/reflectionclass.getstaticpropertyvalue.php
     *
     * @param string $name
     *   The name of the static property for which to return a value.
     * @param mixed $default
     *
     * @return mixed
     *   The value of the static property.
     *
     * @throws ReflectionException
     */
    public function getStaticPropertyValue($name, $default = null)
    {
        // @todo
        throw new ReflectionException('Method not implemented');
    }

    /**
     * Sets static property value.
     *
     * @see http://php.net/manual/en/reflectionclass.setstaticpropertyvalue.php
     *
     * @param string $name
     *   Property name.
     * @param string $value
     *   New property value.
     *
     * @throws ReflectionException
     */
    public function setStaticPropertyValue($name, $value)
    {
        throw new ReflectionException('Method not implemented');
    }

    /**
     * Gets default properties.
     *
     * @see http://php.net/manual/en/reflectionclass.getdefaultproperties.php
     *
     * @return array
     *   An array of default properties, with the key being the name of
     *   the property and the value being the default value of the property or NULL
     *   if the property doesn't have a default value. The function does not distinguish
     *   between static and non static properties and does not take visibility modifiers
     *   into account.
     */
    public function getDefaultProperties()
    {
        $properties = [];
        foreach ($this->properties as $property) {
            // @todo
            $properties[$property->getName()] = $property->getDefaultValue();
        }
        return $properties;
    }

    /**
     * Checks if iterateable.
     *
     * @see http://php.net/manual/en/reflectionclass.isiterateable.php
     *
     * @return bool
     *   TRUE if class is iterateable, FALSE otherwise.
     *
     * @throws ReflectionException
     */
    public function isIterateable()
    {
        // @todo
        throw new ReflectionException('Method not implemented');
    }

    /**
     * Checks if implements interface.
     *
     * @see http://php.net/manual/en/reflectionclass.implementsinterface.php
     *
     * @param string $interface
     *   The interface name.
     *
     * @return bool
     *   TRUE if class implements interface, FALSE otherwise.
     */
    public function implementsInterface($interface)
    {
        $interface = ltrim($interface, '\\');
        return in_array($interface, $this->interfaceNames);
    }

    /**
     * Get *ReflectionExtension* object for the extension which defined the class.
     *
     * @see http://php.net/manual/en/reflectionclass.getextension.php
     *
     * @return \ReflectionExtension
     *   A *ReflectionExtension* object representing the extension which defined the class,
     *   or NULL for user-defined classes.
     */
    public function getExtension()
    {
        return null;
    }

    /**
     * Gets the name of the extension which defined the class.
     *
     * @see http://php.net/manual/en/reflectionclass.getextensionname.php
     *
     * @return string
     *   The name of the extension which defined the class, or FALSE for user-defined classes.
     *
     * @throws ReflectionException
     */
    public function getExtensionName()
    {
        return false;
    }

    /**
     * Checks if in namespace.
     *
     * @see http://php.net/manual/en/reflectionclass.innamespace.php
     *
     * @return bool
     *   TRUE if class is in namespace, FALSE otherwise.
     */
    public function inNamespace()
    {
        $parts = explode('\\', $this->classFqn);
        return count($parts) > 1;
    }

    /**
     * Get namespace name
     *
     * @see http://php.net/manual/en/reflectionclass.getnamespacename.php
     *
     * @return string
     *   The namespace name.
     */
    public function getNamespaceName()
    {
        $parts = explode('\\', $this->classFqn);
        $n = count($parts);
        if ($n === 1) {
            return '';
        }
        unset($parts[$n - 1]);
        return implode('\\', $parts);
    }

    /**
     * Get short name.
     *
     * @see http://php.net/manual/en/reflectionclass.getshortname.php
     *
     * @return string
     *   The class short name.
     */
    public function getShortName()
    {
        $parts = explode('\\', $this->classFqn);
        return end($parts);
    }
}
