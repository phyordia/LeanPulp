<?php
class Position {
	const None = 0;
	const Top = 1;
	const Right = 2;
	const Bottom = 3;
	const Left = 4;
	const Center = 5;	
	
}


class Options
{
    // property declaration
    public $showResources = Position::None;
	public $includeMathJax = false;
	public $CSSClass = '';
    // method declaration
    // public function displayVar() {
    //    echo $this->var;
    //}
}




?>