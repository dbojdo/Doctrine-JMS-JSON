<?php
/**
 * File DefaultSerializerTypeResolverTest.php
 * Created at: 2016-08-27 18-30
 *
 * @author Daniel Bojdo <daniel.bojdo@web-it.eu>
 */

namespace Webit\DoctrineJmsJson\Tests\Serializer\Type;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Webit\DoctrineJmsJson\Serializer\Type\DefaultSerializerTypeResolver;
use Webit\DoctrineJmsJson\Serializer\Type\Exception\TypeNotResolvedException;

class DefaultSerializerTypeResolverTest extends TestCase
{
    /**
     * @var DefaultSerializerTypeResolver
     */
    private $typeResolver;

    protected function setUp(): void
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
        return [
            [1, 'integer'],
            [1.25, 'double'],
            [true, 'boolean'],
            [false, 'boolean'],
            ['my-string', 'string'],
            [new \DateTime(), 'DateTime'],
            [[], 'array'],
            [
                [
                    new DummyClass()
                ],
                'array<integer,Webit\DoctrineJmsJson\Tests\Serializer\Type\DummyClass>'
            ],
            [
                new ArrayCollection(),
                'Doctrine\Common\Collections\ArrayCollection'
            ],
            [
                new ArrayCollection([
                    new DummyClass()
                ]),
                'Doctrine\Common\Collections\ArrayCollection<integer,Webit\DoctrineJmsJson\Tests\Serializer\Type\DummyClass>'
            ],
            [
                new DummyClass(),
                'Webit\DoctrineJmsJson\Tests\Serializer\Type\DummyClass'
            ]
        ];
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenTypeNotSupported()
    {
        $this->expectException(TypeNotResolvedException::class);
        $value = fopen(sys_get_temp_dir().'/' . md5(microtime()), 'w+');
        $this->typeResolver->resolveType($value);
    }
}

class DummyClass
{

}