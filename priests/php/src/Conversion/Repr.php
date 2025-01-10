<?php

namespace DMJohnson\Ordain\Conversion;

abstract class Repr{
    private static array $registry;

    public function __construct(public readonly string $name){
        static::$registry[$name] = $this;
    }
    /**
     * Given a value in the native type, convert it to this representation
     */
    abstract function dump($value);

    /**
     * Given a value in this representation, convert it to the native type
     */
    abstract function load($value);
}


new class ('html.sanitized') extends Repr{
    /** @var \Symfony\Component\HtmlSanitizer\HtmlSanitizer $sanitizer */
    public $sanitizer;
    
    function dump($value){
        if (!isset($sanitizer)){
            $sanitizer = new \Symfony\Component\HtmlSanitizer\HtmlSanitizer(
                (new \Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig())
                ->allowSafeElements()
                ->allowLinkSchemes(['http', 'https', 'mailto'])
                ->allowMediaSchemes(['http', 'https', 'data'])
                ->forceAttribute('a', 'rel', 'noopener noreferrer')
                ->dropAttribute('ping', '*')
                ->blockElement('form')
                ->dropElement('input')
                ->dropElement('button')
                ->dropElement('textarea')
                ->dropElement('select')
            );
        }
    }

    function load($value){
        return $value;
    }
};

new class ('html.escaped') extends Repr{
    function dump($value){
        return \htmlspecialchars($value);
    }

    function load($value){
        return \htmlspecialchars_decode($value);
    }
};

new class ('php.serialized') extends Repr{
    function dump($value){
        return \serialize($value);
    }

    function load($value){
        return \unserialize($value);
    }
};

new class ('json') extends Repr{
    function dump($value){
        return \json_encode($value);
    }

    function load($value){
        // TODO we need to get the actual target type; struct should be imported as object, but mapping as associative
        return \json_decode($value, true);
    }
};

new class ('iso-date') extends Repr{
    function dump($value){
        return \serialize($value);
    }

    function load($value){
        return \unserialize($value);
    }
};

new class ('iso-datetime') extends Repr{

};

new class ('iso-time') extends Repr{

};

