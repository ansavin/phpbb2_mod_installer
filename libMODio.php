<?php
include("class.vd.php");
/*****************************************
*            libModIo.php
******************************************
* Library wrapper for MOD input/output
* operations
*****************************************/

class MODio {
	var $posStart;
	var $posEnd;
	var $posInStart;
	var $posInEnd;
	
	var $data;
	var $file;
	
	var $flagOpen;
	var $flagFind;
	var $flagInline;

	var $incrementing_string;
	
	var $errno;
	
	function MODio() {
		$this->posStart = 0;
		$this->posEnd   = 0;
		$this->posInStart = 0;
		$this->posInEnd   = 0;
		
		$this->data = "";
		$this->file = "";
		
		$this->flagOpen = false;
		$this->flagFind = false;
		$this->flagInline = false;

		$this->incrementing_string = "";
		
		$this-> errno = 0;
	}
	
	function load( $fname ) {
		$fd = fopen( $fname, "r" );
		
		if(!$fd) {
			$this->errno = -1;
			return false;
		}
		
		$this->posStart   = 0;
		$this->posEnd     = 0;
		$this->posInStart = 0;
		$this->posInEnd   = 0;

		$this->flagOpen = true;
		$this->file = $fname;
		
		$this->data = fread( $fd, filesize( $fname ) );
		fclose($fd);
		
		return strlen($this->data);
	}
	
	function find( $str ) {
		if( !$this->flagOpen ) {
			$this->errno = -3;
			return false;
		}
		vd::dump($str, "Searching for");
		$arr = explode("\n",trim($str));
		
		while ( $pos = strpos( $this->data, trim($arr[0]), $this->posEnd ) )
		{
			$subline = substr($this->data, $pos);
			$subline = explode("\n",$subline);
			//vd::dump(substr($this->data, $pos, strlen($arr[0])+100),"Possibly found at position $pos");
			
			for($i = 0, $len = 0; $i < sizeof($arr); $i++)
			{
				//vd::dump($arr[$i],"\$arr[$i]");
				//vd::dump($subline[$i],"\$subline[$i]");
				$len += strlen($subline[$i])+1;
				
				if( trim($arr[$i]) == "" && trim($subline[$i]) != "" )
				{
					$this->posEnd = $pos + $len - (strlen($subline[$i])+1);
					continue 2;
				}
				else if( trim($subline[$i]) == trim($arr[$i]) )
				{
					continue;
				}
				else if( strstr($subline[$i], trim($arr[$i]) ) )
				{
					continue;
				}
				else
				{
					$this->posEnd = $pos + $len - (strlen($subline[$i])+1);
					continue 2;
				}
			}
			break;
		}
		
		if ( $pos === false ) {
			vd::dump($str,"Failed request");
			$this->errno = -2;
			return false;
		}
		
		$this->posStart = $pos;
		//vd::dump($this->data[$pos + $len],"Last symbol");
		for($this->posEnd = $pos + $len; $this->posEnd < strlen($this->data); $this->posEnd++)
		{
			if($this->data[$this->posEnd-1] == "\n")
			{
				break;
			}
		}
		
		for(; $this->posStart > 0; $this->posStart--)
		{
			if($this->data[$this->posStart-1] == "\n")
			{
				break;
			}
		}
		
		$this->posInEnd = $this->posInStart = $this->posStart;
		
 		vd::dump(substr($this->data,$this->posStart,$this->posEnd-$this->posStart),"Found at position {$this->posStart}");
		
		$this->flagFind = true;
		
		return $pos;
	}
	
	function replace( $str ) {
		if( !$this->flagOpen || !$this->flagFind ) {
			$this->errno = -3;
			return false;
		}
		
		$this->data = substr_replace( $this->data, $str, $this->posStart, $this->posEnd-$this->posStart );
		
		$this->posEnd = $this->posStart + strlen( $str );
		$this->posInStart = $this->posInEnd = $this->posStart;
		$this->flagFind = false;
		$this->flagInline = false;
		
		return $this->posStart;
	}
	
	function afterAdd( $str ) {
		if( !$this->flagOpen || !$this->flagFind ) {
			$this->errno = -3;
			return false;
		}
		
		$this->data = substr_replace( $this->data, $str, $this->posEnd, 0 );
		
		return $this->posEnd;
	}
	
	function beforeAdd( $str ) {
		if( !$this->flagOpen || !$this->flagFind ) {
			$this->errno = -3;
			return false;
		}
		$this->data = substr_replace( $this->data, $str, $this->posStart, 0 );
		
		$this->posStart += strlen( $str );
		$this->posEnd += strlen( $str );
		$this->posInStart += strlen( $str );
		$this->posInEnd += strlen( $str );
		
		return $this->posStart-strlen( $str );
	}
	
	function inlineFind( $str ) {
		if( !$this->flagOpen || !$this->flagFind ) {
			$this->errno = -3;
			return false;
		}

		//need this for INCREMENT instruction
		if ( preg_match('/\{\%:\d{1}\}/', $str) ) {
			$str = preg_replace('/\{\%:\d{1}\}$/', '', $str, 1);
			$regex_used = true;
			$sub = substr( $this->data, $this->posStart, $this->posEnd - $this->posStart);
			$pos = strpos( $sub, $str, $this->posInEnd-$this->posStart );
			if( $pos === false ) {
				$this->errno = -2;
				return false;
			}
			$this->posInStart = $pos + $this->posStart;
			$this->posInEnd = $this->posInStart + strlen( $str );
			vd::dump(substr($this->data, $this->posInStart,$this->posInEnd-$this->posInStart),"Inline found");
			//reading digit to increment
			$this->incrementing_string = substr($this->data, $this->posInEnd, 1);
			$this->flagInline = true;
		
		return $this->posInStart;
		}
		
		$sub = substr( $this->data, $this->posStart, $this->posEnd - $this->posStart);
		vd::dump($str, "Inline searching for");
		$pos = strpos( $sub, $str, $this->posInEnd-$this->posStart );
		if( $pos === false ) {
			$this->errno = -2;
			return false;
		}
		
		$this->posInStart = $pos + $this->posStart;
		$this->posInEnd = $this->posInStart + strlen( $str );
		vd::dump(substr($this->data, $this->posInStart,$this->posInEnd-$this->posInStart),"Inline found");
		$this->flagInline = true;
		
		return $this->posInStart;
	}

	// func to apply INCREMENT instruction
	function increment( $str ) {
		if ( !$this->flagInline ) {
			$this->errno = -3;
			return false;	
		}

		if ( $this->incrementing_string === '' ) {
			$this->errno = -3;
			return false;
		}

		vd::dump($this->incrementing_string, "Incrementing string");

		preg_match('/[+-]\d+/', $str, $m);

		$num = (int)$m[0];

		$this->incrementing_string = (string)((int)$this->incrementing_string + $num);

		vd::dump($this->incrementing_string, "Incremented string");

		$this->data = substr_replace( $this->data, $this->incrementing_string, $this->posInEnd, 1 );

		return true;
	}
	
	function inlineReplace( $str ) {
		if( !$this->flagOpen || !$this->flagFind || !$this->flagInline ) {
			$this->errno = -3;
			return false;
		}
		
		$this->data = substr_replace( $this->data, $str, $this->posInStart, $this->posInEnd - $this->posInStart );
		
		$this->posEnd -= ($this->posInEnd - $this->posInStart) - strlen( $str );
		$this->posInEnd = $this->posInStart + strlen( $str );
		
		$this->flagInline = false;
		
		return $this->posInStart;
	}
	
	function inlineBefore( $str ) {
		if( !$this->flagOpen || !$this->flagFind || !$this->flagInline ) {
			$this->errno = -3;
			return false;
		}
		
		$this->data = substr_replace( $this->data, $str, $this->posInStart, 0 );
		
		$this->posInStart += strlen( $str );
		$this->posInEnd += strlen( $str );
		$this->posEnd += strlen( $str );
		
		return $this->posInStart - strlen( $str );
	}
	
	function inlineAfter( $str ) {
		if( !$this->flagOpen || !$this->flagFind || !$this->flagInline ) {
			$this->errno = -3;
			return false;
		}
		
		$this->data = substr_replace( $this->data, $str, $this->posInEnd, 0 );
		
		$this->posEnd += strlen( $str );
		
		return $this->posEnd;
	}
	
	function saveas( $fname ) {
		if( !$this->flagOpen ) {
			$this->errno = -3;
			return false;
		}
		
		$fd = fopen( $fname, "w" );
		
		if( !$fd ){
			$this->errno = -4;
			return false;
		}
		
		$n = fwrite( $fd, $this->data );
		fclose( $fd );
		
		return $n;
	}
	
	function save() {
		if( !$this->flagOpen ) {
			$this->errno = -3;
			return false;
		}
		
		$fd = fopen( $this->file , "w" );
		
		if( !$fd ){
			$this->errno = -4;
			return false;
		}
		
		$n = fwrite( $fd, $this->data );
		fclose( $fd );
		
		return $n;
	}
	
	function unload() {
		$this->posStart = 0;
		$this->posEnd   = 0;
		$this->posInStart = 0;
		$this->posInEnd   = 0;
		
		$this->data = "";
		$this->file = "";
		
		$this->flagOpen = false;
		$this->flagFind = false;
		$this->flagInline = false;
		
		$this-> errno = 0;
	}
}
?>