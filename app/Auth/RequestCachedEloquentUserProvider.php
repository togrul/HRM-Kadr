<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class RequestCachedEloquentUserProvider extends EloquentUserProvider
{
    /**
     * @var array<string, \Illuminate\Contracts\Auth\Authenticatable|null>
     */
    private array $usersById = [];

    /**
     * @param  mixed  $identifier
     */
    public function retrieveById($identifier): ?UserContract
    {
        $key = (string) $identifier;

        if (array_key_exists($key, $this->usersById)) {
            return $this->usersById[$key];
        }

        return $this->usersById[$key] = parent::retrieveById($identifier);
    }
}
