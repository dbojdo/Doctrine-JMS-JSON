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
use JMS\Serializer\Serializer;
use Webit\DoctrineJmsJson\DBAL\Exception\JmsJsonTypeInitializationException;
use Webit\DoctrineJmsJson\Serializer\Type\SerializerTypeResolver;

class JmsJsonType extends Type
{
    const NAME = 'jms_json';

    /**
     * @var Serializer
     */
    private static $serializer;

    /**
     * @var SerializerTypeResolver
     */
    private static $typeResolver;

    /**
     * @param Serializer $serializer
     * @param SerializerTypeResolver $typeResolver
     */
    public static function initialize(Serializer $serializer, SerializerTypeResolver $typeResolver)
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
        return $platform->getJsonTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * @inheritdoc
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $dbValue = array(
            '_jms_type' => $this->typeResolver()->resolveType($value),
            'data' => $value
        );

        return $this->serializer()->serialize($dbValue, 'json');
    }

    /**
     * @inheritdoc
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if($value === null) {
            return null;
        }

        list($type, $phpValue) = $this->resolveJmsTypeAndData($value);
        if (!$type) {
            throw new \RuntimeException('Could not find JMS type in the database value.');
        }

        if (is_array($phpValue)) {
            $phpValue = $this->serializer()->fromArray($phpValue, $type);
        } else {
            $phpValue = $this->serializer()->fromArray((array)$phpValue, sprintf('array<%s>', $type));
            $phpValue = array_shift($phpValue);
        }

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
     * @return Serializer
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

    private function resolveJmsTypeAndData($value)
    {
        $arData = @json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return array(
                isset($arData['_jms_type']) ? $arData['_jms_type'] : null,
                isset($arData['data']) ? $arData['data'] : null
            );
        }

        // try backward compatibility
        @list($type, $data) = explode('::', $value, 2);
        if ($data) {
            $data = @json_decode($data, true);
        }

        return array($type, $data);
    }
}
