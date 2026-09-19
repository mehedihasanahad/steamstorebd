<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * An uploaded image has to be a given shape, and big enough to stay sharp.
 *
 * The storefront paints every hero slide into a fixed aspect-ratio box, so an
 * image of the wrong shape is not a cosmetic problem — `object-cover` silently
 * throws away whatever does not fit, usually the half of the artwork with the
 * offer written on it. Rejecting it at upload is the only point where someone
 * can still do something about it.
 *
 * The ratio is checked with a tolerance rather than exactly: a 1600×500 export
 * and a 1456×455 one are the same shape to any reader, and Laravel's built-in
 * `dimensions:ratio=` rule would reject the second for being 0.0004 out.
 */
class ImageAspectRatio implements ValidationRule
{
    public function __construct(
        private int $ratioWidth,
        private int $ratioHeight,
        private int $minWidth,
        private float $tolerance = 0.03,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Editing a record without touching the image leaves the stored path
        // in the field. There is nothing to measure, and nothing has changed
        // that would need measuring.
        if (! $value instanceof UploadedFile) {
            return;
        }

        $contents = rescue(fn () => $value->get(), null, report: false);
        $size = $contents === null ? false : @getimagesizefromstring($contents);

        if ($size === false) {
            $fail('The :attribute could not be read as an image.');

            return;
        }

        [$width, $height] = $size;

        if ($width < $this->minWidth) {
            $fail("The :attribute must be at least {$this->minWidth}px wide. This one is {$width}px.");

            return;
        }

        $wanted = $this->ratioWidth / $this->ratioHeight;

        if (abs(($width / $height) - $wanted) > $wanted * $this->tolerance) {
            $fail(sprintf(
                'The :attribute must be %d:%d — %d×%d, for example. This one is %d×%d.',
                $this->ratioWidth,
                $this->ratioHeight,
                $this->minWidth,
                (int) round($this->minWidth * $this->ratioHeight / $this->ratioWidth),
                $width,
                $height,
            ));
        }
    }
}
