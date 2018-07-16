# Prismic Eloquent
Use the Prismic Api in a more (friendly) eloquent like way.

## Requirements
- Laravel 5.5+
- Prismic SDK 4+

## Example Laravel project
[https://github.com/robindrost/prismic-eloquent-example](https://github.com/robindrost/prismic-eloquent-example)

## Installation
Install through Composer:

```
composer require robindrost/prismic-eloquent
```

Download Zip:

https://github.com/robindrost/prismic-eloquent/archive/master.zip

## Difference between default Prismic api kit

#### How you normally use the Prismic api kit

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

Start with changing the values inside the config/prismic-eloquent.php. You will need a prismic repository URL and an access token to use the Prismic api.

## Creating models

```
namespace App;

use RobinDrost\PrismicEloquent\Model;

class Page extends Model
{

}

```

By default the class name is used as the Prismic content type. This class
asumes there is a content type 'page'. Class names are converted to lower snake case.

You can always override the getTypeName method but this is not required.

```
public function getTypeName()
{
    return 'your_content_type_name';
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
    // Document attribute
    public function getId()
    {
       return $this->attribute('id');
    }

    // Document field
    public function getTitle()
    {
       return $this->field('title')[0]->text . ' my super suffix.';
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
#### Find by UID

```
$page = Page::find('my-title');
```

#### Find by document ID

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

#### Language

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

You can either define a relationship through a method on your model or a get{FIELDNAME} method to make it accessible directly through properties.

```
class Page extends Model
{
    public function getMyAwesomeArticle()
    {
        return $this->hasOne(Article::class, 'my_awesome_article');
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

#### Has many relation fields

You can also support the Prismic array syntax by suffixing your fields in Prismic with an array key.
Lets say we have 3 fields with the given names in Prismic:
- my_awesome_article[0]
- my_awesome_article[1]
- my_awesome_article[2]

Note: You can only configure fields like this in the JSON editor of prismic.

On the model you can use the hasMany method instead of the hasOne method:

```
public function getMyAwesomeArticle()
{
    return $this->hasMany(Article::class, 'my_awesome_article');
}
```

#### Group with relational fields

```
class Page extends Model
{
  public function getMyAwesomeArticles()
  {
      return $this->hasOneInGroup(Article::class, 'mygroup_field', 'my_awesome_articles');
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

#### Support multiple model types on a relation
All relational method support an array as the model name. Lets say your relation
can be an article and a person:

```
public function getMyAwesomeArticle()
{
    return $this->hasOne([
        'article => Article::class,
        'person' => Person::class,
    ], 'my_awesome_article');
}
```

Note that the key must be the content type name of the relation.

## Singluar types

The examples above are used with repeatable types e.g news etc. Prismic also
allows you to create a single type content type.

This is how you can query a single type.

```
$page = Page::single();
```

You can still apply requirements like a language:

```
$page = Page::language('nl-NL')->single();
```

This will return an instance of a model with the data from the single type.

## Fallback to the API

You can always access the Prismic Api directly in case you find youself in a situation
where a method is missing.

Lets use the page model as an example:

```
Page::api()->query(YOUR_QUERY_LOGIC);
```

This is where you have to add your own logic. Please see the [prismic.io documentation](https://prismic.io/docs/php/query-the-api/how-to-query-the-api) on
of to work with the api directly.

The api will not return an instance of a model but a plain response. You can always
attach this data by calling the ```$page->attachDocument($document)``` function.

## References
[Prismic documentation](https://prismic.io/docs/php/getting-started/with-the-php-starter-kit)
[Prismic PHP kit](https://github.com/prismicio/php-kit)

## Feedback

Please create issues or pull requests in case you find any problems while using the package.
