<?php 
namespace DMJohnson\Ordain\Conversion\TypedValue;

use DMJohnson\Ordain\Conversion\TypedValue;
use DMJohnson\Ordain\Model\ScalarType;

class StringValue extends TypedValue{
    public readonly string $value;

    public function __construct(string $value, public readonly string $encoding = 'UTF-8'){
        assert(\mb_check_encoding($value, $encoding), "Not a valid $encoding string: $value");
        parent::__construct(new ScalarType('string'), $value);
    }
}