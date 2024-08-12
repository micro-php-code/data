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
});

test('str', function () {
    expect(Str::snake('HelloWorld'))->toBe('hello_world')
        ->and(Str::camel('hello_world'))->toBe('helloWorld')
        ->and(Str::camel(''))->toBe('')
        ->and(Str::startsWith('hello', 'he'))->toBeTrue();
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