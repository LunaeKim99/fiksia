<?php

namespace App\Http\Controllers;

use App\Models\Novel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NovelCoverController extends Controller
{
    public function __invoke(Request $request, Novel $novel): StreamedResponse
    {
        Gate::authorize('viewCover', $novel);

        if ($novel->cover_path === null || ! Storage::disk('local')->exists($novel->cover_path)) {
            abort(404, 'Sampul tidak ditemukan.');
        }

        return Storage::disk('local')->response($novel->cover_path, basename($novel->cover_path), [], 'inline');
    }
}
