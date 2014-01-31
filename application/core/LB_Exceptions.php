<?php
class LB_Exceptions extends CI_Exceptions{
	function __construct() {
		parent::__construct();
	}
	
	function show_error($heading, $message, $template = 'error_general', $status_code = 500)
	{
		!is_null($status_code) && set_status_header($status_code);

		$message = '<p>'.implode('</p><p>', ( ! is_array($message)) ? array($message) : $message).'</p>';

		if (ob_get_level() > $this->ob_level + 1)
		{
			ob_end_flush();
		}
		ob_start();
		include(APPPATH.'errors/'.$template.'.php');
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}
	
}
