<?php
declare(strict_types=1);

namespace App\EntityBuilder;

use App\DTO\IndicatorDTO;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\DtoBundle\Interfaces\DTOInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

abstract class EntityBuilderBase
{
    protected DTOInterface $config;
    /** @var array<IndicatorEntityInterface> */
    protected array $indicatorDependencies;

    /** @var class-string<IndicatorEntityInterface> */
    protected string $entityClass;

    private Connection $connection;

    private string $sequenceName;

    /**
     * @param DTOInterface $config
     * @param array<IndicatorEntityInterface> $indicatorDependencies
     * @param EntityManagerInterface $em
     */
    public function __construct(
        DTOInterface $config,
        array $indicatorDependencies,
        EntityManagerInterface $em,
    ) {
        $this->config = $config;
        $this->indicatorDependencies = $indicatorDependencies;

        $this->connection = $em->getConnection();

        $metadata = $em->getClassMetadata($this->entityClass);
        $this->sequenceName = sprintf(
            '%s_%s_seq',
            $metadata->getTableName(),
            $metadata->getSingleIdentifierColumnName(),
        );
    }

    /**
     * @param KlineInterface $kline
     * @param IndicatorEntityInterface|null $prevEntity
     * @param array<IndicatorDTO> $indicatorDependencies
     *
     * @return IndicatorEntityInterface
     */
    abstract public function build(
        KlineInterface $kline,
        IndicatorEntityInterface|null $prevEntity,
        array $indicatorDependencies,
    ): IndicatorEntityInterface;

    /**
     * @param DTOInterface $config
     * @param class-string<DTOInterface> $expectedConfigClassName
     * @return void
     */
    final protected function validateConfigClass(DTOInterface $config, string $expectedConfigClassName): void
    {
        if(!$config instanceof $expectedConfigClassName) {
            throw new \InvalidArgumentException(sprintf(
                'Expected config of type %s, got %s',
                $expectedConfigClassName,
                get_class($config),
            ));
        }
    }

    final protected function getNextId(bool $isClosed): int
    {
        if($isClosed === false) {
            return 0;
        }

        return $this->connection->fetchOne(sprintf(
            'SELECT NEXTVAL(\'%s\')',
            $this->sequenceName,
        ));
    }
}
