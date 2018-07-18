<?php

namespace RobinDrost\PrismicEloquent\Tests\Stubs;

class ModelSingleTypeStub extends \RobinDrost\PrismicEloquent\Model
{
    public static function getTypeName()
    {
        return 'single_page';
    }
}
