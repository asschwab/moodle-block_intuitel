<?php
namespace intuitel;

class loreRecommendation
{
    var $loId;
    /*
     * @var integer
     */
    var $score;
    
    function __construct($loId,$score)
    {
        $this->loId=$loId;
        $this->score=$score;
    }
}