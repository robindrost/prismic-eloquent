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
        $this->setModelNamespace(! empty($modelNamespace) ?? config('prismiceloquent.model_namespace'));
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
     * @param object $document
     * @return ModelContract|null
     */
    public function resolve($document) :? ModelContract
    {
        if ($this->isValid($document)) {
            return $this->transformTypeToModel($document->type)::findById($document->id);
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
     * @return array
     */
    public function resolveMany(array $group) : array
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

        foreach ($group as $key => $field) {
            if ($this->isValid($field)) {
                $references->push([
                    'key' => $key,
                    'id' => $field->id,
                    'model' => $this->transformTypeToModel($field->type),
                ]);
            }
        }

        $models = $anonymousModel::findByIds($references->pluck('id')->toArray());

        foreach ($references as $reference) {
            $group[$reference['key']] = $reference['model']::newInstance(
                $models->firstWhere('document.id', $reference['id'])
            );
        }

        return $group;
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
