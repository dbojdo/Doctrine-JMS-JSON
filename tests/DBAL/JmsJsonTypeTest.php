<?php
/**
 * File JmsJsonTypeTest.php
 * Created at: 2016-08-25 23-01
 *
 * @author Daniel Bojdo <daniel.bojdo@web-it.eu>
 */

namespace Webit\DoctrineJmsJson\Tests\DBAL;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Webit\DoctrineJmsJson\DBAL\Exception\JmsJsonTypeInitializationException;
use Webit\DoctrineJmsJson\DBAL\JmsJsonType;
use Webit\DoctrineJmsJson\Serializer\Type\SerializerTypeResolver;

class JmsJsonTypeTest extends TestCase
{
    /** @var JmsJsonType */
    private $type;

    /** @var AbstractPlatform|ObjectProphecy */
    private $platform;

    /** @var Serializer */
    private $serializer;

    /** @var SerializerTypeResolver|ObjectProphecy */
    private $typeResolver;

    public function setUp(): void
    {
        try {
            Type::addType(JmsJsonType::NAME, JmsJsonType::class);
        } catch (DBALException $e) {
        }

        $this->type = Type::getType(JmsJsonType::NAME);
        $this->platform = $this->prophesize(AbstractPlatform::class);
        $this->serializer = $this->buildSerializer();
        $this->typeResolver = $this->prophesize(SerializerTypeResolver::class);
    }

    /**
     * @return \JMS\Serializer\SerializerInterface
     */
    private function buildSerializer()
    {
        $builder = SerializerBuilder::create();

        return $builder->build();
    }

    /**
     * @test
     */
    public function shouldProvideSqlDeclaration()
    {
        $fieldDeclaration = ['field' => 'declaration'];

        $sqlDeclaration = 'SQL Declaration';

        $this->platform->getJsonTypeDeclarationSQL($fieldDeclaration)->willReturn($sqlDeclaration);

        $this->assertEquals(
            $sqlDeclaration,
            $this->type->getSQLDeclaration($fieldDeclaration, $this->platform->reveal())
        );
    }

    /**
     * @test
     */
    public function shouldRequiresSQLCommentHint()
    {
        $this->assertTrue($this->type->requiresSQLCommentHint($this->platform->reveal()));
    }

    /**
     * @test
     */
    public function shouldBeAwareOfName()
    {
        $this->assertEquals(JmsJsonType::NAME, $this->type->getName());
    }

    /**
     * @test
     */
    public function shouldNotAllowForDoubleInitialization()
    {
        $this->initializeJmsJsonType(null, null);

        JmsJsonType::initialize($this->serializer, $this->serializer, $this->typeResolver->reveal());

        $this->expectException(JmsJsonTypeInitializationException::class);
        JmsJsonType::initialize($this->serializer, $this->serializer, $this->typeResolver->reveal());
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenSerializerNotSet()
    {
        $this->expectException(JmsJsonTypeInitializationException::class);
        $this->initializeJmsJsonType(null, null);

        $this->type->convertToPHPValue(
            json_encode(['_jms_type' => 'string', 'data' => 'some-string']),
            $this->platform->reveal()
        );
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenTypeResolverNotSet()
    {
        $this->expectException(JmsJsonTypeInitializationException::class);
        $this->expectExceptionMessageMatches('/type\ resolver/');

        $this->initializeJmsJsonType(null, null);

        $this->type->convertToDatabaseValue('xxxx', $this->platform->reveal());
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenJmsTypeCouldNotBeResolvedFromTheDatabaseValue()
    {
        $this->expectException(\RuntimeException::class);

        $this->initializeJmsJsonType($this->serializer, $this->typeResolver->reveal());
        $this->type->convertToPHPValue(json_encode(array('data' => 'some-data')), $this->platform->reveal());
    }

    /**
     * @param SerializerInterface|null $serializer
     * @param SerializerTypeResolver|null $typeResolver
     */
    private function initializeJmsJsonType(
        SerializerInterface $serializer = null,
        SerializerTypeResolver $typeResolver = null
    ) {

        try {
            if ($serializer && $typeResolver) {
                JmsJsonType::initialize($serializer, $serializer, $typeResolver);
                return;
            }
        } catch (JmsJsonTypeInitializationException $e) {
        }

        $this->initializeWithReflection($serializer, $typeResolver);
    }

    /**
     * @param SerializerInterface|null $serializer
     * @param SerializerTypeResolver|null $typeResolver
     * @throws \ReflectionException
     */
    private function initializeWithReflection(
        SerializerInterface $serializer = null,
        SerializerTypeResolver $typeResolver = null
    ) {
        $refObject = new \ReflectionClass(get_class($this->type));

        $serializerProperty = $refObject->getProperty('serializer');
        $serializerProperty->setAccessible(true);
        $serializerProperty->setValue($serializer);

        $arrayTransformerProperty = $refObject->getProperty('arrayTransformer');
        $arrayTransformerProperty->setAccessible(true);
        $arrayTransformerProperty->setValue($serializer);

        $typeResolverProperty = $refObject->getProperty('typeResolver');
        $typeResolverProperty->setAccessible(true);
        $typeResolverProperty->setValue($typeResolver);
    }
}
