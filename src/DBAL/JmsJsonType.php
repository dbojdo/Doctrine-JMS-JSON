<?php
/**
 * File JmsJsonType.php
 * Created at: 2016-08-25 22-22
 *
 * @author Daniel Bojdo <daniel.bojdo@web-it.eu>
 */

namespace Webit\DoctrineJmsJson\DBAL;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use JMS\Serializer\Serializer;
use Webit\DoctrineJmsJson\DBAL\Exception\JmsJsonTypeInitializationException;
use Webit\DoctrineJmsJson\Serializer\Type\SerializerTypeResolver;

final class JmsJsonType extends Type
{
    public const NAME = 'jms_json';

    private const TYPE_KEY = '_jms_type';
    private const DATA_KEY = 'data';
    private const LEGACY_SEPARATOR = '::';

    /** @var Serializer */
    private static $serializer;

    /** @var SerializerTypeResolver */
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

        $dbValue = [
            self::TYPE_KEY => $this->typeResolver()->resolveType($value),
            self::DATA_KEY => $value
        ];

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

        return $phpValue;
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

    private function resolveJmsTypeAndData($value)
    {
        $arData = @json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return array(
                isset($arData[self::TYPE_KEY]) ? $arData[self::TYPE_KEY] : null,
                isset($arData[self::DATA_KEY]) ? $arData[self::DATA_KEY] : null
            );
        }

        // try backward compatibility
        @list($type, $data) = explode(self::LEGACY_SEPARATOR, $value, 2);
        if ($data) {
            $data = @json_decode($data, true);
        }

        return array($type, $data);
    }
}
