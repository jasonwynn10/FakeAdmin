<?php
namespace jasonwynn10\FakeAdmin\network;

use jasonwynn10\FakeAdmin\Main;
use pocketmine\block\Solid;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\PlayerNetworkSessionAdapter;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\SourceInterface;
use pocketmine\Player;

class FakeAdmin extends Player {
	public $spec_needRespawn = false;
	private $forceMovement;
	private $currentState = Main::ACTION_DORMANT;
	public $target = "";
	/** @var int */
	public $directionFindTick = 0;
	/** @var int */
	private $attackDelay = 6;
	/** @var float */
	public $xOffset = 0.0;
	/** @var float */
	public $yOffset = 0.0;
	/** @var float */
	public $zOffset = 0.0;

	public function __construct(SourceInterface $interface, $clientID, $ip, $port){
		parent::__construct($interface, $clientID, $ip, $port); // TODO: Change the autogenerated stub
	}

	/**
	 * @return Vector3
	 */
	public function getForceMovement(){
		return $this->forceMovement;
	}

	/**
	 * @return PlayerNetworkSessionAdapter
	 */
	public function getSessionAdapter() {
		return $this->sessionAdapter;
	}

	public function getCurrentAction() : int {
		return $this->currentState;
	}

	public function setAction(int $action) {
		$this->currentState = $action;
	}

	public function getGravity() {
		return $this->gravity;
	}

	public function generateNewDirection(): bool {
		if($this->directionFindTick < 120) {
			return false;
		}
		$this->setSprinting((bool) mt_rand(0, 1));
		$this->flying = (bool) mt_rand(0, 1);
		$i = mt_rand(0, 1) === 1 ? 1 : -1;
		$this->xOffset = lcg_value() * 7 * $i;
		if($this->flying) {
			$this->yOffset = lcg_value() * 3 * $i;
		}
		$this->zOffset = lcg_value() * 7 * $i;
		$this->directionFindTick = mt_rand(0, 40);
		return true;
	}

	public function checkWalkingArea(): bool {
		if($this->distance($block = $this->getTargetBlock(2)) <= 1.5) {
			if($block instanceof Solid) {
				if((int) $block->y === (int) $this->getEyeHeight()) {
					$this->directionFindTick = 120;
					return true;
				}
			}
		}
		return false;
	}

	public function getSpeed(): float {
		return ($this->isSprinting() ? 0.13 : 0.1);
	}

	public function hit(Player $player): bool {
		if(($this->isCreative() and $this->distance($player) > 7.5) or ($this->isSurvival() and $this->distance($player) > 5)) { // TODO: Find actual reach length of a player.
			return false;
		}
		if($this->attackDelay < 6) {
			$this->attackDelay++;
			return false;
		}
		$player->attack(7, new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 7));
		$pk = new AnimatePacket();
		$pk->action = 1;
		$pk->entityRuntimeId = $this->getId();
		foreach($this->getViewers() as $p) {
			$p->dataPacket($pk);
		}
		return true;
	}
}