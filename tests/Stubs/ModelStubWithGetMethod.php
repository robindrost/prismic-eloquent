<?php

namespace RobinDrost\PrismicEloquent\Tests\Stubs;

use RobinDrost\PrismicEloquent\Model;

class ModelStubWithGetMethod extends Model
{
    public function getTitle()
    {
        return $this->field('title') . ' extra text';
    }

    public static function getTypeName()
    {
        return 'page';
    }
}
