<?php

namespace MultiVersion\network\proto\v419;

use MultiVersion\network\proto\utils\ReflectionUtils;
use MultiVersion\network\proto\v419\packets\types\v419ItemStackRequestExectutor;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\inventory\transaction\TransactionCancelledException;
use pocketmine\inventory\transaction\TransactionValidationException;
use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\handler\ItemStackRequestProcessException;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\protocol\ItemStackResponsePacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequest;
use pocketmine\network\mcpe\protocol\types\inventory\stackresponse\ItemStackResponse;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;
use pocketmine\utils\Utils;
use ReflectionException;

class v419InGamePacketHandler extends InGamePacketHandler{
	private InventoryManager $inventoryManager;

	public function __construct(Player $player, NetworkSession $session, InventoryManager $inventoryManager){
		$this->inventoryManager = $inventoryManager;
		parent::__construct($player, $session, $inventoryManager);
	}
	public function handlePlayerAction(PlayerActionPacket $packet) : bool{
		switch($packet->action){
			case PlayerAction::JUMP:
				$this->getPlayer()->jump();
				break;
			case PlayerAction::START_SPRINT:
				$this->getPlayer()->toggleSprint(true);
				break;
			case PlayerAction::STOP_SPRINT:
				$this->getPlayer()->toggleSprint(false);
				break;
			case PlayerAction::START_SNEAK:
				$this->getPlayer()->toggleSneak(true);
				break;
			case PlayerAction::STOP_SNEAK:
				$this->getPlayer()->toggleSneak(false);
				break;
			case PlayerAction::START_SWIMMING:
				$this->getPlayer()->toggleSwim(true);
				break;
			case PlayerAction::STOP_SWIMMING:
				$this->getPlayer()->toggleSwim(false);
				break;
			case PlayerAction::START_GLIDE:
				$this->getPlayer()->toggleGlide(true);
				break;
			case PlayerAction::STOP_GLIDE:
				$this->getPlayer()->toggleGlide(false);
				break;
			default:
				return parent::handlePlayerAction($packet);
		}
		return true;
	}

	private function handleSingleItemStackRequest(ItemStackRequest $request) : ItemStackResponse{
		if(count($request->getActions()) > 60){
			throw new PacketHandlingException("Too many actions in ItemStackRequest");
		}
		$executor = new v419ItemStackRequestExectutor($this->getPlayer(), $this->getSession()->getInvManager(), $request);
		try{
			$transaction = $executor->generateInventoryTransaction();
			$result = $this->executeInventoryTransaction($transaction, $request->getRequestId());
		}catch(ItemStackRequestProcessException $e){
			$result = false;
			$this->getSession()->getLogger()->debug("ItemStackRequest #" . $request->getRequestId() . " failed: " . $e->getMessage());
			$this->getSession()->getLogger()->debug(implode("\n", Utils::printableExceptionInfo($e)));
			$this->inventoryManager->requestSyncAll();
		}

		if(!$result){
			return new ItemStackResponse(ItemStackResponse::RESULT_ERROR, $request->getRequestId());
		}
		return $executor->buildItemStackResponse();
	}

	private function executeInventoryTransaction(InventoryTransaction $transaction, int $requestId) : bool{
		$this->getPlayer()->setUsingItem(false);

		$this->inventoryManager->setCurrentItemStackRequestId($requestId);
		$this->inventoryManager->addTransactionPredictedSlotChanges($transaction);
		try{
			$transaction->execute();
		}catch(TransactionValidationException $e){
			$this->inventoryManager->requestSyncAll();
			$logger = $this->getSession()->getLogger();
			$logger->debug("Invalid inventory transaction $requestId: " . $e->getMessage());

			return false;
		}catch(TransactionCancelledException){
			$this->getSession()->getLogger()->debug("Inventory transaction $requestId cancelled by a plugin");

			return false;
		}finally{
			$this->inventoryManager->syncMismatchedPredictedSlotChanges();
			$this->inventoryManager->setCurrentItemStackRequestId(null);
		}

		return true;
	}

	public function handleItemStackRequest(ItemStackRequestPacket $packet) : bool{
		$responses = [];
		if(count($packet->getRequests()) > 80){
			//TODO: we can probably lower this limit, but this will do for now
			throw new PacketHandlingException("Too many requests in ItemStackRequestPacket");
		}
		foreach($packet->getRequests() as $request){
			$responses[] = $this->handleSingleItemStackRequest($request);
		}

		$this->getSession()->sendDataPacket(ItemStackResponsePacket::create($responses));

		return true;
	}
	/**
	 * @throws ReflectionException
	 */
	private function getSession() : NetworkSession{
		return ReflectionUtils::getProperty(InGamePacketHandler::class, $this, "session");
	}

	public function handleEmote(EmotePacket $packet): bool{
		$this->getPlayer()->emote($packet->getEmoteId(), 20);
		return true;
	}


	/**
	 * @throws ReflectionException
	 */
	private function getPlayer() : Player{
		return ReflectionUtils::getProperty(InGamePacketHandler::class, $this, "player");
	}
}
