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

namespace MultiVersion\network\proto\v419\packets\types\inventory\stackrequest;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestActionType;

/**
 * Swaps two stacks. These don't have to be in the same inventory. This action does not modify the stacks themselves.
 */
final class v419SwapStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;

	public const ID = ItemStackRequestActionType::SWAP;

	public function __construct(
		private v419ItemStackRequestSlotInfo $slot1,
		private v419ItemStackRequestSlotInfo $slot2
	){}

	public function getSlot1() : v419ItemStackRequestSlotInfo{ return $this->slot1; }

	public function getSlot2() : v419ItemStackRequestSlotInfo{ return $this->slot2; }

	public static function read(PacketSerializer $in) : self{
		$slot1 = v419ItemStackRequestSlotInfo::read($in);
		$slot2 = v419ItemStackRequestSlotInfo::read($in);
		return new self($slot1, $slot2);
	}

	public function write(PacketSerializer $out) : void{
		$this->slot1->write($out);
		$this->slot2->write($out);
	}
}