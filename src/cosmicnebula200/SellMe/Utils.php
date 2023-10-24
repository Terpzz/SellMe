<?php

declare(strict_types=1);

namespace cosmicnebula200\SellMe;

use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\player\Player;

class Utils
{

    public static function sellItem(Player $player, Item $item): bool
    {
        $amount = self::getAmount($item);
        if ($amount === 0)
            return false;
        
        SellMe::getInstance()->getEconomyProvider()->addToMoney($player, $amount * $item->getCount(), [
            "item" => $item->getVanillaName(),
            "amount" => $item->getCount(),
        ]);
        
        return true;
    }

    public static function getAmount(Item $item): int
    {
        $parsedItem = StringToItemParser::getInstance()->parse("{$item->getId()}:{$item->getMeta()}");
        
        if ($parsedItem !== null && SellMe::$prices->getNested("prices.{$parsedItem->getId()}:{$parsedItem->getMeta()}") !== false) {
            return (int)SellMe::$prices->getNested("prices.{$parsedItem->getId()}:{$parsedItem->getMeta()}");
        }

        if (SellMe::$prices->getNested("prices.{$item->getId()}") !== false) {
            return (int)SellMe::$prices->getNested("prices.{$item->getId()}");
        }

        return 0;
    }

    public static function getName(string $data): string
    {
        $parsedItem = StringToItemParser::getInstance()->parse($data);
        
        if ($parsedItem !== null) {
            return $parsedItem->getName();
        }

        return "";
    }
    
    public static function addToPrices(Player $player, int $price, bool $overwrite = false): void
    {
        $item = $player->getInventory()->getItemInHand();
        $string = "{$item->getId()}:{$item->getMeta()}";

        if (!$overwrite && Utils::getAmount($item) !== 0) {
            $player->sendMessage(SellMe::$messages->getMessage(
                'sell.error-adding',
                [],
                "The item already exists in prices. You can use '/sell overwrite' command to overwrite the price."
            ));
            return;
        }

        if ($price <= 0) {
            $player->sendMessage(SellMe::$messages->getMessage(
                'sell.non-positive',
                [],
                "The price cannot be a non-positive integer."
            ));
            return;
        }

        SellMe::$prices->setNested("prices.$string", $price);
        SellMe::$prices->save();
        SellMe::$prices->reload();
        
        $player->sendMessage(SellMe::$messages->getMessage(
            'sell.added',
            [
                'item' => $item->getName(),
                'amount' => $price
            ],
            'Added {ITEM} for {AMOUNT} to the list of prices'
        ));
    }
}
