<?php

namespace RobinDrost\PrismicEloquent\Contracts;

interface DocumentResolver
{
    /**
     * Resolve a single document.
     *
     * @param mixed $document
     * @return Model
     */
    public function resolve($document) :? Model;

    /**
     * Resolve many documents.
     *
     * @param array $documents
     * @return array
     */
    public function resolveMany(array $documents) : array;

    /**
     * Set the model namespace. This is used to link document types to models.
     *
     * @param string $modelNamespace
     * @return mixed
     */
    public function setModelNamespace(string $modelNamespace);
}
