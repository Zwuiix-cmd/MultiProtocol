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

namespace MultiVersion\network\proto\v419\packets\types;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use function count;

final class v419ItemInteractionData{

	private int $requestId;
	/** @var InventoryTransactionChangedSlotsHack[] */
	private array $requestChangedSlots;
	private UseItemTransactionData $transactionData;

	/**
	 * @param InventoryTransactionChangedSlotsHack[] $requestChangedSlots
	 */
	public function __construct(int $requestId, array $requestChangedSlots, UseItemTransactionData $transactionData){
		$this->requestId = $requestId;
		$this->requestChangedSlots = $requestChangedSlots;
		$this->transactionData = $transactionData;
	}

	public function getRequestId() : int{
		return $this->requestId;
	}

	/**
	 * @return InventoryTransactionChangedSlotsHack[]
	 */
	public function getRequestChangedSlots() : array{
		return $this->requestChangedSlots;
	}

	public function getTransactionData() : UseItemTransactionData{
		return $this->transactionData;
	}

	public static function read(PacketSerializer $in) : self{
		$requestId = $in->getVarInt();
		$requestChangedSlots = [];
		if($requestId !== 0){
			$len = $in->getUnsignedVarInt();
			for($i = 0; $i < $len; ++$i){
				$requestChangedSlots[] = InventoryTransactionChangedSlotsHack::read($in);
			}
		}
		$transactionData = new v419UseItemTransactionData();
		$transactionData->decode($in);
		return new self($requestId, $requestChangedSlots, $transactionData);
	}

	public function write(PacketSerializer $out) : void{
		$out->putVarInt($this->requestId);
		if($this->requestId !== 0){
			$out->putUnsignedVarInt(count($this->requestChangedSlots));
			foreach($this->requestChangedSlots as $changedSlot){
				$changedSlot->write($out);
			}
		}
		$this->transactionData->encode($out);
	}
}
