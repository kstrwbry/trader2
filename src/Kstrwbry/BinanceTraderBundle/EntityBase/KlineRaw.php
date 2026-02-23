<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\EntityBase;

use App\DTO\KlinerawDTO;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineRawInterface;
use App\Kstrwbry\BinanceTraderBundle\Trait\IdTrait;
use Doctrine\ORM\Mapping as ORM;

use DateTime;
use function substr;

abstract class KlineRaw implements KlineRawInterface
{
    use IdTrait;

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

    public function __construct(KlinerawDTO $klinerawDTO) {
        $this->startTime = $klinerawDTO->getStartTime();
        $this->startTimeDate = $klinerawDTO->getStartTimeDate();
        $this->closeTime = $klinerawDTO->getCloseTime();
        $this->closeTimeDate = $klinerawDTO->getCloseTimeDate();
        $this->symbol = $klinerawDTO->getSymbol();
        $this->interval = $klinerawDTO->getInterval();
        $this->firstTradeID = $klinerawDTO->getFirstTradeID();
        $this->lastTradeID = $klinerawDTO->getLastTradeID();
        $this->open = $klinerawDTO->getOpen();
        $this->openFloat = $klinerawDTO->getOpenFloat();
        $this->close = $klinerawDTO->getClose();
        $this->closeFloat = $klinerawDTO->getCloseFloat();
        $this->high = $klinerawDTO->getHigh();
        $this->highFloat = $klinerawDTO->getHighFloat();
        $this->low = $klinerawDTO->getLow();
        $this->lowFloat = $klinerawDTO->getLowFloat();
        $this->baseAssetVolume = $klinerawDTO->getBaseAssetVolume();
        $this->baseAssetVolumeFloat = $klinerawDTO->getBaseAssetVolumeFloat();
        $this->tradesCount = $klinerawDTO->getTradesCount();
        $this->tradesCountInt = $klinerawDTO->getTradesCountInt();
        $this->isClosed = $klinerawDTO->getIsClosed();
        $this->isClosedBool = $klinerawDTO->getIsClosedBool();
        $this->quoteAssetVolume = $klinerawDTO->getQuoteAssetVolume();
        $this->quoteAssetVolumeFloat = $klinerawDTO->getQuoteAssetVolumeFloat();
        $this->takerBuyBaseAssetVolume = $klinerawDTO->getTakerBuyBaseAssetVolume();
        $this->takerBuyBaseAssetVolumeFloat = $klinerawDTO->getTakerBuyBaseAssetVolumeFloat();
        $this->takerBuyQuoteAssetVolume = $klinerawDTO->getTakerBuyQuoteAssetVolume();
        $this->takerBuyQuoteAssetVolumeFloat = $klinerawDTO->getTakerBuyQuoteAssetVolumeFloat();
        $this->runIndex = $klinerawDTO->getRunIndex();
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
