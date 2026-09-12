<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Old slugs of brands and products. When an admin renames a slug, links and
 * search results pointing at the old URL keep working through a 301 instead
 * of turning into 404s.
 */
class SlugRedirect extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'old_slug',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Call from a model's `updated` event, while getOriginal() still holds the
     * slug it moved away from.
     */
    public static function rememberSlugChange(Model $model): void
    {
        $type = $model->getMorphClass();

        static::updateOrCreate(
            ['model_type' => $type, 'old_slug' => $model->getOriginal('slug')],
            ['model_id' => $model->getKey()],
        );

        // The new slug is live again, so an older redirect claiming it must go.
        static::where('model_type', $type)->where('old_slug', $model->slug)->delete();
    }

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @return TModel|null
     */
    public static function findModel(string $modelClass, string $slug): ?Model
    {
        return static::where('model_type', (new $modelClass)->getMorphClass())
            ->where('old_slug', $slug)
            ->first()
            ?->model;
    }
}
