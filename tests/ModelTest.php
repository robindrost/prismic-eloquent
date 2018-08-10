<?php

namespace RobinDrost\PrismicEloquent\Tests;

use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use RobinDrost\PrismicEloquent\Contracts\DocumentResolver;
use RobinDrost\PrismicEloquent\Model;
use RobinDrost\PrismicEloquent\Contracts\Model as ModelContract;

class ModelTest extends TestCase
{
    protected $documentStub;

    /**
     * @var ModelContract
     */
    protected $model;

    public function setUp()
    {
        parent::setUp();

        $this->documentStub = json_decode(json_encode([
            'type' => 'test',
            'data' => [
                'title' => 'Test',
                'test_field' => 'test',
            ],
        ]));

        $this->model = new class() extends Model {
            public static function getTypeName(): string
            {
                return 'test';
            }
        };
    }

    public function testItCanReturnTheContentType()
    {
        $this->assertEquals('test', $this->model::getTypeName());
    }

    public function testItCanCreateANewInstanceOfItself()
    {
        $this->assertInstanceOf(Model::class, $this->model::newInstance($this->documentStub));
    }

    public function testItCanAccessFieldsByCamelCase()
    {
        $model = $this->model::newInstance($this->documentStub);
        $this->assertEquals($this->documentStub->data->test_field, $model->testField);
    }

    public function testItCanAttachADocument()
    {
        $this->model->attachDocument($this->documentStub);
        $this->assertEquals($this->documentStub->data->title, $this->model->title);
    }

    public function testItCanCreateACollectionOfModels()
    {
        $models = [
            $this->model::newInstance($this->documentStub),
            $this->model::newInstance($this->documentStub),
            $this->model::newInstance($this->documentStub),
        ];

        $collection = $this->model::newCollection($models);

        $this->assertInstanceOf(Collection::class, $collection);
    }

    public function testItCanCheckIfAnAttributeExists()
    {
        $this->assertTrue($this->model::newInstance($this->documentStub)->hasAttribute('type'));
        $this->assertFalse($this->model::newInstance($this->documentStub)->hasAttribute('non_existing'));
    }

    public function testItCanRetrieveAnAttribute()
    {
        $this->assertEquals(
            $this->documentStub->type,
            $this->model::newInstance($this->documentStub)->attribute('type')
        );
    }

    public function testItCanCheckIfAFieldExists()
    {
        $this->assertTrue($this->model::newInstance($this->documentStub)->hasField('title'));
        $this->assertFalse($this->model::newInstance($this->documentStub)->hasField('non_existing'));
    }

    public function testItCanRetrieveAField()
    {
        $this->assertEquals(
            $this->documentStub->data->title,
            $this->model::newInstance($this->documentStub)->field('title')
        );
    }
}
