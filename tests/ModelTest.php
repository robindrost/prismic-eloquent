<?php

namespace RobinDrost\PrismicEloquent\Tests;

use Illuminate\Support\Collection;
use RobinDrost\PrismicEloquent\Tests\Stubs\ModelStub;
use RobinDrost\PrismicEloquent\Tests\Stubs\ModelStubWithGetMethod;

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
     public function it_can_attach_data()
     {
         $model = new ModelStub;
         $model->attachDocument($this->document);
         $this->assertEquals($this->document, $model->document);
     }

     /**
      * @test
      */
     public function it_can_access_data_by_properties()
     {
         $model = new ModelStub;
         $model->attachDocument($this->document);
         $this->assertEquals($this->document->data->title, $model->title);
     }

     /**
      * @test
      */
     public function it_will_use_get_methods_when_specified()
     {
         $model = new ModelStubWithGetMethod;
         $model->attachDocument($this->document);
         $this->assertEquals($this->document->data->title . ' extra text', $model->title);
     }

    /**
     * @test
     */
     public function it_can_retrieve_document_attribute()
     {
         $model = new ModelStub;
         $model->attachDocument($this->document);
         $this->assertEquals($this->document->id, $model->id);
     }

     /**
      * @test
      */
     public function it_can_create_an_collection_of_models()
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