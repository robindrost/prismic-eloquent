<?php

namespace RobinDrost\PrismicEloquent;

class Relationship
{
    protected $model;
    protected $field;
    protected $fields;
    protected $group;

    public function __construct($model, $field, $fields, $group = null)
    {
        $this->model = $model;
        $this->field = $field;
        $this->fields = $fields;
        $this->group = $group;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function resolve($originalModel)
    {
        empty($this->group)
            ? $this->resolveOne($originalModel) : $this->resolveMany($originalModel);
    }

    public function resolveOne($originalModel)
    {
        $field = $originalModel->field($this->field);

        if ($this->isValidDocument($field)) {
            if (is_array($this->model)) {
                $originalModel->data->{$this->field} = $this->model[$field->type]::newInstance($field);
            } else {
                $originalModel->data->{$this->field} = $this->model::newInstance($field);
            }
        }
    }

    public function resolveMany($originalModel)
    {
        $originalModel->data->{$this->group} = collect($originalModel->field($this->group))
            ->map(function ($group) {
                $field = $group->{$this->field};

                if (is_array($this->model)) {
                    return $this->model[$field->type]::newInstance($field);
                } else {
                    return $this->model::newInstance($field);
                }
            });
    }

    protected function isValidDocument($document)
    {
        return true;
    }
}
