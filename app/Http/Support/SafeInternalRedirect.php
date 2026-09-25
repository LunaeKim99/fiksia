<?php

namespace App\Http\Support;

use Illuminate\Http\Request;

final class SafeInternalRedirect
{
    public static function resolve(Request $request): string
    {
        $fallback = route('account');
        $referer = $request->headers->get('referer');

        if (! is_string($referer)
            || $referer === ''
            || preg_match('/[\x00-\x1F\x7F]/', $referer) === 1
            || str_contains($referer, '\\')) {
            return $fallback;
        }

        $base = parse_url($request->getSchemeAndHttpHost());
        $target = parse_url($referer);

        if ($base === false || $target === false) {
            return $fallback;
        }

        $baseScheme = strtolower((string) ($base['scheme'] ?? ''));
        $targetScheme = strtolower((string) ($target['scheme'] ?? ''));
        $baseHost = strtolower((string) ($base['host'] ?? ''));
        $targetHost = strtolower((string) ($target['host'] ?? ''));
        $basePort = (int) ($base['port'] ?? ($baseScheme === 'https' ? 443 : 80));
        $targetPort = (int) ($target['port'] ?? ($targetScheme === 'https' ? 443 : 80));
        $path = (string) ($target['path'] ?? '/');

        if ($baseScheme !== $targetScheme
            || $baseHost !== $targetHost
            || $basePort !== $targetPort
            || $path === ''
            || $path[0] !== '/'
            || str_starts_with($path, '//')
            || preg_match('/[\x00-\x1F\x7F]/', $path) === 1
            || str_contains($path, '\\')) {
            return $fallback;
        }

        return $path.(isset($target['query']) ? '?'.$target['query'] : '');
    }
}
