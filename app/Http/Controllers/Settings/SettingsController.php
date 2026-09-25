<?php

namespace App\Http\Controllers\Settings;

use App\Application\Settings\SettingsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Http\Support\SafeInternalRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SettingsController extends Controller
{
    private const UPDATE_ERROR = 'Pengaturan gagal diperbarui. Periksa kembali isian Anda.';

    public function __construct(private readonly SettingsService $settings) {}

    public function show(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Settings/Index', [
            'settings' => $this->settings->forUser($user),
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'googleConnected' => $user->google_id !== null,
                'hasPassword' => $user->password !== null,
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $section = $request->section();
        $validated = $request->validated();
        $redirectUrl = SafeInternalRedirect::resolve($request);

        if ($validated === []) {
            return redirect()
                ->to($redirectUrl)
                ->withErrors(['settings' => 'No valid settings provided.'])
                ->with('error', self::UPDATE_ERROR);
        }

        try {
            $this->settings->update(
                $request->user(),
                $section,
                $validated,
            );
        } catch (\Throwable) {
            return redirect()->to($redirectUrl)->with('error', self::UPDATE_ERROR);
        }

        return redirect()
            ->to($redirectUrl)
            ->with('success', 'Pengaturan berhasil diperbarui.');
    }
}
