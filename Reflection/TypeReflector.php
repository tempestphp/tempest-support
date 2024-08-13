<?php

declare(strict_types=1);

namespace Tempest\Support\Reflection;

use Exception;
use Generator;
use ReflectionClass as PHPReflectionClass;
use ReflectionIntersectionType as PHPReflectionIntersectionType;
use ReflectionNamedType as PHPReflectionNamedType;
use ReflectionParameter as PHPReflectionParameter;
use ReflectionProperty as PHPReflectionProperty;
use ReflectionType as PHPReflectionType;
use ReflectionUnionType as PHPReflectionUnionType;
use Reflector as PHPReflector;
use TypeError;

final readonly class TypeReflector implements Reflector
{
    private string $definition;

    public function __construct(
        private PHPReflector|PHPReflectionType|string $reflector,
    ) {
        $this->definition = $this->resolveDefinition($this->reflector);
    }

    public function asClass(): ClassReflector
    {
        return new ClassReflector($this->definition);
    }

    public function equals(string|TypeReflector $type): bool
    {
        if (is_string($type)) {
            $type = new TypeReflector($type);
        }

        return $this->definition === $type->definition;
    }

    public function accepts(mixed $input): bool
    {
        $test = eval(sprintf('return fn (%s $input) => $input;', $this->definition));

        try {
            $test($input);
        } catch (TypeError) {
            return false;
        }

        return true;
    }

    public function matches(string $className): bool
    {
        return is_a($this->definition, $className, true);
    }

    public function getName(): string
    {
        return $this->definition;
    }

    public function getShortName(): string
    {
        $parts = explode('\\', $this->definition);

        return $parts[array_key_last($parts)];
    }

    public function isBuiltIn(): bool
    {
        return in_array($this->definition, [
            'string',
            'bool',
            'float',
            'int',
            'array',
            'null',
            'object',
            'callable',
            'resource',
            'never',
            'void',
            'true',
            'false',
        ]);
    }

    public function isClass(): bool
    {
        return class_exists($this->definition);
    }

    public function isIterable(): bool
    {
        return in_array($this->definition, [
            'array',
            'iterable',
            Generator::class,
        ]);
    }

    /** @return self[] */
    public function split(): array
    {
        return array_map(
            fn (string $part) => new self($part),
            preg_split('/[&|]/', $this->definition),
        );
    }

    private function resolveDefinition(PHPReflector|PHPReflectionType|string $reflector): string
    {
        if (is_string($reflector)) {
            return $reflector;
        }

        if (
            $reflector instanceof PHPReflectionParameter
            || $reflector instanceof PHPReflectionProperty
        ) {
            return $this->resolveDefinition($reflector->getType());
        }

        if ($reflector instanceof PHPReflectionClass) {
            return $reflector->getName();
        }

        if ($reflector instanceof PHPReflectionNamedType) {
            return $reflector->getName();
        }

        if ($reflector instanceof PHPReflectionUnionType) {
            return implode('|', array_map(
                fn (PHPReflectionType $reflectionType) => $this->resolveDefinition($reflectionType),
                $reflector->getTypes(),
            ));
        }

        if ($reflector instanceof PHPReflectionIntersectionType) {
            return implode('&', array_map(
                fn (PHPReflectionType $reflectionType) => $this->resolveDefinition($reflectionType),
                $reflector->getTypes(),
            ));
        }

        throw new Exception('Could not resolve type');
    }
}
