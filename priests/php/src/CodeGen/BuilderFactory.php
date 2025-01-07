<?php
namespace DMJohnson\Ordain\CodeGen;

use PhpParser\BuilderFactory as PhpParserBuilderFactory;
use PhpParser\{Node,Comment};

class BuilderFactory extends PhpParserBuilderFactory{

    /**
     * Creates a generic block builder.
     *
     * @return Builder\Block The created block builder
     */
    public function block(): Builder\Block {
        return new Builder\Block();
    }

    /**
     * Creates a no-op node with a comment.
     *
     * @param string|Comment $comment If this is a string, it should include all delimiters (such 
     *  as // or /*) in addition to the actual comment text.
     */
    public function comment($comment): Node\Stmt\Nop {
        $nop = new Node\Stmt\Nop();
        if ($comment instanceof Comment){
            $comments = [$comment];
        }
        else{
            $comments = [new Comment($comment)];
        }
        $nop->setAttribute('comments', $comments);
        return $nop;
    }

    public function if($condition): Builder\If_ {
        return new Builder\If_($condition);
    }

    public function switch($condition): Builder\Switch_ {
        return new Builder\Switch_($condition);
    }
}
