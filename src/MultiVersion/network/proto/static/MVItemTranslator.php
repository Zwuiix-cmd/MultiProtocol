<?php

namespace MultiVersion\network\proto\static;

use InvalidArgumentException;
use pocketmine\data\bedrock\item\BlockItemIdMap;
use pocketmine\data\bedrock\item\ItemDeserializer;
use pocketmine\data\bedrock\item\ItemSerializer;
use pocketmine\data\bedrock\item\ItemTypeDeserializeException;
use pocketmine\data\bedrock\item\ItemTypeSerializeException;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\TypeConversionException;
use pocketmine\network\mcpe\protocol\serializer\ItemTypeDictionary;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\format\io\GlobalItemDataHandlers;

class MVItemTranslator{

	public const NO_BLOCK_RUNTIME_ID = 0; //this is technically a valid block runtime ID, but is used to represent "no block" (derp mojang)

	public function __construct(
		private ItemTypeDictionary $itemTypeDictionary,
		private MVBlockStateDictionary $blockStateDictionary,
		private ItemSerializer $itemSerializer,
		private ItemDeserializer $itemDeserializer,
		private BlockItemIdMap $blockItemIdMap,
		private MVItemIdMetaDowngrader $itemDataDowngrader,
	){

	}

	/**
	 * @return int[]|null
	 * @phpstan-return array{int, int, ?int}|null
	 */
	public function toNetworkIdQuiet(Item $item) : ?array{
		try{
			return $this->toNetworkId($item);
		}catch(ItemTypeSerializeException){
			return null;
		}
	}

	/**
	 * @return int[]
	 * @phpstan-return array{int, int, ?int}
	 *
	 * @throws ItemTypeSerializeException
	 */
	public function toNetworkId(Item $item) : array{
		//TODO: we should probably come up with a cache for this

		$itemData = $this->itemSerializer->serializeType($item);

		[$name, $meta] = $this->itemDataDowngrader->downgrade($itemData->getName(), $itemData->getMeta());
		try{
			$numericId = $this->itemTypeDictionary->fromStringId($name);
		}catch(InvalidArgumentException){
			throw new ItemTypeSerializeException("Unknown item type $name");
		}

		$blockStateData = $itemData->getBlock();

		if($blockStateData !== null){
			$blockRuntimeId = $this->blockStateDictionary->lookupStateIdFromData($blockStateData);
			if($blockRuntimeId === null){
				throw new AssumptionFailedError("Unmapped blockstate returned by blockstate serializer: " . $blockStateData->toNbt());
			}
		}else{
			$blockRuntimeId = null;
		}

		return [$numericId, $meta ?? $itemData->getMeta(), $blockRuntimeId];
	}

	/**
	 * @throws ItemTypeSerializeException
	 */
	public function toNetworkNbt(Item $item) : CompoundTag{
		//TODO: this relies on the assumption that network item NBT is the same as disk item NBT, which may not always
		//be true - if we stick on an older world version while updating network version, this could be a problem (and
		//may be a problem for multi version implementations)
		return $this->itemSerializer->serializeStack($item)->toNbt();
	}

	/**
	 * @throws TypeConversionException
	 */
	public function fromNetworkId(int $networkId, int $networkMeta, int $networkBlockRuntimeId) : Item{
		try{
			$stringId = $this->itemTypeDictionary->fromIntId($networkId);
		}catch(InvalidArgumentException $e){
			//TODO: a quiet version of fromIntId() would be better than catching InvalidArgumentException
			throw TypeConversionException::wrap($e, "Invalid network itemstack ID $networkId");
		}

		$blockStateData = null;
		if($this->blockItemIdMap->lookupBlockId($stringId) !== null){
			$blockStateData = $this->blockStateDictionary->generateCurrentDataFromStateId($networkBlockRuntimeId);
			if($blockStateData === null){
				throw new TypeConversionException("Blockstate runtimeID $networkBlockRuntimeId does not correspond to any known blockstate");
			}
		}elseif($networkBlockRuntimeId !== self::NO_BLOCK_RUNTIME_ID){
			throw new TypeConversionException("Item $stringId is not a blockitem, but runtime ID $networkBlockRuntimeId was provided");
		}

		[$stringId, $networkMeta] = GlobalItemDataHandlers::getUpgrader()->getIdMetaUpgrader()->upgrade($stringId, $networkMeta);

		try{
			return $this->itemDeserializer->deserializeType(new SavedItemData($stringId, $networkMeta, $blockStateData));
		}catch(ItemTypeDeserializeException $e){
			throw TypeConversionException::wrap($e, "Invalid network itemstack data");
		}
	}
}