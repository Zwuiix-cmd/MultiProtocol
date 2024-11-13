<?php

namespace MultiVersion\network\proto\v419\packets;

use MultiVersion\network\proto\utils\ReflectionUtils;
use MultiVersion\network\proto\v419\packets\types\inventory\stackrequest\v419ItemStackRequest;
use MultiVersion\network\proto\v419\packets\types\v419ItemInteractionData;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequest;
use pocketmine\network\mcpe\protocol\types\ItemInteractionData;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\types\PlayerBlockActionStopBreak;
use pocketmine\network\mcpe\protocol\types\PlayerBlockActionWithBlockInfo;
use pocketmine\network\mcpe\protocol\types\PlayMode;
use ReflectionException;

class v419PlayerAuthInputPacket extends PlayerAuthInputPacket{

	/**
	 * @throws ReflectionException
	 */
	protected function decodePayload(PacketSerializer $in) : void{
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "pitch", $in->getLFloat());
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "yaw", $in->getLFloat());
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "position", $in->getVector3());
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "moveVecX", $in->getLFloat());
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "moveVecZ", $in->getLFloat());
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "headYaw", $in->getLFloat());
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "inputFlags", $in->getUnsignedVarLong());
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "inputMode", $in->getUnsignedVarInt());
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "playMode", $in->getUnsignedVarInt());
		if(ReflectionUtils::getProperty(PlayerAuthInputPacket::class, $this, "playMode") === PlayMode::VR){
			ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "vrGazeDirection", $in->getVector3());
		}
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "tick", $in->getUnsignedVarLong());
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "delta", $in->getVector3());

		if($this->hasFlag(PlayerAuthInputFlags::PERFORM_ITEM_INTERACTION)){
			$d = v419ItemInteractionData::read($in);
			ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, 'itemInteractionData', new ItemInteractionData($d->getRequestId(), $d->getRequestChangedSlots(), $d->getTransactionData()));
		}
		if($this->hasFlag(PlayerAuthInputFlags::PERFORM_ITEM_STACK_REQUEST)){
			$request = v419ItemStackRequest::read($in);
			ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "itemStackRequest", new ItemStackRequest($request->getRequestId(), $request->getActions(), $request->getFilterStrings(), $request->getFilterStringCause()));
		}
		if($this->hasFlag(PlayerAuthInputFlags::PERFORM_BLOCK_ACTIONS)){
			$blockActions = [];
			$max = $in->getVarInt();
			for($i = 0; $i < $max; ++$i){
				$actionType = $in->getVarInt();
				$blockActions[] = match (true) {
					PlayerBlockActionWithBlockInfo::isValidActionType($actionType) => PlayerBlockActionWithBlockInfo::read($in, $actionType),
					$actionType === PlayerAction::STOP_BREAK => new PlayerBlockActionStopBreak(),
					default => throw new PacketDecodeException("Unexpected block action type $actionType")
				};
			}
			ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "blockActions", $blockActions);
		}
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "analogMoveVecX", 0);
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "analogMoveVecZ", 0);
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putLFloat($this->getPitch());
		$out->putLFloat($this->getYaw());
		$out->putVector3($this->getPosition());
		$out->putLFloat($this->getMoveVecX());
		$out->putLFloat($this->getMoveVecZ());
		$out->putLFloat($this->getHeadYaw());
		$out->putUnsignedVarLong($this->getInputFlags());
		$out->putUnsignedVarInt($this->getInputMode());
		$out->putUnsignedVarInt($this->getPlayMode());
		if($this->getPlayMode() === PlayMode::VR){
			assert($this->getVrGazeDirection() !== null);
			$out->putVector3($this->getVrGazeDirection());
		}
		$out->putUnsignedVarLong($this->getTick());
		$out->putVector3($this->getDelta());

		$this->getItemInteractionData()?->write($out);
		$this->getItemStackRequest()?->write($out);
		$blockActions = $this->getBlockActions();
		if($blockActions !== null){
			$out->putVarInt(count($blockActions));
			foreach($blockActions as $blockAction){
				$out->putVarInt($blockAction->getActionType());
				$blockAction->write($out);
			}
		}
	}
}