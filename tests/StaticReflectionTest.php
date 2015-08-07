<?php
namespace StaticReflection;

class StaticReflectionTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var ClassFinderInterface
     */
    protected $finder;

    /**
     * @var StaticReflectionFactory
     */
    protected $factory;

    protected function setUp()
    {
        $this->finder = new Psr0Finder(dirname(__FILE__));
        $this->factory = new StaticReflectionFactory($this->finder);
    }

    public function testFinder()
    {
        $baseDir = dirname(__FILE__);
        $this->assertEquals($baseDir . '/Example/AbstractHello.php', $this->finder->findClassFile('Example\AbstractHello'));
        $this->assertEquals($baseDir . '/Example/Hello.php', $this->finder->findClassFile('Example\Hello'));
        $this->assertEquals($baseDir . '/Example/HelloInterface.php', $this->finder->findClassFile('Example\HelloInterface'));
        $this->assertEquals($baseDir . '/Example/HelloTrait.php', $this->finder->findClassFile('Example\HelloTrait'));
    }

    public function testAbstractHello()
    {
        /** @var StaticReflectionClass $class */
        $class = $this->factory->getClass('Example\AbstractHello');
        $this->assertTrue($class->isAbstract());
        $this->assertEquals('Example\AbstractHello', $class->getName());
        $methods = $class->getMethods();
        $this->assertCount(4, $methods);
        $this->assertArrayHasKey('baseMethod', $methods);
        $this->assertArrayHasKey('abstractMethod', $methods);
        $this->assertArrayHasKey('staticMethod', $methods);
        $this->assertArrayHasKey('overrideMethod', $methods);

        $method = $methods['baseMethod'];
        $this->assertTrue($method->isPublic());
        $this->assertFalse($method->isProtected());
        $this->assertFalse($method->isPrivate());
        $this->assertFalse($method->isStatic());
        $this->assertFalse($method->isAbstract());
        $this->assertFalse($method->isFinal());
        $this->assertFalse($method->isConstructor());
        $this->assertFalse($method->isDestructor());
        $this->assertTrue($method->isUserDefined());
        $this->assertEquals('baseMethod', $method->getName());
        $this->assertEquals(0, $method->getNumberOfParameters());
        $this->assertEquals(0, $method->getNumberOfRequiredParameters());

        $method = $methods['abstractMethod'];
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isAbstract());
        $this->assertFalse($method->isProtected());
        $this->assertFalse($method->isPrivate());
        $this->assertFalse($method->isStatic());
        $this->assertFalse($method->isFinal());
        $this->assertFalse($method->isConstructor());
        $this->assertFalse($method->isDestructor());
        $this->assertTrue($method->isUserDefined());
        $this->assertEquals('abstractMethod', $method->getName());
        $this->assertEquals(0, $method->getNumberOfParameters());
        $this->assertEquals(0, $method->getNumberOfRequiredParameters());

        $method = $methods['staticMethod'];
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertFalse($method->isProtected());
        $this->assertFalse($method->isPrivate());
        $this->assertFalse($method->isAbstract());
        $this->assertFalse($method->isFinal());
        $this->assertFalse($method->isConstructor());
        $this->assertFalse($method->isDestructor());
        $this->assertTrue($method->isUserDefined());
        $this->assertEquals('staticMethod', $method->getName());
        $this->assertEquals(0, $method->getNumberOfParameters());
        $this->assertEquals(0, $method->getNumberOfRequiredParameters());

        $method = $methods['overrideMethod'];
        $this->assertTrue($method->isProtected());
        $this->assertFalse($method->isPublic());
        $this->assertFalse($method->isPrivate());
        $this->assertFalse($method->isStatic());
        $this->assertFalse($method->isAbstract());
        $this->assertFalse($method->isFinal());
        $this->assertFalse($method->isConstructor());
        $this->assertFalse($method->isDestructor());
        $this->assertTrue($method->isUserDefined());
        $this->assertEquals('overrideMethod', $method->getName());
        $this->assertEquals(0, $method->getNumberOfParameters());
        $this->assertEquals(0, $method->getNumberOfRequiredParameters());
    }

    public function testHello()
    {
        /** @var StaticReflectionClass $class */
        $class = $this->factory->getClass('Example\Hello');
        $this->assertFalse($class->isAbstract());
        $this->assertEquals('Example\Hello', $class->getName());
        $methods = $class->getMethods();
        $this->assertCount(8, $methods);
        $this->assertArrayHasKey('baseMethod', $methods);
        $this->assertArrayHasKey('abstractMethod', $methods);
        $this->assertArrayHasKey('staticMethod', $methods);
        $this->assertArrayHasKey('overrideMethod', $methods);
        $this->assertArrayHasKey('greet', $methods);
        $this->assertArrayHasKey('traitOverride', $methods);
        $this->assertArrayHasKey('say', $methods);
        $this->assertArrayHasKey('parameterTest', $methods);

        $method = $methods['baseMethod'];
        $this->assertTrue($method->isPublic());
        $this->assertFalse($method->isProtected());
        $this->assertFalse($method->isPrivate());
        $this->assertFalse($method->isStatic());
        $this->assertFalse($method->isAbstract());
        $this->assertFalse($method->isFinal());
        $this->assertFalse($method->isConstructor());
        $this->assertFalse($method->isDestructor());
        $this->assertTrue($method->isUserDefined());
        $this->assertEquals('baseMethod', $method->getName());
        $this->assertEquals(0, $method->getNumberOfParameters());
        $this->assertEquals(0, $method->getNumberOfRequiredParameters());

        $method = $methods['abstractMethod'];
        $this->assertTrue($method->isPublic());
        $this->assertFalse($method->isAbstract());
        $this->assertFalse($method->isProtected());
        $this->assertFalse($method->isPrivate());
        $this->assertFalse($method->isStatic());
        $this->assertFalse($method->isFinal());
        $this->assertFalse($method->isConstructor());
        $this->assertFalse($method->isDestructor());
        $this->assertTrue($method->isUserDefined());
        $this->assertEquals('abstractMethod', $method->getName());
        $this->assertEquals(0, $method->getNumberOfParameters());
        $this->assertEquals(0, $method->getNumberOfRequiredParameters());

        $method = $methods['staticMethod'];
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertFalse($method->isProtected());
        $this->assertFalse($method->isPrivate());
        $this->assertFalse($method->isAbstract());
        $this->assertFalse($method->isFinal());
        $this->assertFalse($method->isConstructor());
        $this->assertFalse($method->isDestructor());
        $this->assertTrue($method->isUserDefined());
        $this->assertEquals('staticMethod', $method->getName());
        $this->assertEquals(0, $method->getNumberOfParameters());
        $this->assertEquals(0, $method->getNumberOfRequiredParameters());

        $method = $methods['overrideMethod'];
        $this->assertTrue($method->isProtected());
        $this->assertFalse($method->isPublic());
        $this->assertFalse($method->isPrivate());
        $this->assertFalse($method->isStatic());
        $this->assertFalse($method->isAbstract());
        $this->assertFalse($method->isFinal());
        $this->assertFalse($method->isConstructor());
        $this->assertFalse($method->isDestructor());
        $this->assertTrue($method->isUserDefined());
        $this->assertEquals('overrideMethod', $method->getName());
        $this->assertEquals(0, $method->getNumberOfParameters());
        $this->assertEquals(0, $method->getNumberOfRequiredParameters());

        $method = $methods['parameterTest'];
        $parameters = $method->getParameters();
        $this->assertEquals('abstract hello', $parameters[9]->getDefaultValue());
    }

    public function testDefaultProperties()
    {
        $class = $this->factory->getClass('Example\Foo');
        $this->assertEquals([
            'inheritedProperty' => 'inheritedDefault',
            'property' => 'propertyDefault',
            'privateProperty' => 'privatePropertyDefault',
            'staticProperty' => 'staticProperty',
            'defaultlessProperty' => null,
        ], $class->getDefaultProperties());
    }

    public function testStaticPropertyGetValue()
    {
        $class = $this->factory->getClass('Example\Foo');
        $this->assertEquals('staticProperty', $class->getProperty('staticProperty')->getValue());
    }

    /**
     * @expectedException \ReflectionException
     * @expectedExceptionMessage ReflectionProperty::getValue() expects exactly 1 parameter, 0 given
     */
    public function testPropertyGetValueNoObject()
    {
        $class = $this->factory->getClass('Example\Foo');
        $class->getProperty('property')->getValue();
    }

    /**
     * @expectedException \ReflectionException
     * @expectedExceptionMessage Class Example\NoSuchClass does not exist
     */
    public function testNotFound()
    {
        $this->factory->getClass('Example\NoSuchClass');
    }
}
