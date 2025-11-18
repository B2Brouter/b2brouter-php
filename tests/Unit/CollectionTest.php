<?php

namespace B2BRouter\Tests\Unit;

use B2BRouter\Collection;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    public function testCollectionCreation()
    {
        $data = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
        ];

        $meta = [
            'total' => 100,
            'offset' => 0,
            'limit' => 2
        ];

        $collection = new Collection($data, $meta);

        $this->assertCount(2, $collection);
        $this->assertEquals($data, $collection->all());
        $this->assertEquals($meta, $collection->getMeta());
    }

    public function testGetTotal()
    {
        $collection = new Collection([], ['total' => 100, 'offset' => 0, 'limit' => 25]);
        $this->assertEquals(100, $collection->getTotal());
    }

    public function testGetOffset()
    {
        $collection = new Collection([], ['total' => 100, 'offset' => 25, 'limit' => 25]);
        $this->assertEquals(25, $collection->getOffset());
    }

    public function testGetLimit()
    {
        $collection = new Collection([], ['total' => 100, 'offset' => 0, 'limit' => 25]);
        $this->assertEquals(25, $collection->getLimit());
    }

    public function testHasMoreWhenThereAreMoreResults()
    {
        $collection = new Collection(
            array_fill(0, 25, ['id' => 1]),
            ['total' => 100, 'offset' => 0, 'limit' => 25]
        );

        $this->assertTrue($collection->hasMore());
    }

    public function testHasMoreWhenThereAreNoMoreResults()
    {
        $collection = new Collection(
            array_fill(0, 25, ['id' => 1]),
            ['total' => 100, 'offset' => 75, 'limit' => 25]
        );

        $this->assertFalse($collection->hasMore());
    }

    public function testHasMoreWithExactLastPage()
    {
        $collection = new Collection(
            array_fill(0, 25, ['id' => 1]),
            ['total' => 100, 'offset' => 75, 'limit' => 25]
        );

        // 75 + 25 = 100, so no more results
        $this->assertFalse($collection->hasMore());
    }

    public function testHasMoreWithoutMeta()
    {
        $collection = new Collection([['id' => 1]]);
        $this->assertFalse($collection->hasMore());
    }

    public function testIteration()
    {
        $data = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
            ['id' => 3, 'name' => 'Item 3'],
        ];

        $collection = new Collection($data);

        $items = [];
        foreach ($collection as $key => $item) {
            $items[$key] = $item;
        }

        $this->assertEquals($data, $items);
    }

    public function testMultipleIterations()
    {
        $data = [
            ['id' => 1],
            ['id' => 2],
        ];

        $collection = new Collection($data);

        // First iteration
        $count1 = 0;
        foreach ($collection as $item) {
            $count1++;
        }

        // Second iteration should work
        $count2 = 0;
        foreach ($collection as $item) {
            $count2++;
        }

        $this->assertEquals(2, $count1);
        $this->assertEquals(2, $count2);
    }

    public function testEmptyCollection()
    {
        $collection = new Collection([]);

        $this->assertCount(0, $collection);
        $this->assertEquals([], $collection->all());

        $count = 0;
        foreach ($collection as $item) {
            $count++;
        }

        $this->assertEquals(0, $count);
    }

    public function testCollectionWithNullMeta()
    {
        $collection = new Collection([['id' => 1]], null);

        $this->assertNull($collection->getMeta());
        $this->assertNull($collection->getTotal());
        $this->assertNull($collection->getOffset());
        $this->assertNull($collection->getLimit());
        $this->assertFalse($collection->hasMore());
    }

    public function testCountable()
    {
        $data = array_fill(0, 5, ['id' => 1]);
        $collection = new Collection($data);

        $this->assertCount(5, $collection);
        $this->assertEquals(5, count($collection));
    }
}
