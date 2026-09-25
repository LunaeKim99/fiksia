<?php

use App\Application\Settings\SettingsNormalizer;

it('rejects colors outside strict RRGGBB format', function () {
    expect((new SettingsNormalizer)->isHexColor('#fff'))->toBeFalse()
        ->and((new SettingsNormalizer)->isHexColor('red'))->toBeFalse()
        ->and((new SettingsNormalizer)->isHexColor('#A1B2C3'))->toBeTrue();
});

it('merges only the requested appearance keys', function () {
    $normalizer = new SettingsNormalizer;
    $current = $normalizer->defaults()['appearance'];
    $next = $normalizer->appearance($current, ['mode' => 'dark']);

    expect($next['mode'])->toBe('dark')
        ->and($next['preset'])->toBe($current['preset'])
        ->and($next['primaryColor'])->toBe($current['primaryColor']);
});

it('resolves canonical preset colors', function () {
    $normalizer = new SettingsNormalizer;
    $current = $normalizer->defaults()['appearance'];

    $violet = $normalizer->appearance($current, ['preset' => 'violet']);
    expect($violet['preset'])->toBe('violet')
        ->and($violet['primaryColor'])->toBe('#7C3AED')
        ->and($violet['secondaryColor'])->toBeNull();

    $ocean = $normalizer->appearance($current, [
        'preset' => 'ocean',
        'accentStyle' => 'gradient',
        'primaryColor' => '#ff0000',
        'secondaryColor' => '#00ff00',
    ]);
    expect($ocean['secondaryColor'])->toBe('#2563EB');

    $customSingle = $normalizer->appearance($current, [
        'preset' => 'custom',
        'accentStyle' => 'single',
        'primaryColor' => '#123456',
        'secondaryColor' => '#abcdef',
    ]);
    expect($customSingle['secondaryColor'])->toBeNull();

    $customDualNoSecondary = $normalizer->appearance($current, [
        'preset' => 'custom',
        'accentStyle' => 'dual',
        'primaryColor' => '#112233',
    ]);
    expect($customDualNoSecondary)->toBe($normalizer->defaults()['appearance']);

    expect($normalizer->appearance($current, [
        'preset' => 'custom',
        'accentStyle' => 'gradient',
        'primaryColor' => 'red',
    ])['primaryColor'])->toBe($current['primaryColor']);
});

it('ignores unknown appearance keys', function () {
    $normalizer = new SettingsNormalizer;
    $current = $normalizer->defaults()['appearance'];
    $next = $normalizer->appearance($current, ['unknownKey' => 'x', 'mode' => 'light']);

    expect($next)->not->toHaveKey('unknownKey')
        ->and($next['mode'])->toBe('light');
});

it('rejects invalid reading enums', function () {
    $normalizer = new SettingsNormalizer;
    $current = $normalizer->defaults()['reading'];
    $next = $normalizer->reading($current, [
        'fontSize' => 'huge',
        'fontFamily' => 'comic',
        'lineHeight' => '9.9',
        'columnWidth' => 'giant',
    ]);

    expect($next)->toBe($current);
});

it('returns safe unavailable values for privacy and notifications', function () {
    $normalizer = new SettingsNormalizer;
    $privacy = $normalizer->privacy(
        $normalizer->defaults()['privacy'],
        ['publicReadingActivity' => true],
    );
    $notifications = $normalizer->notifications(
        $normalizer->defaults()['notifications'],
        ['email' => ['enabled' => true]],
    );

    expect($privacy)->toBe($normalizer->defaults()['privacy'])
        ->and($notifications)->toBe($normalizer->defaults()['notifications']);
});
