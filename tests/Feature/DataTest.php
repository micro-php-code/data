<?php

declare(strict_types=1);

use MicroPHP\Data\Attribute\DataAttribute;
use MicroPHP\Data\Data;
use MicroPHP\Data\Util\Str;

test('data', function () {
    $value = ['name' => '123', 'phones' => [1, 2], 'b' => ['name' => 'hello'], 'address_detail' => 'haa'];
    $a = A::from($value);

    expect($a->name)
        ->toBe('123')
        ->and($a->phones)->toBe([1, 2])
        ->and($a->toArray())->toBe($value);
});

test('serialize', function () {
    $value = ['name' => '123', 'phones' => [1, 2], 'b' => ['name' => 'hello'], 'address_detail' => 'haa'];
    $a = A::from($value);
    /** @var A $b */
    $b = unserialize(serialize($a));
    expect($b)->toBeInstanceOf(A::class)
        ->and($b->name)->toBe($a->name)
        ->and($b->addressDetail)->toBe($a->addressDetail);

    $c = C::from(['name' => '123', 'a' => enumA::A]);
    $c->toArray();
    $c1 = unserialize(serialize($c));
    expect($c1)->toBeInstanceOf(C::class);
});

test('str', function () {
    $value = ['name' => 'hello'];
    $d = D::from($value);
    expect($d)->toBeInstanceOf(Data::class)
        ->and($d->name)
        ->toEqual($value['name']);
});

test('union type', function () {
    expect(Str::snake('HelloWorld'))->toBe('hello_world')
        ->and(Str::camel('hello_world'))->toBe('helloWorld')
        ->and(Str::camel(''))->toBe('')
        ->and(Str::startsWith('hello', 'he'))->toBeTrue();
});

test('enum', function () {
    $c = C::from(['name' => '123', 'a' => 'a']);
    expect($c->a)->toBe(enumA::A)
        ->and($c->toArray())->toBe(['name' => '123', 'a' => 'a']);
    $c = serialize($c);
    $c = unserialize($c);
    expect($c->a)->toBe(enumA::A);
});

it('throws exception', function () {
    C::from(['name' => '123', 'a' => 'b']);
})->throws(ValueError::class);

test('construct', function () {
    $e = new E('hello');
    expect($e->name)->toBe('hello');
    $e = serialize($e);
    $e = unserialize($e);
    expect($e)->toBeInstanceOf(E::class);
});

test('object toArray', function () {
    $e = new F(new DateTimeImmutable(), new E('hello'));
    expect($e->toArray()['date'])->toBeInstanceOf(DateTimeImmutable::class)
        ->and($e->toArray()['e'])->toBeArray();
});

#[DataAttribute(toSnakeArray: true)]
class A extends Data
{
    public string $name;

    public array $phones;

    public B $b;

    public string $addressDetail;
}

class B extends Data
{
    public string $name;
}

class C extends Data
{
    public string $name;

    public EnumA $a;
}

enum enumA: string
{
    case A = 'a';
}

class D extends Data
{
    public int|string $name;
}

class E extends Data
{
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
        parent::__construct();
    }
}

class F extends Data
{
    public DateTimeImmutable $date;

    public E $e;

    public function __construct(DateTimeImmutable $date, E $e)
    {
        $this->date = $date;
        $this->e = $e;
        parent::__construct();
    }
}
