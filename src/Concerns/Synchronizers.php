<?php

namespace IMW\RepositoryQS\Concerns;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

/**
 * Synchronizers helpers
 *
 * @author Yassine Sedrani <sed.yassine@live.fr>
 * @package Flamingoo
 * @license MIT
 */
trait Synchronizers
{

    /**
     * Synchronize product images
     *
     * @param \App\Models\Product $product
     * @param array|null $images
     * @return void
     */
    public static function synchronizeProductImages($product, ?array $images = [])
    {
        // if no image was found we'll just perform a clean
        if (is_null($images) || empty($images)) {
            $imageIds = [];
        }

        // Handle new/existing product images
        else {
            foreach ($images as $priority => $image) {
                $imageIds[] = $product->images()->updateOrCreate(
                    ['uri' => $image['uri']],
                    [
                        'primary' => $image['primary'],
                        'secondary' => $image['secondary'],
                        'priority' => $priority + 1
                    ]
                )->id;
            }
        }


        // Clean unused images
        $product->images()
            ->whereNotIn('id', $imageIds)
            ->get()
            ->each(function ($image) {
                Storage::delete($image->uri); // this should be in a listener or subscriber
                $image->delete();
            });
    }

    /**
     * Synchronize product options
     *
     * @param \App\Models\Product $product
     * @param array|null $options
     * @param string|null $locale
     * @return void
     */
    public static function synchronizeProductOptions($product, ?array $options = [], ?string $locale = null)
    {
        // if no option was found we'll just perform a clean
        if (is_null($options) || empty($options)) {
            $optionIds = [];
        } else {
            $defaultLocale  = $locale ?? Config::get('app.locale');

            foreach ($options as $optionData) {
                // If the id is present in the data let's try to retrieve it
                $option = $product->options()->find(isset($optionData['id']) ? $optionData['id'] : null)
                    ?? $product->options()->create();

                $option->update([
                    'required' => (bool) $optionData['required'],
                    $defaultLocale => [
                        'name' => $optionData['name']
                    ]
                ]);

                // Update options choices
                static::synchronizeOptionChoices($option, $optionData['values'], $defaultLocale);

                $optionIds[] = $option->id;
            }
        }

        // Clean unused options
        $product->options()
            ->whereNotIn('id', $optionIds)
            ->delete();
    }

    /**
     * Synchronize product option choices
     *
     * @param \App\Models\ProductAttribute $option
     * @param array|null $choices
     * @param string|null $locale
     * @return void
     */
    public static function synchronizeOptionChoices($option, ?array $choices = [], ?string $locale = null)
    {
        // if no choice was found we'll just perform a clean
        if (is_null($choices) || empty($choices)) {
            $choiceIds = [];
        } else {
            $defaultLocale  = $locale ?? Config::get('app.locale');

            foreach ($choices as $choiceData) {
                // If the id is present in the data let's try to retrieve it
                $choice = $option->values()->find(isset($choiceData['id']) ? $choiceData['id'] : null)
                    ?? $option->values()->create();

                $choice->update([
                    'fee' => (float) $choiceData['fee'],
                    $defaultLocale => [
                        'value' => $choiceData['value']
                    ]
                ]);

                $choiceIds[] = $choice->id;
            }
        }

        // Clean unused
        $option->values()
            ->whereNotIn('id', $choiceIds)
            ->delete();
    }

    /**
     * Synchronize product options
     *
     * @param \App\Models\Product $product
     * @param array|null $features
     * @param string|null $locale
     * @return void
     */
    public static function synchronizeProductFeatures($product, ?array $features = [], ?string $locale = null)
    {
        // if no option was found we'll just perform a clean
        if (is_null($features) || empty($features)) {
            $featureIds = [];
        } else {
            $defaultLocale  = $locale ?? Config::get('app.locale');

            foreach ($features as $priority => $featureData) {
                // If the id is present in the data let's try to retrieve it
                $feature = \App\Models\ProductFeature::whereTranslation('name', $featureData['name'])
                    ->updateOrCreate([], [
                        'priority' => $priority + 1,
                        $defaultLocale => [
                            'name' => $featureData['name']
                        ]
                    ]);

                if ($feature->icon !== $featureData['icon']) {
                    $feature->update(['icon' => $featureData['icon']]);
                }

                $featureIds[$feature->id] = ['value' => $featureData['value']];
            }
        }

        // Synchronize products features
        $product->features()->sync($featureIds);
    }

    /**
     * Synchronize product categories
     *
     * @param \App\Models\Product $product
     * @param array|null $categories
     * @return void
     */

    public static function synchronizeProductCategories($product, ?array $categories = [])
    {
        $product->categories()->sync(
            (is_null($categories) || empty($categories))
                ? []
                : collect($categories)->pluck('id')
        );
    }

    /**
     * Synchronize product categories
     *
     * @param \App\Models\Product $product
     * @param array|null $categories
     * @return void
     */

    public static function synchronizeProductCollections($product, ?array $collections = [])
    {
        $product->collections()->sync(
            (is_null($collections) || empty($collections))
                ? []
                : collect($collections)->pluck('id')
        );
    }
}
