<?php
namespace My\Project\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use DMJohnson\Ordain\Conversion\Repr;

/**
 * A doctrine type that hooks into Ordain
 * 
 * This is used to:
 *  - Handle sql.repr tags
 *  - Allow a custom sql.type
 */
class TypeHook extends Type{
    /** @var string[] $hookTypes a list of registered type names known to be TypeHook instances */
    private static array $hookTypes = [];

    public function __construct(
        string $registerAs, 
        public readonly string $backingType,
        public readonly ?string $sqlType,
        public readonly ?string $repr,
        ){
        Type::getTypeRegistry()->register($registerAs, $this);
        static::$hookTypes[] = $registerAs;
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform):string{
        if (isset($this->sqlType)) return $this->sqlType;
        else return Type::getTypeRegistry()->get($this->backingType)->getSQLDeclaration($fieldDeclaration, $platform);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform):mixed{
        $value = Type::getTypeRegistry()->get($this->backingType)->convertToPHPValue($value, $platform);
        if (isset($this->repr)){
            $repr = Repr::of($this->repr);
            $value = $repr->load($value);
        }
        return $value;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform):mixed{
        if (isset($this->repr)){
            $repr = Repr::of($this->repr);
            $value = $repr->dump($value);
        }
        $value = Type::getTypeRegistry()->get($this->backingType)->convertToDatabaseValue($value, $platform);
        return $value;
    }
}