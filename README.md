# Automated Crypto Trading
designed to automate your crypto strategies. configure your strategies and monitor how it went.
## TODO
- decouple project into 3 types:
  - fetch klines (command)
  - place orders from strategies (command)
  - monitoring + action (frontend)
- define and implement minimum requirement
- make project vendor(able)
- make strategies maximum save by stop-loss orders or a maximum of 1% loss
## Requirements
- modern technology. e.g. php 8.1
## Hints
- to be sure the indicators are calculated correctly a minimum of `period` data is needed. see configureation of [Strategies](#strategies). 
## Usage
- feel free to use as less of the components (fetching data, strategies, monitoring, order placement) as you want.
## Frontend
(Not implemented yet)
- monitoring
- order placement
## Code Base
- used snippets of https://github.com/markrogoyski/math-php to avoid "ext-trader" and to implement indicators
- used snippets of https://github.com/bitfinexcom/bfx-hf-indicators-py to implement indicators
## Entity initialization
### Base
- Implement `Interface\KlineRawInterface` and `Interface\KlineInterface` from any existing or new entity or simply extend `EntityBase\Kline` and `EntityBase\KlineRaw`
- Apply entities to Interfaces so Doctrine knows how to connect them because the entity relations are defined via interfaces for abstraction layer and flexibility.
- doctrine.yaml config example
    ```yaml
    doctrine:
      dbal:
        orm:
            resolve_target_entities:
              App\Interface\KlineInterface: App\Entity\Kline
              App\Interface\KlineRawInterface: App\Entity\KlineRaw
    ```
### Indicator Entities
Implement interface for each of the following indicators or extend `EntityBase`s.  
If you choose `EntityBase`, the indicators will have automatically a `@ORM\OneToOne` relation to `Interface\KlineInterface` because they use the `Trait\KlineConnectionTrait`. (For more details see `Trait\KlineConnectionTrate`)
#### Indicator entities to use
  - StdDev (`Interface\StdDevInterface` or `EntityBase\StdDev`)
  - RVI (`Interface\RVIInterface` or `EntityBase\RVI`)
  - RSI (`Interface\RSIInterface` or `EntityBase\RSI`)
  - MACD (`Interface\MACDInterface` or `EntityBase\MACD`)
### Defined indicators
(some text is missing)
### Indicator Calculation
Fetch data from database or define your own `ArrayCollection` and push them into an indicator calculation class:
```php
use Doctrine\Common\Collections\ArrayCollection;
use App\Indicator\RVI as RVICalculator;
use App\Entity\RVI as RVIEntity;
use App\Interface\RVIInterface;
use Doctrine\ORM\EntityManagerInterface;

/** @var RVIInterface[] $entities */
$entities [];
$collection = new ArrayCollection($entities);

// collect some data and store it in collection
// or simply get them from database

$calculator = new RVICalculator(new $collection);

// indicator calculator also handles collection
$rvi = new RVIEntity(...some arguments)
$calculator->add($rvi);

// if collection gets updated from outside calculator
$collection->add($rvi);
$calculator->refresh();

// if the rvi entities are already managed, just flush.
// if not, persist them first.
/** @var EntityManagerInterface $em */
$em->flush();
```
## Strategies
(Not implemented yet)
- Trading strategies are configured in strategies.yaml and make use of indicators and their signals.
- It's also possible to define different order types like 'market' or 'stop_loss'
## Trading
(Not implemented yet)
### Trading indicator signals
