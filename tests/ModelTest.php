<?php

namespace RobinDrost\PrismicEloquent\Tests;

use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use RobinDrost\PrismicEloquent\Tests\Stubs\PageStub;

class ModelTest extends TestCase
{
    protected $documentStub;

    public function setUp()
    {
        parent::setUp();

        $this->documentStub = json_decode(json_encode([
            'type' => 'test',
            'data' => [
                'title' => 'Test',
            ],
        ]));
    }

    public function testItCanReturnTheContentType()
    {
        $this->assertEquals('page_stub', PageStub::getTypeName());
    }

    public function testItCanCreateANewInstanceOfItself()
    {
        $this->assertInstanceOf(PageStub::class, PageStub::newInstance($this->documentStub));
    }

    public function testItCanCreateACollectionOfModels()
    {
        $models = [
            PageStub::newInstance($this->documentStub),
            PageStub::newInstance($this->documentStub),
            PageStub::newInstance($this->documentStub),
        ];

        $collection = PageStub::newCollection($models);

        $this->assertInstanceOf(Collection::class, $collection);
    }

    public function testItCanCheckIfAnAttributeExists()
    {
        $this->assertTrue(PageStub::newInstance($this->documentStub)->hasAttribute('type'));
        $this->assertFalse(PageStub::newInstance($this->documentStub)->hasAttribute('non_existing'));
    }

    public function testItCanRetrieveAnAttribute()
    {
        $this->assertEquals(
            $this->documentStub->type,
            PageStub::newInstance($this->documentStub)->attribute('type')
        );
    }

    public function testItCanCheckIfAFieldExists()
    {
        $this->assertTrue(PageStub::newInstance($this->documentStub)->hasField('title'));
        $this->assertFalse(PageStub::newInstance($this->documentStub)->hasField('non_existing'));
    }

    public function testItCanRetrieveAField()
    {
        $this->assertEquals(
            $this->documentStub->data->title,
            PageStub::newInstance($this->documentStub)->field('title')
        );
    }
}
