<?php

namespace RobinDrost\PrismicEloquent\Tests\Stubs;

class ModelStub extends \RobinDrost\PrismicEloquent\Model
{
    public function getParentAttribute()
    {
        return $this->hasOne(ModelStub::class, 'parent');
    }

    public function parentWithMultipleModels()
    {
        return $this->hasOne(['page' => ModelStub::class], 'parent');
    }

    public function getOtherPagesAttribute()
    {
        return $this->hasMany(ModelStub::class, 'other_pages', 'other_page');
    }

    public static function getTypeName()
    {
        return 'page';
    }
}
