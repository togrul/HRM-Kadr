<?php

namespace App\Modules\PerformanceEvaluation\Application\Services\Kpi;

use InvalidArgumentException;

/**
 * The formula DSL of calculated KPIs (spec §4.1): numbers, other KPIs as `{KPI_CODE}`,
 * + - * / and parentheses, comparisons (> < >= <= == !=, giving 1 or 0) and the
 * functions IF(cond, then, else), MIN(…), MAX(…), ROUND(x[, digits]) and ABS(x).
 * A hand-written recursive-descent parser — nothing is ever `eval`-ed.
 *
 * Evaluation returns null when a referenced KPI has no value yet or a division by zero
 * occurs, so the calculated KPI simply stays unscored until its inputs arrive.
 */
class KpiFormula
{
    private const FUNCTIONS = ['IF', 'MIN', 'MAX', 'ROUND', 'ABS'];

    /** @var array<int, array{0: string, 1: string}> */
    private array $tokens = [];

    private int $pos = 0;

    /** @var array<string, float|null> */
    private array $values = [];

    /**
     * The KPI codes the formula reads.
     *
     * @return array<int, string>
     *
     * @throws InvalidArgumentException on a syntax error
     */
    public function references(string $formula): array
    {
        $this->parse($formula, []);

        return collect($this->tokens)->where('0', 'ref')->pluck(1)->unique()->values()->all();
    }

    /**
     * @param  array<string, float|null>  $values  KPI code → value
     *
     * @throws InvalidArgumentException on a syntax error
     */
    public function evaluate(string $formula, array $values): ?float
    {
        $result = $this->parse($formula, $values);

        return $result === null || ! is_finite($result) ? null : round($result, 4);
    }

    /**
     * @param  array<string, float|null>  $values
     */
    private function parse(string $formula, array $values): ?float
    {
        $this->tokens = $this->tokenize($formula);
        $this->pos = 0;
        $this->values = $values;

        if ($this->tokens === []) {
            throw new InvalidArgumentException(__('performance_evaluation::kpi.formula.errors.empty'));
        }

        $result = $this->comparison();

        if ($this->pos < count($this->tokens)) {
            throw new InvalidArgumentException(__('performance_evaluation::kpi.formula.errors.unexpected', ['token' => $this->tokens[$this->pos][1]]));
        }

        return $result;
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function tokenize(string $formula): array
    {
        $pattern = '/\s*(?:(?<num>\d+(?:\.\d+)?)|\{(?<ref>[A-Z0-9_]+)\}|(?<name>[A-Za-z]+)|(?<op>>=|<=|==|!=|[-+*\/(),<>]))/A';
        $tokens = [];
        $offset = 0;
        $formula = rtrim($formula);

        while ($offset < strlen($formula)) {
            if (! preg_match($pattern, $formula, $match, 0, $offset)) {
                throw new InvalidArgumentException(__('performance_evaluation::kpi.formula.errors.unexpected', ['token' => mb_substr(ltrim(substr($formula, $offset)), 0, 8)]));
            }

            $offset += strlen($match[0]);
            $tokens[] = match (true) {
                $match['num'] !== '' => ['num', $match['num']],
                $match['ref'] !== '' => ['ref', $match['ref']],
                $match['name'] !== '' => ['fn', strtoupper($match['name'])],
                default => ['op', $match['op']],
            };
        }

        return $tokens;
    }

    /**
     * Advances the token cursor.
     *
     * @phpstan-impure
     */
    private function comparison(): ?float
    {
        $left = $this->additive();
        $op = $this->peek();

        if (in_array($op, ['>', '<', '>=', '<=', '==', '!='], true)) {
            $this->pos++;
            $right = $this->additive();

            if ($left === null || $right === null) {
                return null;
            }

            return (float) match ($op) {
                '>' => $left > $right,
                '<' => $left < $right,
                '>=' => $left >= $right,
                '<=' => $left <= $right,
                '==' => abs($left - $right) < 1e-9,
                default => abs($left - $right) >= 1e-9,
            };
        }

        return $left;
    }

    private function additive(): ?float
    {
        $value = $this->term();

        while (in_array($this->peek(), ['+', '-'], true)) {
            $op = $this->tokens[$this->pos++][1];
            $right = $this->term();
            $value = $value === null || $right === null ? null : ($op === '+' ? $value + $right : $value - $right);
        }

        return $value;
    }

    private function term(): ?float
    {
        $value = $this->unary();

        while (in_array($this->peek(), ['*', '/'], true)) {
            $op = $this->tokens[$this->pos++][1];
            $right = $this->unary();

            if ($op === '/' && $right !== null && abs($right) < 1e-12) {
                $value = null;

                continue;
            }

            $value = $value === null || $right === null ? null : ($op === '*' ? $value * $right : $value / $right);
        }

        return $value;
    }

    private function unary(): ?float
    {
        if ($this->peek() === '-') {
            $this->pos++;
            $value = $this->unary();

            return $value === null ? null : -$value;
        }

        return $this->primary();
    }

    private function primary(): ?float
    {
        $token = $this->tokens[$this->pos] ?? null;

        if ($token === null) {
            throw new InvalidArgumentException(__('performance_evaluation::kpi.formula.errors.incomplete'));
        }

        $this->pos++;

        return match ($token[0]) {
            'num' => (float) $token[1],
            'ref' => $this->reference($token[1]),
            'fn' => $this->call($token[1]),
            default => $token[1] === '(' ? $this->group() : throw new InvalidArgumentException(__('performance_evaluation::kpi.formula.errors.unexpected', ['token' => $token[1]])),
        };
    }

    private function group(): ?float
    {
        $value = $this->comparison();
        $this->expect(')');

        return $value;
    }

    private function reference(string $code): ?float
    {
        return $this->values[$code] ?? null;
    }

    private function call(string $name): ?float
    {
        if (! in_array($name, self::FUNCTIONS, true)) {
            throw new InvalidArgumentException(__('performance_evaluation::kpi.formula.errors.unknown_function', ['name' => $name]));
        }

        $this->expect('(');
        $args = [$this->comparison()];
        while ($this->peek() === ',') {
            $this->pos++;
            $args[] = $this->comparison();
        }
        $this->expect(')');

        $arity = match ($name) {
            'IF' => count($args) === 3,
            'ABS' => count($args) === 1,
            'ROUND' => in_array(count($args), [1, 2], true),
            default => true,
        };
        if (! $arity) {
            throw new InvalidArgumentException(__('performance_evaluation::kpi.formula.errors.arguments', ['name' => $name]));
        }

        if ($name === 'IF') {
            return $args[0] === null ? null : ($args[0] != 0.0 ? $args[1] : $args[2]);
        }

        if (in_array(null, $args, true)) {
            return null;
        }

        return match ($name) {
            'MIN' => min($args),
            'MAX' => max($args),
            'ABS' => abs($args[0]),
            default => round($args[0], (int) ($args[1] ?? 0)),
        };
    }

    private function peek(): ?string
    {
        $token = $this->tokens[$this->pos] ?? null;

        return $token !== null && $token[0] === 'op' ? $token[1] : null;
    }

    private function expect(string $op): void
    {
        if ($this->peek() !== $op) {
            throw new InvalidArgumentException(__('performance_evaluation::kpi.formula.errors.expected', ['token' => $op]));
        }

        $this->pos++;
    }
}
