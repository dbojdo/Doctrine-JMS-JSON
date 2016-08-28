<?php
/**
 * File DefaultSerializerTypeResolverTest.php
 * Created at: 2016-08-27 18-30
 *
 * @author Daniel Bojdo <daniel.bojdo@web-it.eu>
 */

namespace Webit\DoctrineJmsJson\Tests\Serializer\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Webit\DoctrineJmsJson\Serializer\Type\DefaultSerializerTypeResolver;

class DefaultSerializerTypeResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DefaultSerializerTypeResolver
     */
    private $typeResolver;

    protected function setUp()
    {
        $this->typeResolver = new DefaultSerializerTypeResolver();
    }

    /**
     * @param $value
     * @param $type
     * @test
     * @dataProvider values
     */
    public function shouldResolveTypeByValue($value, $type)
    {
        $this->assertEquals($type, $this->typeResolver->resolveType($value));
    }

    public function values()
    {
        return array(
            array(1, 'integer'),
            array(1.25, 'double'),
            array(true, 'boolean'),
            array(false, 'boolean'),
            array('my-string', 'string'),
            array(new \DateTime(), 'DateTime'),
            array(array(), 'array'),
            array(
                array(
                    new DummyClass()
                ),
                'array<Webit\DoctrineJmsJson\Tests\Serializer\Type\DummyClass>'
            ),
            array(
                new ArrayCollection(),
                'Doctrine\Common\Collections\ArrayCollection'
            ),
            array(
                new ArrayCollection(array(
                    new DummyClass()
                )),
                'Doctrine\Common\Collections\ArrayCollection<Webit\DoctrineJmsJson\Tests\Serializer\Type\DummyClass>'
            ),
            array(
                new DummyClass(),
                'Webit\DoctrineJmsJson\Tests\Serializer\Type\DummyClass'
            )
        );
    }

    /**
     * @test
     * @expectedException \Webit\DoctrineJmsJson\Serializer\Type\Exception\TypeNotResolvedException
     */
    public function shouldThrowExceptionWhenTypeNotSupported()
    {
        $value = fopen(sys_get_temp_dir().'/' . md5(microtime()), 'w+');
        $this->typeResolver->resolveType($value);
    }
}

class DummyClass
{

}