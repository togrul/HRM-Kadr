<?php

namespace App\Services\Orders\Document\Nodes;

/**
 * Marker interface for the order-document AST.
 *
 * The AST is the single structured representation of a rendered order; an
 * HtmlRenderer turns it into editable preview HTML and a DocxRenderer turns the
 * SAME tree into a Word document, so layout logic lives in one place instead of
 * being duplicated across two renderers.
 */
interface DocumentNode {}
