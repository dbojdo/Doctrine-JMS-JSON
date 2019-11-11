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
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Webit\DoctrineJmsJson\DBAL\Exception\JmsJsonTypeInitializationException;
use Webit\DoctrineJmsJson\DBAL\JmsJsonType;
use Webit\DoctrineJmsJson\Serializer\Type\DefaultSerializerTypeResolver;
use Webit\DoctrineJmsJson\Serializer\Type\SerializerTypeResolver;

class JmsJsonTypeFunctionalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractPlatform|\Mockery\MockInterface
     */
    private $platform;

    /**
     * @var JmsJsonType
     */
    private $type;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var SerializerTypeResolver
     */
    private $typeResolver;

    protected function setUp()
    {
        $this->platform = \Mockery::mock('Doctrine\DBAL\Platforms\AbstractPlatform');

        $this->serializer = $this->buildSerializer();
        $this->typeResolver = new DefaultSerializerTypeResolver();

        try {
            Type::addType(JmsJsonType::NAME, 'Webit\DoctrineJmsJson\DBAL\JmsJsonType');
        } catch (DBALException $e) {
        }

        try {
            JmsJsonType::initialize($this->serializer, $this->typeResolver);
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
        $this->assertEquals($databaseValue, $this->type->convertToDatabaseValue($phpValue, $this->platform));
    }

    /**
     * @param mixed $phpValue
     * @param string $databaseValue
     * @test
     * @dataProvider values
     */
    public function shouldConvertToPhpValue($phpValue, $databaseValue)
    {
        $this->assertEquals($phpValue, $this->type->convertToPHPValue($databaseValue, $this->platform));
    }

    public function values()
    {
        $date = date_create('2019-11-02 12:33:21');

        return array(
            array(null, null),
            array(1, json_encode(array('_jms_type' => 'integer', 'data' => 1))),
            array(1.25, json_encode(array('_jms_type' => 'double', 'data' => 1.25))),
            array(true, json_encode(array('_jms_type' => 'boolean', 'data' => true))),
            array(false, json_encode(array('_jms_type' => 'boolean', 'data' => false))),
            array('abcd', json_encode(array('_jms_type' => 'string', 'data' => 'abcd'))),
            array(array('k1' => 'v1'), json_encode(array('_jms_type' => 'array<string,string>', 'data' => array('k1' => 'v1')))),
            array(array('v1', 'v2'), json_encode(array('_jms_type' => 'array<integer,string>', 'data' => array('v1', 'v2')))),
            array($date, json_encode(array('_jms_type' => 'DateTime', 'data' => $date->format(\DateTime::ISO8601)))),
            array(
                new ArrayCollection(array('v1', 'v2')),
                json_encode(array('_jms_type' => 'Doctrine\Common\Collections\ArrayCollection<integer,string>', 'data' => array("v1", "v2")))
            ),
            array(
                new ArrayCollection(array('k1' => 'v1', 'k2' => 'v2')),
                json_encode(array('_jms_type' => 'Doctrine\Common\Collections\ArrayCollection<string,string>', 'data' => array("k1" => "v1", "k2" => "v2")))
            ),
            array(
                new ArrayCollection(
                    array(
                        new Dummy('item1', $date),
                        new Dummy('item2', $date)
                    )
                ),
                json_encode(
                    array(
                        '_jms_type' => 'Doctrine\Common\Collections\ArrayCollection<integer,Webit\DoctrineJmsJson\Tests\DBAL\Dummy>',
                        'data' => array(
                            array('name' => 'item1', 'date' => $date->format(DATE_ISO8601)),
                            array('name' => 'item2', 'date' => $date->format(DATE_ISO8601)),
                        )
                    )
                )
            ),
            array(
                new DummyAggregate(
                    123,
                    'myName',
                    new ArrayCollection(
                        array(
                            new Dummy('item1', $date),
                            new Dummy('item2', $date)
                        )
                    )
                ),
                json_encode(
                    array(
                        '_jms_type' => 'Webit\DoctrineJmsJson\Tests\DBAL\DummyAggregate',
                        'data' => array(
                            'id' => 123,
                            'name' => 'myName',
                            'items' => array(
                                array(
                                    'name' => 'item1',
                                    'date' => $date->format(DATE_ISO8601)
                                ),
                                array(
                                    'name' => 'item2',
                                    'date' => $date->format(DATE_ISO8601)
                                )
                            )
                        )
                    )
                )
            )
        );
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
