<?php

/**
 * 返回元数据中$key最新的一个值
 */
if(!function_exists('get_meta')){
	function get_meta(array $object, $key){
		
		if(!array_key_exists('meta', $object) || !array_key_exists($key, $object['meta'])){
			return false;
		}
		
		return array_pop($object['meta'][$key]);
	}
}

/**
 * 返回$relation关系最新的一个关联对象（信息）
 */
if(!function_exists('get_relative')){
	function get_relative(array $object, $relation, $field = 'name'){
		if(!array_key_exists('relative', $object) || !array_key_exists($relation, $object['relative'])){
			return false;
		}
		
		$relative = array_pop($object['relative'][$relation]);
		
		if(!$relative){
			return false;
		}
		
		if(is_null($field)){
			return $relative;
		}
		
		return $relative[$field];
	}
}

/**
 * 返回最新的一个状态，或指定状态名的最新一个时间
 */
if(!function_exists('get_status')){
	function get_status(array $object, $name = null){
		if(!array_key_exists('status', $object)){
			return false;
		}
		
		if($object['status'] && array_is_numerical_index($object['status'])){
			$status = array_pop($object['status']);
			if(!is_null($name) && array_key_exists($name, $status)){
				return $status[$name];
			}
			else{
				return $status;
			}
		}
		
		if(is_null($name)){
			$status_names = array_keys($object['status']);
			return array_pop($status_names);
		}
		
		if(!array_key_exists($name, $object['status'])){
			return false;
		}
		
		return $object['status'][$name];
		
	}
}

if(!function_exists('get_tag')){
	function get_tag($object, $taxonomy){
		if(!array_key_exists('tag', $object) || !array_key_exists($taxonomy, $object['tag'])){
			return false;
		}
		
		return implode(', ', $object['tag'][$taxonomy]);
	}
}