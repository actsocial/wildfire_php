<?php

	define( "STR62KEY" , '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' );

	function int10to62($int10) {
		$s62 = '';
		$r = 0;
		while ($int10 != 0) {
			$r = $int10 % 62;
			$s62 = substr(STR62KEY,$r,1)."".$s62;
			$int10 = floor($int10 / 62);
		}
		return $s62;
	}
	
	function  mid2url($mid) {
		if (!is_string($mid)) return false;
		$url = '';
		for ($i = strlen($mid) - 7; $i > -7; $i = $i - 7)
		{
			$offset1 = $i < 0 ? 0 : $i;
			$offset2 = $i + 7;
			$num = substr($mid,$offset1, $offset2-$offset1);
			$num = int10to62($num);
			$url = $num."".$url;
		}
		return $url;
	}
?>