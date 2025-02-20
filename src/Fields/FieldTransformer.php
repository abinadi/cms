<?php

namespace Statamic\Fields;

use Statamic\Support\Arr;
use Statamic\Facades\Fieldset;

class FieldTransformer
{
    public static function fromVue(array $submitted)
    {
        $method = $submitted['type'] . 'SectionField';

        return static::$method($submitted);
    }

    private static function importSectionField(array $submitted)
    {
        return array_filter([
            'import' => $submitted['fieldset'],
            'prefix' => $submitted['prefix'] ?? null
        ]);
    }

    private static function inlineSectionField(array $submitted)
    {
        return array_filter([
            'handle' => $submitted['handle'],
            'field' => static::cleanConfig(array_except($submitted['config'], ['isNew']))
        ]);
    }

    private static function referenceSectionField(array $submitted)
    {
        return array_filter([
            'handle' => $submitted['handle'],
            'field' => $submitted['field_reference'],
            'config' => static::cleanConfig(array_only($submitted['config'], $submitted['config_overrides']))
        ]);
    }

    private static function cleanConfig($config)
    {
        $config = Arr::removeNullValues($config);

        if (Arr::get($config, 'width') === 100) {
            unset($config['width']);
        }

        return $config;
    }

    public static function toVue($field): array
    {
        if (isset($field['import'])) {
            return static::importFieldToVue($field);
        }

        return (is_string($field['field']))
            ? static::referenceFieldToVue($field)
            : static::inlineFieldToVue($field);
    }

    private static function referenceFieldToVue($field): array
    {
        $fieldsetField = array_get(static::fieldsetFields(), $field['field'], []);

        $mergedConfig = array_merge(
            $fieldsetFieldConfig = array_get($fieldsetField, 'config', []),
            $config = array_get($field, 'config', [])
        );

        $mergedConfig['width'] = $mergedConfig['width'] ?? 100;

        return [
            'handle' => $field['handle'],
            'type' => 'reference',
            'field_reference' => $field['field'],
            'config' => $mergedConfig,
            'config_overrides' => array_keys($config),
            'fieldtype' => $fieldsetField['type'],
        ];
    }

    private static function inlineFieldToVue($field): array
    {
        $config = $field['field'];
        $config['width'] = $config['width'] ?? 100;

        return [
            'handle' => $field['handle'],
            'type' => 'inline',
            'config' => $config,
            'fieldtype' => $config['type'] ?? 'text',
        ];
    }

    private static function importFieldToVue($field): array
    {
        return [
            'type' => 'import',
            'fieldset' => $field['import'],
            'prefix' => $field['prefix'] ?? null,
        ];
    }

    public static function fieldsetFields()
    {
        if (app()->has($binding = 'statamic.fieldset.fields')) {
            return app($binding);
        }

        $fields = Fieldset::all()->flatMap(function ($fieldset) {
            return collect($fieldset->fields())->mapWithKeys(function ($field, $handle) use ($fieldset) {
                return [$fieldset->handle().'.'.$field->handle() => array_merge($field->toBlueprintArray(), [
                    'fieldset' => [
                        'handle' => $fieldset->handle(),
                        'title' => $fieldset->title(),
                    ]
                ])];
            });
        })->sortBy('display')->all();

        app()->instance($binding, $fields);

        return $fields;
    }
}
