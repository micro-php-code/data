<?php

use Ltaooo\Data\Attribute\DataAttribute;
use Ltaooo\Data\Data;

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