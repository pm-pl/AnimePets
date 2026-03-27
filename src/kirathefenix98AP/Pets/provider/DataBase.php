<?php

namespace kirathefenix98AP\Pets\provider;

use kirathefenix98AP\Pets\Main;
use kirathefenix98AP\Pets\session\Session;
use pocketmine\player\Player;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;

class DataBase
{

    use SingletonTrait;

    private DataConnector $dataConnector;

    public function load(Main $plugin): void
    {
        try {
            $this->dataConnector = libasynql::create($plugin, $plugin->getConfig()->get('database'), [
                'mysql' => 'database/mysql.sql',
                'sqlite' => 'database/sqlite.sql'
            ]);
        } catch (SqlError $error) {
            $plugin->getLogger()->error($error->getMessage());
        }

        $this->dataConnector->executeGeneric('tables.players');

        $this->dataConnector->waitAll();

        $plugin->getLogger()->info(TextFormat::GREEN . 'Loading player tables...');
    }

    public function get(Player|string $player): Promise
    {
        $name = $player instanceof Player ? $player->getName() : $player;

        $promiseResolver = new PromiseResolver();

        $this->dataConnector->executeSelect(
            'request.get',
            [
                'playerName' => $name
            ],
            function (array $rows) use ($promiseResolver) {
                if (count($rows) === 0) {
                    $promiseResolver->resolve(null);

                    return $promiseResolver->getPromise();
                }

                $promiseResolver->resolve(Session::create($rows[0]));
            }
        );

        return $promiseResolver->getPromise();
    }

    public function insert(string $playerName, string|null $petType, string|null $petName): void
    {
        $this->dataConnector->executeChange(
            'request.insert',
            [
                'playerName' => $playerName,
                'petType' => $petType,
                'petName' => $petName
            ],
            fn() => Main::getInstance()->getLogger()->notice('Update data for ' . $playerName)
        );
    }

    public function shutdown(): void
    {
        if (!$this->dataConnector instanceof DataConnector) return;

        $this->dataConnector->waitAll();

        $this->dataConnector->close();
    }
}
