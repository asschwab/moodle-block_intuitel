<?php
namespace intuitel;

class EnvEntry
{
var $userid;
var $type;
var $value;
var $timestamp;   

function __construct(\stdClass $record=null)
{
    if ($record!=null)
    {
    $this->userid=$record->userid;
    $this->type=$record->type;
    $this->value=$record->value;
    $this->timestamp=$record->timestamp;
    }
}

}