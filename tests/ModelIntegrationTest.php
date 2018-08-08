<?php

namespace RobinDrost\PrismicEloquent\Tests;

use Illuminate\Support\Collection;
use RobinDrost\PrismicEloquent\Model;
use RobinDrost\PrismicEloquent\Tests\Stubs\ModelStub;
use RobinDrost\PrismicEloquent\Tests\Stubs\ModelSingleTypeStub;

class ModelIntegrationTest extends \Orchestra\Testbench\TestCase
{
    /**
     * @test
     */
    public function itShouldFindASingleType()
    {
        $singlePage = ModelSingleTypeStub::single('single_page');

        $this->assertInstanceOf(Model::class, $singlePage);
        $this->assertEquals('single_page', $singlePage->type);
    }

    /**
     * @test
     */
    public function itShouldFindADocumentById()
    {
        $page = ModelStub::findById('W0XqJx8AAMLjIlBe');

        $this->assertInstanceOf(Model::class, $page);
        $this->assertEquals('W0XqJx8AAMLjIlBe', $page->id);
    }

    /**
     * @test
     */
    public function itShouldFindADocumentByUId()
    {
        $page = ModelStub::find('a');

        $this->assertInstanceOf(Model::class, $page);
        $this->assertEquals('a', $page->uid);
    }

    /**
     * @test
     */
    public function itShouldFindAllPages()
    {
        $pages = ModelStub::all();

        $this->assertInstanceOf(Collection::class, $pages);
    }

    /**
     * @test
     */
    public function itCanApplyAWhereClause()
    {
        $page = ModelStub::where('field', 'test')->first();

        $this->assertInstanceOf(Model::class, $page);
        $this->assertEquals($page->field, 'test');
    }

    /**
     * @test
     */
    public function itCanApplyAWhereInClause()
    {
        $page = ModelStub::whereIn('field', ['test'])->first();

        $this->assertInstanceOf(Model::class, $page);
        $this->assertEquals($page->field, 'test');
    }

    /**
     * @test
     */
    public function itCanApplyAWhereTagsClause()
    {
        $page = ModelStub::whereTags('test')->first();

        $this->assertInstanceOf(Model::class, $page);
        $this->assertEquals($page->field, 'test');
    }

    /**
     * @test
     */
    public function itCanApplyAWherePublicationDateClause()
    {
        $page = ModelStub::wherePublicationDate('July', 'month')->first();
        $this->assertInstanceOf(Model::class, $page);

        $page = ModelStub::wherePublicationDate(2018, 'year')->first();
        $this->assertInstanceOf(Model::class, $page);
    }

    /**
     * @test
     */
    public function itCanOrderTheResults()
    {
        $page = ModelStub::orderBy('title')->first();
        $this->assertInstanceOf(Model::class, $page);
        $this->assertEquals('A', $page->title[0]->text);

        $page = ModelStub::orderBy('title desc')->first();
        $this->assertInstanceOf(Model::class, $page);
        $this->assertEquals('B', $page->title[0]->text);
    }

    /**
     * @test
     */
    public function itCanResolveARelationship()
    {
        $page = ModelStub::findById('W0XqJx8AAMLjIlBe');
        $this->assertEquals($page->parent->title[0]->text, 'B');
    }

    /**
     * @test
     */
    public function itCanResolveARelationshipWithMultipleModelOptions()
    {
        $page = ModelStub::findById('W0XqJx8AAMLjIlBe');

        $this->assertEquals($page->parentWithMultipleModels()->title[0]->text, 'B');
    }

    /**
     * @test
     */
    public function itCanHandleAHasManyRelationship()
    {
        $page = ModelStub::findById('W0XqJx8AAMLjIlBe');

        foreach ($page->other_pages as $page) {
            $this->assertInstanceOf(ModelStub::class, $page->other_page);
        }
    }

    /**
     * @test
     */
    public function itCanPeformASearch()
    {
        $results = ModelStub::search('A')->get();

        $this->assertInstanceOf(Collection::class, $results);
    }

    /**
     * @test
     */
    public function itCanMakeAPaginatedRequest()
    {
        $page = ModelStub::paginate();
        $this->assertEquals(2, $page->total());

        $page = ModelStub::paginate(1);
        $this->assertEquals(2, $page->total());
        $this->assertEquals(2, $page->lastPage());
    }

    /**
     * @test
     */
    public function itCanPreLoadRelations()
    {
        $page = ModelStub::with('parent')->findById('W0XqJx8AAMLjIlBe');
        $this->assertEquals($page->parent->title[0]->text, 'B');
    }

    protected function getPackageProviders($app)
    {
        return ['RobinDrost\PrismicEloquent\Providers\ServiceProvider'];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('prismiceloquent.url', 'https://robindrost.cdn.prismic.io/api/v2');
    }
}
