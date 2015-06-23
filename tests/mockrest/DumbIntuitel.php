<?php
namespace intuitel;
require_once 'simulator.php';

class DumbIntuitel extends simulator
{

    public function process_learner_update_request($loId,$learnerid)
    {
        $this->selectedLORE=$this->_get_LO_recommendation();
    }
    
public function _get_LO_recommendation()
{
    $course = $this->course;
    $selected = array();
    // emulate a selection of LOs
    $sections = $course->getChildren();
    foreach ($sections as $child)
    {
        $section = $this->intuitelAdaptor->createLO($child);
        $modules = $section->getChildren();
        foreach ($modules as $childLoId)
        {
            $rnd=rand(0, 100);
            if ($rnd>50)
                $selected[]=new loreRecommendation($childLoId,rand(0,100));
        }
    }
    return $selected;
}

public function get_tug_fragment($index=null)
{
$responses['0']= <<<xml
<Tug uId="jmb0001" mId="12345678-1234-abcd-ef12-123456789014">
	<MType>1</MType>
	<MData>Good Morning, dear Learner!</MData>
</Tug>
xml;
    /**
     TUG Mtype=2
    */
    $responses['1']= <<<xml
<Tug uId="jmb0001" mId="12345678-1234-abcd-ef12-123456789014">
	<MType>2</MType>
	<MData>Important. Good Morning, dear Learner!</MData>
</Tug>
xml;
    $responses['2']= <<<xml
<Tug uId="jmb0001" mId="12345678-1234-abcd-ef12-123456789014">
	<MType>2</MType>
	<MData><![CDATA[
<h4>"Welcome to INTUITEL tutorship"</h4>
<h5>Personalized recommendations for an effective learning.</h5>
<div id="lipsum">
<p>
Your teacher has designed more than one path for this subject. Please, let us advise you about the more efficient sequence of learning objects.</p>
</div>
]]></MData>
</Tug>
xml;

    /**
     TUG Mtype=3
    */
    $responses['3']= <<<xml
<Tug uId="jmb0001" mId="12345678-1234-abcd-ef12-123456789014">
	<MType>3</MType>
	<MData>Please, answer this personal question: do you like discuss with your colleages about technical matters?</MData>
</Tug>
xml;
    /**
     TUG Mtype=4
    */
    $responses['4']= <<<xml
<Tug uId="jmb0001" mId="12345678-1234-abcd-ef12-123456789014">
	<MType>4</MType>
	<MData><![CDATA[
		<h4>Environment</h4>
<p>
Please, tell me how noisy is your studying location by now.</p>
		<select multiple="multiple" name="DataName[]">
			<option name=one value=one> Very quiet </option>
			<option name=two value=two> Some non-disturbing sounds (i.e. computer fans) </option>
			<option name=three value=three> Unpleasant. </option>
		</select>
]]></MData>
</Tug>
xml;
    /**
     TUG Mtype=5
    */
    $responses['5']= <<<xml
<Tug uId="jmb0001" mId="12345678-1234-abcd-ef12-123456789014">
	<MType>5</MType>
	<MData><![CDATA[
		<h4>Environment</h4>
<p>
Please, tell me how confortable is your studying desktop.</p>
		<select name="DataName[]">
			<option name=one value=one> Spaceful and tidy </option>
			<option name=two value=two> Small but tidy </option>
			<option name=three value=three> Cluttered and dirty. </option>
		</select>
]]></MData>
</Tug>
xml;
    /**
     TUG Mtype=3
    */
    $responses['100']= <<<xml
<Tug uId="jmb0001" mId="12345678-1234-abcd-ef12-123456789014">
	<MType>100</MType>
	<MData>Please, answer this personal question: Which learning object of this course has been more illustrating so far?</MData>
</Tug>
xml;
    /**
     TUG Mtype=200
    */
    $responses['200']= <<<xml
<Tug uId="jmb0001" mId="12345678-1234-abcd-ef12-123456789014">
	<MType>200</MType>
	<MData><![CDATA[http://sampleswap.org/mp3/artist/ArtisFeeling/ArtisFeeling_Four-320.mp3]]></MData>
</Tug>
xml;
    $responses['300']= <<<xml
<Tug uId="jmb0001" mId="12345678-1234-abcd-ef12-123456789014">
	<MType>300</MType>
	<MData><![CDATA[http://www.youtube.com/watch?v=HQLWHkrOl98]]></MData>
</Tug>
xml;
    if ($index==null)
    {
        $i = rand(0, count($responses)-1);
        $vals=array_values($responses);
        return $vals[$i];
    }
    else
    {
        return $responses[(string)$index];
    }

}


}