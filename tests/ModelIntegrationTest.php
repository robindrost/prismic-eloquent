<?php

namespace RobinDrost\PrismicEloquent\Tests;

use Illuminate\Support\Collection;
use RobinDrost\PrismicEloquent\Tests\Stubs\PageStub;
use RobinDrost\PrismicEloquent\Tests\Stubs\AuthorStub;

class ModelIntegrationTest extends \PHPUnit\Framework\TestCase
{
    public function testItCanFindADocumentById()
    {
        $model = PageStub::findById('a');

        $this->assertInstanceOf(PageStub::class, $model);
    }

    public function testItCanFindDocumentsByIds()
    {
        $model = PageStub::findByIds(['a']);

        $this->assertInstanceOf(Collection::class, $model);
    }

    public function testItCanFindAllDocuments()
    {
        $models = PageStub::all();

        $this->assertInstanceOf(Collection::class, $models);
    }

    public function testItCanPaginateDocuments()
    {
        $models = PageStub::paginate(2);

        $this->assertInstanceOf(Collection::class, $models);
        $this->assertCount(2, $models);
    }

    public function testItCanApplyAWhereClause()
    {
        $model = PageStub::where('title', 'test')->first();

        $this->assertInstanceOf(PageStub::class, $model);
    }

    public function testItCanFetchRelatedContentFields()
    {
        $model = ModelStub::fetch('author.name')->findById('1');

        $this->assertEquals('Test', $model->author->test);
    }
}
