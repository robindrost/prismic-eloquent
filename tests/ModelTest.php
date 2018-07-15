<?php

namespace RobinDrost\PrismicEloquent\Tests;

use Illuminate\Support\Collection;
use RobinDrost\PrismicEloquent\Tests\Stubs\ModelStub;
use RobinDrost\PrismicEloquent\Tests\Stubs\ModelStubWithGetMethod;
use RobinDrost\PrismicEloquent\Tests\Stubs\ModelStubNoType;

class ModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \stdClass
     */
    protected $document;

    public function setUp()
    {
        $this->document = json_decode(json_encode([
            'id' => 1,
            'data' => [
                'title' => 'Test',
            ],
        ]));

        return parent::setUp();
    }

    /**
     * @test
     */
    public function itUsesTheClassNameAsContentType()
    {
        $model = new ModelStubNoType;
        $this->assertEquals($model->getTypeName(), 'model_stub_no_type');
    }

    /**
     * @test
    */
    public function itCanAttachData()
    {
        $model = new ModelStub;
        $model->attachDocument($this->document);
        $this->assertEquals($this->document, $model->document);
    }

    /**
     * @test
    */
    public function itCanAccessDataByProperties()
    {
        $model = new ModelStub;
        $model->attachDocument($this->document);
        $this->assertEquals($this->document->data->title, $model->title);
    }

    /**
     * @test
    */
    public function itWillUseAGetMethodWhenSpecified()
    {
        $model = new ModelStubWithGetMethod;
        $model->attachDocument($this->document);
        $this->assertEquals($this->document->data->title . ' extra text', $model->title);
    }

    /**
     * @test
     */
    public function itCanRetrieveDocumentAttributes()
    {
        $model = new ModelStub;
        $model->attachDocument($this->document);
        $this->assertEquals($this->document->id, $model->id);
    }

    /**
     * @test
    */
    public function itCanCreateACollectionOfModels()
    {
        $models = [
            new ModelStub,
            new ModelStub,
            new ModelStub,
        ];

        $model = new ModelStub;
        $this->assertInstanceOf(Collection::class, $model->newCollection($models));
    }
}
