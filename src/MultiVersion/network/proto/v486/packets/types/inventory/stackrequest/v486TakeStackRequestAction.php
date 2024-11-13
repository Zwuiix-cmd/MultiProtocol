<?php
namespace MultiVersion\network\proto\v486\packets\types\inventory\stackrequest;

use MultiVersion\network\proto\v486\packets\types\inventory\stackrequest\trait\v486TakeOrPlaceStackRequestActionTrait;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestActionType;

final class v486TakeStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;
	use v486TakeOrPlaceStackRequestActionTrait;

	public const ID = ItemStackRequestActionType::TAKE;
}