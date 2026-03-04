<?php
declare(strict_types=1);

namespace App\Trader;

use App\DTO\ExchangeinfoDTO;
use App\DTO\IndicatorDTO;
use App\DTO\KlinerawDTO;
use App\EntityBuilder\KlineBuilder;
use App\EntityBuilder\KlineRawBuilder;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\SignalPropertyInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\TraderConsts;
use App\Strategy\Strategy;
use Binance\API;
use Throwable;

use function floor;
use function print_r;
use function rtrim;
use function strlen;
use function strrchr;

class Trader
{
    /** @var array<string, ExchangeinfoDTO> */
    private array $exchangeInfos = [];

    private array $klinesBeforeClose = [];

    public function __construct(
        private readonly API $binanceApiBlank,
        private readonly API $binanceApiLogin,
        private readonly KlineBuilder $klineBuilder,
        private readonly KlineRawBuilder $klineRawBuilder,
    ) {
    }

    public function trade(
        KlinerawDTO $klinerawDTO,
        Strategy    $strategy,
    ): void {
        $crossCount = 0;
        $buyCount = 0;
        $sellCount = 0;

        // TODO: Implement stop loss
        //$exchangeInfoDTO = $this->createExchangeInfoDTO($klinerawDTO->getSymbol());
        //$stopLoss = 0.01; // 1% stop loss
        //$lastBuyTradePrice = $exchangeInfoDTO->getLastBuyTradePrice();
        //$closeFloat = $klinerawDTO->getCloseFloat();

        if (false === $klinerawDTO->getIsClosedBool()) {
            $this->klinesBeforeClose[] = $klinerawDTO;
            $indicatorEntities = iterator_to_array(
                $strategy->addKline($this->buildKline($strategy, $klinerawDTO)),
            );

            foreach ($strategy->getIndicators() as $indicatorDTO) {
                $indicatorDTO->getIndicator()->pop();
            }
        } else {
            $this->klinesBeforeClose = [];

            $indicatorEntities = array_map(
                static fn (IndicatorDTO $indicatorDTO) => $indicatorDTO->getPrevEntity(),
                $strategy->getIndicators(),
            );
        }

        foreach ($indicatorEntities as $indicatorName => $indicatorEntity) {
            if (false === $indicatorEntity instanceof SignalPropertyInterface) {
                continue;
            }

            if ($indicatorEntity->getCross() === TraderConsts::CROSS_UP) {
                $crossCount++;
            }

            if ($indicatorEntity->getSignal() === TraderConsts::SIGNAL_BUY) {
                $buyCount++;
            }

            if ($indicatorEntity->getSignal() === TraderConsts::SIGNAL_SELL || $indicatorEntity->getCross() === TraderConsts::CROSS_DOWN) {
                $sellCount++;
            }

            $indicatorName = str_pad($indicatorName, 4);
            $signal = str_pad((string)$indicatorEntity->getSignal(), 2, ' ', STR_PAD_LEFT);
            $cross = str_pad((string)$indicatorEntity->getCross(), 2, ' ', STR_PAD_LEFT);

            $message = sprintf(
                "[Log %s] %s | Signal: %s | Cross: %s | closed: %d | runIndex: %d",
                $indicatorEntity->getId(),
                $indicatorName,
                $signal,
                $cross,
                $klinerawDTO->getIsClosedBool(),
                $klinerawDTO->getRunIndex(),
            ) . PHP_EOL;

            if ($klinerawDTO->getIsClosedBool() === true) {
                // Add green color for Bash
                echo "\033[32m$message\033[0m";
            } else {
                echo $message;
            }

            if (false === $klinerawDTO->getIsClosedBool()) {
                $indicatorDTO->getIndicator()->pop();
            }
        }

        if ($sellCount > 0) {
            $this->sell($klinerawDTO->getSymbol(), $klinerawDTO->getCloseFloat());

            return;
        }

        //$predictedPrice = $this->predictPrice();

        if ($klinerawDTO->getIsClosedBool() && $buyCount > 0 && $crossCount > 0) {
            $this->buy($klinerawDTO->getSymbol(), $klinerawDTO->getCloseFloat());

            return;
        }
        echo "\n[Log] Nothing to do\n\n";
    }

    private function buildKline(Strategy $strategy, KlinerawDTO $klinerawDTO): KlineInterface
    {
        return $this->klineBuilder->build(
            $this->klineRawBuilder->build($klinerawDTO),
            $strategy->getKline(),
        );
    }

    private function sell(string $symbol, float $currentPrice): void
    {
        try {
            $exchangeInfoDTO = $this->getExchangeInfoDTO($symbol, $currentPrice);

            if ($exchangeInfoDTO->getQuantityToSell() < $exchangeInfoDTO->getMinQty()) {
                echo "\n\n[Sell] Nothing to sell (current quantity: {$exchangeInfoDTO->getQuantityToSell()}, minQty: {$exchangeInfoDTO->getMinQty()})\n\n";
                return;
            }

            $response = $this->binanceApiLogin->marketSell($symbol, $exchangeInfoDTO->getQuantityToSell());

            echo "\n\n[Sell] Response:\n";
            print_r($response);
            echo "\n\n\n";

            $this->createExchangeInfoDTO($symbol);
        } catch (Throwable $e) {
            // Log the error or handle it as needed
            echo "\n\n[Sell] Error during sell operation: " . $e->getMessage() . "\n\n\n";
        }
    }

    private function buy(string $symbol, float $currentPrice): void
    {
        try {
            $exchangeInfoDTO = $this->getExchangeInfoDTO($symbol, $currentPrice);

            if ($exchangeInfoDTO->getQuantityToBuy() < $exchangeInfoDTO->getMinQty()) {
                echo "\n\n[Sell] Nothing to buy (current quantity: {$exchangeInfoDTO->getQuantityToBuy()}, minQty: {$exchangeInfoDTO->getMinQty()})\n\n";
                return;
            }

            $response = $this->binanceApiLogin->marketBuy($symbol, $exchangeInfoDTO->getQuantityToBuy());
            echo "[Buy]Bought {$exchangeInfoDTO->getQuantityToBuy()} {$exchangeInfoDTO->getBaseAsset()} using {$exchangeInfoDTO->getQuoteAssetToBuy()} {$exchangeInfoDTO->getQuoteAsset()}\n";
            echo "[Buy] Response:\n";
            print_r($response);
            echo "\n\n\n";

            $this->createExchangeInfoDTO($symbol);
        } catch (Throwable $e) {
            // Log the error or handle it as needed
            echo "\n\n[Buy] Error during buy operation: " . $e->getMessage() . "\n\n\n";
        }
    }

    private function getExchangeInfoDTO(string $symbol, float $currentPrice, float $percentageToSpend = 50): ExchangeinfoDTO
    {
        $exchangeInfoDTO = $this->createExchangeInfoDTO($symbol);

        $balances = $this->binanceApiLogin->balances();

        $baseAssetAvailable = (float)$balances[$exchangeInfoDTO->getBaseAsset()]['available'];
        $quoteAssetAvailable = (float)$balances[$exchangeInfoDTO->getQuoteAsset()]['available'];

        $quantityToBuy = ($quoteAssetAvailable * ($percentageToSpend / 100)) / $currentPrice;
        $quantityToBuy = floor($quantityToBuy * (10 ** $exchangeInfoDTO->getPrecision())) / (10 ** $exchangeInfoDTO->getPrecision());

        $quantityToSell = floor($baseAssetAvailable * (10 ** $exchangeInfoDTO->getPrecision())) / (10 ** $exchangeInfoDTO->getPrecision());

        $exchangeInfoDTO
            ->setCurrentPrice($currentPrice)
            ->setBaseAssetAvailable($baseAssetAvailable)
            ->setQuoteAssetAvailable($quoteAssetAvailable)
            ->setQuantityToBuy($quantityToBuy)
            ->setQuoteAssetToBuy($quantityToBuy * $currentPrice)
            ->setPercentageToSpend($percentageToSpend)
            ->setQuantityToSell($quantityToSell);

        return $exchangeInfoDTO;
    }

    private function refreshTradePrices(ExchangeinfoDTO $exchangeInfoDTO): void
    {
        $trades = array_reverse($this->binanceApiLogin->myTrades($exchangeInfoDTO->getSymbol(), 50));

        $buyTrades = array_values(array_filter($trades, static fn(array $trade) => (bool)$trade['isBuyer'] === true));
        $sellTrades = array_values(array_filter($trades, static fn(array $trade) => (bool)$trade['isBuyer'] === false));

        $buyTradePrice = $buyTrades[0]['price'] ?? null;
        $sellTradePrice = $sellTrades[0]['price'] ?? null;

        $exchangeInfoDTO
            ->setLastBuyTradePrice($buyTradePrice ? (float)$buyTradePrice : null)
            ->setLastSellTradePrice($sellTradePrice ? (float)$sellTradePrice : null);
    }

    private function createExchangeInfoDTO($symbol): ExchangeinfoDTO
    {
        $exchangeInfoDTO = $this->exchangeInfos[$symbol] ?? null;

        if (!$exchangeInfoDTO) {
            $exchangeInfo = $this->binanceApiBlank->exchangeInfo();
            $symbolInfo = $exchangeInfo['symbols'][$symbol];

            $lotSizeFilter = [];
            foreach ($symbolInfo['filters'] as $filter) {
                if ($filter['filterType'] === 'LOT_SIZE') {
                    $lotSizeFilter = $filter;
                }
            }

            $precision = strlen(substr(strrchr(rtrim((string)$lotSizeFilter['stepSize'], '0'), '.'), 1));

            $exchangeInfoDTO = new ExchangeinfoDTO()
                ->setSymbol($symbol)
                ->setBaseAsset($symbolInfo['baseAsset'])
                ->setQuoteAsset($symbolInfo['quoteAsset'])
                ->setStepSize((float)$lotSizeFilter['stepSize'])
                ->setMinQty((float)$lotSizeFilter['minQty'])
                ->setPrecision($precision);
        }

        $this->refreshTradePrices($exchangeInfoDTO);

        return $exchangeInfoDTO;
    }
}
