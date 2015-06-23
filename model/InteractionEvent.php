<?php
namespace intuitel;
class InteractionEvent extends VisitEvent
{
    var $description;
    function __construct(UserId $userId, $id,$description, $time)
    {
        $this->description=$description;
        $this->loId=new LOId($id);
        $this->time=$time;
        $this->userId=$userId;
    }
}