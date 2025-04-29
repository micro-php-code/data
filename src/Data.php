<?php

declare(strict_types=1);

namespace MicroPHP\Data;

use ArrayAccess;
use Exception;
use JsonSerializable;
use MicroPHP\Contract\ArrayAble;
use MicroPHP\Data\Attribute\DataAttribute;
use MicroPHP\Data\Traits\ArrayAccessTrait;
use MicroPHP\Data\Util\Str;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionProperty;
use ReflectionUnionType;

class Data implements ArrayAble, ArrayAccess, JsonSerializable
{
    use ArrayAccessTrait;

    protected ?ReflectionClass $_staticReflection = null;

    /**
     * @param mixed $data
     * @throws
     */
    public function __construct($data = [])
    {
        $this->fill($this->isArrayAble($data) ? $data->toArray() : $data);
    }

    public function __toString(): string
    {
        return json_encode($this->jsonSerialize(), JSON_UNESCAPED_UNICODE);
    }

    public function __debugInfo(): ?array
    {
        return $this->objectToArray($this, false);
    }

    public function __serialize(): array
    {
        $data = [];
        foreach ($this->getStaticReflection()->getProperties() as $property) {
            if (! $this->isInsideProperty($property)) {
                $data[$property->getName()] = $property->getValue($this);
            }
        }
        return $data;
    }

    public static function from($data): static
    {
        return new static($data);
    }

    /**
     * @return $this
     * @throws
     */
    public function fill(array $data): static
    {
        $data = $this->beforeFill($data);
        if (empty($data)) {
            return $this;
        }
        foreach ($this->getStaticReflection()->getProperties() as $property) {
            $propertyName = $property->getName();
            $camelCasePropertyName = Str::camel($propertyName);
            $snakePropertyName = Str::snake($propertyName);
            if (
                ! array_key_exists($camelCasePropertyName, $data)
                && ! array_key_exists($snakePropertyName, $data)
                && ! $property->isInitialized($this)
            ) {
                throw new Exception("Property {$property->getName()} is not set in : " . get_class($this));
            }
            $type = $property->getType();
            $value = $data[$camelCasePropertyName] ?? ($data[$snakePropertyName] ?? null);
            if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
                $property->setValue($this, $value);
            } elseif ($type->isBuiltin() && ! is_null($value)) {
                $property->setValue($this, $value);
            } elseif (PHP_VERSION_ID > 80100 && enum_exists($type->getName())) {
                if (is_int($value) || is_string($value)) {
                    $value = $type->getName()::{'from'}($value);
                }
                $property->setValue($this, $value);
            } elseif (class_exists($type->getName())) {
                if (is_array($value)) {
                    $instance = new ($type->getName());
                    if ($instance instanceof Data) {
                        $instance->fill($value);
                    }
                    $property->setValue($this, $instance);
                } else {
                    $property->setValue($this, $value);
                }
            }
        }
        $this->afterFill($data);
        return $this;
    }

    public function toArray(): array
    {
        $toSnake = false;
        $attribute = $this->getStaticReflection()->getAttributes(DataAttribute::class)[0] ?? null;
        if ($attribute) {
            $attribute = $attribute->newInstance();
            $toSnake = $attribute->toSnakeArray;
        }

        return $this->objectToArray($this, $toSnake);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    protected function propertyToArray(object $object, bool $toSnake, ReflectionProperty ...$properties): array
    {
        if (! $object instanceof Data) {
            return json_decode(json_encode($object), true);
        }
        $result = [];
        foreach ($properties as $property) {
            if ($this->isInsideProperty($property)) {
                continue;
            }
            $name = $toSnake ? Str::snake($property->getName()) : $property->getName();
            if ($property->isInitialized($object)) {
                $result[$name] = $this->forValue($property->getValue($object), $toSnake);
            } else {
                $result[$name] = null;
            }
        }
        return $result;
    }

    protected function forValue(mixed $value, bool $toSnake)
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->forValue($item, $toSnake), $value);
        }
        if (is_object($value)) {
            if (PHP_VERSION_ID > 80100 && enum_exists(get_class($value))) {
                return $value->value;
            }
            if ($this->isArrayAble($value)) {
                return $value->toArray();
            }
            return $this->objectToArray($value, $toSnake);
        }
        return $value;
    }

    protected function objectToArray(object $object, $toSnake): array
    {
        return $this->propertyToArray($object, $toSnake, ...$this->getReflectionClass($object)->getProperties());
    }

    /**
     * @throws
     */
    protected function getReflectionClass(object|string $object): ReflectionClass
    {
        return new ReflectionClass($object);
    }

    protected function getStaticReflection(): ReflectionClass
    {
        if ($this->_staticReflection) {
            return $this->_staticReflection;
        }
        return $this->_staticReflection = $this->getReflectionClass($this);
    }

    protected function isInsideProperty(ReflectionProperty $property): bool
    {
        return Str::startsWith($property->getName(), '_');
    }

    protected function isArrayAble($data): bool
    {
        return $data instanceof ArrayAble || is_object($data) && method_exists($data, 'toArray');
    }

    protected function beforeFill(array $data): array
    {
        return $data;
    }

    protected function afterFill(array $data) {}
}
