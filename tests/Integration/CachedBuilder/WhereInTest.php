<?php namespace GeneaLabs\LaravelModelCaching\Tests\Integration\CachedBuilder;

use GeneaLabs\LaravelModelCaching\Tests\Fixtures\Author;
use GeneaLabs\LaravelModelCaching\Tests\Fixtures\Book;
use GeneaLabs\LaravelModelCaching\Tests\Fixtures\UncachedAuthor;
use GeneaLabs\LaravelModelCaching\Tests\Fixtures\UncachedBook;
use GeneaLabs\LaravelModelCaching\Tests\IntegrationTestCase;
use GeneaLabs\LaravelModelCaching\Tests\Fixtures\Post;
use GeneaLabs\LaravelModelCaching\Tests\Fixtures\Publisher;
use GeneaLabs\LaravelModelCaching\Tests\Fixtures\UncachedPublisher;
use GeneaLabs\LaravelModelCaching\Tests\Fixtures\UncachedPost;

class WhereInTest extends IntegrationTestCase
{
    public function testWhereInUsingCollectionQuery()
    {
        $key = sha1("genealabs:laravel-model-caching:testing:{$this->testingSqlitePath}testing.sqlite:books:genealabslaravelmodelcachingtestsfixturesbook-author_id_in_1_2_3_4");
        $tags = [
            "genealabs:laravel-model-caching:testing:{$this->testingSqlitePath}testing.sqlite:genealabslaravelmodelcachingtestsfixturesbook",
        ];
        $authors = (new UncachedAuthor)
            ->where("id", "<", 5)
            ->get(["id"]);

        $books = (new Book)
            ->whereIn("author_id", $authors)
            ->get();
        $cachedResults = $this
            ->cache()
            ->tags($tags)
            ->get($key)['value'];
        $liveResults = (new UncachedBook)
            ->whereIn("author_id", $authors)
            ->get();

        $this->assertEquals($liveResults->pluck("id"), $books->pluck("id"));
        $this->assertEquals($liveResults->pluck("id"), $cachedResults->pluck("id"));
    }

    public function testWhereInWhenSetIsEmpty()
    {
        $key = sha1("genealabs:laravel-model-caching:testing:{$this->testingSqlitePath}testing.sqlite:authors:genealabslaravelmodelcachingtestsfixturesauthor-id_in_-authors.deleted_at_null");
        $tags = [
            "genealabs:laravel-model-caching:testing:{$this->testingSqlitePath}testing.sqlite:genealabslaravelmodelcachingtestsfixturesauthor",
        ];
        $authors = (new Author)
            ->whereIn("id", [])
            ->get();
        $cachedResults = $this
            ->cache()
            ->tags($tags)
            ->get($key)['value'];
        $liveResults = (new UncachedAuthor)
            ->whereIn("id", [])
            ->get();

        $this->assertEquals($liveResults->pluck("id"), $authors->pluck("id"));
        $this->assertEquals($liveResults->pluck("id"), $cachedResults->pluck("id"));
    }

    public function testBindingsAreCorrectWithMultipleWhereInClauses()
    {
        $key = sha1("genealabs:laravel-model-caching:testing:{$this->testingSqlitePath}testing.sqlite:authors:genealabslaravelmodelcachingtestsfixturesauthor-name_in_John-id_in_-name_in_Mike-authors.deleted_at_null");
        $tags = [
            "genealabs:laravel-model-caching:testing:{$this->testingSqlitePath}testing.sqlite:genealabslaravelmodelcachingtestsfixturesauthor",
        ];
        $authors = (new Author)
            ->whereIn("name", ["John"])
            ->whereIn("id", [])
            ->whereIn("name", ["Mike"])
            ->get();
        $cachedResults = $this
            ->cache()
            ->tags($tags)
            ->get($key)['value'];
        $liveResults = (new UncachedAuthor)
            ->whereIn("name", ["Mike"])
            ->whereIn("id", [])
            ->whereIn("name", ["John"])
            ->get();

        $this->assertEquals($liveResults->pluck("id"), $authors->pluck("id"));
        $this->assertEquals($liveResults->pluck("id"), $cachedResults->pluck("id"));
    }

    public function testWhereInUsesCorrectBindings()
    {
        $key = sha1("genealabs:laravel-model-caching:testing:{$this->testingSqlitePath}testing.sqlite:authors:genealabslaravelmodelcachingtestsfixturesauthor-id_in_1_2_3_4_5-id_between_1_99999-authors.deleted_at_null");
        $tags = ["genealabs:laravel-model-caching:testing:{$this->testingSqlitePath}testing.sqlite:genealabslaravelmodelcachingtestsfixturesauthor"];

        $authors = (new Author)
            ->whereIn('id', [1,2,3,4,5])
            ->whereBetween('id', [1, 99999])
            ->get();
        $cachedResults = $this->cache()
            ->tags($tags)
            ->get($key)['value'];
        $liveResults = (new UncachedAuthor)
            ->whereIn('id', [1,2,3,4,5])
            ->whereBetween('id', [1, 99999])
            ->get();

        $this->assertEmpty($authors->diffKeys($cachedResults));
        $this->assertEmpty($liveResults->diffKeys($cachedResults));
    }

    public function testWhereInSubQueryUsesCorrectBindings()
    {
        $key = sha1("genealabs:laravel-model-caching:testing:{$this->testingSqlitePath}testing.sqlite:books:genealabslaravelmodelcachingtestsfixturesbook-publisher_id_in_select_id_from_publishers_where_name_=_Publisher_Foo_or_name_=_Publisher_Bar-id_>_0");
        $tags = [
            "genealabs:laravel-model-caching:testing:{$this->testingSqlitePath}testing.sqlite:genealabslaravelmodelcachingtestsfixturesbook",
        ];

        /** @var Collection $publishers */
        $publishers = factory(UncachedPublisher::class, 5)->create();
        $publishers->get(1)->update(['name' => 'Publisher Foo']);
        $publishers->get(3)->update(['name' => 'Publisher Bar']);

        $publishers->each(function (UncachedPublisher $publisher) {
            factory(UncachedBook::class, 2)->create(['publisher_id' => $publisher->id]);
        });

        $books = Book::query()
            ->whereIn('publisher_id',
                Publisher::select('id')
                    ->where('name', 'Publisher Foo')
                    ->orWhere('name', 'Publisher Bar')
            )
            ->where('id', '>', 0)
            ->get()->pluck('id')->toArray();

        $cachedResults = $this
            ->cache()
            ->tags($tags)
            ->get($key)['value'];

        $liveResults = Book::query()
            ->whereIn('publisher_id',
                Publisher::select('id')
                    ->where('name', 'Publisher Foo')
                    ->orWhere('name', 'Publisher Bar')
            )->get()->pluck('id')->toArray();

        $this->assertCount(4, $books);
        $this->assertSame($liveResults, $books);
        $this->assertSame($liveResults, $cachedResults->pluck('id')->filter()->toArray());
    }
}
