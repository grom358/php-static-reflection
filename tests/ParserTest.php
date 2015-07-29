<?php
namespace StaticReflection;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Parser
     */
    protected $parser;

    protected function setUp()
    {
        $finder = $this->getMock('StaticReflection\ClassFinderInterface');
        $factory = new StaticReflectionFactory($finder);
        $this->parser = new Parser($factory);
    }

    public function testEmpty()
    {
        $this->parser->parseSource('');
        $this->assertEmpty($this->parser->getClasses());
        $this->assertEmpty($this->parser->getFunctions());
    }

    public function testNoCode()
    {
        $this->parser->parseSource("<?php\n");
        $this->assertEmpty($this->parser->getClasses());
        $this->assertEmpty($this->parser->getFunctions());
    }

    public function testNamespace()
    {
        $this->parser->parseSource('<?php /** test */ namespace MyNamespace\Test ; body();', '\Pharborist\Namespaces\NamespaceNode');

        // Test with body
        $this->parser->parseSource('namespace MyNamespace\Test\Body { }', '\Pharborist\Namespaces\NamespaceNode');

        // Test global
        $this->parser->parseSource('namespace { }', '\Pharborist\Namespaces\NamespaceNode');
    }

    public function testUseDeclaration()
    {
        $this->parser->parseSource('<?php use MyNamespace\MyClass as MyAlias ;');
    }

    public function testFunctionDeclaration()
    {
        $this->parser->parseSource('<?php function my_func(array $a, callable $b, namespace\Test $c, \MyNamespace\Test $d, $e = 1, &$f, $g) { }');
    }

    public function testConstDeclaration()
    {
        $this->parser->parseSource('<?php const MyConst = 1;');
    }

    public function testIf()
    {
        $source = <<<'EOF'
<?php
if ($condition) {
    then();
} elseif ($other_condition) {
    other_then();
} elseif ($another_condition) {
} else { do_else(); }
EOF;
        $this->parser->parseSource($source);
    }

    public function testAlternativeIf()
    {
        $source = <<<'EOF'
<?php
if ($condition):
    then();
elseif ($other_condition):
    other_then();
elseif ($another_condition):
    ;
else:
    do_else();
endif;
EOF;
        $this->parser->parseSource($source);
    }

    public function testForeach()
    {
        $source = <<<'EOF'
<?php
foreach ($array as $k => &$v)
    body();
EOF;
        $this->parser->parseSource($source);

        $source = <<<'EOF'
<?php
foreach ($array as $v)
    body();
EOF;
        $this->parser->parseSource($source);
    }

    public function testAlternativeForeach()
    {
        $source = <<<'EOF'
<?php
foreach ($array as $k => &$v):
    body();
endforeach;
EOF;
        $this->parser->parseSource($source);

        $source = <<<'EOF'
<?php
foreach ($array as $v):
    body();
endforeach;
EOF;
        $this->parser->parseSource($source);
    }

    public function testWhile()
    {
        $source = <<<'EOF'
<?php
while ($cond)
    body();
EOF;
        $this->parser->parseSource($source);
    }

    public function testAlternativeWhile()
    {
        $source = <<<'EOF'
<?php
while ($cond):
    body();
endwhile;
EOF;
        $this->parser->parseSource($source);
    }

    public function testDoWhile()
    {
        $source = <<<'EOF'
<?php
do
    body();
while ($cond);
EOF;
        $this->parser->parseSource($source);
    }

    public function testFor()
    {
        $source = <<<'EOF'
<?php
for ($i = 0; $i < 10; ++$i)
    body();
EOF;
        $this->parser->parseSource($source);
    }

    public function testAlternativeFor()
    {
        $source = <<<'EOF'
<?php
for ($i = 0; $i < 10; ++$i):
    body();
endfor;
EOF;
        $this->parser->parseSource($source);
    }

    public function testForever()
    {
        $source = <<<'EOF'
<?php
for (;;)
    body();
EOF;
        $this->parser->parseSource($source);
    }

    public function testSwitch()
    {
        $source = <<<'EOF'
<?php
switch ($cond) {
    case 'a':
        break;
    case 'fall':
    case 'through':
        break;
    default:
        break;
}
EOF;
        $this->parser->parseSource($source);
    }

    public function testAlternativeSwitch()
    {
        $source = <<<'EOF'
<?php
switch ($cond):
    case 'a':
        break;
    case 'fall':
    case 'through':
        break;
    default:
        break;
endswitch;
EOF;
        $this->parser->parseSource($source);
    }

    public function testTryCatch()
    {
        $source = <<<'EOF'
<?php
try { try_body(); }
catch (SomeException $e) { some_body(); }
catch (OtherException $e) { other_body(); }
finally { final_body(); }
EOF;
        $this->parser->parseSource($source);
    }

    public function testDeclare()
    {
        $source = '<?php declare(DECLARE_TEST = 1, MY_CONST = 2) { body(); }';
        $this->parser->parseSource($source);
    }

    public function testAlternativeDeclare()
    {
        $source = '<?php declare(DECLARE_TEST = 1, MY_CONST = 2): body(); enddeclare;';
        $this->parser->parseSource($source);
    }

    public function testTemplate()
    {
        $source = <<<'EOF'
<p>Hello World!</p>
EOF;
        $this->parser->parseSource($source);

        $source = <<<'EOF'
<p>This is a template file</p>
<p>Hello, <?=$name?>. Welcome to <?=$lego . 'world'?>!</p>
<?php
code();
?><h1>End of template</h1><?php more_code();
EOF;
        $this->parser->parseSource($source);
    }

    public function testClassDeclaration()
    {
        $source = <<<'EOF'
<?php
/** Class doc comment. */
abstract class MyClass extends ParentClass implements SomeInterface, AnotherInterface
{
    use A, B, C {
        B::smallTalk insteadof A;
        A::bigTalk insteadof B, C;
        B::bigTalk as talk;
        sayHello as protected;
    }

    /** const doc comment */
    const MY_CONST = 1;

    /** property doc comment */
    public $publicProperty = 1;

    protected $protectedProperty;
    private $privateProperty;
    static public $classProperty;
    var $backwardsCompatibility;

    /** method doc comment. */
    public function myMethod($a, $b) { perform(); }

    final public function noOverride() {}

    static public function classMethod() {}

    abstract public function mustImplement();

    function noVisibility() {}
}
EOF;
        $this->parser->parseSource($source);
        $classes = $this->parser->getClasses();
        $this->assertCount(1, $classes);
        $this->assertArrayHasKey('MyClass', $classes);
        $class = $classes['MyClass'];
        $this->assertFalse($class->isTrait());
        $this->assertFalse($class->isInterface());
        $this->assertTrue($class->isAbstract());
        $this->assertEquals('/** Class doc comment. */', $class->getDocComment());
        $this->assertEquals('ParentClass', $class->getParentClassName());
        $this->assertEquals(['SomeInterface', 'AnotherInterface'], $class->getInterfaceNames());
        $this->assertEquals(['A', 'B', 'C'], $class->getTraitNames());
        $this->assertTrue($class->hasConstant('MY_CONST'));
        $this->assertTrue($class->hasProperty('publicProperty'));
        $this->assertTrue($class->hasProperty('protectedProperty'));
        $this->assertTrue($class->hasProperty('privateProperty'));
        $this->assertTrue($class->hasProperty('classProperty'));
        $this->assertTrue($class->hasProperty('backwardsCompatibility'));
        $this->assertTrue($class->hasMethod('myMethod'));
        $this->assertTrue($class->hasMethod('noOverride'));
        $this->assertTrue($class->hasMethod('classMethod'));
        $this->assertTrue($class->hasMethod('mustImplement'));
        $this->assertTrue($class->hasMethod('noVisibility'));
    }

    public function testTraitAlias()
    {
        $source = <<<'EOF'
<?php
namespace MyNamespace;

class Test
{
  use TestTrait {
    testMethod as aliasMethod;
  }
}
EOF;
        $this->parser->parseSource($source);
    }

    public function testArrayProperty()
    {
        $source = <<<'EOF'
<?php
class Test
{
    public $array = array('hello', 'world');
}
EOF;
        $this->parser->parseSource($source);
    }

    public function testNestedArray()
    {
        $source = <<<'EOF'
<?php
class Test
{
    public $array = ['hello', ['world']];
}
EOF;
        $this->parser->parseSource($source);
    }

    public function testComplexString()
    {
        $source = <<<'EOF'
<?php
class Test
{
    public static function nested()
    {
        $embed = "{$var} ${var}!";
    }
}
EOF;
        $this->parser->parseSource($source);
    }

    public function testComplexStringInBlock()
    {
        $source = <<<'EOF'
<?php
{
    $embed = "{$var} ${var}!";
}
EOF;
        $this->parser->parseSource($source);
    }

    public function testHeredoc()
    {
        $source = <<<'EOF'
<?php
class Test
{
    const MYCONST = <<<'EOD'
Test constant heredoc.
EOD;
}
EOF;
        $this->parser->parseSource($source);
    }

    public function testHeredocArray()
    {
        $source = <<<'EOF'
<?php
class Test
{
    private $docs = array(
        'hello' => <<<EOD
hello
EOD

        ,'world' => <<<EOD
world
EOD
    );
}
EOF;
        $this->parser->parseSource($source);
    }

    public function testParameterClassConstant()
    {
        $source = <<<'EOF'
        <?php
class Test
{
    public function test($test = TestInterface::TEST_CONST) {
    }
}
EOF;
        $this->parser->parseSource($source);
    }

    public function testInterfaceDeclaration()
    {
        $source = <<<'EOF'
<?php
/** interface */
interface MyInterface extends SomeInterface, AnotherInterface
{
    const MY_CONST = 1;

    /** interface method */
    public function myMethod($a, $b);
}
EOF;
        $this->parser->parseSource($source);
        $classes = $this->parser->getClasses();
        $this->assertCount(1, $classes);
        $this->assertArrayHasKey('MyInterface', $classes);
        $interface = $classes['MyInterface'];
        $this->assertTrue($interface->isInterface());
        $this->assertTrue($interface->hasConstant('MY_CONST'));
        $this->assertTrue($interface->hasMethod('myMethod'));
    }
}
