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
    public function shouldResolveTypeByValue($value, $type): void
    {
        $this->assertEquals($type, $this->typeResolver->resolveType($value));
    }

    public function values()
    {
        yield [1, 'integer'];
        yield [1.25, 'double'];
        yield [true, 'boolean'];
        yield [false, 'boolean'];
        yield ['my-string', 'string'];
        yield [new \DateTime(), 'DateTime'];
        yield [[], 'array'];
        yield [
            [new DummyClass()],
            'array<integer,Webit\DoctrineJmsJson\Tests\Serializer\Type\DummyClass>'
        ];
        yield [
            new ArrayCollection(),
            'Doctrine\Common\Collections\ArrayCollection'
        ];
        yield [
            new ArrayCollection([new DummyClass()]),
            'Doctrine\Common\Collections\ArrayCollection<integer,Webit\DoctrineJmsJson\Tests\Serializer\Type\DummyClass>'
        ];
        yield [
            new DummyClass(),
            'Webit\DoctrineJmsJson\Tests\Serializer\Type\DummyClass'
        ];
        yield [
            DummyEnum::One,
            "enum<'Webit\DoctrineJmsJson\Tests\Serializer\Type\DummyEnum', 'name'>"
        ];
        yield [
            DummyEnumInt::One,
            "enum<'Webit\DoctrineJmsJson\Tests\Serializer\Type\DummyEnumInt', 'value'>"
        ];
        yield [
            DummyEnumString::One,
            "enum<'Webit\DoctrineJmsJson\Tests\Serializer\Type\DummyEnumString', 'value'>"
        ];
        yield [
            [DummyEnumString::One, DummyEnumString::Two],
            "array<integer,enum<'Webit\DoctrineJmsJson\Tests\Serializer\Type\DummyEnumString', 'value'>>"
        ];
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenTypeNotSupported(): void
    {
        $this->expectException(TypeNotResolvedException::class);
        $value = fopen(sys_get_temp_dir().'/' . md5(microtime()), 'w+');
        $this->typeResolver->resolveType($value);
    }
}

class DummyClass
{

}

enum DummyEnum {
    case One;
}

enum DummyEnumInt: int
{
    case One = 1;
}

enum DummyEnumString: string
{
    case One = 'one';
    case Two = 'two';
}