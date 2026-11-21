<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Registry\Container;

use LogicException;
use Psr\Container\ContainerInterface;

use function array_find;
use function array_merge;
use function method_exists;
use function sprintf;

/**
 * @internal
 *
 * @template TKey of string
 * @template TVal of mixed
 *
 * @implements ContainerInterface<TKey,TVal>
 */
final class ChainContainer implements ContainerInterface
{
    /** @api */
    public function __construct(
        /** @var list<ContainerInterface<TKey,TVal>> */
        private readonly array $containers,
    ) {
    }

    public function has(string $id): bool
    {
        return null !== $this->firstContainerWith($id);
    }

    public function get(string $id): mixed
    {
        return $this->firstContainerWith($id)?->get($id)
            ?? throw new ServiceNotFoundException($id);
    }

    /** @return array<string,string> */
    public function getProvidedServices(): array
    {
        $providedServicesPerContainer = [];

        foreach ($this->containers as $container) {
            if (!method_exists($container, 'getProvidedServices')) {
                throw new LogicException(sprintf('Container "%s" does not list its provided services: it has no getProvidedServices() method.', $container::class));
            }

            $providedServicesPerContainer[] = $container->getProvidedServices();
        }

        /** @var list<array<string,string>> $providedServicesPerContainer */
        return array_merge(...$providedServicesPerContainer);
    }

    /**
     * @param TKey $id
     *
     * @return ?ContainerInterface<TKey,TVal>
     */
    private function firstContainerWith(string $id): ?ContainerInterface
    {
        return array_find($this->containers, static fn(ContainerInterface $container): bool => $container->has($id));
    }
}
