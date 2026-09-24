<?php

/**
 * Every Blade view compiles to valid PHP.
 *
 * This exists because of one afternoon. An inline `@php(...)` was added to a
 * view that already contained a `@php ... @endphp` block further down. Blade
 * pairs raw blocks by regex, so the inline directive was captured by that
 * later `@endphp`, everything between the two stopped being compiled, and the
 * order page returned a 500. Nothing in the template looked wrong, the
 * directives balanced, and the error surfaced forty lines away from the cause.
 *
 * A view only compiles when something renders it, so a template nobody has a
 * test for can sit broken until a customer finds it. Compiling all of them
 * costs a second and removes the whole category.
 */

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

function everyBladeView(): array
{
    return collect(File::allFiles(resource_path('views')))
        ->filter(fn ($file) => str_ends_with($file->getFilename(), '.blade.php'))
        ->map(fn ($file) => $file->getPathname())
        ->values()
        ->all();
}

it('has views to check at all', function () {
    expect(everyBladeView())->not->toBeEmpty();
});

it('compiles every view to valid PHP', function () {
    $broken = [];

    foreach (everyBladeView() as $view) {
        try {
            // TOKEN_PARSE makes the tokeniser reject what it cannot parse,
            // which is a syntax check without shelling out per file.
            token_get_all(Blade::compileString(File::get($view)), TOKEN_PARSE);
        } catch (ParseError $e) {
            $relative = str_replace('\\', '/', str_replace(resource_path('views') . DIRECTORY_SEPARATOR, '', $view));

            $broken[$relative] = $e->getMessage();
        }
    }

    expect($broken)->toBe([]);
});

it('never puts an inline @php() in a view that also has a @php block', function () {
    // The two forms cannot share a file: Blade extracts raw blocks first, and
    // the inline one is swallowed by the block's @endphp.
    $offenders = [];

    foreach (everyBladeView() as $view) {
        $source = File::get($view);

        if (str_contains($source, '@php(') && str_contains($source, '@endphp')) {
            $offenders[] = str_replace('\\', '/', str_replace(resource_path('views') . DIRECTORY_SEPARATOR, '', $view));
        }
    }

    expect($offenders)->toBe([]);
});
