<?php declare(strict_types=1);

namespace DMJohnson\Ordain\CodeGen\Builder;

use PhpParser;
use PhpParser\BuilderHelpers;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Expr;
use PhpParser\Builder\Declaration;

class If_ extends Declaration {
    /** @var Expr[] */
    private array $branches = [];
    /** @var Stmt[][] */
    private array $stmts = [];
    private int $cursor;

    /**
     * Creates a if/elseif/else builder.
     *
     */
    public function __construct($condition) {
        // TODO enforce node to be Expr
        $this->branches[] = BuilderHelpers::normalizeNode($condition);
        $this->stmts[] = [];
        $this->cursor = 0;
    }

    /**
     * Adds a statement to the currently active branch of this if statement.
     *
     * @param Node|PhpParser\Builder $stmt The statement to add
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function addStmt($stmt) {
        $this->stmts[$this->cursor][] = BuilderHelpers::normalizeStmt($stmt);

        return $this;
    }

    /**
     * Add a new elseif branch to this if statement.
     * 
     * All future statements will be added to this new branch.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function elseif($condition){
        // TODO enforce node to be Expr
        $this->branches[] = BuilderHelpers::normalizeNode($condition);
        $this->stmts[] = [];
        $this->cursor++;
        return $this;
    }

    /**
     * Add a new else branch to this if statement.
     * 
     * All future statements will be added to this new branch.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function else(){
        // TODO ensure there can only be one else branch
        $this->branches[] = null;
        $this->stmts[] = [];
        $this->cursor++;
        return $this;
    }

    /**
     * Returns the built node.
     *
     * @return Stmt\Block The built node
     */
    public function getNode(): Stmt\If_ {
        $cursor = $this->cursor;
        if (\is_null($this->branches[$cursor])){
            $else = new Stmt\Else_($this->stmts[$cursor]);
            $cursor--;
        }
        else{
            $else = null;
        }
        $elseifs = [];
        while ($cursor > 0){
            $elseifs[] = new Stmt\ElseIf_(
                $this->branches[$cursor],
                $this->stmts[$cursor],
            );
            $cursor--;
        }
        return new Stmt\If_($this->branches[0], [
            'stmts' => $this->stmts[0],
            'elseifs' => \array_reverse($elseifs),
            'else' => $else,
        ], $this->attributes);
    }
}