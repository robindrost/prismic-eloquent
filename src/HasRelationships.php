<?php

namespace RobinDrost\PrismicEloquent;

trait HasRelationships
{
    /**
     * @var
     */
    protected $relations = [];

    /**
     * Define a has One relationship. This method is used to specify a direct
     * relationship on a content relation field.
     *
     * @param string|array $model
     * @param string $field
     *
     * @return Model
     */
    protected function hasOne($model, $field)
    {
        if (array_key_exists($field, $this->relations)) {
            return $this->relations[$field];
        }

        if (!$this->validateField($this->field($field))) {
            return;
        }

        $document = $this->newEmptyQuery()->findById($this->field($field)->id);

        if (is_array($model)) {
            $model = $model[$document->type];
        }

        $this->relations[$field] = $model::newInstance($document);

        return $this->relations[$field];
    }

    /**
     * Define a has many relationship. This method is used on group fields
     * that have sub content relation fields.
     *
     * @param string|array $model
     * @param string $group
     * @param string $field
     *
     * @return array
     */
    protected function hasMany($model, $group, $field)
    {
        if (array_key_exists("$group.$field", $this->relations)) {
            return $this->relations["$group.$field"];
        }

        $ids = collect($this->field($group))->filter(function ($item) use ($field) {
            return $this->validateField($item->{$field});
        })->map(function ($item) use ($field) {
            return $item->{$field}->id;
        })->toArray();

        if (!empty($ids)) {
            $content = $this->newEmptyQuery()->whereIn('document.id', $ids)->get();

            foreach ($this->field($group) as $key => $item) {
                $document = $content->firstWhere('document.id', $item->{$field}->id);

                if (is_array($model)) {
                    $model = $model[$document->type];
                }

                $item->{$field} = $model::newInstance($document);
            }

            $this->relations["$group.$field"] = $this->field($group);

            return $this->relations["$group.$field"];
        }

        return [];
    }

    /**
     * Define a has one relationship inside a slice fields primary section.
     *
     * @param string|array $model
     * @param string $slice
     * @param string $field
     * @param string $group (the slices field name, by default 'body')
     *
     * @return Model
     */
    protected function hasOneInSlice($model, $slice, $field, $group = 'body')
    {
        $slices = $this->field($group);

        $ids = [];

        foreach ($slices as $slice) {
            if ($slice->slice_type === $slice) {
                if ($this->validateField($slice->primary->{$field})) {
                    $ids[] = $slice->primary->{$field}->id;
                }
            }
        }

        if (!empty($ids)) {
            $this->model->newEmptyQuery()->whereIn('document.id', $ids)->get();

            foreach ($slices as $slice) {
                if ($slice->slice_type === $slice) {
                    $document = $content->firstWhere('document.id', $slice->primary->{$field}->id);

                    if (is_array($model)) {
                        $model = $model[$document->type];
                    }

                    $slice->primary->{$field} = $model::newInstance($document);
                }
            }
        }
    }

    /**
     * Define a has many relationship inside a slice items section.
     *
     * @param string|array $model
     * @param string $slice
     * @param string $field
     * @param string $group (the slices field name, by default 'body')
     *
     * @return Model
     */
    protected function hasManyInSlice($model, $slice, $field, $group = 'body')
    {
        $slices = $this->field($group);

        $ids = [];

        foreach ($slices as $slice) {
            if ($slice->slice_type === $slice) {
                if ($this->validateField($slice->items->{$field})) {
                    $ids[] = $slice->items->{$field}->id;
                }
            }
        }

        if (!empty($ids)) {
            $this->model->newEmptyQuery()->whereIn('document.id', $ids)->get();

            foreach ($slices as $slice) {
                if ($slice->slice_type === $slice) {
                    foreach ($slice->items as $item) {
                        $document = $content->firstWhere('document.id', $item->{$field}->id);

                        if (is_array($model)) {
                            $model = $model[$document->type];
                        }

                        $item->{$field} = $model::newInstance($document);
                    }
                }
            }
        }
    }

    /**
     * Validate a field that has a relation defined.
     *
     * @param \stdClass $field
     *
     * @return bool
     */
    protected function validateField($field)
    {
        return is_object($field) &&
            property_exists($field, 'link_type') &&
            $field->link_type === 'Document' &&
            property_exists($field, 'id') &&
            !$field->isBroken;
    }
}
