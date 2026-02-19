<?php
declare(strict_types=1);

namespace App\Kstrwbry\BaseBundle;

use App\Kstrwbry\BaseBundle\DependencyInjection\KstrwbryBaseConfiguration;
use App\Kstrwbry\BaseBundle\DependencyInjection\KstrwbryBaseExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\String\UnicodeString;

class KstrwbryBaseBundle extends AbstractBundle
{
    /** @var array<KstrwbryBaseConfiguration> */
    protected const array BUNDLE_CONFIGURATIONS = [];

    private string $bundlePath;

    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = $this->createContainerExtension();
        }

        return $this->extension;
    }

    protected function createContainerExtension(): ?ExtensionInterface
    {
        $class = $this->getContainerExtensionClass();

        if (!class_exists($class)) {
            return new KstrwbryBaseExtension(
                $this->getAlias(),
                $this->getBundlePath(),
                static::BUNDLE_CONFIGURATIONS
            );
        }

        if (is_a($class, KstrwbryBaseExtension::class, true)) {
            return new $class(
                $this->getAlias(),
                $this->getBundlePath(),
                static::BUNDLE_CONFIGURATIONS
            );
        }

       return new $class();
    }

    /**
     * Returns the bundle's container extension class.
     */
    protected function getAlias(): string
    {
        return new UnicodeString($this->getBaseName())->snake()->toString();
    }

    protected function getBaseName(): string
    {
        return preg_replace('/Bundle$/', '', $this->getName());
    }

    private function getBundlePath(): string
    {
        return $this->bundlePath ??= dirname(new \ReflectionObject($this)->getFileName());
    }
}