<?php 
namespace DMJohnson\Ordain\Conversion\TypedValue;

use DMJohnson\Ordain\Conversion\TypedValue;
use DMJohnson\Ordain\Model\ScalarType;

class BinaryValue extends TypedValue{
    public readonly string $value;

    public function __construct(string $value){
        parent::__construct(new ScalarType('binary'), $value);
    }
}