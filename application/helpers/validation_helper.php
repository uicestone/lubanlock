<?php

function verify_idcard($idcard){
	if(!is_string($idcard) || strlen($idcard)!=18){
		return false;
	}
	$sum=$idcard[0]*7+$idcard[1]*9+$idcard[2]*10+$idcard[3]*5+$idcard[4]*8+$idcard[5]*4+$idcard[6]*2+$idcard[7]+$idcard[8]*6+$idcard[9]*3+$idcard[10]*7+$idcard[11]*9+$idcard[12]*10+$idcard[13]*5+$idcard[14]*8+$idcard[15]*4+$idcard[16]*2;
	$mod = $sum % 11;
	$vericode_dic=array(1, 0, 'x', 9, 8, 7, 6, 5, 4, 3, 2);
	if($vericode_dic[$mod] == strtolower($idcard[17])){
		return true;
	}
}

function is_mobile_number($number){
	if(is_numeric($number) && $number%1==0 && substr($number,0,1)=='1' && strlen($number)==11){
		return true;
	}else{
		return false;
	}
}
