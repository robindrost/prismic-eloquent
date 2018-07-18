<?php

namespace RobinDrost\PrismicEloquent\Tests\Stubs;

class ModelStub extends \RobinDrost\PrismicEloquent\Model
{
    public function parent()
    {
        return $this->hasOne(ModelStub::class, 'parent');
    }

    public function parentWithMultipleModels()
    {
        return $this->hasOne(['page' => ModelStub::class], 'parent');
    }

    public function relatedPages()
    {
        return $this->hasMany(ModelStub::class, 'other_pages', 'other_page');
    }

    public function linked()
    {
        return $this->hasManyThroughGroup(ModelStub::class, 'other_pages', 'other_page');
    }

    public function getTypeName()
    {
        return 'page';
    }
}
