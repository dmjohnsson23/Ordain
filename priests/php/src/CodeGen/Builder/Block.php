<?php declare(strict_types=1);

namespace DMJohnson\Ordain\CodeGen\Builder;

use PhpParser;
use PhpParser\BuilderHelpers;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Builder\Declaration;

class Block extends Declaration {
    /** @var Stmt[] */
    private array $stmts = [];

    /**
     * Creates a block builder.
     *
     */
    public function __construct() {
    }

    /**
     * Adds a statement.
     *
     * @param Node|PhpParser\Builder $stmt The statement to add
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function addStmt($stmt) {
        $this->stmts[] = BuilderHelpers::normalizeStmt($stmt);

        return $this;
    }

    /**
     * Returns the built node.
     *
     * @return Stmt\Block The built node
     */
    public function getNode(): Node {
        return new Stmt\Block($this->stmts, $this->attributes);
    }
}