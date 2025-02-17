<?php

namespace DMJohnson\Ordain\Conversion;

use DMJohnson\Ordain\Exceptions\OrdainException;
use DMJohnson\Ordain\Model\ScalarType;
use DMJohnson\Ordain\Model\Type;

class PhpTypes implements TypeConverter{
    function toOrdained(mixed $value, Type $ordainedType):TypedValue{
        // Scalar types
        if ($ordainedType instanceof ScalarType){
            switch ($ordainedType->type){
                case 'binary':
                    if (\is_int($value)){
                        // TODO convert to bytes (figure out details: endian, size, etc...)
                    }
                    return new TypedValue\BinaryValue((string)$value);
                case 'bool':
                    return new TypedValue\BoolValue((bool)$value);
                case 'date':
                    break; // TODO how to store properly?
                case 'datetime':
                    break; // TODO how to store properly?
                case 'decimal':
                    if (\is_numeric($value)){
                        return new TypedValue\DecimalValue($value);
                    }
                    break;
                case 'float':
                    if (\is_numeric($value)){
                        return new TypedValue\FloatValue((float)$value);
                    }
                    break;
                case 'int':
                    if (\is_numeric($value)){
                        return new TypedValue\IntValue((int)$value);
                    }
                    break;
                case 'string':
                    // TODO what is the encoding?
                    return new TypedValue\StringValue((string)$value);
                case 'time':
                    break; // TODO how to store properly?
            }
            throw new OrdainException('Cannot convert '.\gettype($value).' '.var_export($value, true)." to $ordainedType->type");
        }
        // TODO custom typedefs
    }

    function fromOrdained(TypedValue $value, string $targetType):mixed{

    }
}