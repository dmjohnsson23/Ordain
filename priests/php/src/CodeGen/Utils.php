<?php
namespace DMJohnson\Ordain\CodeGen;

use DMJohnson\Ordain\Exceptions\OrdainException;
use DMJohnson\Ordain\Model\{NamedTypeReference,Tag,Typedef};
use DMJohnson\Ordain\Seeker;
use PhpParser\{BuilderHelpers, Parser, ParserFactory, PrettyPrinter};
use PhpParser\Builder\Declaration;
use PhpParser\Comment\Doc;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\Return_;
use ValueError;


abstract class Utils{
    static function formatDocComment(string $text, $indent=''){
        $comment = $indent.'/**'.\PHP_EOL;
        foreach (\preg_split("/\r\n|\n|\r/", $text) as $line){
            $comment .= $indent.' * '.$line.\PHP_EOL;
        }
        $comment .= $indent.' */';
        return new Doc($comment);
    }
}