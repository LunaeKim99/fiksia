<?php

namespace App\Application\Auth\Contracts;

use App\Domain\Shared\ValueObjects\GoogleIdentity;
use Illuminate\Http\RedirectResponse;

interface GoogleAuthProvider
{
    public function redirect(): RedirectResponse;

    public function user(): GoogleIdentity;
}
