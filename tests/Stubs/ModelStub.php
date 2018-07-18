<?php

namespace RobinDrost\PrismicEloquent\Tests\Stubs;

class ModelStub extends \RobinDrost\PrismicEloquent\Model
{
    public function parent()
    {
        return $this->hasOne(ModelStub::class, 'parent', [
            'title'
        ]);
    }

    public function parentWithMultipleModels()
    {
        return $this->hasOne(['page' => ModelStub::class], 'parent', [
            'page' => [
                'title'
            ]
        ]);
    }

    public function relatedPages()
    {
        return $this->hasMany(ModelStub::class, 'other_pages', 'other_page', [
            'title'
        ]);
    }

    public function linked()
    {
        return $this->hasManyThroughGroup(ModelStub::class, 'other_pages', 'other_page');
    }

    public static function getTypeName()
    {
        return 'page';
    }
}
