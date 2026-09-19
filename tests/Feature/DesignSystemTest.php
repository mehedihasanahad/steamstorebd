<?php

/**
 * The regression guard for the storefront design system.
 *
 * Before the rebuild the storefront carried 459 inline `style="..."`
 * attributes and 293 hardcoded hex literals, which is precisely why a new
 * theme could not be delivered by editing a config file. Without a test, they
 * grow back one "just this once" at a time.
 *
 * The rule: a rebuilt view describes appearance with design tokens, never with
 * a literal. Alpine's `:style` binding is exempt — it computes a value at
 * runtime from state, which a class cannot do.
 */

use Illuminate\Support\Facades\File;

/** Every view that is part of the rebuilt storefront surface. */
function designSystemViews(): array
{
    $roots = [
        resource_path('views/storefront'),
        resource_path('views/components/ui'),
        resource_path('views/components/catalog'),
        resource_path('views/auth'),
        resource_path('views/errors'),
        resource_path('views/vendor/pagination'),
    ];

    $files = collect($roots)
        ->filter(fn (string $path) => File::isDirectory($path))
        ->flatMap(fn (string $path) => File::allFiles($path))
        ->map(fn ($file) => $file->getPathname());

    $singles = [
        resource_path('views/layouts/storefront.blade.php'),
        resource_path('views/layouts/guest.blade.php'),
        resource_path('views/profile/edit.blade.php'),
        resource_path('views/components/auth-shell.blade.php'),
        resource_path('views/components/auth-password.blade.php'),
        resource_path('views/components/product-chat-buttons.blade.php'),
    ];

    return $files->concat(array_filter($singles, fn (string $path) => File::exists($path)))
        ->filter(fn (string $path) => str_ends_with($path, '.blade.php'))
        ->values()
        ->all();
}

/**
 * Views that may still write a literal, and why.
 *
 * Every entry here is another company's brand mark, which we do not get to
 * re-tint. This list is not a to-do list; it is expected to stay this short.
 */
const DESIGN_SYSTEM_ALLOW_LIST = [
    'components/auth-google.blade.php' => 'Google mandates the exact colours of its own sign-in mark.',
];

function designSystemRelativePath(string $path): string
{
    return str_replace('\\', '/', str_replace(resource_path('views') . DIRECTORY_SEPARATOR, '', $path));
}

function designSystemIsAllowed(string $path): bool
{
    return array_key_exists(designSystemRelativePath($path), DESIGN_SYSTEM_ALLOW_LIST);
}

describe('the rebuilt storefront', function () {
    it('has views to check at all', function () {
        // A broken glob would make every assertion below pass vacuously.
        expect(designSystemViews())->not->toBeEmpty();
    });

    it('writes no static style attribute', function () {
        $offenders = [];

        foreach (designSystemViews() as $view) {
            if (designSystemIsAllowed($view)) {
                continue;
            }

            // `:style` and `x-bind:style` compute a value from state, which a
            // class genuinely cannot do; a bare `style=` never has that excuse.
            preg_match_all('/(?<![:\w-])style\s*=\s*"/', File::get($view), $matches);

            if ($matches[0] !== []) {
                $offenders[designSystemRelativePath($view)] = count($matches[0]);
            }
        }

        expect($offenders)->toBe([]);
    });

    it('writes no hex colour literal', function () {
        $offenders = [];

        foreach (designSystemViews() as $view) {
            if (designSystemIsAllowed($view)) {
                continue;
            }

            preg_match_all('/#[0-9a-fA-F]{6}\b/', File::get($view), $matches);

            if ($matches[0] !== []) {
                $offenders[designSystemRelativePath($view)] = array_values(array_unique($matches[0]));
            }
        }

        expect($offenders)->toBe([]);
    });

    it('keeps the allow-list honest', function () {
        foreach (array_keys(DESIGN_SYSTEM_ALLOW_LIST) as $relative) {
            expect(File::exists(resource_path('views/' . $relative)))
                ->toBeTrue("Allow-listed view {$relative} no longer exists — drop it from the list.");
        }
    });

    it('declares every colour token once, in the stylesheet', function () {
        $css = File::get(resource_path('css/storefront.css'));

        foreach (['--surface-0', '--surface-1', '--surface-2', '--surface-3',
                  '--text-hi', '--text-mid', '--text-low',
                  '--accent', '--accent-hover',
                  '--success', '--warning', '--danger'] as $token) {
            expect($css)->toContain($token . ':');
        }
    });

    it('resolves every Tailwind colour token to a custom property', function () {
        $config = File::get(base_path('tailwind.storefront.config.js'));

        // If a token were given a literal here, the stylesheet would stop being
        // the single source of truth and the two could drift apart.
        foreach (['surface', 'ink', 'accent', 'success', 'warning', 'danger'] as $group) {
            expect($config)->toContain($group);
        }

        expect($config)->toContain('rgb(var(--surface-0) / <alpha-value>)')
            ->and($config)->toContain('rgb(var(--accent) / <alpha-value>)');
    });
});

describe('accessibility floors', function () {
    it('gives the layout a skip link and a main landmark', function () {
        $layout = File::get(resource_path('views/layouts/storefront.blade.php'));

        expect($layout)->toContain('Skip to content')
            ->and($layout)->toContain('<main id="main">');
    });

    it('never removes a focus outline without replacing it', function () {
        $css = File::get(resource_path('css/storefront.css'));

        expect($css)->toContain(':focus-visible')
            ->and($css)->toContain('outline: 2px solid');
    });

    it('honours a reduced-motion preference', function () {
        expect(File::get(resource_path('css/storefront.css')))
            ->toContain('prefers-reduced-motion');
    });
});
