<?php

namespace RobinDrost\PrismicEloquent\Contracts;

interface DocumentResolver
{
    /**
     * Resolve a single document.
     *
     * @param object $parent
     * @param string $field
     * @return Model
     */
    public function resolve($parent, string $field) :? Model;

    /**
     * Resolve many documents.
     *
     * @param array $documents
     */
    public function resolveMany(array $documents);

    /**
     * Set the model namespace. This is used to link document types to models.
     *
     * @param string $modelNamespace
     * @return mixed
     */
    public function setModelNamespace(string $modelNamespace);
}
