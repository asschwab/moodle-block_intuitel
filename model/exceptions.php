<?php
namespace intuitel;
class IntuitelException extends \Exception
{
	var $statusCode=400;
	public function getStatusCode()
	{
		return $this->statusCode;
	}
}
class UnknownLOTypeException extends IntuitelException
{
	
}
class UnknownLOException extends IntuitelException
{
	
}
class UnknownIDException extends IntuitelException
{

}
class AccessDeniedException extends IntuitelException
{
	var $statusCode=401;
}
class UnknownUserException extends IntuitelException
{
	
}
class ProtocolErrorException extends IntuitelException
{
	function __construct($message,$statusCode=400)
	{
		parent::__construct ( $message );
		$this->statusCode=$statusCode;
	}
}