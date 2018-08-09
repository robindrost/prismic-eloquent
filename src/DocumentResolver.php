<?php

namespace RobinDrost\PrismicEloquent;

use RobinDrost\PrismicEloquent\Contracts\Model as ModelContract;
use RobinDrost\PrismicEloquent\Contracts\DocumentResolver as DocumentResolverContract;
use RobinDrost\PrismicEloquent\Contracts\QueryBuilder as QueryBuilderContract;

class DocumentResolver implements DocumentResolverContract
{

    /**
     * @var string
     */
    protected $modelNamespace;

    /**
     * @param null $modelNamespace
     */
    public function __construct($modelNamespace = null)
    {
        $this->setModelNamespace(
            ! empty($modelNamespace) ? $modelNamespace : config('prismiceloquent.model_namespace')
        );
    }

    /**
     * @inheritdoc
     */
    public function setModelNamespace(string $modelNamespace)
    {
        $this->modelNamespace = $modelNamespace;
    }

    /**
     * Resolve a content relation by its type and id.
     *
     * @param object $parent
     * @param string $field
     */
    public function resolve($parent, string $field)
    {
        if ($this->isValid($parent->{$field})) {
            if (property_exists($parent, 'data')) {
                $parent = $parent->data;
            }

            $parent->{$field} = $this->transformTypeToModel($parent->{$field}->type)::findById($parent->{$field}->id);
        }
    }

    /**
     * Use this method on groups that have content relation fields. This
     * will load all fields at once instead of single queries each time.
     *
     * E.g:
     * $documentResolver->resolveArray($articles->my_group_field);
     *
     * @param array $group
     */
    public function resolveMany(array $group)
    {
        $references = collect([]);

        // We need an anonymous model without a content type to retrieve
        // all sort of pages since a relational field can use multiple
        // content types.
        $anonymousModel = new class() extends Model {
            public function newQuery() : QueryBuilderContract
            {
                return new QueryBuilder($this);
            }
        };

        foreach ($group as $key => $document) {
            foreach ($document as $field => $data) {
                if ($this->isValid($data)) {
                    $references->push([
                        'key' => $key,
                        'field' => $field,
                        'id' => $data->id,
                        'model' => $this->transformTypeToModel($data->type),
                    ]);
                }
            }
        }

        $models = collect($anonymousModel::api()->getByIds($references->pluck('id')->toArray())->results);

        foreach ($references as $reference) {
            $group[$reference['key']]->{$reference['field']} = $reference['model']::newInstance(
                $models->firstWhere('id', $reference['id'])
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function resolveEagerLoaded(ModelContract $model, string $field)
    {
        if ($this->isValid($model->data->{$field})) {
            $model->data->{$field} = $this->transformTypeToModel(
                $model->{$field}->type::newInstance($model->data->{$field})
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function resolveManyEagerLoaded(array $group)
    {
        foreach ($group as $key => $document) {
            foreach ($document as $field => $data) {
                if ($this->isValid($data)) {
                    $group[$key]->{$field} = $this->transformTypeToModel(
                        $data->type::newInstance($data)
                    );
                }
            }
        }
    }

    /**
     * Parse the given document type to a Prismic Eloquent model.
     *
     * @param string $documentType
     * @return string
     */
    protected function transformTypeToModel(string $documentType) : string
    {
        return $this->modelNamespace . ucfirst(camel_case($documentType));
    }

    /**
     * Validate a (field) document if it is usable for resolving.
     *
     * @param $document
     * @return bool
     */
    protected function isValid($document) : bool
    {
        return is_object($document) &&
            property_exists($document, 'isBroken') &&
            ! $document->isBroken &&
            property_exists($document, 'id');
    }
}
