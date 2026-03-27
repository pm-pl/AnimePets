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

namespace kirathefenix98AP\Vehicles\form;

use kirathefenix98AP\Vehicles\Main;
use kirathefenix98AP\Vehicles\utils\Utils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

use onebone\economyapi\EconomyAPI;
use pocketmine\item\VanillaItems;

class FormsManager {

    public function __construct(
        private Main $plugin
    ){
        // NOOP
    }

    public function getPlugin(){
        return $this->plugin;
    }

    public function OpenMainForm(Player $player){
        $form = new SimpleForm(function (Player $player, ?int $data = null){
            if($data === null)
                return false;
            
            switch ($data){
                case 0:
                    $this->OpenMyCarsForm($player);
                    break;
                case 1:
                    $this->OpenCarsShopForm($player);
                    break;
                case 2:
                    $this->OpenPetrolShopForm($player);
                    break;
            }
        });

        $form->setTitle("VehicleUI");

        $form->addButton("My Cars");
        $form->addButton("Cars Shop");
        $form->addButton("Petrol Shop");

        $player->sendForm($form);
    }

    public function OpenMyCarsForm(Player $player){
        $cars = $this->getPlugin()->getCars($player);

        $form = new SimpleForm(function (Player $player, ?int $data = null) use ($cars){
            if($data === null)
                return false;
            
            if(count($cars) > 0){
                $carsByKey = array_keys($cars);
                if(isset($carsByKey[$data])){
                    if(isset($cars[$carsByKey[$data]])){
                        $car = $cars[$carsByKey[$data]];
                        var_dump($car);
                        $this->OpenCarMangement($player, $car);
                    }
                }
            }
        });

        $form->setTitle("MyCars Form");

        if(count($cars) > 0){
            $form->setContent("Please select the vehicle which want spawn it.");
            foreach ($cars as $carData){
                $form->addButton(TextFormat::YELLOW . ucfirst($carData["model"]) . TextFormat::RESET . " | " . TextFormat::YELLOW . Utils::getColorByName($carData["color"]) . "\n" . TextFormat::GRAY . "%" . $carData["charge"]);
            }
        } else {
            $form->setContent(TextFormat::RED . "You don't have any Vehicle in your account goto cars shop and buy one.");
            $form->addButton("Okay");
        }

        $player->sendForm($form);
    }

    public function OpenCarMangement(Player $player, array $car){
        $form = new SimpleForm(function (Player $player, ?int $data = null) use ($car){
            if($data === null)
                return false;
            
            switch ($data){
                case 0:
                    if($this->getPlugin()->getSessionManager()->getSession($player) !== null){
                        $player->sendMessage(TextFormat::RED . "You can't spawn more than 1 vehicle in the same time!");
                        return false;
                    }

                    $session = $this->getPlugin()->getSessionManager()->createSession($player, $car["model"], $car["color"]);
                    if($session !== null){
                        $keyItem = VanillaItems::IRON_NUGGET();

                        $data = [
                            "owner" => $player->getName(),
                            "model" => $car["model"],
                            "color" => $car["color"]
                        ];

                        $keyItem->getNamedTag()->setString("VehicleKey", json_encode($data));
                        $keyItem->setCustomName(ucfirst($car["model"]). " Key");
                        $player->getInventory()->addItem($keyItem);
                        
                        $player->sendMessage(TextFormat::YELLOW . "your Session opened, and your Vehicle spawned.");
                    }
                    break;
                case 1:
                    $this->OpenReChargeForm($player, $car);
                    break; 
            }
            
        });

        $form->setTitle(ucfirst($car["model"]) . " Mangement");

        $data = $this->getPlugin()->getVehicle("Benz");

        $form->setContent("\n" . 
            "- Speed: " . TextFormat::GREEN . $data["speed"]  . TextFormat::RESET . "\n" . 
            "- MaxRiders: " . TextFormat::GREEN . $data["maxriders"] .  TextFormat::RESET . "\n" .
            "- MaxSpeed: " . TextFormat::GREEN . $data["maxspeed"] .  TextFormat::RESET . "\n" . 
            "- Charge: " . TextFormat::GREEN . "[" . Utils::getChargePercentage($car["charge"]) . TextFormat::GREEN . " ]" . TextFormat::RESET . "\n" .
            "- Color: " . Utils::getColorByName($car["color"]) . TextFormat::RESET . "\n"
        );

        $form->addButton("Spawn Vehicle");
        $form->addButton("ReCharge Vehicle");

        $player->sendForm($form);
    }

    public function OpenReChargeForm(Player $player, array $car){
        $availableCharges = [100, 50, 30];
        $form = new SimpleForm(function (Player $player, ?int $data = null) use ($car, $availableCharges){
            if($data === null)
                return false;
            
            if(isset($availableCharges[$data])){
                $key = $availableCharges[$data];
                $currentItems = $this->getPlugin()->getPetrolItemCount($player, $key);
                $currentCharge = $car["charge"];
                if($currentItems > 0){
                    if($currentCharge == 100){
                        $player->sendMessage(TextFormat::RED . "Your vehicle charge is full");
                        return false;
                    }

                    $currentCharge += $key;

                    if($currentCharge > 100){
                        $currentCharge = 100;
                    }

                    Main::getInstance()->updateCharge($player, $car["model"], $car["color"], $currentCharge);
                } else {
                    $player->sendMessage(TextFormat::RED . "You must have at least 1 item star of this type to be able to use it.");
                }
            }

            switch ($data){
                case 0:
                    $currentCharge = $car["charge"];
                    break;
            }
        });

        $form->setTitle("ReCharge Form");

        foreach ($availableCharges as $key){
            $form->addButton($key . "% Charge Star\n" . TextFormat::GRAY . "( " . TextFormat::YELLOW .  Utils::number_abbr($this->getPlugin()->getPetrolItemCount($player, $key)) . TextFormat::GRAY . " Available )");
        }

        $player->sendForm($form);
    }

    public function OpenCarsShopForm(Player $player){
        $cars = array_keys($this->getPlugin()->getVehicles());

        $form = new SimpleForm(function (Player $player, int $data = null) use ($cars){
            if($data === null)
                return false;
            
            if($cars[$data]){
                $car = $cars[$data];
                $this->SelectColorForm($player, $car);
            }
        });

        $form->setTitle("Cars Shop Form");

        foreach ($cars as $car){
            $form->addButton($car);
        }

        $player->sendForm($form);
    }

    public function SelectColorForm(Player $player, $car){
        $colors = ["Blue", "Red", "Yellow", "Green", "Aqua", "Gray"];
        $availableColors = $this->getPlugin()->getAvailableColors($car);
        $colors = array_values(array_filter($colors, function ($color) use ($availableColors){
            return in_array(strtolower($color), $availableColors);
        }));

        $form = new SimpleForm(function (Player $player, ?int $data = null) use ($car, $colors){
            if($data === null)
                return false;
            if(isset($colors[$data]))
            {
                $color = $colors[$data];
                if(!$this->getPlugin()->haveCar($car, $color, $player)){
                    $this->OpenCarShopConfirm($player, $car, $color);
                } else {
                    $player->sendMessage(TextFormat::RED . "You're already have this car with " . $color . " color.");
                }
            }
        });

        $form->setTitle("Select Color Form");
        $form->setContent("Please select the color who want apply to your vehicle.");

        foreach ($colors as $name){
            $form->addButton($name);
        }

        $player->sendForm($form);
    }

    public function OpenCarShopConfirm(Player $player, string $car, string $color){
        $cost = $this->getPlugin()->getVehicle(strtolower($car), "cost");
        $form = new ModalForm(function (Player $player, $data = null) use ($car, $color, $cost){
            if($data === null)
                return false;
            
                switch ($data){
                    case true:
                        $moneyAPI = EconomyAPI::getInstance();
                        $playerMoney = $moneyAPI->myMoney($player);
                        if($playerMoney >= $cost){
                            $moneyAPI->reduceMoney($player, $cost);
                            $this->getPlugin()->addCar($player, $car, $color);
    
                            $player->sendMessage(TextFormat::YELLOW . "you've been successfully purchased this vehicle");
                        } else {
                            $player->sendMessage(TextFormat::RED . "You don't have enough money to buy this item.");
                        }
                        break;
                    case false:

                        break;
                }
        });

        $form->setTitle("Buy Confirm");
        $form->setContent("Are you sure you want to buy this vehicle ($" . $cost . ") ?");

        $form->setButton1("Yes");
        $form->setButton2("No");

        $player->sendForm($form);
    }

    public function OpenPetrolShopForm(Player $player){
        $c1 = $this->getPlugin()->getConfig()->get("100.charge.cost", 600);
        $c2 = $this->getPlugin()->getConfig()->get("50.charge.cost", 300);
        $c3 = $this->getPlugin()->getConfig()->get("30.charge.cost", 150);
        $form = new SimpleForm(function (Player $player, ?int $data = null) use ($c1, $c2, $c3){
            if($data === null)
                return false;
            
            if(!class_exists(EconomyAPI::class)){
                return false;
            }
            
            $moneyAPI = EconomyAPI::getInstance();
            $playerMoney = $moneyAPI->myMoney($player);
            switch ($data){
                case 0:
                    if($playerMoney >= $c1){
                        $moneyAPI->reduceMoney($player, $c1);
                        $this->getPlugin()->addPetrolItem($player, [
                            "type" => 100,
                            "count" => 1
                        ]);

                        $player->sendMessage(TextFormat::YELLOW . "This item has been successfully purchased");
                    } else {
                        $player->sendMessage(TextFormat::RED . "You don't have enough money to buy this item.");
                    }
                    break;
                case 1:
                    if($playerMoney >= $c2){
                        $moneyAPI->reduceMoney($player, $c2);
                        $this->getPlugin()->addPetrolItem($player, [
                            "type" => 50,
                            "count" => 1
                        ]);

                        $player->sendMessage(TextFormat::YELLOW . "This item has been successfully purchased");
                    } else {
                        $player->sendMessage(TextFormat::RED . "You don't have enough money to buy this item.");
                    }
                    break;
                case 2:
                    if($playerMoney >= $c3){
                        $moneyAPI->reduceMoney($player, $c3);
                        $this->getPlugin()->addPetrolItem($player, [
                            "type" => 30,
                            "count" => 1
                        ]);

                        $player->sendMessage(TextFormat::YELLOW . "This item has been successfully purchased");
                    } else {
                        $player->sendMessage(TextFormat::RED . "You don't have enough money to buy this item.");
                    }
                    break;
            }
        });

        $form->setTitle("Petrol Shop Form");

        $form->addButton(TextFormat::YELLOW . "100% Charge Star\n" . TextFormat::GRAY . "$" . $c1);
        $form->addButton(TextFormat::YELLOW . "50% Charge Star\n" . TextFormat::GRAY . "$" . $c2);
        $form->addButton(TextFormat::YELLOW . "30% Charge Star\n" . TextFormat::GRAY . "$" . $c3);

        $player->sendForm($form);
    }

}