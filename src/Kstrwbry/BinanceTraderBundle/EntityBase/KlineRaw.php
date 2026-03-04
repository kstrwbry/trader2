<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\EntityBase;

use App\DTO\KlinerawDTO;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineRawInterface;
use App\Kstrwbry\BinanceTraderBundle\Trait\IdTrait;
use Doctrine\ORM\Mapping as ORM;

use DateTime;

abstract class KlineRaw implements KlineRawInterface
{
    use IdTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:'id', type:'bigint', nullable:false, options:['unsigned' => true])]
    protected ?int $id = null;

    #[ORM\Column(name:'run_index', type:'integer', nullable:false, options:['unsigned' => true])]
    protected readonly int $runIndex;

    #[ORM\Column(name:'start_time', type:'string', nullable:false, options:['unsigned' => true])]
    protected readonly string $startTime;

    #[ORM\Column(name:'start_time_date', type:'datetime', nullable:false, options:['unsigned' => true])]
    protected readonly DateTime $startTimeDate;

    #[ORM\Column(name:'close_time', type:'string', nullable:false, options:['unsigned' => true])]
    protected readonly string $closeTime;

    #[ORM\Column(name:'close_time_date', type:'datetime', nullable:false, options:['unsigned' => true])]
    protected readonly DateTime $closeTimeDate;

    #[ORM\Column(name:'symbol', type:'string', nullable:false, options:['unsigned' => true])]
    protected readonly string $symbol;

    #[ORM\Column(name:'interval', type:'string', nullable:false, options:['unsigned' => true])]
    protected readonly string $interval;

    #[ORM\Column(name:'first_trade_id', type:'string', nullable:true, options:['unsigned' => true])]
    protected readonly string $firstTradeID;

    #[ORM\Column(name:'last_trade_id', type:'string', nullable:true, options:['unsigned' => true])]
    protected readonly string $lastTradeID;

    #[ORM\Column(name:'open', type:'string', nullable:false, options:['unsigned' => true])]
    protected readonly string $open;

    #[ORM\Column(name:'open_float', type:'float', nullable:false, options:['unsigned' => true])]
    protected readonly float $openFloat;

    #[ORM\Column(name:'close', type:'string', nullable:false, options:['unsigned' => true])]
    protected readonly string $close;

    #[ORM\Column(name:'close_float', type:'float', nullable:false, options:['unsigned' => true])]
    protected readonly float $closeFloat;

    #[ORM\Column(name:'high', type:'string', nullable:false, options:['unsigned' => true])]
    protected readonly string $high;

    #[ORM\Column(name:'high_float', type:'float', nullable:false, options:['unsigned' => true])]
    protected readonly float $highFloat;

    #[ORM\Column(name:'low', type:'string', nullable:false, options:['unsigned' => true])]
    protected readonly string $low;

    #[ORM\Column(name:'low_float', type:'float', nullable:false, options:['unsigned' => true])]
    protected readonly float $lowFloat;

    #[ORM\Column(name:'base_asset_volume', type:'string', nullable:false, options:['unsigned' => true])]
    protected readonly string $baseAssetVolume;

    #[ORM\Column(name:'base_asset_volume_float', type:'float', nullable:false, options:['unsigned' => true])]
    protected readonly float $baseAssetVolumeFloat;

    #[ORM\Column(name:'trades_count', type:'string', nullable:false, options:['unsigned' => true])]
    protected readonly string $tradesCount;

    #[ORM\Column(name:'trades_count_int', type:'integer', nullable:false, options:['unsigned' => true])]
    protected readonly int $tradesCountInt;

    #[ORM\Column(name:'is_closed', type:'string', nullable:false, options:['unsigned' => true])]
    protected readonly string $isClosed;

    #[ORM\Column(name:'is_closed_bool', type:'boolean', nullable:false, options:['unsigned' => true])]
    protected readonly bool $isClosedBool;

    #[ORM\Column(name:'quote_asset_volume', type:'string', nullable:false, options:['unsigned' => true])]
    protected readonly string $quoteAssetVolume;

    #[ORM\Column(name:'quote_asset_volume_float', type:'float', nullable:false, options:['unsigned' => true])]
    protected readonly float $quoteAssetVolumeFloat;

    #[ORM\Column(name:'taker_buy_base_asset_volume', type:'string', nullable:false, options:['unsigned' => true])]
    protected readonly string $takerBuyBaseAssetVolume;

    #[ORM\Column(name:'taker_buy_base_asset_volume_float', type:'float', nullable:false, options:['unsigned' => true])]
    protected readonly float $takerBuyBaseAssetVolumeFloat;

    #[ORM\Column(name:'taker_buy_quote_asset_volume', type:'string', nullable:false, options:['unsigned' => true])]
    protected readonly string $takerBuyQuoteAssetVolume;

    #[ORM\Column(name:'taker_buy_quote_asset_volume_float', type:'float', nullable:false, options:['unsigned' => true])]
    protected readonly float $takerBuyQuoteAssetVolumeFloat;

    public function __construct(KlinerawDTO $klineRawDTO) {
        $this->startTime = $klineRawDTO->getStartTime();
        $this->startTimeDate = $klineRawDTO->getStartTimeDate();
        $this->closeTime = $klineRawDTO->getCloseTime();
        $this->closeTimeDate = $klineRawDTO->getCloseTimeDate();
        $this->symbol = $klineRawDTO->getSymbol();
        $this->interval = $klineRawDTO->getInterval();
        $this->firstTradeID = $klineRawDTO->getFirstTradeID();
        $this->lastTradeID = $klineRawDTO->getLastTradeID();
        $this->open = $klineRawDTO->getOpen();
        $this->openFloat = $klineRawDTO->getOpenFloat();
        $this->close = $klineRawDTO->getClose();
        $this->closeFloat = $klineRawDTO->getCloseFloat();
        $this->high = $klineRawDTO->getHigh();
        $this->highFloat = $klineRawDTO->getHighFloat();
        $this->low = $klineRawDTO->getLow();
        $this->lowFloat = $klineRawDTO->getLowFloat();
        $this->baseAssetVolume = $klineRawDTO->getBaseAssetVolume();
        $this->baseAssetVolumeFloat = $klineRawDTO->getBaseAssetVolumeFloat();
        $this->tradesCount = $klineRawDTO->getTradesCount();
        $this->tradesCountInt = $klineRawDTO->getTradesCountInt();
        $this->isClosed = $klineRawDTO->getIsClosed();
        $this->isClosedBool = $klineRawDTO->getIsClosedBool();
        $this->quoteAssetVolume = $klineRawDTO->getQuoteAssetVolume();
        $this->quoteAssetVolumeFloat = $klineRawDTO->getQuoteAssetVolumeFloat();
        $this->takerBuyBaseAssetVolume = $klineRawDTO->getTakerBuyBaseAssetVolume();
        $this->takerBuyBaseAssetVolumeFloat = $klineRawDTO->getTakerBuyBaseAssetVolumeFloat();
        $this->takerBuyQuoteAssetVolume = $klineRawDTO->getTakerBuyQuoteAssetVolume();
        $this->takerBuyQuoteAssetVolumeFloat = $klineRawDTO->getTakerBuyQuoteAssetVolumeFloat();
        $this->runIndex = $klineRawDTO->getRunIndex();
    }

    public function getClose(): float
    {
        return $this->closeFloat;
    }

    public function isClosed(): bool
    {
        return $this->isClosedBool;
    }

    public function getRunIndex(): int
    {
        return $this->runIndex;
    }
}
