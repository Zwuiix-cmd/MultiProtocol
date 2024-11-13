<?php
namespace MultiVersion\network\proto\v419\packets\types\inventory\stackrequest;

use MultiVersion\network\proto\v419\packets\types\inventory\stackrequest\trait\v419TakeOrPlaceStackRequestActionTrait;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestActionType;

final class v419TakeStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;
	use v419TakeOrPlaceStackRequestActionTrait;

	public const ID = ItemStackRequestActionType::TAKE;
}