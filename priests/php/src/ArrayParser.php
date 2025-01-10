<?php
namespace DMJohnson\Ordain;

abstract class ArrayParser{
    /**
     * Build a model from an array, such as would be obtained by parsing a JSON or YAML definition.
     * 
     * @throws Exceptions\ParseException If the input is not well-formed
     * @return array<string,Model\Typedef>
     */
    static function parse(array $array){
        $model = [];
        foreach($array as $key=>$value){
            $model[$key] = static::parseTypedefArray($key, $value, $model);
        }
        return $model;
    }

    /**
     * Build a typedef from an array
     * 
     * @throws Exceptions\ParseException If the input is not well-formed
     * @return Model\Typedef
     */
    protected static function parseTypedefArray(string $key, array $value, array &$model){
        if (!isset($value['type'])) throw new Exceptions\ParseException("Typedef $key has no type");
        $type = $value['type'];
        if (\in_array($type, Model\ScalarType::TYPES)){
            $type = new Model\ScalarType($type);
        }
        elseif (\is_string($type)){
            $type = new Model\NamedTypeReference($type);
        }
        // TODO parse inline type
        else throw new Exceptions\ParseException("Type could not be parsed in typedef $key");
        if (isset($value['docs']) && !\is_string($value['docs'])) throw new Exceptions\ParseException("Typedef $key has a non-string docs");
        $docs = $value['docs'] ?? null;
        if (isset($value['tags'])) $tags = static::parseTagsArray($key, $value['tags']);
        else $tags = [];
        if (isset($value['fields'])){
            $fields = [];
            foreach ($value['fields'] as $fieldKey=>$fieldValue){
                $fields[$fieldKey] = static::parseTypedefArray("$key.$fieldKey", $fieldValue, $model);
            }
        }
        else $fields = null;
        return new Model\Typedef($key, $type, $docs, new Model\TagRepository($tags), $fields);
    }

    /**
     * Build a list of tags from an array
     * 
     * @throws Exceptions\ParseException If the input is not well-formed
     * @return Model\Tag[]
     */
    protected static function parseTagsArray(string $typedefKey, array $tags){
        $parsed = [];
        foreach ($tags as $key=>$value){
            if (\is_string($key)){
                $parsed[] = Model\Tag::fromKeyValue($key, $value);
            }
            elseif (\is_int($key) && \is_string($value)){
                $parsed[] = Model\Tag::fromKeyValue($value, true);
            }
            elseif (\is_int($key) && \is_array($value) && \count($value) === 1 && !\array_is_list($value)){
                $parsed[] = Model\Tag::fromKeyValue(\array_keys($value)[0], \array_values($value)[0]);
            }
            else{
                throw new Exceptions\ParseException("Tag $key in typedef $typedefKey could not be parsed");
            }
        }
        return $parsed;
    }
}