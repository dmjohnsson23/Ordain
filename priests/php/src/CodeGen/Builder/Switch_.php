<?php declare(strict_types=1);

namespace DMJohnson\Ordain\CodeGen\Builder;

use PhpParser;
use PhpParser\BuilderHelpers;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Expr;
use PhpParser\Builder\Declaration;

class Switch_ extends Declaration {
    /** @var Expr */
    private Expr $condition;
    /** @var Expr[] */
    private array $cases = [];
    /** @var Stmt[][] */
    private array $stmts = [];
    private int $cursor = -1;

    /**
     * Creates a if/elseif/else builder.
     *
     */
    public function __construct($condition, private bool $autoBreak = true) {
        // TODO enforce node to be Expr
        $this->condition = BuilderHelpers::normalizeNode($condition);
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
     * Add a new case branch to this switch statement.
     * 
     * All future statements will be added to this new branch.
     *
     * @param mixed $value The value of the case
     * 
     * @return $this The builder instance (for fluid interface)
     */
    public function case($value){
        $this->cases[] = BuilderHelpers::normalizeValue($value);
        $this->stmts[] = [];
        $this->cursor++;
        return $this;
    }

    /**
     * Add a new default branch to this if statement.
     * 
     * All future statements will be added to this new branch.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function default(){
        // TODO ensure there can only be one else branch
        $this->cases[] = null;
        $this->stmts[] = [];
        $this->cursor++;
        return $this;
    }

    /**
     * Returns the built node.
     *
     * @return Stmt\Block The built node
     */
    public function getNode(): Stmt\Switch_ {
        return new Stmt\Switch_($this->condition, \array_map(function ($case, $stmts){
            if ($this->autoBreak){
                $stmts[] = new Stmt\Break_();
            }
            return new Stmt\Case_($case, $stmts);
        }, $this->cases, $this->stmts), $this->attributes);
    }
}