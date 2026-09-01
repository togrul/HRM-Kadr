<?php

namespace App\Modules\Integration\Console\Commands;

use App\Models\ApiToken;
use App\Modules\Integration\Support\Contract;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Issue a token for the finance system.
 *
 * A console command rather than a screen: this is done once per installation by
 * whoever sets the systems up, and the plaintext must not sit in a browser
 * history or a Livewire payload.
 */
class IssueIntegrationTokenCommand extends Command
{
    protected $signature = 'integration:token
        {name : Who the token is for, e.g. "ARBAY finance"}
        {--ability=* : Feed abilities; omit for all}
        {--expires= : Expiry date (Y-m-d); omit for none}';

    protected $description = 'Issue an integration API token (the plaintext is shown once)';

    public function handle(): int
    {
        /** @var list<string> $abilities */
        $abilities = (array) $this->option('ability');

        $expires = $this->option('expires');

        $result = ApiToken::generate(
            name: (string) $this->argument('name'),
            abilities: $abilities === [] ? null : $abilities,
            expiresAt: $expires ? Carbon::parse((string) $expires) : null,
        );

        $this->newLine();
        $this->line('  '.$result['plain']);
        $this->newLine();

        $this->components->warn('This is the only time the token is shown. Store it now.');

        $this->components->info(sprintf(
            'Abilities: %s',
            $abilities === [] ? 'all' : implode(', ', $abilities),
        ));

        $this->components->info('Available abilities: '.implode(', ', Contract::abilities()));

        return self::SUCCESS;
    }
}
