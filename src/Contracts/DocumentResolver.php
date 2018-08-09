<?php

namespace RobinDrost\PrismicEloquent\Contracts;

interface DocumentResolver
{
    /**
     * Resolve a single document.
     *
     * @param object $parent
     * @param string $field
     */
    public function resolve($parent, string $field);

    /**
     * Resolve many documents.
     *
     * @param array $documents
     */
    public function resolveMany(array $documents);

    /**
     * Resolve a document to a model that has been eager loaded already.
     *
     * @param Model $model
     * @param string $field
     * @return mixed
     */
    public function resolveEagerLoaded(Model $model, string $field);

    /**
     * Resolve many documents to models that are eager loaded already.
     *
     * @param array $group
     * @return mixed
     */
    public function resolveManyEagerLoaded(array $group);

    /**
     * Set the model namespace. This is used to link document types to models.
     *
     * @param string $modelNamespace
     * @return mixed
     */
    public function setModelNamespace(string $modelNamespace);
}
