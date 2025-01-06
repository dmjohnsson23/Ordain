<?php
namespace DMJohnson\Ordain;

use Symfony\Component\Yaml\Yaml;

abstract class YamlParser{
    /**
     * Build a model from a YAML string
     * 
     * @throws Exceptions\ParseException If the input is not well-formed
     * @return array<string,Model\Typedef>
     */
    static function parse(string $yaml){
        return ArrayParser::parse(Yaml::parse($yaml));
    }

    /**
     * Build a model from a YAML file
     * 
     * @throws Exceptions\ParseException If the input is not well-formed
     * @return array<string,Model\Typedef>
     */
    static function parseFile(string $filename){
        return ArrayParser::parse(Yaml::parseFile($filename));
    }
}