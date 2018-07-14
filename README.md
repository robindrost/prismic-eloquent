# Prismic Eloquent

Use the Prismic Api in a more (friendly) eloquent like way.


## Requirement
- Laravel 5.5+
- Prismic SDK 4+

## Installation
Install through Composer:

```
composer require robindrost/prismic-eloquent
```

Download Zip:

https://github.com/robindrost/prismic-eloquent/archive/master.zip

## Difference between default Prismic api kit

#### How you normally use the prismic api kit

```
class PageController extends Controller
{
    public function index()
    {
        $api = Api::get('https://your-repo-name.prismic.io/api/v2');
      
        $response = $api->query(
            Predicates::at('document.type', 'page'),
            [
                'fetchLinks' => ['author.name', 'author.hair_color],
                'orderings' => '[my.page.date desc]' 
            ]
        );
        
        $result = $response->getResults()[0];
        
        return view('page', [
            'page' => $result,
        ]);
    }
}
```

page.blade.php:

```
<h1>{{ $page->date->title }}</h1>
``` 

#### How to use it with Prismic eloquent

```
class PageController extends Controller
{
    public function index()
    {
        $page = Page::with('author.name', 'author.hair_color')
            ->orderBy('date desc')
            ->get();
        
        return view('page', [
            'page' => $page,
        ]);
    }
}
```

page.blade.php:

```
<h1>{{ $page->title }}</h1>
``` 

## Configuration

Copy the default configuration

```
php artisan vendor:publish
```

Start with changing the values inside the config/prismic-eloquent.php. You will need. a prismic repository URL and an access token to use the Prismic api.

## Creating models

```
namespace App;

use RobinDrost\PrismicEloquent\Model;

class Page extends Model
{
	public function getTypeName()
	{
		return 'page';
	}
}

```

By default you are able to retrieve any property from the data type.

```
App\Page::findByUid(1)->title;

```

Will return The response from the title field.

Optional get methods:

```
namespace App;

use RobinDrost\PrismicEloquent\Model;

class Page extends Model
{
	public function getTitle()
	{
		return $this->attribute('title') . ' my super suffix.';
	}

	public function getTypeName()
	{
		return 'page';
	}
}

```

Now calling the title property will use the defined get method.

```
App\Page::find(1)->title;

```

Will return:

Will return The response from the title field + ' my super suffix'.

#### Field and attributes

By default the model will look for a get{Name} method, or a field with the given name of a direct document attribute like uid.

``` $model->uid ``` Will return the document's uid (**as long as there is no field with the name uid**).


## Query the Api
####Find by UID

```
$page = Page::find('my-title');

```

####Find by document ID

```
$page = Page::findById('w6yHsaAw98a');

```

#### Collection of pages

```
Page::all();
```

#### Collection of pages **filtered**

```
Page::where('color', 'red')->get();
```

#### Collection of pages filtered by **multiple values**

```
Page::whereIn('color', ['red', 'blue])->get();
```

#### Collection of pages filtered by **tags**

```
Page::tags('color', ['red', 'blue])->get();
```

#### Collection of pages filtered by **publication date**

```
Page::wherePublicationDate('2018-07-03')->get();
```

```
Page::wherePublicationDate('2018', 'year')->get();
```

```
Page::wherePublicationDate('may', 'month')->get();
```

#### Ordering

```
Page::orderBy('field')->get();
```

```
Page::orderBy('field asc')->get();
```

```
Page::orderBy('field desc')->get();
```

#### Language's

```
Page::language('nl-NL')->find('test-slug');
```

#### Paginate
You are also able to ti paginated queries. This works exactly the same as the normal paginate query you are used to. It takes a parameter from the url (by default: 'page') and runs a paginated query. The returned value is a LenghtAwarePaginator.

```
Page::paginate();
```

You can either specify the amount of pages here or on your model's perPage property.

```
Page::paginate(10);
```

Custom URL parameter

```
Page::paginate(10, [], 'myPageQueryParameter');
```

#### Specify fields

The methods all, get, and paginate have an option to specify fields that should get returned.

```
Page::all(['title', 'body'])
Page::get(['title', 'body'])
Page::paginate(10, ['title', 'body'])
```

#### Chaining
All methods are chainable e.g:

```
Page::wherePublicationDate('may', 'month')->where('field', 'value')->get();
```

## Relationships

Prismic offers an option to fetch linked content (throug content relation field).
Link: https://prismic.io/docs/php/query-the-api/fetch-linked-document-fields

Please note that you can only specify some fields as related fields as described in the documentation of Prismic (link above).

#### Usage:

Relations are a bit different then you are used to in Eloquent. Relations are defined in get methods (described above). Here is an example:

```
class Page extends Model
{
	public function getMyAwesomeArticle()
	{
		return $this->relation(Article::class, 'my_awesome_article');
	}
	
	public function getTypeName()
	{
		return 'page';
	}
}
```
You have to specify the related model and field on the current model.
In this case the related content is an article and the data is currently in the field my_awesome_article.

```
$page = Page::with('article.title', 'article.body')->get();

dump($page->article->title);
dump($page->article->body);
```

Note that you need to specify the "content type" in this case "article".

#### Group with relational fields


```
class Page extends Model
{
	public function getMyAwesomeArticles()
	{
		return $this->relations(Article::class, 'mygroup_field', 'my_awesome_articles');
	}
	
	public function getTypeName()
	{
		return 'page';
	}
}
```

This will return an collection of the group field with loaded relations.

```
$page = Page::with('article.title', 'article.body')->get();

$page->articles->each(function ($item) {
	dump($item->my_awesome_articles->title;
});
```


## Caching

You are free to cache the returned data in any way you want. Like with the normal Laravel models.

## Feedback

Please create issues or pull requests in case you find any problems while using the package.
