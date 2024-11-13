<?php

/*
 * This file is part of BedrockProtocol.
 * Copyright (C) 2014-2022 PocketMine Team <https://github.com/pmmp/BedrockProtocol>
 *
 * BedrockProtocol is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace MultiVersion\network\proto\v419\packets;

use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;

class v419MobArmorEquipmentPacket extends MobArmorEquipmentPacket{
	public const NETWORK_ID = ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET;

	public int $actorRuntimeId;

	//this intentionally doesn't use an array because we don't want any implicit dependencies on internal order
	public ItemStackWrapper $head;
	public ItemStackWrapper $chest;
	public ItemStackWrapper $legs;
	public ItemStackWrapper $feet;

	public static function fromLatest(MobArmorEquipmentPacket $packet): self{
		$npk = new self;
		$npk->actorRuntimeId = $packet->actorRuntimeId;
		$npk->head = $packet->head;
		$npk->chest = $packet->chest;
		$npk->legs = $packet->legs;
		$npk->feet = $packet->feet;
		return $npk;
	}


	protected function decodePayload(PacketSerializer $in) : void{
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->head = $in->getItemStackWrapper();
		$this->chest = $in->getItemStackWrapper();
		$this->legs = $in->getItemStackWrapper();
		$this->feet = $in->getItemStackWrapper();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putItemStackWrapper($this->head);
		$out->putItemStackWrapper($this->chest);
		$out->putItemStackWrapper($this->legs);
		$out->putItemStackWrapper($this->feet);
	}
}