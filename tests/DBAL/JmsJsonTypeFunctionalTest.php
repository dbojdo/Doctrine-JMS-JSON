<?php
/**
 * File JmsJsonTypeFunctionalTest.php
 * Created at: 2016-08-26 21-30
 *
 * @author Daniel Bojdo <daniel.bojdo@web-it.eu>
 */

namespace Webit\DoctrineJmsJson\Tests\DBAL;

use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
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
use Webit\DoctrineJmsJson\Serializer\Type\DefaultSerializerTypeResolver;
use Webit\DoctrineJmsJson\Serializer\Type\SerializerTypeResolver;

class JmsJsonTypeFunctionalTest extends TestCase
{
    /** @var AbstractPlatform|ObjectProphecy */
    private $platform;

    /** @var JmsJsonType */
    private $type;

    /** @var Serializer */
    private $serializer;

    protected function setUp(): void
    {
        $this->platform = $this->prophesize(AbstractPlatform::class);

        $this->serializer = $this->buildSerializer();

        try {
            Type::addType(JmsJsonType::NAME, JmsJsonType::class);
        } catch (DBALException $e) {
        }

        try {
            JmsJsonType::initialize($this->serializer, $this->serializer, new DefaultSerializerTypeResolver());
        } catch (JmsJsonTypeInitializationException $e) {
        }

        $this->type = Type::getType(JmsJsonType::NAME);
    }

    /**
     * @param mixed $phpValue
     * @param string $databaseValue
     * @test
     * @dataProvider values
     */
    public function shouldConvertToDatabaseValue($phpValue, $databaseValue)
    {
        $this->assertEquals($databaseValue, $this->type->convertToDatabaseValue($phpValue, $this->platform->reveal()));
    }

    /**
     * @param mixed $phpValue
     * @param string $databaseValue
     * @test
     * @dataProvider values
     */
    public function shouldConvertToPhpValue($phpValue, $databaseValue)
    {
        $this->assertEquals($phpValue, $this->type->convertToPHPValue($databaseValue, $this->platform->reveal()));
    }

    public function values()
    {
        $date = date_create('2019-11-02 12:33:21');

        return [
            [null, null],
            [1, json_encode(['_jms_type' => 'integer', 'data' => 1])],
            [1.25, json_encode(['_jms_type' => 'double', 'data' => 1.25])],
            [true, json_encode(['_jms_type' => 'boolean', 'data' => true])],
            [false, json_encode(['_jms_type' => 'boolean', 'data' => false])],
            ['abcd', json_encode(['_jms_type' => 'string', 'data' => 'abcd'])],
            [['k1' => 'v1'], json_encode(['_jms_type' => 'array<string,string>', 'data' => ['k1' => 'v1']])],
            [['v1', 'v2'], json_encode(['_jms_type' => 'array<integer,string>', 'data' => ['v1', 'v2']])],
            [$date, json_encode(['_jms_type' => 'DateTime', 'data' => $date->format(\DateTime::ATOM)])],
            [
                new ArrayCollection(['v1', 'v2']),
                json_encode(['_jms_type' => 'Doctrine\Common\Collections\ArrayCollection<integer,string>', 'data' => ["v1", "v2"]])
            ],
            [
                new ArrayCollection(['k1' => 'v1', 'k2' => 'v2']),
                json_encode(['_jms_type' => 'Doctrine\Common\Collections\ArrayCollection<string,string>', 'data' => ["k1" => "v1", "k2" => "v2"]])
            ],
            [
                new ArrayCollection(
                    [
                        new Dummy('item1', $date),
                        new Dummy('item2', $date)
                    ]
                ),
                json_encode(
                    [
                        '_jms_type' => 'Doctrine\Common\Collections\ArrayCollection<integer,Webit\DoctrineJmsJson\Tests\DBAL\Dummy>',
                        'data' => [
                            ['name' => 'item1', 'date' => $date->format(\DateTime::ATOM)],
                            ['name' => 'item2', 'date' => $date->format(\DateTime::ATOM)],
                        ]
                    ]
                )
            ],
            [
                new DummyAggregate(
                    123,
                    'myName',
                    new ArrayCollection(
                        [
                            new Dummy('item1', $date),
                            new Dummy('item2', $date)
                        ]
                    )
                ),
                json_encode(
                    [
                        '_jms_type' => 'Webit\DoctrineJmsJson\Tests\DBAL\DummyAggregate',
                        'data' => [
                            'id' => 123,
                            'name' => 'myName',
                            'items' => [
                                [
                                    'name' => 'item1',
                                    'date' => $date->format(\DateTime::ATOM)
                                ],
                                [
                                    'name' => 'item2',
                                    'date' => $date->format(\DateTime::ATOM)
                                ]
                            ]
                        ]
                    ]
                )
            ]
        ];
    }

    /**
     * @test
     */
    public function itIsBackwardCompatibleInDatabaseToPhpValueConversion()
    {
        $expectedPhpValue = new Dummy(
            $name = 'some-name',
            $date = date_create('2019-11-02 12:33:21')
        );

        $databaseValue = sprintf(
            '%s::%s',
            get_class($expectedPhpValue),
            $this->serializer->serialize($expectedPhpValue, 'json')
        );

        $this->assertEquals($expectedPhpValue, $this->type->convertToPHPValue($databaseValue, $this->platform->reveal()));
    }

    /**
     * @return \JMS\Serializer\SerializerInterface
     */
    private function buildSerializer()
    {
        $builder = SerializerBuilder::create();

        return $builder->build();
    }
}


class Dummy
{
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $name;

    /**
     * @var \DateTime
     * @JMS\Type("DateTime")
     */
    private $date;

    /**
     * Dummy constructor.
     * @param string $name
     * @param \DateTime $date
     */
    public function __construct($name, \DateTime $date)
    {
        $this->name = $name;
        $this->date = $date;
    }
}

class DummyAggregate
{
    /**
     * @var int
     * @JMS\Type("integer")
     */
    private $id;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $name;

    /**
     * @var ArrayCollection|Dummy[]
     * @JMS\Type("ArrayCollection<Webit\DoctrineJmsJson\Tests\DBAL\Dummy>")
     */
    private $items;

    /**
     * DummyAggregate constructor.
     * @param int $id
     * @param string $name
     * @param ArrayCollection|Dummy[] $items
     */
    public function __construct($id, $name, ArrayCollection $items)
    {
        $this->id = $id;
        $this->name = $name;
        $this->items = $items;
    }
}
