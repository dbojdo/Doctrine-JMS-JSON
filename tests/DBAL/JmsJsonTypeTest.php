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
use JMS\Serializer\SerializerInterface;
use Webit\DoctrineJmsJson\DBAL\Exception\JmsJsonTypeInitializationException;
use Webit\DoctrineJmsJson\DBAL\JmsJsonType;
use Webit\DoctrineJmsJson\Serializer\Type\SerializerTypeResolver;

class JmsJsonTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JmsJsonType
     */
    private $type;

    /**
     * @var AbstractPlatform|\Mockery\MockInterface
     */
    private $platform;

    /**
     * @var Serializer|\Mockery\MockInterface
     */
    private $serializer;

    /**
     * @var SerializerTypeResolver|\Mockery\MockInterface
     */
    private $typeResolver;

    public function setUp()
    {
        try {
            Type::addType(JmsJsonType::NAME, 'Webit\DoctrineJmsJson\DBAL\JmsJsonType');
        } catch (DBALException $e) {
        }

        $this->type = Type::getType(JmsJsonType::NAME);
        $this->platform = \Mockery::mock('Doctrine\DBAL\Platforms\AbstractPlatform');
        $this->serializer = \Mockery::mock('JMS\Serializer\Serializer');
        $this->typeResolver = \Mockery::mock('Webit\DoctrineJmsJson\Serializer\Type\SerializerTypeResolver');
    }

    /**
     * @test
     */
    public function shouldProvideSqlDeclaration()
    {
        $fieldDeclaration = array('field' => 'declaration');

        $sqlDeclaration = 'SQL Declaration';

        $this->platform
            ->shouldReceive('getJsonTypeDeclarationSQL')
            ->with($fieldDeclaration)
            ->once()
            ->andReturn($sqlDeclaration);

        $this->assertEquals(
            $sqlDeclaration,
            $this->type->getSQLDeclaration($fieldDeclaration, $this->platform)
        );
    }

    /**
     * @test
     */
    public function shouldRequiresSQLCommentHint()
    {
        $this->assertTrue($this->type->requiresSQLCommentHint($this->platform));
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

        JmsJsonType::initialize($this->serializer, $this->typeResolver);

        $this->setExpectedException('\Webit\DoctrineJmsJson\DBAL\Exception\JmsJsonTypeInitializationException');
        JmsJsonType::initialize($this->serializer, $this->typeResolver);
    }

    /**
     * @test
     * @expectedException \Webit\DoctrineJmsJson\DBAL\Exception\JmsJsonTypeInitializationException
     * @expectedExceptionMessageRegExp /serializer/
     */
    public function shouldThrowExceptionWhenSerializerNotSet()
    {
        $this->initializeJmsJsonType(null, null);

        $this->type->convertToPHPValue('some-data', $this->platform);
    }

    /**
     * @test
     * @expectedException \Webit\DoctrineJmsJson\DBAL\Exception\JmsJsonTypeInitializationException
     * @expectedExceptionMessageRegExp /type\ resolver/
     */
    public function shouldThrowExceptionWhenTypeResolverNotSet()
    {
        $this->initializeJmsJsonType(null, null);

        $this->type->convertToDatabaseValue('xxxx', $this->platform);
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
                JmsJsonType::initialize($serializer, $typeResolver);
                return;
            }
        } catch (JmsJsonTypeInitializationException $e) {
        }

        $this->initializeWithReflection($serializer, $typeResolver);
    }

    /**
     * @param SerializerInterface|null $serializer
     * @param SerializerTypeResolver|null $typeResolver
     */
    private function initializeWithReflection(
        SerializerInterface $serializer = null,
        SerializerTypeResolver $typeResolver = null
    ) {
        $refObject = new \ReflectionClass(get_class($this->type));

        $serializerProperty = $refObject->getProperty('serializer');
        $serializerProperty->setAccessible(true);
        $serializerProperty->setValue($serializer);

        $typeResolverProperty = $refObject->getProperty('typeResolver');
        $typeResolverProperty->setAccessible(true);
        $typeResolverProperty->setValue($typeResolver);
    }
}
