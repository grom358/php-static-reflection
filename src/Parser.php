<?php
namespace StaticReflection;

/**
 * A PHP 5.6 compatible parser.
 *
 * Scans top level statements for class, interface, traits, and function declarations.
 */
class Parser
{
    /**
     * @var StaticReflectionFactory
     */
    private $factory;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var array
     */
    private $tokens;

    /**
     * @var int
     */
    private $pos;

    /**
     * @var int
     */
    private $length;

    /**
     * @var int|string
     */
    private $tokenType;

    /**
     * @var string
     */
    private $tokenText;

    /**
     * @var string
     */
    private $namespace;

    /**
     * Associative array of alias to fully qualified name.
     *
     * @var array
     */
    private $aliases;

    /**
     * @var string
     */
    private $docComment;

    /**
     * Associative array of fully qualified class name to StaticReflectionClass.
     *
     * @var StaticReflectionClass[]
     */
    private $classes;

    /**
     * Associative array of fully qualified functions to StaticReflectionFunction.
     *
     * @var StaticReflectionFunction[]
     */
    private $functions;

    /**
     * Current class name.
     *
     * @var string
     */
    private $className;

    /**
     * Current trait name.
     *
     * @var string
     */
    private $traitName;

    /**
     * Current method name.
     *
     * @var string
     */
    private $methodName;

    /**
     * Current function name.
     *
     * @var string
     */
    private $functionName;

    /**
     * Construct Parser.
     *
     * @param StaticReflectionFactory $factory
     */
    public function __construct($factory)
    {
        $this->factory = $factory;
        $this->className = '';
        $this->traitName = '';
        $this->methodName = '';
        $this->functionName = '';
    }

    /**
     * @return StaticReflectionClass[]
     */
    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * @return StaticReflectionFunction[]
     */
    public function getFunctions()
    {
        return $this->functions;
    }

    public function parseFile($filename)
    {
        $this->filename = $filename;
        $contents = file_get_contents($filename);
        $this->parse($contents);
    }

    public function parseSource($source)
    {
        $this->filename = null;
        $this->parse($source);
    }

    protected function parse($source)
    {
        $this->tokens = token_get_all($source);
        $this->pos = 0;
        $this->length = count($this->tokens);
        $this->docComment = false;
        $this->namespace = '';
        $this->classes = [];
        $this->functions = [];
        $this->aliases = [];
        $this->nextToken();

        $this->template();
        if ($this->tokenType) {
            try {
                $this->code();
            } catch (HaltCompilerException $e) {
                // halt compiler aborts the parse.
            }
        }
    }

    public function clear()
    {
        $this->filename = null;
        $this->tokens = null;
        $this->pos = 0;
        $this->length = 0;
        $this->docComment = false;
        $this->namespace = null;
        $this->aliases = null;
        $this->classes = null;
        $this->functions = null;
    }

    private function nextToken()
    {
        $this->docComment = false;
        if ($this->pos >= $this->length) {
            $this->tokenType = NULL;
            $this->tokenText = NULL;
        } else {
            do {
                $token = $this->tokens[$this->pos++];
                if (is_array($token)) {
                    $this->tokenType = $token[0];
                    $this->tokenText = $token[1];
                } else {
                    $this->tokenType = $this->tokenText = $token;
                }
                if ($this->tokenType === T_DOC_COMMENT) {
                    $this->docComment = $this->tokenText;
                }
            } while ($this->pos < $this->length && ($this->tokenType === T_WHITESPACE || $this->tokenType === T_COMMENT || $this->tokenType === T_DOC_COMMENT));
            if ($this->tokenType === T_WHITESPACE || $this->tokenType === T_COMMENT || $this->tokenType === T_DOC_COMMENT) {
                $this->tokenType = NULL;
                $this->tokenText = NULL;
            }
        }
    }

    private function getLineNumber() {
        $pos = min($this->pos, $this->length - 1);
        $token = $this->tokens[$pos];
        while ($pos > 0 && !is_array($token)) {
            $token = $this->tokens[--$pos];
        }
        if ($pos === 0 && !is_array($token)) {
            $lineNo = 1;
        } else {
            $lineNo = $token[2] + substr_count($token[1], "\n");
        }
        return $lineNo;
    }

    private function error($message) {
        $lineNo = $this->getLineNumber();
        throw new ParserException($this->filename, $lineNo, $message);
    }

    private function expected($expected)
    {
        $actual = $this->tokenText ?: 'end of file';
        $this->error("Expected $expected but got $actual");
    }

    private function match($expectedType)
    {
        if ($expectedType !== $this->tokenType) {
            $this->expected(is_string($expectedType) ? $expectedType : token_name($expectedType));
        }
        $tokenText = $this->tokenText;
        $this->nextToken();
        return $tokenText;
    }

    private function optMatch($expectedType)
    {
        if ($expectedType === $this->tokenType) {
            $tokenText = $this->tokenText;
            $this->nextToken();
            return $tokenText;
        }
        return null;
    }

    private function isMatch($expectedType)
    {
        return $this->optMatch($expectedType) !== null;
    }

    private function isLookAhead($expected_type, $ignore = null)
    {
        for ($offset = 0; $this->pos + $offset < $this->length; $offset++) {
            $token = $this->tokens[$this->pos + $offset];
            $type = is_array($token) ? $token[0] : $token;
            if ($type === T_WHITESPACE || $type === T_COMMENT) {
                continue;
            }
            if ($type === $expected_type) {
                return true;
            } elseif ($ignore === null || $type !== $ignore) {
                return false;
            }
        }
        return false;
    }

    private function lookAhead($skip = [])
    {
        for ($offset = 0; $this->pos + $offset < $this->length; $offset++) {
            $token = $this->tokens[$this->pos + $offset];
            $type = is_array($token) ? $token[0] : $token;
            if ($type === T_WHITESPACE || $type === T_COMMENT) {
                continue;
            }
            if (in_array($type, $skip)) {
                continue;
            }
            return $type;
        }
        return null;
    }

    private function block()
    {
        $this->match('{');
        $braceCount = 1;
        while ($this->tokenType && $braceCount > 0) {
            if ($this->tokenType === '{') {
                $braceCount++;
            } elseif ($this->tokenType === '}') {
                $braceCount--;
            }
            $this->nextToken();
        }
        if ($braceCount > 0) {
            $this->expected('}');
        }
    }

    private function matchEndStatement()
    {
        if ($this->isMatch(T_CLOSE_TAG)) {
            $this->template();
            if ($this->tokenType) {
                $this->match(T_OPEN_TAG);
            }
        } else {
            $this->match(';');
        }
    }

    private function template()
    {
        while ($this->tokenType === T_INLINE_HTML) {
            $this->nextToken();

            // Skip over <?= style sections.
            if ($this->tokenType === T_OPEN_TAG_WITH_ECHO) {
                do {
                    $this->nextToken();
                } while ($this->tokenType && $this->tokenType !== T_CLOSE_TAG);
                $this->tokenType && $this->match(T_CLOSE_TAG);
            }
        }
    }

    private function code()
    {
        $this->match(T_OPEN_TAG);
        while ($this->tokenType) {
            $this->topStatement();
        }
    }

    private function topStatement()
    {
        switch ($this->tokenType) {
            case T_USE:
                $this->useStatement();
                break;
            case T_ABSTRACT:
            case T_FINAL:
            case T_CLASS:
                $this->classDeclaration();
                break;
            case T_INTERFACE:
                $this->interfaceDeclaration();
                break;
            case T_TRAIT:
                $this->traitDeclaration();
                break;
            case T_CLOSE_TAG:
                $this->match(T_CLOSE_TAG);
                $this->template();
                $this->tokenType && $this->match(T_OPEN_TAG);
                break;
            case T_HALT_COMPILER:
                $this->match(T_HALT_COMPILER);
                $this->match('(');
                $this->match(')');
                $this->isMatch(T_CLOSE_TAG) || $this->match(';');
                throw new HaltCompilerException();
            default:
                if ($this->tokenType === T_FUNCTION && $this->isLookAhead(T_STRING, '&')) {
                    $this->functionDeclaration();
                } elseif ($this->tokenType === T_NAMESPACE && !$this->isLookAhead(T_NS_SEPARATOR)) {
                    $this->_namespace();
                } else {
                    $this->statement();
                }
                break;
        }
    }

    private function qualifiedName(&$baseName = null)
    {
        $fullPath = null;
        if ($this->tokenType === T_NAMESPACE) {
            $this->match(T_NAMESPACE);
            $fullPath = $this->namespace;
            $subPath = '';
            do {
                $subPath .= $this->match(T_NS_SEPARATOR);
                $baseName = $this->match(T_STRING);
                $subPath .= $baseName;
            } while ($this->tokenType && $this->tokenType === T_NS_SEPARATOR);
            $fullPath .= $subPath;
        } elseif ($this->tokenType === T_STRING) {
            $fullPath = $baseName = $this->match(T_STRING);
            if (isset($this->aliases[$fullPath])) {
                $fullPath = $this->aliases[$fullPath];
            } else {
                $fullPath = ($this->namespace ? $this->namespace . '\\' : '') . $fullPath;
            }
            while ($sep = $this->optMatch(T_NS_SEPARATOR)) {
                $baseName = $this->match(T_STRING);
                $fullPath .= $sep . $baseName;
            }
        } elseif ($this->tokenType === T_NS_SEPARATOR) {
            $fullPath = '';
            do {
                $fullPath .= $this->match(T_NS_SEPARATOR);
                $baseName = $this->match(T_STRING);
                $fullPath .= $baseName;
            } while ($this->tokenType && $this->tokenType === T_NS_SEPARATOR);
            $fullPath = substr($fullPath, 1);
        }
        return $fullPath;
    }

    private function fullyQualifiedName()
    {
        $this->isMatch(T_NS_SEPARATOR);
        $path = $baseName = $this->match(T_STRING);
        while ($sep = $this->optMatch(T_NS_SEPARATOR)) {
            $baseName = $this->match(T_STRING);
            $path .= $sep . $baseName;
        }
        return [$path, $baseName];
    }

    private function useStatement()
    {
        $this->match(T_USE);
        if ($this->tokenType === T_CONST || $this->tokenType === T_FUNCTION) {
            // skip processing use statement.
            do {
                $this->nextToken();
            } while ($this->tokenType && $this->tokenType !== ';');
            $this->matchEndStatement();
            return;
        }
        // Create alias.
        do {
            list ($fullPath, $aliasName) = $this->fullyQualifiedName();
            if ($this->isMatch(T_AS)) {
                $aliasName = $this->match(T_STRING);
            }
            $this->aliases[$aliasName] = $fullPath;
        } while ($this->optMatch(','));
        $this->matchEndStatement();
    }

    private function classDeclaration()
    {
        $docComment = $this->docComment;
        $modifiers = $this->isMatch(T_FINAL) ? \ReflectionClass::IS_FINAL : 0;
        if ($modifiers === 0) {
            $modifiers = $this->isMatch(T_ABSTRACT) ? \ReflectionClass::IS_EXPLICIT_ABSTRACT : 0;
        }
        $this->match(T_CLASS);
        $className = $this->match(T_STRING);
        $classFqn = $this->namespace ? $this->namespace . '\\' . $className : $className;
        $this->className = $classFqn;
        $parentClassFqn = null;
        if ($this->optMatch(T_EXTENDS)) {
            $parentClassFqn = $this->qualifiedName();
        }
        $implements = [];
        if ($this->optMatch(T_IMPLEMENTS)) {
            do {
                $implements[] = $this->qualifiedName();
            } while ($this->optMatch(','));
        }
        $class = new StaticReflectionClass(
            $this->factory,
            StaticReflectionClass::TYPE_CLASS,
            $this->filename,
            $docComment,
            $modifiers,
            $classFqn,
            $implements,
            $parentClassFqn
        );
        $this->classBody($class);
        $this->classes[$classFqn] = $class;
        $this->className = '';
    }

    /**
     * @param StaticReflectionClass $class
     */
    private function classBody($class)
    {
        $this->match('{');
        while ($this->tokenType && $this->tokenType !== '}') {
            switch ($this->tokenType) {
                case T_CONST:
                    $this->classConstant($class);
                    break;
                case T_VAR:
                    $this->classProperty($class);
                    break;
                case T_FUNCTION:
                    $this->classMethod($class);
                    break;
                case T_PUBLIC:
                case T_PROTECTED:
                case T_PRIVATE:
                case T_STATIC:
                case T_ABSTRACT:
                case T_FINAL:
                    $lookAhead = $this->lookAhead([T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_ABSTRACT, T_FINAL]);
                    if ($lookAhead === T_FUNCTION) {
                        $this->classMethod($class);
                    } elseif ($lookAhead === T_VARIABLE) {
                        $this->classProperty($class);
                    } elseif ($this->tokenType) {
                        $this->expected('method');
                    }
                    break;
                case T_USE:
                    $this->traitUse($class);
                    break;
                default:
                    $this->expected('class statement');
                    break;
            }
        }
        $this->match('}');
    }

    /**
     * @param StaticReflectionClass $class
     */
    private function classConstant($class)
    {
        $this->match(T_CONST);
        do {
            $constantName = $this->match(T_STRING);
            $this->match('=');
            $value = $this->scalarExpression();
            $class->addConstant($constantName, $value);
        } while ($this->isMatch(','));
        $this->matchEndStatement();
    }

    /**
     * @param StaticReflectionClass $class
     */
    private function classProperty($class)
    {
        static $accessBitMask = \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE;
        $docComment = $this->docComment;
        $modifiers = 0;
        $find = true;
        while ($find) {
            $accessModifier = $modifiers & $accessBitMask;
            switch ($this->tokenType) {
                case T_VAR:
                case T_PUBLIC:
                case T_PROTECTED:
                case T_PRIVATE:
                    if ($accessModifier) {
                        $this->error('Multiple access modifiers not allowed');
                    }
                    break;
            }
            switch ($this->tokenType) {
                case T_VAR:
                    $this->match(T_VAR);
                    $modifiers |= \ReflectionProperty::IS_PUBLIC;
                    break;
                case T_PUBLIC:
                    $this->match(T_PUBLIC);
                    $modifiers |= \ReflectionProperty::IS_PUBLIC;
                    break;
                case T_PROTECTED:
                    $this->match(T_PROTECTED);
                    $modifiers |= \ReflectionProperty::IS_PROTECTED;
                    break;
                case T_PRIVATE:
                    $this->match(T_PRIVATE);
                    $modifiers |= \ReflectionProperty::IS_PRIVATE;
                    break;
                case T_ABSTRACT:
                    $this->error('Properties cannot be declared abstract');
                    break;
                case T_FINAL:
                    $this->error('Cannot declare property final, the final modifier is allowed only for methods and classes');
                    break;
                case T_STATIC:
                    $this->match(T_STATIC);
                    if ($modifiers & \ReflectionProperty::IS_STATIC) {
                        $this->error('Multiple static modifiers not allowed');
                    }
                    $modifiers |= \ReflectionProperty::IS_STATIC;
                    break;
                case T_VARIABLE:
                    $find = false;
                    break;
                default:
                    $this->expected('function');
                    break;
            }
        }
        do {
            $variableName = $this->match(T_VARIABLE);
            $propertyName = ltrim($variableName, '$');
            $defaultValue = null;
            if ($hasDefaultValue = $this->isMatch('=')) {
                $defaultValue = $this->scalarExpression();
            }
            $class->addProperty(new StaticReflectionProperty($class, $docComment, $modifiers, $propertyName, $hasDefaultValue, $defaultValue));
        } while ($this->isMatch(','));
        $this->matchEndStatement();
    }

    private function classMethod($class)
    {
        static $accessBitMask = \ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED | \ReflectionMethod::IS_PRIVATE;
        static $finalAbstractMask = \ReflectionMethod::IS_ABSTRACT | \ReflectionMethod::IS_FINAL;
        $modifiers = 0;
        $find = true;
        while ($find) {
            $accessModifier = $modifiers & $accessBitMask;
            switch ($this->tokenType) {
                case T_PUBLIC:
                case T_PROTECTED:
                case T_PRIVATE:
                    if ($accessModifier) {
                        $this->error('Multiple access modifiers not allowed');
                    }
                    break;
                case T_FINAL:
                case T_ABSTRACT:
                    if (($modifiers & $finalAbstractMask) === $finalAbstractMask) {
                        $this->error('Cannot use the final modifier on an abstract class method');
                    }
                    break;
            }
            switch ($this->tokenType) {
                case T_PUBLIC:
                    $this->match(T_PUBLIC);
                    $modifiers |= \ReflectionMethod::IS_PUBLIC;
                    break;
                case T_PROTECTED:
                    $this->match(T_PROTECTED);
                    $modifiers |= \ReflectionMethod::IS_PROTECTED;
                    break;
                case T_PRIVATE:
                    $this->match(T_PRIVATE);
                    $modifiers |= \ReflectionMethod::IS_PRIVATE;
                    break;
                case T_ABSTRACT:
                    $this->match(T_ABSTRACT);
                    if ($modifiers & \ReflectionMethod::IS_ABSTRACT) {
                        $this->error('Multiple abstract modifiers not allowed');
                    }
                    $modifiers |= \ReflectionMethod::IS_ABSTRACT;
                    break;
                case T_FINAL:
                    $this->match(T_FINAL);
                    if ($modifiers & \ReflectionMethod::IS_FINAL) {
                        $this->error('Multiple final modifiers not allowed');
                    }
                    $modifiers |= \ReflectionMethod::IS_FINAL;
                    break;
                case T_STATIC:
                    $this->match(T_STATIC);
                    if ($modifiers & \ReflectionMethod::IS_STATIC) {
                        $this->error('Multiple static modifiers not allowed');
                    }
                    $modifiers |= \ReflectionMethod::IS_STATIC;
                    break;
                case T_FUNCTION:
                    $find = false;
                    break;
                default:
                    $this->expected('function');
                    break;
            }
        }
        $this->method($class, $modifiers, $modifiers & \ReflectionMethod::IS_ABSTRACT);
    }

    /**
     * @param StaticReflectionClass $class
     */
    private function traitUse($class)
    {
        $this->match(T_USE);
        do {
            $class->addTraitName($this->qualifiedName());
        } while ($this->isMatch(','));
        if ($this->isMatch('{')) {
            while (!$this->isMatch('}')) {
                if ($this->isLookAhead(T_AS)) {
                    $traitName = null;
                    $methodName = $this->match(T_STRING);
                } else {
                    $traitName = $this->qualifiedName();
                    $this->match(T_DOUBLE_COLON);
                    $methodName = $this->match(T_STRING);
                }
                if ($traitName === null || $this->tokenType === T_AS) {
                    $this->match(T_AS);
                    $visibility = null;
                    if ($this->tokenType === T_PRIVATE || $this->tokenType === T_PROTECTED || $this->tokenType === T_PUBLIC) {
                        $visibility = $this->tokenType;
                        $this->nextToken();
                    }
                    if ($visibility === null || $this->tokenType === T_STRING) {
                        $aliasName = $this->match(T_STRING);
                    } else {
                        $aliasName = null;
                    }
                    $class->addAliasRule($traitName, $methodName, $visibility, $aliasName);
                } else {
                    $this->match(T_INSTEADOF);
                    $insteadOf = [];
                    do {
                        $insteadOf[] = $this->qualifiedName();
                    } while ($this->isMatch(','));
                    $class->addPrecedenceRule($traitName, $methodName, $insteadOf);
                }
                $this->matchEndStatement();
            }
        } else {
            $this->matchEndStatement();
        }
    }

    /**
     * @param StaticReflectionClass $class
     * @param int $modifiers
     * @param bool $abstract
     */
    private function method($class, $modifiers, $abstract)
    {
        $docComment = $this->docComment;
        $this->match(T_FUNCTION);
        $returnsReference = $this->isMatch('&');
        $methodName = $this->match(T_STRING);
        $this->methodName = $this->className . '::' . $methodName;
        $method = new StaticReflectionMethod($class, $docComment, $modifiers, $returnsReference, $methodName);
        $this->parameterList($method);
        if ($abstract) {
            $this->matchEndStatement();
        } else {
            $this->functionBody($method);
        }
        $class->addMethod($method);
        $this->methodName = '';
    }

    /**
     * @param StaticReflectionMethod|StaticReflectionFunction $function
     */
    private function functionBody($function)
    {
        $this->match('{');
        $braceCount = 1;
        $startStatement = true;
        while ($this->tokenType && $braceCount > 0) {
            if ($startStatement && $this->tokenType === T_STATIC && $this->isLookAhead(T_VARIABLE)) {
                $this->match(T_STATIC);
                do {
                    $variableName = $this->match(T_VARIABLE);
                    $value = null;
                    if ($this->isMatch('=')) {
                        $value = $this->scalarExpression();
                    }
                    $function->addStaticVariable($variableName, $value);
                } while ($this->isMatch(','));
                $this->matchEndStatement();
                continue;
            }
            switch ($this->tokenType) {
                case '{':
                case T_CURLY_OPEN:
                case T_DOLLAR_OPEN_CURLY_BRACES:
                    $braceCount++;
                    $startStatement = true;
                    break;
                case '}':
                    $braceCount--;
                    $startStatement = true;
                    break;
                case ';':
                    $startStatement = true;
                    break;
                default:
                    $startStatement = false;
                    break;
            }

            $this->nextToken();
        }
        if ($braceCount > 0) {
            $this->expected('}');
        }
    }

    /**
     * @return mixed|ScalarExpression
     *
     * @throws ParserException
     */
    private function scalarExpression()
    {
        // Possible terminating token for scalar expression is one of:
        //   - Class constant ; or ,
        //   - Class property ; or ,
        //   - Parameter default value , or )
        //   - Static variable default value , or ;
        // The terminators ) and , are ignored while inside
        // an array or parentheses.
        $parenCount = 0;
        $bracketCount = 0;
        $snippet = '';
        $constantIndex = 0;
        $constants = [];
        $classIndex = 0;
        $classes = [];
        while ($this->tokenType && $this->tokenType !== ';' && !($parenCount === 0 && $bracketCount === 0 && ($this->tokenType === ')' || $this->tokenType === ','))) {
            if ($this->tokenType === T_STRING) {
                $keyword = strtolower($this->tokenText);
                if ($keyword === 'true' || $keyword === 'false' || $keyword === 'null') {
                    $this->match(T_STRING);
                    $snippet .= $keyword . ' ';
                    continue;
                } elseif ($keyword === 'self') {
                    $this->match(T_STRING);
                    $this->match(T_DOUBLE_COLON);
                    $constantName = $this->match(T_STRING);
                    $snippet .= "@$classIndex";
                    $classes[] = [$this->className, $constantName];
                    $classIndex++;
                    continue;
                }
            } elseif ($this->tokenType === T_START_HEREDOC) {
                $snippet .= $this->match(T_START_HEREDOC);
                if ($this->tokenType !== T_END_HEREDOC) {
                    $snippet .= $this->match(T_ENCAPSED_AND_WHITESPACE);
                }
                $snippet .= $this->match(T_END_HEREDOC) . PHP_EOL;
                continue;
            }
            switch ($this->tokenType) {
                case T_LOGICAL_OR:
                case T_LOGICAL_XOR:
                case T_LOGICAL_AND:
                case '?':
                case ':':
                case T_BOOLEAN_OR:
                case T_BOOLEAN_AND:
                case '|':
                case '^':
                case '&':
                case T_IS_EQUAL:
                case T_IS_IDENTICAL:
                case T_IS_NOT_EQUAL:
                case T_IS_NOT_IDENTICAL:
                case '<':
                case T_IS_SMALLER_OR_EQUAL:
                case T_IS_GREATER_OR_EQUAL:
                case '>':
                case T_SL:
                case T_SR:
                case '+':
                case '-':
                case '.':
                case '*':
                case '/':
                case '%':
                case '!':
                case '~':
                case T_POW:
                case T_LNUMBER:
                case T_DNUMBER:
                case T_CONSTANT_ENCAPSED_STRING:
                case ',':
                case T_DOUBLE_ARROW:
                    $snippet .= $this->tokenText . ' ';
                    break;
                case T_ARRAY:
                    $snippet .= $this->tokenText;
                    break;
                case '(':
                    $snippet .= '(';
                    $parenCount++;
                    break;
                case ')':
                    $snippet .= ') ';
                    $parenCount--;
                    break;
                case '[':
                    $snippet .= '[';
                    $bracketCount++;
                    break;
                case ']':
                    $snippet .= '] ';
                    $bracketCount--;
                    break;
                case T_STRING:
                case T_NAMESPACE:
                case T_NS_SEPARATOR:
                    $className = $this->qualifiedName();
                    if ($this->isMatch(T_DOUBLE_COLON)) {
                        if ($this->isMatch(T_CLASS)) {
                            $snippet .= "'$className'" . ' ';
                        } else {
                            $constantName = $this->match(T_STRING);
                            $snippet .= "@$classIndex";
                            $classes[] = [$className, $constantName];
                            $classIndex++;
                        }
                    } else {
                        $snippet .= "`@$constantIndex";
                        $constants[] = $className;
                        $constantIndex++;
                    }
                    continue 2;
                case T_LINE:
                    $snippet .= $this->getLineNumber();
                    break;
                case T_FILE:
                    $filename = $this->filename ?: 'php shell code';
                    $snippet .= "'$filename'";
                    break;
                case T_DIR:
                    $dirname = dirname($this->filename) ?: getcwd();
                    $snippet .= "'$dirname'";
                    break;
                case T_TRAIT_C:
                    $snippet .= "'{$this->traitName}'";
                    break;
                case T_METHOD_C:
                    $snippet .= "'{$this->methodName}'";
                    break;
                case T_FUNC_C:
                    $snippet .= "'{$this->functionName}'";
                    break;
                case T_NS_C:
                    $snippet .= "'{$this->namespace}'";
                    break;
                case T_CLASS_C:
                    $snippet .= "'{$this->className}'";
                    break;
                default:
                    $this->expected('scalar expression');
                    break;
            }
            $this->nextToken();
        }
        if ($constantIndex > 0 || $classIndex > 0) {
            return new ScalarExpression($this->factory, $snippet, $classes, $constants);
        } else {
            $v = null;
            $ret = @eval('static $v = ' . $snippet . ';' . PHP_EOL);
            if ($ret === false) {
                $this->error('Syntax error');
            }
            return $v;
        }
    }

    private function interfaceDeclaration()
    {
        $docComment = $this->docComment;
        $this->match(T_INTERFACE);
        $interfaceName = $this->match(T_STRING);
        $interfaceFqn = $this->namespace ? $this->namespace . '\\' . $interfaceName : $interfaceName;
        $this->className = $interfaceFqn;
        $extends = [];
        if ($this->isMatch(T_EXTENDS)) {
            do {
                $extends[] = $this->qualifiedName();
            } while ($this->isMatch(','));
        }
        $interface = new StaticReflectionClass(
            $this->factory,
            StaticReflectionClass::TYPE_INTERFACE,
            $this->filename,
            $docComment,
            0,
            $interfaceFqn,
            $extends
        );
        $this->match('{');
        while ($this->tokenType !== '}') {
            switch ($this->tokenType) {
                case T_CONST:
                    $this->classConstant($interface);
                    break;
                case T_PUBLIC:
                case T_PROTECTED:
                case T_PRIVATE:
                case T_STATIC:
                case T_ABSTRACT:
                case T_FINAL:
                case T_FUNCTION:
                    $this->interfaceMethod($interface);
                    break;
                default:
                    $this->expected('interface statement');
                    break;
            }
        }
        $this->match('}');
        $this->classes[$interfaceFqn] = $interface;
        $this->className = '';
    }

    private function interfaceMethod($interface)
    {
        $accessBitMask = \ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED | \ReflectionMethod::IS_PRIVATE;
        $modifiers = 0;
        $find = true;
        while ($find) {
            $accessModifier = $modifiers & $accessBitMask;
            switch ($this->tokenType) {
                case T_PUBLIC:
                case T_PROTECTED:
                case T_PRIVATE:
                    if ($accessModifier) {
                        $this->error('Multiple access modifiers not allowed');
                    }
                    break;
            }
            switch ($this->tokenType) {
                case T_PUBLIC:
                    $this->match(T_PUBLIC);
                    $modifiers |= \ReflectionMethod::IS_PUBLIC;
                    break;
                case T_PROTECTED:
                    $this->match(T_PROTECTED);
                    $modifiers |= \ReflectionMethod::IS_PROTECTED;
                    break;
                case T_PRIVATE:
                    $this->match(T_PRIVATE);
                    $modifiers |= \ReflectionMethod::IS_PRIVATE;
                    break;
                case T_ABSTRACT:
                    $this->error('Abstract modifier not allowed on method');
                    break;
                case T_FINAL:
                    $this->error('Final modifier not allowed on method');
                    break;
                case T_STATIC:
                    $this->match(T_STATIC);
                    if ($modifiers & \ReflectionMethod::IS_STATIC) {
                        $this->error('Multiple static modifiers not allowed');
                    }
                    $modifiers |= \ReflectionMethod::IS_STATIC;
                    break;
                case T_FUNCTION:
                    $find = false;
                    break;
                default:
                    $this->expected('function');
                    break;
            }
        }
        $this->method($interface, $modifiers, true);
    }

    private function traitDeclaration()
    {
        $docComment = $this->docComment;
        $this->match(T_TRAIT);
        $traitName = $this->match(T_STRING);
        $traitFqn = $this->namespace ? $this->namespace . '\\' . $traitName : $traitName;
        $this->traitName = $traitFqn;
        $trait = new StaticReflectionClass(
            $this->factory,
            StaticReflectionClass::TYPE_TRAIT,
            $this->filename,
            $docComment,
            0,
            $traitFqn,
            []
        );
        $this->classBody($trait);
        $this->classes[$traitFqn] = $trait;
        $this->traitName = '';
    }

    private function functionDeclaration()
    {
        $docComment = $this->docComment;
        $this->match(T_FUNCTION);
        $returnsReference = $this->isMatch('&');
        $functionName = $this->match(T_STRING);
        $functionName = $this->namespace ? $this->namespace . '\\' . $functionName : $functionName;
        $this->functionName = $functionName;
        $function = new StaticReflectionFunction($docComment, $returnsReference, $functionName);
        $this->parameterList($function);
        $this->functionBody($function);
        $this->functions[$functionName] = $function;
        $this->functionName = '';
    }

    private function parameterList($function)
    {
        $this->match('(');
        if ($this->tokenType === ')') {
            $this->match(')');
            return;
        }
        $position = 0;
        $this->parameter($function, $position++);
        while ($this->isMatch(',')) {
            $this->parameter($function, $position++);
        }
        $this->match(')');
    }

    /**
     * @param StaticReflectionMethod $function
     * @param int $position
     */
    private function parameter($function, $position)
    {
        $typeHint = null;
        if ($this->isMatch(T_ARRAY)) {
            $typeHint = 'array';
        } elseif ($this->isMatch(T_CALLABLE)) {
            $typeHint = 'callable';
        } elseif ($this->tokenType === T_STRING || $this->tokenType === T_NS_SEPARATOR || $this->tokenType === T_NAMESPACE) {
            $typeHint = $this->qualifiedName();
        }
        $byRef = $this->isMatch('&');
        $variadic = $this->isMatch(T_ELLIPSIS);
        $variableName = $this->match(T_VARIABLE);
        $parameterName = ltrim($variableName, '$');
        $hasDefaultValue = false;
        $defaultValue = null;
        $defaultValueConstant = null;
        if ($this->isMatch('=')) {
            $hasDefaultValue = true;
            $lookAhead = $this->lookAhead();
            if ($this->tokenType === T_STRING && ($lookAhead === ',' || $lookAhead === ')')) {
                $defaultValueConstant = $this->tokenText;
            }
            $defaultValue = $this->scalarExpression();
        }
        $parameter = new StaticReflectionParameter($function, $position, $typeHint, $byRef, $variadic, $parameterName, $hasDefaultValue, $defaultValue, $defaultValueConstant);
        $function->addParameter($parameter);
    }

    private function _namespace()
    {
        $this->match(T_NAMESPACE);
        if ($this->tokenType !== '{') {
            list($this->namespace) = $this->fullyQualifiedName();
        }
        if ($this->isMatch('{')) {
            while ($this->tokenType && $this->tokenType !== '}') {
                $this->topStatement();
            }
            $this->match('}');
        } else {
            $this->matchEndStatement();
        }
    }

    private function statement()
    {
        switch ($this->tokenType) {
            case T_FOR:
                $this->_for();
                break;
            case T_FOREACH:
                $this->_foreach();
                break;
            case T_WHILE:
                $this->_while();
                break;
            case T_IF:
                $this->_if();
                break;
            case T_SWITCH:
                $this->_switch();
                break;
            case T_DECLARE:
                $this->_declare();
                break;
            case T_DO:
                $this->match(T_DO);
                $this->statement();
                $this->match(T_WHILE);
                $this->condition();
                $this->matchEndStatement();
                break;
            case T_TRY:
                $this->_try();
                break;
            case '{':
                $this->block();
                break;
            default:
                if ($this->tokenType === T_STRING && $this->isLookAhead(':')) {
                    // Goto label.
                    $this->match(T_STRING);
                    $this->match(':');
                } else {
                    // Any other statement ends in ;
                    while ($this->tokenType && $this->tokenType !== ';' && $this->tokenType !== T_CLOSE_TAG) {
                        $this->nextToken();
                    }
                    $this->matchEndStatement();
                }
                break;
        }
    }

    private function _for()
    {
        $this->match(T_FOR);
        $this->condition();
        if ($this->isMatch(':')) {
            $this->innerStatementBlock(T_ENDFOR);
        } else {
            $this->statement();
        }
    }

    private function _foreach()
    {
        $this->match(T_FOREACH);
        $this->condition();
        if ($this->isMatch(':')) {
            $this->innerStatementBlock(T_ENDFOREACH);
        } else {
            $this->statement();
        }
    }

    private function _while()
    {
        $this->match(T_WHILE);
        $this->condition();
        if ($this->isMatch(':')) {
            $this->innerStatementBlock(T_ENDWHILE);
        } else {
            $this->statement();
        }
    }

    private function _if()
    {
        $this->match(T_IF);
        $this->condition();
        if ($this->isMatch(':')) {
            $this->innerStatementBlock(T_ENDIF);
        } else {
            $this->statement();
            while ($this->isMatch(T_ELSEIF)) {
                $this->condition();
                $this->statement();
            }
            if ($this->isMatch(T_ELSE)) {
                $this->statement();
            }
        }
    }

    private function _switch()
    {
        $this->match(T_SWITCH);
        $this->condition();
        if ($this->isMatch(':')) {
            $this->innerStatementBlock(T_ENDSWITCH);
        } else {
            $this->block();
        }
    }

    private function _declare()
    {
        $this->match(T_DECLARE);
        $this->condition();
        if ($this->isMatch(':')) {
            $this->innerStatementBlock(T_ENDDECLARE);
        } else {
            $this->statement();
        }
    }

    private function _try()
    {
        $this->match(T_TRY);
        $this->block();
        while ($this->isMatch(T_CATCH)) {
            $this->match('(');
            $this->qualifiedName();
            $this->match(T_VARIABLE);
            $this->match(')');
            $this->block();
        }
        if ($this->isMatch(T_FINALLY)) {
            $this->block();
        }
    }

    private function condition()
    {
        $this->match('(');
        $parenCount = 1;
        while ($this->tokenType && $parenCount > 0) {
            if ($this->tokenType === '(') {
                $parenCount++;
            } elseif ($this->tokenType === ')') {
                $parenCount--;
            }
            $this->nextToken();
        }
        if ($parenCount > 0) {
            $this->expected(')');

        }
    }

    private function innerStatementBlock($terminator)
    {
        while ($this->tokenType && $this->tokenType !== $terminator) {
            $this->nextToken();
        }
        $this->match($terminator);
        $this->matchEndStatement();
    }
}
