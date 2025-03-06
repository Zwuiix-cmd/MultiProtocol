<?php
namespace MultiVersion\network\proto\v419\packets\types\inventory\stackrequest;

use MultiVersion\network\proto\v419\packets\types\inventory\stackrequest\trait\v419TakeOrPlaceStackRequestActionTrait;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestActionType;

final class v419NiqueTaMereAction extends ItemStackRequestAction{
    use GetTypeIdFromConstTrait;

	public const ID = 0x0;
    public function write(PacketSerializer $out) : void
    {
    }
}