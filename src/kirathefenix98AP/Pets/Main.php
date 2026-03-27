<?php

/*
 *  A paid plugin for PocketMine-MP.
 *
 *   _           _ _   _    ___   ___  _____             
 *  | |         (_) | | |  / _ \ / _ \|  __ \            
 *  | |     __ _ _| |_| |_| (_) | (_) | |  | | _____   __
 *  | |    / _` | | __| '_ \__, |> _ <| |  | |/ _ \ \ / /
 *  | |___| (_| | | |_| | | |/ /| (_) | |__| |  __/\ V / 
 *  |______\__,_|_|\__|_| |_/_/  \___/|_____/ \___| \_/  
 *  
 *  Copyright (c) Laith98Dev
 *  
 *  Youtube: Laith Youtuber
 *  Discord: Laith98Dev#0695 or @u.oo
 *  Github: Laith98Dev
 *  Email: spt.laithdev@gamil.com
 *  Donate: https://paypal.me/Laith113
 *  
 *  This plugin was sold under the Laith98Dev Publication License
 *  
 *  The terms of the license must be adhered to and never violated
 *  any violation of license permissions will not be negotiated.
 *  
 *  You will receive the license file with the plugin
 *  and it will also be inside the plugin.
 *  
 *  Since you have this plugin means that you have purchased it
 *  and you are prohibited from using it
 *  as a commercial product, distributing it, selling it or changing the rights
 *  or the name of the original developer
 *  which is Laith98Dev and it only for private use.
 *  
 *  You can also find the license from here
 *  <https://github.com/Laith98Dev/Paid-Services-License/blob/main/LICENSE>.
 *  
 */

namespace kirathefenix98AP\Pets;

use kirathefenix98AP\Pets\command\PetsCommand;
use kirathefenix98AP\Pets\form\SimpleForm;
use kirathefenix98AP\Pets\session\Session;
use kirathefenix98AP\Pets\utils\TypeHolder;
use kirathefenix98AP\Pets\entity\PetBase;
use kirathefenix98AP\Pets\form\CustomForm;
use kirathefenix98AP\Pets\provider\DataBase;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\utils\TextFormat;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\world\World;

/**
 * @Laith98Dev
 * 
 * I've noticed about my pets plugin has beeen leaked by someone their name started with `ito` 
 * If you're saw this message, i wanna told you if you want me to do a legal action edit anything again 
 * and read the license many times before try to edit it and as the license says `any violation of license permissions will not be negotiated`
 * 
 * This plugin i havce made it since 8 monthes and it exist in my github paid plugins repo.
 * 
 * Good luck
 */
class Main extends PluginBase
{

    private static $instance;

    public array $sessions = [];

    protected function onLoad(): void
    {
        self::$instance = $this;

        $this->saveDefaultConfig();

        $this->saveResource("AnimePets.mcpack");

        DataBase::getInstance()->load($this);

        $packManager = $this->getServer()->getResourcePackManager();
        $packManager->setResourceStack(array_merge($packManager->getResourceStack(), [new ZippedResourcePack($this->getDataFolder() . "AnimePets.mcpack")]));
        ($serverForceResources = new \ReflectionProperty($packManager, "serverForceResources"))->setAccessible(true);
        $serverForceResources->setValue($packManager, true);
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }

    protected function onEnable(): void
    {
        if (!is_dir($this->getDataFolder() . "pets_inventory")) {
            mkdir($this->getDataFolder() . "pets_inventory");
        }
        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

        TypeHolder::init();

        $this->getServer()->getCommandMap()->register($this->getName(), new PetsCommand($this));
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }

    protected function onDisable(): void
    {
        foreach ($this->getServer()->getWorldManager()->getWorlds() as $world) {
            foreach ($world->getEntities() as $entity) {
                if ($entity instanceof PetBase) $entity->fastClose();
            }
        }

        DataBase::getInstance()->shutdown();
    }

    public function getSession(Player $player): ?Session
    {
        return $this->sessions[strtolower($player->getName())] ?? null;
    }

    public function getSessions(): array
    {
        return $this->sessions;
    }

    public function createSession(Player $player): void
    {
        DataBase::getInstance()->get($player)->onCompletion(
            function (?Session $session) use ($player) {
                if (!$player->isOnline()) return;

                if ($session === null) {
                    $session = new Session($player->getName());
                }

                $this->sessions[strtolower($player->getName())] = $session;

                $this->getLogger()->info('Load session for ' . $player->getName() . ' successfully.');

                if (!$this->canSetPet($player->getWorld())) return;

                $session->respawnPet();
            },
            fn() => $this->getLogger()->error('Error to load profile for ' . $player->getName())
        );
    }

    public function OpenMainForm(Player $player, Session $session): void
    {
        $form = new SimpleForm(function (Player $player, ?int $data = null) use ($session) {
            if ($data === null) {
                return false;
            }

            switch ($data) {
                case 0:
                    $this->OpenSpawnForm($player, $session);
                    break;
                case 1:
                    if ($session->getPetType() === null and !$session->havePet()) {
                        $player->sendMessage(TextFormat::RED . 'You currently do not have any pets.');

                        return;
                    }

                    $session->close(true);

                    $player->sendMessage(TextFormat::GREEN . "AnimePet removed successfully!");
                    break;
            }
        });

        $form->setTitle("§y§r");
        $form->setContent("§eAnimePets");


        $form->addButton("§r§aSpawn AnimePet");
        $form->addButton("§r§cDespawn AnimePet");

        $player->sendForm($form);
    }
    public function refactorName($name): string
    {
        $partesNombre = explode(":", $name);

        $nombrePokemon = $partesNombre[1];

        $nombrePokemon = ucfirst(strtolower($nombrePokemon));

        return $nombrePokemon;
    }

    public function OpenSpawnForm(Player $player, Session $session): void
    {
        $types = TypeHolder::getTypes();

        $types = array_filter($types, fn($type) => $player->hasPermission((TypeHolder::getTypePerm($type) !== null ? TypeHolder::getTypePerm($type) : "unknown.pet")));

        $types = array_values($types);

        sort($types);

        $form = new SimpleForm(function (Player $player, ?int $data = null) use ($session, $types) {
            if ($data === null || count($types) == 0) {
                return false;
            }

            $type = $types[$data] ?? null;

            if ($type !== null) $this->OpenSelectNameForm($player, $type, $session);
        });

        $form->setTitle("§y§r");
        $form->setContent("§eSelecciona un AnimePet");

        if (count($types) == 0) {
            $form->setContent(TextFormat::RED . "You don't have any AnimePet!" . TextFormat::RESET);
            $form->addButton("Okay");
        } else {
            foreach ($types as $name) {
                $form->addButton($this->refactorName($name));
            }
        }

        $player->sendForm($form);
    }

    public function OpenSelectNameForm(Player $player, string $type, Session $session): void
    {
        $form = new CustomForm(function (Player $player, $data = null) use ($session, $type) {
            if ($data === null) {
                return false;
            }

            $name = null;

            if (isset($data[0]) && strlen($data[0]) > 0) {
                $name = $data[0];
            } else {
                $name = $session->getPetName() ?? $player->getName() . "'s AnimePet";
            }

            if (!$this->canSetPet($player->getWorld())) {
                $player->sendMessage(TextFormat::RED . 'You cant use pets in this world.');

                return;
            }

            if ($session->getPetType() !== null and $session->havePet()) $session->close(true);

            $session->createPet($type, $name);
        });

        $form->setTitle("§y§r");

        $form->addInput("§r§7Enter the name of your AnimePet:", ($session->getPetName() ?? $player->getName() . "'s AnimePet"), "");

        $player->sendForm($form);
    }

    public function canSetPet(World $world): bool
    {
        return in_array($world->getFolderName(), $this->getConfig()->get('available-worlds'), true);
    }
}
