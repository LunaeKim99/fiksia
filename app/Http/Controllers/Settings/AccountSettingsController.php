<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateAccountProfileRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

final class AccountSettingsController extends Controller
{
    public function updateProfile(UpdateAccountProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $name = (string) $request->validated()['name'];

        DB::transaction(function () use ($user, $name): void {
            $user->update(['name' => $name]);
        });

        return redirect()
            ->route('settings.show')
            ->with('success', 'Nama akun berhasil diperbarui.');
    }
}
