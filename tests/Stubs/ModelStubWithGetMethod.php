<?php

namespace RobinDrost\PrismicEloquent\Tests\Stubs;

use RobinDrost\PrismicEloquent\Model;

class ModelStubWithGetMethod extends Model
{
    public function getTitle()
    {
        return $this->field('title') . ' extra text';
    }

    public function getTypeName()
    {
        return 'page';
    }
}
