<?php
namespace intuitel;
class VisitEvent
{
	/**
	 * 
	 * @var UserId
	 */
	var $userId;
	/**
	 * 
	 * @var long
	 */
	var $time;
	/**
	 * 
	 * @var LOId
	 */
	var $loId;
	/**
	 * estimated duration of this event. May be null if no computation exists.
	 * @var long
	 */
	var $duration=null;
	
	function __construct(UserId $userId, LOId $loId,$time)
	{
		$this->loId=$loId;
		$this->time=$time;
		$this->userId=$userId;
	}
}