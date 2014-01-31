<?php
class LB_Lang extends CI_Lang{
	function __construct() {
		parent::__construct();
	}
	
	/**
	 * @access	public
	 * @param	string	$line	the language line
	 * @return	string
	 */
	function line($line = '')
	{
		$value = ($line == '' OR ! isset($this->language[$line])) ? FALSE : $this->language[$line];

		// Because killer robots like unicorns!
		if ($value === FALSE)
		{
			$value = $line;
			log_message('error', 'Could not find the language line "'.$line.'"');
		}

		return $value;
	}
	
}
?>
