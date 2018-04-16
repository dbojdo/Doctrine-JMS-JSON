<?php
/**
 * File JmsJsonType.php
 * Created at: 2016-08-25 22-22
 *
 * @author Daniel Bojdo <daniel.bojdo@web-it.eu>
 */

namespace Webit\DoctrineJmsJson\DBAL;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use JMS\Serializer\SerializerInterface;
use Webit\DoctrineJmsJson\DBAL\Exception\JmsJsonTypeInitializationException;
use Webit\DoctrineJmsJson\Serializer\Type\SerializerTypeResolver;

class JmsJsonType extends Type
{
    const NAME = 'jms_json';

    /**
     * @var SerializerInterface
     */
    private static $serializer;

    /**
     * @var SerializerTypeResolver
     */
    private static $typeResolver;

    /**
     * @param SerializerInterface $serializer
     * @param SerializerTypeResolver $typeResolver
     */
    public static function initialize(SerializerInterface $serializer, SerializerTypeResolver $typeResolver)
    {
        if (self::$serializer) {
            throw new JmsJsonTypeInitializationException(
                'DBAL type of "jms_json" has been already initialized.'
            );
        }

        self::$serializer = $serializer;
        self::$typeResolver = $typeResolver;
    }

    /**
     * @inheritdoc
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getClobTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * @inheritdoc
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $type = $this->typeResolver()->resolveType($value);

        return sprintf('%s::%s', $type, $this->serializer()->serialize($value, 'json'));
    }

    /**
     * @inheritdoc
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if($value === null) {
            return null;
        }
        
        @list($type, $json) = explode('::', $value, 2);

        $phpValue = $this->serializer()->deserialize($json, $type, 'json');
        if (! $this->isCollection($type)) {
            return $phpValue;
        }

        return $this->fixCollection($phpValue);
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @inheritdoc
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }

    /**
     * @return SerializerInterface
     */
    private function serializer()
    {
        if (!self::$serializer) {
            throw new JmsJsonTypeInitializationException(
                'DBAL type of "jms_json" has not been initialized properly as it requires serializer to be configured.'
            );
        }

        return self::$serializer;
    }

    /**
     * @return SerializerTypeResolver
     */
    private function typeResolver()
    {
        if (!self::$typeResolver) {
            throw new JmsJsonTypeInitializationException(
                'DBAL type of "jms_json" has not been initialized properly as requires type resolver to be configured.'
            );
        }

        return self::$typeResolver;
    }

    /**
     * @param string $type
     * @return bool
     */
    private function isCollection($type)
    {
        return substr($type, -10) == 'Collection' || strpos($type, 'Collection<');
    }

    /**
     * @param mixed $phpValue
     * @return ArrayCollection
     */
    private function fixCollection($phpValue)
    {
        return new ArrayCollection((array) $phpValue);
    }
}
