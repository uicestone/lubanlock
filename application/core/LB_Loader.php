<?php
class LB_Loader extends CI_Loader{
	
	function __construct(){
		parent::__construct();
	}

	/**
	 * 在view中载入js的简写
	 * @param string $js_file_path js文件的路径文件名（不含"web/"和".js"）
	 */
	function javascript($js_file_path){
		$path = $js_file_path.'.js';
		$hash = filemtime($path);
		return '<script type="text/javascript" src="' . site_url() . $path . '?' . $hash . '"></script>' . "\n";
	}

	/**
	 * 在view中载入外部css链接的简写
	 */
	function stylesheet($css_file_path){
		$path = $css_file_path.'.css';
		$hash = filemtime($path);
		return '<link rel="stylesheet" href="' . site_url() . $path . '?' . $hash . '" type="text/css" />' . "\n";
	}
	
}
?>