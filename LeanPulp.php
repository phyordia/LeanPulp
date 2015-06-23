<?php


function __autoload($class_name) {
	include $class_name . '.php';
}


class LeanPulp {

	// Public


	public function __construct($a_filename) {
		$this->m_opts = new Options();
		$this->m_fileName = $a_filename;
		$this->m_pulp = $this->getPulp();
	}


	public function getDocumentRoot(){
		return $m_documentRoot;
	}

	public function Options() {
		return $this->m_opts;
	}

	public function Squeeze() {

		//$this->m_pulp = $this->getPulp();
		print $this->getMathJaxHeader();

		
		if($this->m_opts->showResources == Position::Top) {
			$this->m_pulp = $this->getResources().$this->m_pulp;
		} else if ($this->m_opts->showResources == Position::Bottom) {
			$this->m_pulp = $this->m_pulp.$this->getResources();
		}

		print $this->m_pulp;

	}


	// Private
	private $m_pulp;
	private $m_fileName;
	public $m_opts;



	private function getResources(){
		return "<a href=\"".trim($this->m_fileName)."\">source</a>";
	}

	private function getMathJaxHeader() {
		if ($this->m_opts->includeMathJax){

			return "<script type=\"text/x-mathjax-config\">
 MathJax.Hub.Config({
 tex2jax: {inlineMath: [['$','$'],['$$','$$']]}
 });
 </script>
 <script type=\"text/javascript\"   src=\"http://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS-MML_HTMLorMML\"></script>";	
		} else {
			return "";
		}
	}

	private function genRandomString() {
		$length = 9;
		$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		$string ='';
		for ($p = 0; $p < $length; $p++) {
			$string .= $characters[mt_rand(0, strlen($characters))];
		}
		return $string;
	}

	private function ReplaceToken($tag){
		if (sscanf($tag, '<img src="%s',$dump)) return '"></img>';
		if ($tag == '<a name="') return '"></a>';
		if ($tag == ' ') return '';
		return preg_replace("/^(.{1})/", "</", $tag);
	}


	private function getPulp(){

		$source = file_get_contents($this->m_fileName);
		$documentRoot = dirname($this->m_fileName).'/';


		// Remove equations from text and replace them by a key and glue back everything
		$eqns = array();

		$podd = 1;

		$pieces = explode("$$", $source);
		foreach ($pieces as $p) {

			$id = $this->genRandomString();
			$tmp = $pieces[$podd];
			if (trim($tmp)!=""){
				$pieces[$podd]=$id;
				$eqns[$id] = $tmp;
			}
			$podd += 2;
		}
		$source = implode('', $pieces);

		$podd = 1;
		$pieces = explode("$", $source);
		foreach ($pieces as $p) {

			$id = $this->genRandomString().'!';
			$tmp = $pieces[$podd];
			if (trim($tmp)!=""){
				$pieces[$podd]=$id;
				$eqns[$id] = $tmp;
			}
			$podd += 2;
		}

		$source = implode('', $pieces);


		// Section Tokens
		$patSections = array();

		$patSections[7] = '/%.*/';
		$patSections[6] = '/\\\mbox{/';
		$patSections[5] = '/\\\includegraphics.*{/';
		$patSections[4] = '/\\\label{/';
		$patSections[3] = '/\\\caption{/';
		$patSections[2] = '/\\\section{/';
		$patSections[1] = '/\\\subsection{/';
		$patSections[0] = '/\\\subsubsection{/';

		$repSections = array();

		$repSections[7] = '<!-- Something was commented here, thus left out -->';
		$repSections[6] = ' ';
		$repSections[5] = '<img src="'.$documentRoot;
		$repSections[4] = '<a name="';
		$repSections[3] = '<div class="figcaption">';
		$repSections[2] = '<h1>';
		$repSections[1] = '<h2>';
		$repSections[0] = '<h3>';

		$source =  preg_replace($patSections, $repSections, $source);

		// BeginEnd enviroments (dont require a fancy ending tag)
		$patBegEnd = array();
		$patBegEnd[0] = '/\\\begin{itemize}/';
		$patBegEnd[1] = '/\\\end{itemize}/';
		$patBegEnd[2] = '/\\\begin{figure\*}/';
		$patBegEnd[3] = '/\\\end{figure\*}/';
		$patBegEnd[4] = '/\\n\\n/';
		$patBegEnd[5] = '/\\n{1}/';

		$repBegEnd = array();
		$repBegEnd[0]  = '<ul>';
		$repBegEnd[1]  = '</ul>';
		$repBegEnd[2]  = '';
		$repBegEnd[3]  = '';
		$repBegEnd[4]  = '<br/>';
		$repBegEnd[5]  = ' ';

		$source =  preg_replace($patBegEnd, $repBegEnd, $source);


		// Style enviroments
		$patStyle = array();
		$patStyle[0] = '/{\\\bf/';
		$patStyle[1] = '/\\\item/';
		$patStyle[2] = '/\\\textit{/';
		$patStyle[3] = '/{\\\it/';
		$patStyle[4] = '/\\\underline{/';
		$patStyle[5] = '/\\\centerline{/';
		$repStyle = array();
		$repStyle[0]  = '<strong>';
		$repStyle[1]  = '<li>';
		$repStyle[2] = '<em>';
		$repStyle[3] = '<em>';
		$repStyle[4] = '<u>';
		$repStyle[5] = '<span style="align: center;">';
		$source =  preg_replace($patStyle, $repStyle, $source);


		// Tokenize the text
		$tok = strtok($source, "}");
		$output = '';

		while ($tok !== false) {
			$i = '';
			foreach ($repSections as $r) {
				if (strstr($tok,$r)) {
					$i .= $this->ReplaceToken($r);
				}
			}

			foreach ($repStyle as $r) {
				if (strstr($tok,$r)) {
					$i .= $this->ReplaceToken($r);
				}
			}
			$output .= $tok.$i;
			$tok = strtok("}");
		}

		$source = $output;

		// Put Equations back in place

		foreach (array_keys($eqns) as $key) {



			if (strpos($key,'!')){
				$fix='$';
			} else {
				$fix='$$';
			}

			$source =  preg_replace('/'.$key.'/', $fix.$eqns[$key].$fix, $source);
		}

		return $source;
	}
} // End class def
?>