<?php
declare(strict_types=1);

namespace App\Kstrwbry\DtoBundle\Builder;

use App\Kstrwbry\DtoBundle\Base\DtoBase;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Symfony\Component\String\UnicodeString;

class DtoClassBuilder
{
    private string $className;
    private string $namespace;
    private array $properties;

    public function __construct(
        string $className,
        string $namespace,
        array $properties,
    ) {
        $this->className = $className;
        $this->namespace = $namespace;
        $this->properties = $properties;
    }

    public function build(): PhpFile
    {
        $file = new PhpFile();

        $namespace = $file->addNamespace($this->namespace);

        $class = $namespace
            ->addClass($this->className)
            ->setExtends(DtoBase::class);

        foreach ($this->properties as $propertyName => $types) {
            $this->addClassProperty($class, $propertyName, $types);
        }

        return $file;
    }

    private function addClassProperty(ClassType $class, string $propertyName, string $types): void
    {
        $nullable = $this->getIsNullable($types);
        $phpTypes = $this->getPhpTypes($types);

        $this->addProperty($class, $propertyName, $phpTypes, $nullable);
        $this->addPropertyGetter($class, $propertyName, $phpTypes, $nullable);
        $this->addPropertySetter($class, $propertyName, $phpTypes, $nullable);
    }


    protected function addProperty(ClassType $class, string $propertyName, string $phpTypes, bool $nullable): void
    {
        $class->addProperty($propertyName)
            ->setVisibility('protected')
            ->setType($phpTypes)
            ->setNullable($nullable);
    }

    protected function addPropertyGetter(ClassType $class, string $propertyName, string $phpType, bool $nullable): void
    {
        $class->addMethod($this->createGetterName($propertyName))
            ->setReturnType($phpType)
            ->setReturnNullable($nullable)
            ->setBody(sprintf('return $this->%s;', $propertyName));
    }

    protected function addPropertySetter(ClassType $class, string $propertyName, string $phpType, bool $nullable): void
    {
        $class->addMethod($this->createSetterName($propertyName))
            ->setReturnType('static')
            ->setBody(sprintf('$this->%s = $%s;%sreturn $this;', $propertyName, $propertyName, "\n\n"))
            ->addParameter($propertyName)
            ->setType($phpType)
            ->setNullable($nullable);
    }

    protected function getIsNullable($types): bool
    {
        return str_contains($types, 'null');
    }

    protected function getPhpTypes(string $types)
    {
        $phpTypes = explode('|', $types);
        $phpTypes = array_map('trim', array_filter($phpTypes));

        $phpTypes = array_filter(
            array_filter($phpTypes),
            static fn(string $type): bool => $type !== 'null',
        );

        return implode('|', $phpTypes);
    }

    public static function createGetterName(string $propertyName): string
    {
        return static::createMethodName($propertyName, 'get');
    }

    public static function createSetterName(string $propertyName): string
    {
        return static::createMethodName($propertyName, 'set');
    }

    protected static function createMethodName(string $propertyName, string $prefix): string
    {
        return $prefix . new UnicodeString($propertyName)->camel()->title();
    }
}
