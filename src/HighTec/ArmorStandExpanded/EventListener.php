<?php

declare(strict_types=1);

namespace HighTec\ArmorStandExpanded;

use HighTec\ArmorStandExpanded\entity\object\ArmorStand;
use HighTec\ArmorStandExpanded\events\ArmorStandExpandedBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\Player;

/**
 * Class EventListener
 * @package HighTec\ArmorStand
 */
class EventListener implements Listener
{

    /**
     * @var ArmorStandExpanded
     */
    private $instance;

    /**
     * EventListener constructor.
     * @param ArmorStandExpanded $instance
     */
    public function __construct(ArmorStandExpanded $instance)
    {
        $this->instance = $instance;
    }


    /**
     * @param DataPacketReceiveEvent $source
     */
    public function onInventoryTransaction(DataPacketReceiveEvent $source)
    {
        if ($source->getPacket() instanceof InventoryTransactionPacket) {
            if($source->getPacket()->trData instanceof UseItemOnEntityTransactionData) {
                try {
                    $action = $source->getPacket()->trData->getActionType() == UseItemOnEntityTransactionData::ACTION_INTERACT;
                } catch (\ErrorException $e) {
                    return;
                }
                if ($action) {
                    try {
                        $target = $source->getPlayer()->level->getEntity($source->getPacket()->trData->getEntityRuntimeId());
                    } catch (\ErrorException $e) {
                        return;
                    }
                    if ($target instanceof ArmorStand) {
                        if (!$target->isAlive() || $this->instance->canDoThis($source->getPlayer()) === false) {
                            return;
                        }
                        $target->onFirstInteract($source->getPlayer(), $source->getPlayer()->getInventory()->getIteminHand(), $source->getPacket()->trData->getClickPos());
                    }
                }
            }
        }
    }


    /**
     * @param EntityDamageByEntityEvent $source
     */
    public function onArmorStandAttack(EntityDamageByEntityEvent $source)
    {
        if (!$source->getDamager() instanceof Player) {
            return;
        }
        if ($source->getEntity() instanceof ArmorStand) {
            if ($this->instance->canDoThis($source->getDamager()) === false) {
                $source->setCancelled();
                return;
            }
            if ($source->getFinalDamage() >= $source->getEntity()->getHealth()) {
                $ev = new ArmorStandExpandedBreakEvent($source->getDamager(), $source->getEntity());
                $ev->call();
                if ($ev->isCancelled()) {
                    $source->setCancelled();
                }
            }

        }

    }

}