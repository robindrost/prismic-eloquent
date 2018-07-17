<?php

namespace RobinDrost\PrismicEloquent\Tests\Stubs;

class ModelStub extends \RobinDrost\PrismicEloquent\Model
{
    public function parent()
    {
        return $this->hasOne('parent', ['page' => ModelStub::class]);
    }

    public function relatedPages()
    {
        return $this->hasMany('related', ['page' => ModelStub::class]);
    }

    public function linked()
    {
        return $this->hasManyThroughGroup('other_pages', 'other_page', ['page' => ModelStub::class,]);
    }

    public function getTypeName()
    {
        return 'page';
    }
}
