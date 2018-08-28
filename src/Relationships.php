<?php

namespace RobinDrost\PrismicEloquent;

use RobinDrost\PrismicEloquent\Contracts\Model as ModelContract;

trait Relationships
{
    /**
     * @var array
     */
    protected $relationships = [];

    /**
     * Store resolve methods to call and return a query builder.
     *
     * @param mixed ...$resolvers
     * @return ModelContract
     */
    public static function with(...$methods) : ModelContract
    {
        $model = new static;

        $model->relationships = $methods;

        return $model;
    }

    /**
     * Execute all relationship resolvers set through the 'with' method.
     *
     * @return ModelContract
     */
    public function resolveRelationships() : ModelContract
    {
        foreach ($this->relationships as $relationship) {
            if (!method_exists($this, $relationship)) {
                throw new \InvalidArgumentException(
                    "Method $relationship does not exists on model " . static::class . '.'
                );
            }

            $this->{$relationship}();
        }

        return $this;
    }

    /**
     * Define has one relation for a field.
     *
     * @param string $relation
     * @param string $field
     * @param object|null $parent
     */
    protected function hasOne($relation, $field, $parent = null)
    {
        if (empty($parent)) {
            $parent = $this->data;
        }

        if (!$this->isResolvable($parent->{$field})) {
            return $parent->{$field};
        }

        if ($this->isEagerLoaded($parent->{$field})) {
            $model = $this->relationToModel($relation, $parent->{$field}->type);
            $parent->{$field} = $model::newInstance($parent->{$field});
        } else {
            $model = $this->relationToModel($relation, $parent->{$field}->type);
            $parent->{$field} = $model::findById($parent->{$field}->id);
        }

        return $parent->{$field};
    }

    /**
     * Define has many relation for a field.
     *
     * @param string $relation
     * @param string $group
     * @param string $field
     * @param object|null $parent
     */
    protected function hasMany($relation, $group, $field, $parent = null)
    {
        if (empty($parent)) {
            $parent = $this->data;
        }

        $refs = [];

        foreach ($parent->{$group} as $key => $item) {
            if (!$this->isResolvable($item->{$field})) {
                continue;
            }

            if ($this->isEagerLoaded($item->{$field})) {
                $model = $this->relationToModel($relation, $item->{$field}->type);
                $item->{$field} = $model::newInstance($item->{$field});
            } else {
                $refs[$key] = $item->{$field}->id;
            }
        }

        if (!empty($refs)) {
            $documents = static::newInstance(null)->newEmptyQuery()->findByIds($refs);
        }

        foreach ($refs as $key => $ref) {
            $document = $documents->first(function ($document) use ($ref) {
                return $document->id == $ref;
            });

            if (!empty($document)) {
                $model = $this->relationToModel($relation, $document->type);
                $parent->{$group}[$key]->{$field} = $model::newInstance($document->document);
            }
        }

        return $parent->{$group};
    }

    /**
     * Check if a field is resolvable / broken.
     *
     * @param object $data
     *
     * @return bool
     */
    protected function isResolvable($data)
    {
        return !$data instanceof Model
            && is_object($data)
            && property_exists($data, 'isBroken')
            && !$data->isBroken
            && property_exists($data, 'id');
    }

    /**
     * Resolve the corrent relation model based on the document type.
     *
     * @param mixed $relation
     * @param string $type
     *
     * @return string
     */
    protected function relationToModel($relation, string $type) : string
    {
        return is_array($relation) ? $relation[$type] : $relation;
    }

    /**
     * Check if a field already has a data attribute.
     *
     * @param object $field
     *
     * @return bool
     */
    protected function isEagerLoaded($field)
    {
        return property_exists($field, 'data');
    }
}
