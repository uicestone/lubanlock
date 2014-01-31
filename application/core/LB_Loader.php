<?php
class LB_Loader extends CI_Loader{
	
	function __construct(){
		parent::__construct();
	}

	/**
	 * 在view中载入js的简写
	 * @param string $js_file_path js文件的路径文件名（不含"web/js/"和".js"）
	 */
	function javascript($js_file_path){
		$path= $js_file_path.'.js';
		
		if(!file_exists($path)){
			//找不到文件？我们看看这个文件是不是需要根据其他文件合并
			$this->config('minify');
			$sources=$this->config->item('minify_source');
			if(!array_key_exists($path, $sources)){
				//配置文件中没有发现合并列表？放弃吧
				return;
			}else{
				if(true || ENVIRONMENT==='development'){
					//开发环境下，直接根据合并列表分别载入所有文件
					$html='';
					foreach($sources[$path] as $source){
						$hash=filemtime($source);
						$html.='<script type="text/javascript" src="/'.$source.'?'.$hash.'"></script>'."\n";
					}
					return $html;
				}else{
					//测试或生产环境下，合并并保存文件
					$this->driver('minify');
					$CI=&get_instance();
					$combined = $CI->minify->combine_files($sources[$path], 'js', false);
					$CI->minify->save_file($combined, $path);
				}
			}
		}
		
		$hash=filemtime($path);
		return '<script type="text/javascript" src="/'.$path.'?'.$hash.'"></script>'."\n";
	}

	/**
	 * 在view中载入外部css链接的简写
	 */
	function stylesheet($css_file_path){
		$path=$css_file_path.'.css';
		
		if(!file_exists($path)){
			//找不到文件？我们看看这个文件是不是需要根据其他文件合并
			$this->config('minify');
			$sources=$this->config->item('minify_source');

			if(!array_key_exists($path, $sources)){
				//配置文件中没有发现合并列表？放弃吧
				return;
			}else{
				if(true || ENVIRONMENT==='development'){
					//开发环境下，直接根据合并列表分别载入所有文件
					$html='';
					foreach($sources[$path] as $source){
						$hash=filemtime($source);
						$html.="<link rel=\"stylesheet\" href=\"/$source?$hash\" type=\"text/css\" />\n";
					}
					return $html;
				}else{
					//测试或生产环境下，合并并保存文件
					$this->driver('minify');
					$CI=&get_instance();
					$combined = $CI->minify->combine_files($sources[$path], 'css', false);
					$CI->minify->save_file($combined, $path);
				}
			}
		}
		
		$hash=filemtime($path);
		return "<link rel=\"stylesheet\" href=\"/$path?$hash\" type=\"text/css\" />\n";
	}
	
}
?>