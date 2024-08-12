<?php

declare(strict_types=1);

namespace Ltaooo\Data;

use ArrayAccess;
use Exception;
use JsonSerializable;
use Ltaooo\Data\Attribute\DataAttribute;
use Ltaooo\Data\Contract\ArrayAble;
use Ltaooo\Data\Traits\ArrayAccessTrait;
use ReflectionClass;
use ReflectionProperty;
use function Symfony\Component\String\u;

class Data implements ArrayAble, ArrayAccess, JsonSerializable
{
    use ArrayAccessTrait;
    protected ?ReflectionClass $_staticReflection = null;

    protected array $_strCache = [];

    public function __construct(array|Arrayable $data = [])
    {
        $this->fill($data instanceof Arrayable ? $data->toArray() : $data);
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
        return $this->toArray();
    }

    /**
     * @throws Exception
     */
    public function __unserialize(array $data)
    {
        $this->fill($data);
    }

    public static function from(array|Arrayable $data): static
    {
        return new static($data);
    }

    /**
     * @param array $data
     * @return $this
     * @throws
     */
    public function fill(array $data): static
    {
        if (empty($data)) {
            return $this;
        }
        foreach ($this->getStaticReflection()->getProperties() as $property) {
            $propertyName = $property->getName();
            $camelCasePropertyName = $this->strCamel($propertyName);
            $snakePropertyName = $this->strSnake($propertyName);
            if (
                !array_key_exists($camelCasePropertyName, $data)
                && !array_key_exists($snakePropertyName, $data)
                && !$property->isInitialized($this)
            ) {
                throw new Exception("Property {$property->getName()} is not set in : " . get_class($this));
            }
            $type = $property->getType();
            $value = $data[$camelCasePropertyName] ?? ($data[$snakePropertyName] ?? null);
            if ($type->isBuiltin() && !is_null($value)) {
                $property->setValue($this, $value);
            }
            if (class_exists($type->getName())) {
                if (is_array($value)) {
                    $instance = new ($type->getName());
                    if ($instance instanceof Data) {
                        $instance->fill($value);
                    }
                    $property->setValue($this, $instance);
                }
            }
        }
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
        $result = [];
        foreach ($properties as $property) {
            if ($this->isInsideProperty($property)) {
                continue;
            }
            $name = $toSnake ? $this->strSnake($property->getName()) : $property->getName();
            $result[$name] = $this->forValue($property->getValue($object), $toSnake);
        }
        return $result;
    }

    protected function forValue(mixed $value, bool $toSnake)
    {
        if (is_array($value)) {
            return array_map(fn($item) => $this->forValue($item, $toSnake), $value);
        }
        if (is_object($value)) {
            if ($value instanceof Arrayable) {
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
        return $this->_staticReflection ?? $this->getReflectionClass($this);
    }

    protected function strCamel(string $str)
    {
        if (isset($this->_strCache['camel'][$str])) {
            return $this->_strCache['camel'][$str];
        }
        return $this->_strCache['camel'][$str] = u($str)->camel()->toString();
    }

    protected function strSnake(string $str)
    {
        if (isset($this->_strCache['snake'][$str])) {
            return $this->_strCache['snake'][$str];
        }
        return $this->_strCache['snake'][$str] = u($str)->snake()->toString();
    }

    protected function isInsideProperty(ReflectionProperty $property): bool
    {
        if (isset($this->_strCache['inside'][$property->getName()])) {
            return $this->_strCache['inside'][$property->getName()];
        }
        return $this->_strCache['inside'][$property->getName()] = u($property->getName())->startsWith('_');
    }

}