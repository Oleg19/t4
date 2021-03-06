<?php

require_once realpath(__DIR__ . '/../../framework/boot.php');

class StdTest extends PHPUnit_Framework_TestCase {

    public function testArrayAccess()
    {
        $obj = new T4\Core\Std();

        $obj->foo = 'bar';
        $this->assertEquals('bar', $obj->foo);
        $this->assertEquals('bar', $obj['foo']);

        $obj['baz'] = 'bla';
        $this->assertEquals('bla', $obj->baz);
        $this->assertEquals('bla', $obj['baz']);
    }

    public function testCountable()
    {

        $obj = new \T4\Core\Std();
        $this->assertEquals(0, count($obj));
        $obj->a = 1;
        $this->assertEquals(1, count($obj));
        $obj->b = 2;
        $this->assertEquals(2, count($obj));
        unset($obj->a);
        $this->assertEquals(1, count($obj));
    }

    public function testArrayable()
    {
        $array1 = ['foo' => 1, 'bar' => 2, 'baz' => 3];
        $obj1 = new \T4\Core\Std($array1);
        $this->assertEquals(1, $obj1->foo);
        $this->assertEquals(2, $obj1->bar);
        $this->assertEquals(3, $obj1->baz);

        $obj2 = new \T4\Core\Std();
        $obj2->fromArray($array1);
        $this->assertEquals(1, $obj2->foo);
        $this->assertEquals(2, $obj2->bar);
        $this->assertEquals(3, $obj2->baz);

        $this->assertEquals($array1, $obj2->toArray());

        $array2 = ['foo' => 1, 'bar' => ['baz'=>11, 'bla'=>12]];
        $obj3 = new \T4\Core\Std($array2);
        $this->assertEquals(1, $obj3->foo);
        $this->assertTrue($obj3->bar instanceof T4\Core\Std);
        $this->assertEquals(11, $obj3->bar->baz);
        $this->assertEquals(12, $obj3->bar->bla);

        $this->assertEquals($array2, $obj3->toArray());

    }

    public function testMerge()
    {
        $obj1 = new \T4\Core\Std(['foo' => 1]);
        $obj1->merge(['bar' => 2]);
        $this->assertEquals(1, $obj1->foo);
        $this->assertEquals(2, $obj1->bar);
        $this->assertEquals(new \T4\Core\Std(['foo' => 1, 'bar' => 2]), $obj1);

        $obj2 = new \T4\Core\Std(['foo' => 11]);
        $obj2->merge(new \T4\Core\Std(['bar' => 21]));
        $this->assertEquals(11, $obj2->foo);
        $this->assertEquals(21, $obj2->bar);
        $this->assertEquals(new \T4\Core\Std(['foo' => 11, 'bar' => 21]), $obj2);
    }

    public function testNumericOffsets()
    {
        $obj = new \T4\Core\Std();
        $obj[1] = 100;
        $obj->{2} = 200;
        $this->assertEquals(100, $obj[1]);
        $this->assertEquals(100, $obj->{1});
        $this->assertEquals(200, $obj[2]);
        $this->assertEquals(200, $obj->{2});
    }

    public function testIssetUnset()
    {
        $obj = new \T4\Core\Std();
        $this->assertFalse(isset($obj->foo));
        $obj->foo = 'bar';
        $this->assertTrue(isset($obj->foo));
        unset($obj->foo);
        $this->assertFalse(isset($obj->foo));
    }

    public function testChain()
    {
        $obj = new \T4\Core\Std();
        $this->assertFalse(isset($obj->foo));
        $this->assertFalse(isset($obj->foo->bar));
        $this->assertTrue(empty($obj->foo));
        $this->assertTrue(empty($obj->foo->bar));

        $obj->foo->bar = 'baz';
        $this->assertTrue(isset($obj->foo));
        $this->assertTrue(isset($obj->foo->bar));
        $this->assertFalse(empty($obj->foo));
        $this->assertFalse(empty($obj->foo->bar));

        $this->assertTrue($obj->foo instanceof T4\Core\Std);
        $this->assertEquals(new \T4\Core\Std(['bar'=>'baz']), $obj->foo);
        $this->assertEquals('baz', $obj->foo->bar);
    }

}