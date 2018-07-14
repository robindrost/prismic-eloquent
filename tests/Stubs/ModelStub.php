<?php

namespace RobinDrost\PrismicEloquent\Tests\Stubs;

class ModelStub extends \RobinDrost\PrismicEloquent\Model
{
    public function parent()
    {
        return $this->relation(ModelStub::class, 'parent');
    }

    public function linked()
    {
        return $this->relations(ModelStub::class, 'other_pages', 'other_page');
    }

    public function getTypeName()
    {
        return 'page';
    }
}
