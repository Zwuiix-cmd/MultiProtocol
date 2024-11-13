<?php

namespace MultiVersion\network\proto\v419;

use InvalidArgumentException;
use MultiVersion\network\proto\MVPacketSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\network\mcpe\protocol\types\entity\Attribute;
use pocketmine\network\mcpe\protocol\types\entity\BlockPosMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\ByteMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\CompoundTagMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\FloatMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\IntMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\MetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\ShortMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\Vec3MetadataProperty;
use pocketmine\network\mcpe\protocol\types\FloatGameRule;
use pocketmine\network\mcpe\protocol\types\GameRule;
use pocketmine\network\mcpe\protocol\types\IntGameRule;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackExtraData;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackExtraDataShield;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\recipe\IntIdMetaItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;
use pocketmine\network\mcpe\protocol\types\skin\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\skin\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\skin\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use pocketmine\utils\BinaryDataException;
use UnexpectedValueException;

class v419PacketSerializer extends MVPacketSerializer{

	private const PM_BLOCK_RUNTIME_ID_TAG = "___block_runtime_id___";

	public function getSkin() : SkinData{
		$skinId = $this->getString();
		$skinResourcePatch = $this->getString();
		$skinData = $this->getSkinImage();
		$animationCount = $this->getLInt();
		$animations = [];
		for($i = 0; $i < $animationCount; ++$i){
			$skinImage = $this->getSkinImage();
			$animationType = $this->getLInt();
			$animationFrames = $this->getLFloat();
			$expressionType = $this->getLInt();
			$animations[] = new SkinAnimation($skinImage, $animationType, $animationFrames, $expressionType);
		}
		$capeData = $this->getSkinImage();
		$geometryData = $this->getString();
		$animationData = $this->getString();
		$premium = $this->getBool();
		$persona = $this->getBool();
		$capeOnClassic = $this->getBool();
		$capeId = $this->getString();
		$fullSkinId = $this->getString();
		$armSize = $this->getString();
		$skinColor = $this->getString();
		$personaPieceCount = $this->getLInt();
		$personaPieces = [];
		for($i = 0; $i < $personaPieceCount; ++$i){
			$pieceId = $this->getString();
			$pieceType = $this->getString();
			$packId = $this->getString();
			$isDefaultPiece = $this->getBool();
			$productId = $this->getString();
			$personaPieces[] = new PersonaSkinPiece($pieceId, $pieceType, $packId, $isDefaultPiece, $productId);
		}
		$pieceTintColorCount = $this->getLInt();
		$pieceTintColors = [];
		for($i = 0; $i < $pieceTintColorCount; ++$i){
			$pieceType = $this->getString();
			$colorCount = $this->getLInt();
			$colors = [];
			for($j = 0; $j < $colorCount; ++$j){
				$colors[] = $this->getString();
			}
			$pieceTintColors[] = new PersonaPieceTintColor(
				$pieceType,
				$colors
			);
		}

		return new SkinData(
			$skinId,
			"",
			$skinResourcePatch,
			$skinData,
			$animations,
			$capeData,
			$geometryData,
			ProtocolInfo::MINECRAFT_VERSION_NETWORK,
			$animationData,
			$capeId,
			$fullSkinId,
			$armSize,
			$skinColor,
			$personaPieces,
			$pieceTintColors,
			true,
			$premium,
			$persona,
			$capeOnClassic,
			false,
			$override ?? true
		);
	}

	public function putSkin(SkinData $skin) : void{
		$this->putString($skin->getSkinId());
		$this->putString($skin->getResourcePatch());
		$this->putSkinImage($skin->getSkinImage());
		$this->putLInt(count($skin->getAnimations()));
		foreach($skin->getAnimations() as $animation){
			$this->putSkinImage($animation->getImage());
			$this->putLInt($animation->getType());
			$this->putLFloat($animation->getFrames());
			$this->putLInt($animation->getExpressionType());
		}
		$this->putSkinImage($skin->getCapeImage());
		$this->putString($skin->getGeometryData());
		$this->putString($skin->getAnimationData());
		$this->putBool($skin->isPremium());
		$this->putBool($skin->isPersona());
		$this->putBool($skin->isPersonaCapeOnClassic());
		$this->putString($skin->getCapeId());
		$this->putString($skin->getFullSkinId());
		$this->putString($skin->getArmSize());
		$this->putString($skin->getSkinColor());
		$this->putLInt(count($skin->getPersonaPieces()));
		foreach($skin->getPersonaPieces() as $piece){
			$this->putString($piece->getPieceId());
			$this->putString($piece->getPieceType());
			$this->putString($piece->getPackId());
			$this->putBool($piece->isDefaultPiece());
			$this->putString($piece->getProductId());
		}
		$this->putLInt(count($skin->getPieceTintColors()));
		foreach($skin->getPieceTintColors() as $tint){
			$this->putString($tint->getPieceType());
			$this->putLInt(count($tint->getColors()));
			foreach($tint->getColors() as $color){
				$this->putString($color);
			}
		}
	}

	private function getSkinImage() : SkinImage{
		$width = $this->getLInt();
		$height = $this->getLInt();
		$data = $this->getString();
		try{
			return new SkinImage($height, $width, $data);
		}catch(InvalidArgumentException $e){
			throw new PacketDecodeException($e->getMessage(), 0, $e);
		}
	}

	private function putSkinImage(SkinImage $image) : void{
		$this->putLInt($image->getWidth());
		$this->putLInt($image->getHeight());
		$this->putString($image->getData());
	}

	/**
	 * @return int[]
	 * @phpstan-return array{0: int, 1: int, 2: int}
	 * @throws PacketDecodeException
	 */
	private function getItemStackHeader() : array{
		$id = $this->getVarInt();
		if($id === 0){
			return [0, 0, 0];
		}

		$auxValue = $this->getVarInt();
		$count = $auxValue & 0xff;
		$meta = $auxValue >> 8;

		return [$id, $count, $meta];
	}

	private function putItemStackHeader(ItemStack $itemStack) : bool{
		if($itemStack->getId() === 0){
			$this->putVarInt(0);
			return false;
		}

		$this->putVarInt($itemStack->getId());
		$this->putVarInt((($itemStack->getMeta() & 0x7fff) << 8) | $itemStack->getCount());

		return true;
	}

	private function getItemStackFooter(int $id, int $meta, int $count) : ItemStack{
		$nbtLen = $this->getLShort();

		/** @var CompoundTag|null $compound */
		$compound = null;
		if($nbtLen === 0xffff){
			$nbtDataVersion = $this->getByte();
			if($nbtDataVersion !== 1){
				throw new UnexpectedValueException("Unexpected NBT count $nbtDataVersion");
			}
			$compound = $this->getNbtCompoundRoot();
		}elseif($nbtLen !== 0){
			throw new UnexpectedValueException("Unexpected fake NBT length $nbtLen");
		}

		$blockRuntimeId = 0;
		if($compound !== null){
			$blockRuntimeId = $compound->getInt(self::PM_BLOCK_RUNTIME_ID_TAG, 0);
			$compound->removeTag(self::PM_BLOCK_RUNTIME_ID_TAG);
		}

		$canBePlacedOn = [];
		for($i = 0, $canPlaceOn = $this->getVarInt(); $i < $canPlaceOn; ++$i){
			$canBePlacedOn[] = $this->getString();
		}

		$canDestroyBlocks = [];
		for($i = 0, $canDestroy = $this->getVarInt(); $i < $canDestroy; ++$i){
			$canDestroyBlocks[] = $this->getString();
		}

		$blockingTick = null;
		if($id === v419TypeConverter::getInstance()->getTypeConverter()->getShieldRuntimeId()){
			$blockingTick = $this->getVarLong(); //"blocking tick" (ffs mojang)
		}

		$extraData = $blockingTick !== null ?
			new ItemStackExtraDataShield($compound, canPlaceOn: $canBePlacedOn, canDestroy: $canDestroyBlocks, blockingTick: $blockingTick) :
			new ItemStackExtraData($compound, canPlaceOn: $canBePlacedOn, canDestroy: $canDestroyBlocks);
		$extraDataSerializer = PacketSerializer::encoder();
		$extraData->write($extraDataSerializer);

		return new ItemStack($id, $meta, $count, $blockRuntimeId, $extraDataSerializer->getBuffer());
	}

	private function putItemStackFooter(ItemStack $itemStack) : void{
		$blockRuntimeId = $itemStack->getBlockRuntimeId();

		$decoder = PacketSerializer::decoder($itemStack->getRawExtraData(), 0);
		if($itemStack->getId() === v419TypeConverter::getInstance()->getTypeConverter()->getShieldRuntimeId()){
			$extraData = ItemStackExtraDataShield::read($decoder);
		}else{
			$extraData = ItemStackExtraData::read($decoder);
		}
		$compound = new CompoundTag();
		if($extraData->getNbt() !== null){
			$compound = clone $extraData->getNbt();
		}
		$compound->setInt(self::PM_BLOCK_RUNTIME_ID_TAG, $blockRuntimeId);

		$this->putLShort(0xffff);
		$this->putByte(1); //TODO: some kind of count field? always 1 as of 1.9.0
		$this->put((new NetworkNbtSerializer())->write(new TreeRoot($compound)));

		$this->putVarInt(count($extraData->getCanPlaceOn()));
		foreach($extraData->getCanPlaceOn() as $toWrite){
			$this->putString($toWrite);
		}
		$this->putVarInt(count($extraData->getCanDestroy()));
		foreach($extraData->getCanDestroy() as $toWrite){
			$this->putString($toWrite);
		}

		if($itemStack->getId() === v419TypeConverter::getInstance()->getTypeConverter()->getShieldRuntimeId()){
			$this->putVarLong($extraData->getBlockingTick() ?? 0); //"blocking tick" (ffs mojang)
		}
	}

	/**
	 * @throws PacketDecodeException
	 * @throws BinaryDataException
	 */
	public function getItemStackWithoutStackId() : ItemStack{
		[$id, $count, $meta] = $this->getItemStackHeader();

		return $id !== 0 ? $this->getItemStackFooter($id, $meta, $count) : ItemStack::null();

	}

	public function putItemStackWithoutStackId(ItemStack $itemStack) : void{
		if($this->putItemStackHeader($itemStack)){
			$this->putItemStackFooter($itemStack);
		}
	}

	public function getItemStackWrapper() : ItemStackWrapper{
		[$id, $count, $meta] = $this->getItemStackHeader();
		if($id === 0){
			return new ItemStackWrapper(0, ItemStack::null());
		}

		$itemStack = $this->getItemStackFooter($id, $meta, $count);

		return new ItemStackWrapper(0, $itemStack);
	}

	public function putItemStackWrapper(ItemStackWrapper $itemStackWrapper) : void{
		$itemStack = $itemStackWrapper->getItemStack();
		if($this->putItemStackHeader($itemStack)){
			$this->putItemStackFooter($itemStack);
		}
	}

	public function getRecipeIngredient() : RecipeIngredient{
		$id = $this->getVarInt();
		if($id === 0){
			return new RecipeIngredient(new IntIdMetaItemDescriptor(0, 0), 0);
		}
		$meta = $this->getVarInt();
		if($meta === 0x7fff){
			$meta = -1;
		}
		$count = $this->getVarInt();
		return new RecipeIngredient(new IntIdMetaItemDescriptor($id, $meta), $count);
	}

	public function putRecipeIngredient(RecipeIngredient $item) : void{
		$descriptor = $item->getDescriptor();
		if($descriptor?->getTypeId() === IntIdMetaItemDescriptor::ID){
			/** @var IntIdMetaItemDescriptor $descriptor */
			if($descriptor->getId() === 0){
				$this->putVarInt(0);
			}else{
				$this->putVarInt($descriptor->getId());
				$this->putVarInt($descriptor->getMeta() & 0x7fff);
				$this->putVarInt($item->getCount());
			}
		}else{
			$this->putVarInt(0);
		}
	}

	/**
	 * Decodes entity metadata from the stream.
	 *
	 * @return MetadataProperty[]
	 * @phpstan-return array<int, MetadataProperty>
	 *
	 * @throws PacketDecodeException
	 * @throws BinaryDataException
	 */
	public function getEntityMetadata() : array{
		$count = $this->getUnsignedVarInt();
		$metadata = [];
		for($i = 0; $i < $count; ++$i){
			$key = $this->getUnsignedVarInt();
			$type = $this->getUnsignedVarInt();

			$metadata[$key] = $this->readMetadataProperty($type);
		}

		/** @var LongMetadataProperty $flag1Property */
		$flag1Property = $metadata[EntityMetadataProperties::FLAGS] ?? new LongMetadataProperty(0);
		/** @var LongMetadataProperty $flag2Property */
		$flag2Property = $metadata[EntityMetadataProperties::FLAGS2] ?? new LongMetadataProperty(0);
		$flag1 = $flag1Property->getValue();
		$flag2 = $flag2Property->getValue();

		$flag2 <<= 1; // shift left by 1, leaving a 0 at the end
		$flag2 |= (($flag1 >> 63) & 1); // push the last bit from flag1 to the first bit of flag2

		$newFlag1 = $flag1 & ~(~0 << (EntityMetadataFlags::CAN_DASH - 1)); // don't include CAN_DASH and above
		$lastHalf = $flag1 & (~0 << (EntityMetadataFlags::CAN_DASH - 1)); // include everything after where CAN_DASH would be
		$lastHalf <<= 1; // shift left by 1, CAN_DASH is now 0
		$newFlag1 |= $lastHalf; // combine the two halves

		$metadata[EntityMetadataProperties::FLAGS2] = new LongMetadataProperty($flag2);
		$metadata[EntityMetadataProperties::FLAGS] = new LongMetadataProperty($newFlag1);

		return $metadata;
	}

	private function readMetadataProperty(int $type) : MetadataProperty{
		return match ($type) {
			ByteMetadataProperty::ID => ByteMetadataProperty::read($this),
			ShortMetadataProperty::ID => ShortMetadataProperty::read($this),
			IntMetadataProperty::ID => IntMetadataProperty::read($this),
			FloatMetadataProperty::ID => FloatMetadataProperty::read($this),
			StringMetadataProperty::ID => StringMetadataProperty::read($this),
			CompoundTagMetadataProperty::ID => CompoundTagMetadataProperty::read($this),
			BlockPosMetadataProperty::ID => BlockPosMetadataProperty::read($this),
			LongMetadataProperty::ID => LongMetadataProperty::read($this),
			Vec3MetadataProperty::ID => Vec3MetadataProperty::read($this),
			default => throw new PacketDecodeException("Unknown entity metadata type " . $type),
		};
	}

	/**
	 * Writes entity metadata to the packet buffer.
	 *
	 * @param MetadataProperty[]                   $metadata
	 *
	 * @phpstan-param array<int, MetadataProperty> $metadata
	 */
	public function putEntityMetadata(array $metadata) : void{
		$data = $metadata;
		foreach($data as $type => $val){
			$metadata[match ($type) {
				EntityMetadataProperties::AREA_EFFECT_CLOUD_RADIUS => 60,
				EntityMetadataProperties::AREA_EFFECT_CLOUD_WAITING => 61,
				EntityMetadataProperties::AREA_EFFECT_CLOUD_PARTICLE_ID => 62,
				EntityMetadataProperties::SHULKER_ATTACH_FACE => 64,
				EntityMetadataProperties::SHULKER_ATTACH_POS => 66,
				EntityMetadataProperties::TRADING_PLAYER_EID => 67,
				EntityMetadataProperties::COMMAND_BLOCK_COMMAND => 70,
				EntityMetadataProperties::COMMAND_BLOCK_LAST_OUTPUT => 71,
				EntityMetadataProperties::COMMAND_BLOCK_TRACK_OUTPUT => 72,
				EntityMetadataProperties::CONTROLLING_RIDER_SEAT_NUMBER => 73,
				EntityMetadataProperties::STRENGTH => 74,
				EntityMetadataProperties::MAX_STRENGTH => 75,
				EntityMetadataProperties::LIMITED_LIFE => 77,
				EntityMetadataProperties::ARMOR_STAND_POSE_INDEX => 78,
				EntityMetadataProperties::ENDER_CRYSTAL_TIME_OFFSET => 79,
				EntityMetadataProperties::ALWAYS_SHOW_NAMETAG => 80,
				EntityMetadataProperties::COLOR_2 => 81,
				EntityMetadataProperties::SCORE_TAG => 83,
				EntityMetadataProperties::BALLOON_ATTACHED_ENTITY => 84,
				EntityMetadataProperties::PUFFERFISH_SIZE => 85,
				EntityMetadataProperties::BOAT_BUBBLE_TIME => 86,
				EntityMetadataProperties::PLAYER_AGENT_EID => 87,
				EntityMetadataProperties::EAT_COUNTER => 90,
				EntityMetadataProperties::FLAGS2 => 91,
				EntityMetadataProperties::AREA_EFFECT_CLOUD_DURATION => 94,
				EntityMetadataProperties::AREA_EFFECT_CLOUD_SPAWN_TIME => 95,
				EntityMetadataProperties::AREA_EFFECT_CLOUD_RADIUS_PER_TICK => 96,
				EntityMetadataProperties::AREA_EFFECT_CLOUD_RADIUS_CHANGE_ON_PICKUP => 97,
				EntityMetadataProperties::AREA_EFFECT_CLOUD_PICKUP_COUNT => 98,
				EntityMetadataProperties::INTERACTIVE_TAG => 99,
				EntityMetadataProperties::TRADE_TIER => 100,
				EntityMetadataProperties::MAX_TRADE_TIER => 101,
				EntityMetadataProperties::TRADE_XP => 102,
				EntityMetadataProperties::SKIN_ID => 104,
				EntityMetadataProperties::COMMAND_BLOCK_TICK_DELAY => 105,
				EntityMetadataProperties::COMMAND_BLOCK_EXECUTE_ON_FIRST_TICK => 106,
				EntityMetadataProperties::AMBIENT_SOUND_INTERVAL_MIN => 107,
				EntityMetadataProperties::AMBIENT_SOUND_INTERVAL_RANGE => 108,
				EntityMetadataProperties::AMBIENT_SOUND_EVENT => 109,
				default => $type,
			}] = $val;
		}

		/** @var LongMetadataProperty $flag1Property */
		$flag1Property = $metadata[EntityMetadataProperties::FLAGS] ?? new LongMetadataProperty(0);
		/** @var LongMetadataProperty $flag2Property */
		$flag2Property = $metadata[EntityMetadataProperties::FLAGS2] ?? new LongMetadataProperty(0);
		$flag1 = $flag1Property->getValue();
		$flag2 = $flag2Property->getValue();

		if($flag1 !== 0 || $flag2 !== 0){
			$newFlag1 = $flag1 & ~(~0 << (EntityMetadataFlags::CAN_DASH - 1));
			$lastHalf = $flag1 & (~0 << EntityMetadataFlags::CAN_DASH);
			$lastHalf >>= 1;
			$lastHalf &= PHP_INT_MAX;

			$newFlag1 |= $lastHalf;

			if($flag2 !== 0){
				$flag2 = $flag2Property->getValue();
				$newFlag1 ^= ($flag2 & 1) << 63;
				$flag2 >>= 1;
				$flag2 &= PHP_INT_MAX;

				$metadata[EntityMetadataProperties::FLAGS2] = new LongMetadataProperty($flag2);
			}

			$metadata[EntityMetadataProperties::FLAGS] = new LongMetadataProperty($newFlag1);
		}

		$this->putUnsignedVarInt(count($metadata));
		foreach($metadata as $key => $d){
			$this->putUnsignedVarInt($key);
			$this->putUnsignedVarInt($d->getTypeId());
			$d->write($this);
		}
	}

	/**
	 * Reads a list of Attributes from the stream.
	 * @return Attribute[]
	 *
	 * @throws BinaryDataException
	 */
	public function getAttributeList() : array{
		$list = [];
		$count = $this->getUnsignedVarInt();

		for($i = 0; $i < $count; ++$i){
			$min = $this->getLFloat();
			$max = $this->getLFloat();
			$current = $this->getLFloat();
			$default = $this->getLFloat();
			$id = $this->getString();

			$list[] = new Attribute($id, $min, $max, $current, $default, []);
		}

		return $list;
	}

	/**
	 * Writes a list of Attributes to the packet buffer using the standard format.
	 */
	public function putAttributeList(Attribute ...$attributes) : void{
		$this->putUnsignedVarInt(count($attributes));
		foreach($attributes as $attribute){
			$this->putLFloat($attribute->getMin());
			$this->putLFloat($attribute->getMax());
			$this->putLFloat($attribute->getCurrent());
			$this->putLFloat($attribute->getDefault());
			$this->putString($attribute->getId());
		}
	}

	/**
	 * Reads gamerules
	 *
	 * @return GameRule[] game rule name => value
	 * @phpstan-return array<string, GameRule>
	 *
	 * @throws PacketDecodeException
	 * @throws BinaryDataException
	 */
	public function getGameRules() : array{
		$count = $this->getUnsignedVarInt();
		$rules = [];
		for($i = 0; $i < $count; ++$i){
			$name = $this->getString();
			$type = $this->getUnsignedVarInt();
			$rules[$name] = $this->readGameRule($type, false);
		}

		return $rules;
	}

	/**
	 * Writes a gamerule array
	 *
	 * @param GameRule[]                      $rules
	 *
	 * @phpstan-param array<string, GameRule> $rules
	 */
	public function putGameRules(array $rules) : void{
		$this->putUnsignedVarInt(count($rules));
		foreach($rules as $name => $rule){
			$this->putString($name);
			$this->putUnsignedVarInt($rule->getTypeId());
			$rule->encode($this);
		}
	}

	private function readGameRule(int $type, bool $isPlayerModifiable) : GameRule{
		return match ($type) {
			BoolGameRule::ID => BoolGameRule::decode($this, $isPlayerModifiable),
			IntGameRule::ID => IntGameRule::decode($this, $isPlayerModifiable),
			FloatGameRule::ID => FloatGameRule::decode($this, $isPlayerModifiable),
			default => throw new PacketDecodeException("Unknown gamerule type $type"),
		};
	}
}