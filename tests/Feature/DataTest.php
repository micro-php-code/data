<?php

use Ltaooo\Data\Attribute\DataAttribute;
use Ltaooo\Data\Data;
use Ltaooo\Data\Util\Str;

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
    var_dump(serialize($c));
    $c1 = unserialize(serialize($c));
    var_dump($c1);
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
    expect($c->a)->toBe(enumA::A);

});

it('throws exception', function () {
    C::from(['name' => '123', 'a' => 'b']);
})->throws(ValueError::class);

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
    public string|int $name;
}