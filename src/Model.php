<?php

namespace RobinDrost\PrismicEloquent;

use Illuminate\Support\Collection;
use RobinDrost\PrismicEloquent\Contracts\Model as ModelContract;
use RobinDrost\PrismicEloquent\Contracts\QueryBuilder as QueryBuilderContract;
use stdClass;

abstract class Model implements ModelContract
{
    /**
     * @var stdClass
     */
    protected $document;

    /**
     * @var bool
     */
    protected $fieldsToSnakeCase = true;

    /**
     * @var array
     */
    protected $resolvers = [];

    /**
     * Create a new instance of the model with a Prismic document.
     *
     * @param mixed $document
     */
    public function __construct($document = null)
    {
        if (!empty($document)) {
            $this->attachDocument($document);
        }
    }

    /**
     * @inheritdoc
     */
    public function attribute(string $name)
    {
        if ($this->fieldsToSnakeCase) {
            $name = snake_case($name);
        }

        return $this->document->{$name};
    }

    /**
     * @inheritdoc
     */
    public function hasAttribute(string $name) : bool
    {
        return property_exists($this->document, $name);
    }

    /**
     * @inheritdoc
     */
    public function field(string $name)
    {
        return $this->document->data->{$name};
    }

    /**
     * @inheritdoc
     */
    public function hasField(string $name) : bool
    {
        return property_exists($this->document->data, $name);
    }

    /**
     * @inheritdoc
     */
    public function newQuery() : QueryBuilderContract
    {
        return (new QueryBuilder($this))->whereType(static::getTypeName());
    }

    /**
     * Return a query object withouth a type.
     */
    public function newEmptyQuery() : QueryBuilderContract
    {
        return (new QueryBuilder($this));
    }

    /**
     * Execute all resolvers set through the 'with' method.
     *
     * @return ModelContract
     */
    public function resolveDocuments() : ModelContract
    {
        foreach ($this->resolvers as $resolver) {
            $this->{$resolver}();
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

        if ($this->isResolvable($parent->{$field})) {
            if ($this->isEagerLoaded($parent->{$field})) {
                $parent->{$field} =
                    $this->relationToModel($relation, $parent->{$field}->type)::newInstance($parent->{$field});
            } else {
                $parent->{$field} =
                    $this->relationToModel($relation, $parent->{$field}->type)::findById($parent->{$field}->id);
            }
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
            if ($this->isResolvable($item->{$field})) {
                if ($this->isEagerLoaded($item->{$field})) {
                    $item->{$field} =
                        $this->relationToModel($relation, $item->{$field}->type)::newInstance($item->{$field});
                } else {
                    $refs[$key] = $item->{$field}->id;
                }
            }
        }

        $documents = static::newInstance(null)->newEmptyQuery()->findByIds($refs);

        foreach ($refs as $key => $ref) {
            $document = $documents->first(function ($document) use ($ref) {
                return $document->id == $ref;
            });

            if (!empty($document)) {
                $parent->{$group}[$key]->{$field} =
                    $this->relationToModel($relation, $document->type)::newInstance($document);
            }
        }
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
        if (is_array($relation)) {
            return $relation[$type];
        }

        return $relation;
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

    /**
     * @inheritdoc
     */
    public static function getTypeName() : string
    {
        $fullPath = explode('\\', get_called_class());
        return snake_case(array_pop($fullPath));
    }

    /**
     * Store resolve methods to call and return a query builder.
     *
     * @param mixed ...$resolvers
     * @return ModelContract
     */
    public static function with(...$methods) : ModelContract
    {
        $model = new static;

        $model->resolvers = $methods;

        return $model;
    }

    /**
     * @inheritdoc
     */
    public function attachDocument($document) : ModelContract
    {
        $this->document = $document;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function newInstance($document) : ModelContract
    {
        return new static($document);
    }

    /**
     * @inheritdoc
     */
    public static function newCollection(array $models) : Collection
    {
        return collect($models);
    }

    /**
     * Return a new instance of the query builder.
     *
     * @param string $method
     * @param mixed $arguments
     * @return mixed
     */
    public function __call(string $method, $arguments)
    {
        return $this->newQuery()->{$method}(...$arguments);
    }

    /**
     * Create a new instance of the model.
     *
     * @param string $name
     * @param mixed $arguments
     * @return mixed
     */
    public static function __callStatic(string $name, $arguments)
    {
        return (new static)->{$name}(...$arguments);
    }

    /**
     * Check if the given name is either an existing attribute or field and
     * return that.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        if ($this->hasAttribute($name)) {
            return $this->attribute($name);
        }

        if ($this->fieldsToSnakeCase) {
            $name = snake_case($name);
        }

        if ($this->hasField($name)) {
            $fieldMethod = 'get' . ucfirst(camel_case($name)) . 'Field';

            if (method_exists($this, $fieldMethod)) {
                return $this->{$fieldMethod}($this->field($name));
            }

            return $this->field($name);
        }

        return null;
    }
}
