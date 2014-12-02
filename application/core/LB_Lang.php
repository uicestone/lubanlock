<?php
class LB_Lang extends CI_Lang{
	function __construct() {
		parent::__construct();
	}
	
	/**
	 * Language line
	 *
	 * Fetches a single line of text from the language array
	 *
	 * @param	string	$line		Language line key
	 * @param	bool	$log_errors	Whether to log an error message if the line is not found
	 * @return	string	Translation
	 */
	public function line($line, $log_errors = TRUE)
	{
		$value = ($line == '' OR ! isset($this->language[$line])) ? FALSE : $this->language[$line];

		// Because killer robots like unicorns!
		if ($value === FALSE && $log_errors === TRUE)
		{
			$value = $line;
			log_message('error', 'Could not find the language line "'.$line.'"');
		}

		return $value;
	}
	
	public function raw($word, $log_errors = TRUE)
	{
		if(is_null($this->raw)){
			$this->raw = array_flip($this->language);
		}
		
		$value = ($word == '' OR ! isset($this->raw[$word])) ? FALSE : $this->raw[$word];

		if ($value === FALSE && $log_errors === TRUE)
		{
			$value = $word;
			log_message('error', 'Could not find the language line "'.$word.'"');
		}

		return $value;
	}

}
?>
