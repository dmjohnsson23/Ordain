<?php 
namespace DMJohnson\Ordain\Conversion\TypedValue;

use DMJohnson\Ordain\Conversion\TypedValue;
use DMJohnson\Ordain\Model\ScalarType;

class DecimalValue extends TypedValue{
    public readonly string $value;

    public function __construct(string $value){
        assert(\is_numeric($value), 'Decimal values must be numeric');
        parent::__construct(new ScalarType('decimal'), $value);
    }
}