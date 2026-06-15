<?php

namespace App\Services\Orders\Document\Nodes;

/**
 * The signatory block: a multi-line title pinned left with the signer's name pinned
 * right, e.g.
 *   Baş direktorun İnsan resursları,
 *   təşkilati idarəetmə və
 *   kommunikasiyalar üzrə müavini            Sübhan İsmayılov
 */
final class SignatureBlock implements DocumentNode
{
    /**
     * @param  string[]  $titleLines
     */
    public function __construct(
        public readonly array $titleLines,
        public readonly string $name,
    ) {}
}
