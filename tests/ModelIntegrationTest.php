<?php

namespace RobinDrost\PrismicEloquent\Tests;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use RobinDrost\PrismicEloquent\Model;

class ModelIntegrationTest extends \Orchestra\Testbench\TestCase
{
    const TEST_ID = 'W22rSR8AAIP9alVA';

    public function testItCanFindADocumentById()
    {
        $model = ModelStub::findById(self::TEST_ID);
        $this->assertEquals('test', $model->title);
    }

    public function testItCanFindDocumentsById()
    {
        $model = ModelStub::findByIds([self::TEST_ID])->first();
        $this->assertEquals('test', $model->title);
    }

    public function testItCanFetchAllDocuments()
    {
        $models = ModelStub::all();
        $this->assertInstanceOf(Collection::class, $models);

        $models->each(function ($model) {
            $this->assertInstanceOf(ModelStub::class, $model);
        });
    }

    public function testItCanPaginateDocuments()
    {
        $models = ModelStub::paginate(1);
        $this->assertInstanceOf(LengthAwarePaginator::class, $models);
        $this->assertCount(1, $models);
        $this->assertEquals(2, $models->total());
    }

    public function testItCanApplyAWhereClause()
    {
        $model = ModelStub::where('title', 'test')->first();
        $this->assertEquals('test', $model->title);
    }

    public function testItCanApplyAWhereNotClause()
    {
        $model = ModelStub::whereNot('title', 'test')->first();
        $this->assertEquals('test 2', $model->title);
    }

    public function testItCanApplyAWhereInClause()
    {
        $model = ModelStub::where('title', ['test'])->first();
        $this->assertEquals('test', $model->title);
    }

    public function testItCanApplyAWhereTagClause()
    {
        $model = ModelStub::whereTag('test')->first();
        $this->assertEquals('test', $model->title);
    }

    public function testItCanApplyAWhereTagsClause()
    {
        $model = ModelStub::whereTags(['test'])->first();
        $this->assertEquals('test', $model->title);
    }

    public function testItCanApplyAWhereLanguageClause()
    {
        $model = ModelStub::whereLanguage('en-gb')->first();
        $this->assertEquals('test', $model->title);
    }

    public function testItCanOrderResults()
    {
        $model = ModelStub::orderBy('order')->first();
        $this->assertEquals('test', $model->title);

        $model = ModelStub::orderBy('order desc')->first();
        $this->assertEquals('test 2', $model->title);
    }

    public function testItCanFetchRelatedContent()
    {
        $model = ModelStub::fetch('test.title')->findById(self::TEST_ID);
        $this->assertEquals('test 2', $model->related_test->data->title);
    }

    public function testItCanResolveAField()
    {
        $model = ModelStub::with('test')->findById(self::TEST_ID);

        $this->assertInstanceOf(ModelStub::class, $model->related_test);
    }

    public function testItCanResolveManyFields()
    {
        $model = ModelStub::with('testMany')->findById(self::TEST_ID);

        foreach ($model->related_tests as $item) {
            $this->assertInstanceOf(ModelStub::class, $item->test);
        }
    }

    public function testItCanResolveAnEagerLoadedField()
    {
        $model = ModelStub::with('testEagerLoaded')->fetch('test.title')->findById(self::TEST_ID);
        $this->assertInstanceOf(ModelStub::class, $model->related_test);
    }

    public function testItCanResolveManyEagerLoadedFields()
    {
        $model = ModelStub::with('testManyEagerLoaded')->fetch('test.title')->findById(self::TEST_ID);

        foreach ($model->related_tests as $item) {
            $this->assertInstanceOf(ModelStub::class, $item->test);
        }
    }

    public function testItCanHandleScopes()
    {
        $model = ModelStub::testTitle()->first();
        $this->assertEquals('test', $model->title);
    }

    protected function getPackageProviders($app)
    {
        return ['RobinDrost\PrismicEloquent\Providers\ServiceProvider'];
    }
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('prismiceloquent.url', 'https://robindrost.cdn.prismic.io/api/v2');
        $app['config']->set('prismiceloquent.document_resolver.models.test', ModelStub::class);
    }
}

class ModelStub extends Model
{
    public function test()
    {
        $this->hasOne(ModelStub::class, 'related_test');
    }

    public function testMany()
    {
        $this->hasMany(ModelStub::class, 'related_tests', 'test');
    }

    public function testEagerLoaded()
    {
        $this->hasOne(ModelStub::class, 'related_test');
    }

    public function testManyEagerLoaded()
    {
        $this->hasMany(ModelStub::class, 'related_tests', 'test');
    }

    public function scopeTestTitle($query)
    {
        $query->where('title', 'test');
    }

    public static function getTypeName() : string
    {
        return 'test';
    }
}