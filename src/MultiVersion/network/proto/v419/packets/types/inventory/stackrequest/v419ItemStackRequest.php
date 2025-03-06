<?php

namespace MultiVersion\network\proto\v419\packets\types\inventory\stackrequest;

use InvalidArgumentException;
use MultiVersion\network\proto\utils\ReflectionUtils;
use MultiVersion\network\proto\v419\packets\types\inventory\v419ContainerUIIds;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\BeaconPaymentStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftingConsumeInputStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftingCreateSpecificResultStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftRecipeAutoStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftRecipeOptionalStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftRecipeStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CreativeCreateStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DeprecatedCraftingNonImplementedStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DeprecatedCraftingResultsStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DestroyStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DropStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\GrindstoneStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestActionType;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestSlotInfo;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\LabTableCombineStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\LoomStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\MineBlockStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\PlaceStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\SwapStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\TakeStackRequestAction;
use pocketmine\utils\BinaryDataException;
use ReflectionException;
use function count;

final class v419ItemStackRequest{
	/**
	 * @param ItemStackRequestAction[] $actions
	 * @param string[]                 $filterStrings
	 *
	 * @phpstan-param list<string>     $filterStrings
	 */
	public function __construct(
		private int $requestId,
		private array $actions,
		private array $filterStrings,
		private int $filterStringCause
	){
	}

	public function getRequestId() : int{ return $this->requestId; }

	/** @return ItemStackRequestAction[] */
	public function getActions() : array{ return $this->actions; }

	/**
	 * @return string[]
	 * @phpstan-return list<string>
	 */
	public function getFilterStrings() : array{ return $this->filterStrings; }

	public function getFilterStringCause() : int{ return $this->filterStringCause; }

	/**
	 * @throws BinaryDataException
	 * @throws PacketDecodeException
	 * @throws ReflectionException
	 */
	private static function readAction(PacketSerializer $in, int $typeId) : ItemStackRequestAction{
		$action = match ($typeId) {
			TakeStackRequestAction::ID => v419TakeStackRequestAction::read($in),
			PlaceStackRequestAction::ID => v419PlaceStackRequestAction::read($in),
			SwapStackRequestAction::ID => v419SwapStackRequestAction::read($in),
			DropStackRequestAction::ID => v419DropStackRequestAction::read($in),
			DestroyStackRequestAction::ID => DestroyStackRequestAction::read($in),
			CraftingConsumeInputStackRequestAction::ID => CraftingConsumeInputStackRequestAction::read($in),
			CraftingCreateSpecificResultStackRequestAction::ID => CraftingCreateSpecificResultStackRequestAction::read($in),
			LabTableCombineStackRequestAction::ID => LabTableCombineStackRequestAction::read($in),
			BeaconPaymentStackRequestAction::ID => BeaconPaymentStackRequestAction::read($in),
			MineBlockStackRequestAction::ID => MineBlockStackRequestAction::read($in),
			CraftRecipeStackRequestAction::ID => CraftRecipeStackRequestAction::read($in),
			CraftRecipeAutoStackRequestAction::ID => v419CraftRecipeAutoStackRequestAction::read($in),
			CreativeCreateStackRequestAction::ID => CreativeCreateStackRequestAction::read($in),
			CraftRecipeOptionalStackRequestAction::ID => CraftRecipeOptionalStackRequestAction::read($in),
			GrindstoneStackRequestAction::ID => GrindstoneStackRequestAction::read($in),
			LoomStackRequestAction::ID => LoomStackRequestAction::read($in),
			DeprecatedCraftingNonImplementedStackRequestAction::ID => DeprecatedCraftingNonImplementedStackRequestAction::read($in),
			DeprecatedCraftingResultsStackRequestAction::ID => DeprecatedCraftingResultsStackRequestAction::read($in),
            default => new v419NiqueTaMereAction(),
		};
		if($action instanceof v419SwapStackRequestAction){
			if(($containerId = ($slot1 = $action->getSlot1())->getContainerId()) >= v419ContainerUIIds::RECIPE_BOOK){
				$containerId++;
				ReflectionUtils::setProperty(get_class($action), $action, "slot1", new v419ItemStackRequestSlotInfo($containerId, $slot1->getSlotId(), $slot1->getStackId()));
			}
			if(($containerId = ($slot2 = $action->getSlot2())->getContainerId()) >= v419ContainerUIIds::RECIPE_BOOK){
				$containerId++;
				ReflectionUtils::setProperty(get_class($action), $action, "slot2", new v419ItemStackRequestSlotInfo($containerId, $slot2->getSlotId(), $slot2->getStackId()));
			}
		}elseif($action instanceof CraftingConsumeInputStackRequestAction |
			$action instanceof DestroyStackRequestAction |
			$action instanceof v419DropStackRequestAction
		){
			if($action instanceof v419DropStackRequestAction){
				if(($containerId = ($source = $action->getSource())->getContainerId()) >= v419ContainerUIIds::RECIPE_BOOK){
					$containerId++;
					ReflectionUtils::setProperty(get_class($action), $action, "source", new v419ItemStackRequestSlotInfo($containerId, $source->getSlotId(), $source->getStackId()));
				}
			}else{
				if(($containerId = ($source = $action->getSource())->getContainerName()->getContainerId()) >= v419ContainerUIIds::RECIPE_BOOK){
					$containerId++;
					ReflectionUtils::setProperty(get_class($action), $action, "source", new v419ItemStackRequestSlotInfo($containerId, $source->getSlotId(), $source->getStackId()));
				}
			}
		}elseif(
			$action instanceof v419PlaceStackRequestAction |
			$action instanceof v419TakeStackRequestAction
		){
			if(($containerId = ($source = $action->getSource())->getContainerId()) >= v419ContainerUIIds::RECIPE_BOOK){
				$containerId++;
				ReflectionUtils::setProperty(get_class($action), $action, "source", new v419ItemStackRequestSlotInfo($containerId, $source->getSlotId(), $source->getStackId()));
			}
			if(($containerId = ($destination = $action->getDestination())->getContainerId()) >= v419ContainerUIIds::RECIPE_BOOK){
				$containerId++;
				ReflectionUtils::setProperty(get_class($action), $action, "destination", new v419ItemStackRequestSlotInfo($containerId, $destination->getSlotId(), $destination->getStackId()));
			}
		}elseif($action instanceof v419CraftRecipeAutoStackRequestAction){
			$action = new CraftRecipeAutoStackRequestAction($action->getRecipeId(), $action->getRepetitions(), $action->getRepetitions(), $action->getIngredients());
		}
		return $action;
	}

	public static function read(PacketSerializer $in) : self{
		$requestId = $in->getVarInt();
		$actions = [];
		for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
			$typeId = $in->getByte();
			if($typeId >= 7){
				$typeId += ItemStackRequestActionType::LAB_TABLE_COMBINE - 7;
			}
			$actions[] = self::readAction($in, $typeId);
		}
		$filterStrings = [];
		$filterStringCause = 0;
		return new self($requestId, $actions, $filterStrings, $filterStringCause);
	}

	/**
	 * @throws ReflectionException
	 */
	private static function writeAction(PacketSerializer $out, ItemStackRequestAction $action) : void{
		if($action instanceof SwapStackRequestAction){
			if(($containerId = ($slot1 = $action->getSlot1())->getContainerName()->getContainerId()) > v419ContainerUIIds::RECIPE_BOOK){
				$containerId--;
				ReflectionUtils::setProperty(get_class($action), $action, "slot1", new v419ItemStackRequestSlotInfo($containerId, $slot1->getSlotId(), $slot1->getStackId()));
			}elseif($containerId === v419ContainerUIIds::RECIPE_BOOK){
				throw new InvalidArgumentException("Invalid container ID for protocol version 419");
			}
			if(($containerId = ($slot2 = $action->getSlot2())->getContainerName()->getContainerId()) > v419ContainerUIIds::RECIPE_BOOK){
				$containerId--;
				ReflectionUtils::setProperty(get_class($action), $action, "slot2", new v419ItemStackRequestSlotInfo($containerId, $slot2->getSlotId(), $slot2->getStackId()));
			}elseif($containerId === v419ContainerUIIds::RECIPE_BOOK){
				throw new InvalidArgumentException("Invalid container ID for protocol version 419");
			}
		}elseif($action instanceof CraftingConsumeInputStackRequestAction |
			$action instanceof DestroyStackRequestAction |
			$action instanceof DropStackRequestAction
		){
			if(($containerId = ($source = $action->getSource())->getContainerName()->getContainerId()) > v419ContainerUIIds::RECIPE_BOOK){
				$containerId--;
				ReflectionUtils::setProperty(get_class($action), $action, "source", new v419ItemStackRequestSlotInfo($containerId, $source->getSlotId(), $source->getStackId()));
			}elseif($containerId === v419ContainerUIIds::RECIPE_BOOK){
				throw new InvalidArgumentException("Invalid container ID for protocol version 419");
			}
		}elseif($action instanceof v419PlaceStackRequestAction |
			$action instanceof v419TakeStackRequestAction
		){
			if(($containerId = ($source = $action->getSource())->getContainerId()) > v419ContainerUIIds::RECIPE_BOOK){
				$containerId--;
				ReflectionUtils::setProperty(get_class($action), $action, "source", new v419ItemStackRequestSlotInfo($containerId, $source->getSlotId(), $source->getStackId()));
			}elseif($containerId === v419ContainerUIIds::RECIPE_BOOK){
				throw new InvalidArgumentException("Invalid container ID for protocol version 419");
			}
			if(($containerId = ($destination = $action->getDestination())->getContainerId()) > v419ContainerUIIds::RECIPE_BOOK){
				$containerId--;
				ReflectionUtils::setProperty(get_class($action), $action, "destination", new v419ItemStackRequestSlotInfo($containerId, $destination->getSlotId(), $destination->getStackId()));
			}elseif($containerId === v419ContainerUIIds::RECIPE_BOOK){
				throw new InvalidArgumentException("Invalid container ID for protocol version 419");
			}
		}elseif($action instanceof v419CraftRecipeAutoStackRequestAction){
			$action = new CraftRecipeAutoStackRequestAction($action->getRecipeId(), $action->getRepetitions(), $action->getIngredients());
		}
		$action->write($out);
	}

	/**
	 * @throws ReflectionException
	 */
	public function write(PacketSerializer $out) : void{
		$out->putVarInt($this->requestId);
		$out->putUnsignedVarInt(count($this->actions));
		foreach($this->actions as $action){
			$typeId = $action->getTypeId();
			if($typeId >= 7){
				$typeId -= ItemStackRequestActionType::LAB_TABLE_COMBINE - 7;
			}
			$out->putByte($typeId);
			self::writeAction($out, $action);
		}
	}
}
