<?php
include("libMODio.php");
include("sql_parse.php");

class MODparser {
	var $script;
	var $basedir;
	
	var $io;
	
	var $commands;
	var $files;
	var $sql;
	
	var $errno;
	var $errinfo;
	
	var $flagLoaded;
	var	$flagParsed;
	var $flagProcessed;
	var $flagSql;
	
	function MODparser() {
		$this->script = array();
		$this->basedir = "";
		$this->io = new MODio();
		$this->commands = array();
		$this->files = array();
		$this->sql = array();
		$this->flagLoaded = false;
		$this->flagParsed = false;
		$this->flagProcessed = false;
		$this->flagSql = false;
		$this->errinfo = "";
	}
	
	/*****************************************
	*  MODparser::load( $fname )
	*   > $fname - file name of MOD script
	******************************************
	*  This function loads MOD script into
	*  the memory
	*****************************************/
	function load( $fname ) {
		$this->errno = 0;
		$this->errinfo = "";
		
		//Loading lines
		$f = file( $fname );
		
		if( !$f ) {
			$this->errno = -1;
			return false;
		}
		
		//Setting up required variables and flags
		$this->basedir = dirname( $fname );
		$this->script = $f;
		$this->flagLoaded = true;
		
		//Return number of lines read.
		return count( $f );
	}
	
	/*****************************************
	*  MODparser::parse(  )
	******************************************
	*  This function parses MOD script and
	*  generates array of commands
	*****************************************/
	function parse() {
		$this->errno = 0;
		$this->errinfo = "";
		
		if( !$this->flagLoaded ) {
			$this->errno = -3;
			return false;
		}
		$this->flagParsed = false;
		for( $i = 0; $i < count($this->script); $i++ ) {
			$this->errinfo = "Line: $i";
			if( ($this->script[$i][0] == "#") &&
			    (!strstr($this->script[$i],"--[")) &&
			    (!strstr($this->script[$i],"]--")) ) {
				
				continue;
			}
			else if( ($this->script[$i][0] == "#") &&
			    (strstr($this->script[$i],"--[")) &&
			    (strstr($this->script[$i],"]--")) ) {
				
				$arg = array();
				
				$j = $i + 1;
				
				while ( ($j < count( $this->script )) &&
				        ($this->script[$j][0] == "#") )
					$j++;
				while ( ($j < count( $this->script )) &&
				        (trim($this->script[$j]) =="") )
					$j++;
				
				for( ; ($j < count( $this->script )) && ($this->script[$j][0] != "#"); $j++) {
					$arg[] = $this->script[$j];
				}
				
				for( $j = count($arg) - 1; ($j >= 0) && (trim($arg[$j]) == ""); $j-- )
					unset( $arg[$j] );
				
				$arg = implode( "", $arg );
				$command = $this->extractCommand( $this->script[$i] );
								
				if( $command == "SQL" )
				{
					$this->sql = array_merge($this->sql, split_sql_file(trim($arg),';'));
					$this->flagSql = true;
				}
				
				if( (trim($arg) == "") && ($command != "CLOSE") ) {
					$this->errno = -4;
					return false;
				}
				
				if( $command == "UNKNOWN" ) {
					$this->errno = -5;
					return false;
				}
				
				$this->commands[] = array( 'cmd' => $command, 'arg' => $arg );
			}
		}
		$this->flagParsed = true;
		$this->errinfo = "";
		return true;
	}
	
	/*****************************************
	*  MODparser::extractCommand( $str )
	*   > $str - line with command from
	*            MOD script
	******************************************
	*  This function extracts command from
	*  line of MOD script
	*****************************************/
	function extractCommand( $str ) {
		$this->errno = 0;
		$this->errinfo = "";
		
		if( strstr( $str, "OPEN" ) ) {
			$command = "OPEN";
		}
		else if( strstr( $str, "IN-LINE FIND" ) ) {
			$command = "INFIND";
		}
		else if( strstr( $str, "IN-LINE, FIND" ) ) {
			$command = "INFIND";
		}
		else if( strstr( $str, "FIND" ) ) {
			$command = "FIND";
		}
		else if( strstr( $str, "IN-LINE REPLACE" ) ) {
			$command = "INREPLACE";
		}
		else if( strstr( $str, "IN-LINE, REPLACE" ) ) {
			$command = "INREPLACE";
		}
		else if( strstr( $str, "REPLACE" ) ) {
			$command = "REPLACE";
		}
		else if( strstr( $str, "IN-LINE AFTER, ADD" ) ) {
			$command = "INAFTERADD";
		}
		else if( strstr( $str, "AFTER, ADD" ) ) {
			$command = "AFTERADD";
		}
		else if( strstr( $str, "IN-LINE BEFORE, ADD" ) ) {
			$command = "INBEFOREADD";
		}
		else if( strstr( $str, "BEFORE, ADD" ) ) {
			$command = "BEFOREADD";
		}
		else if( strstr( $str, "SAVE/CLOSE" ) ) {
			$command = "CLOSE";
		}
		else if( strstr( $str, "COPY" ) ) {
			$command = "COPY";
		}
		else if( strstr( $str, "SQL" ) ) {
			$command = "SQL";
		}
		else if( strstr( $str, "INCREMENT" ) ) {
			$command = "INCREMENT";
		}
		else {
			$command = "UNKNOWN";
		}
		
		return $command;
	}
	
	/*****************************************
	*  MODparser::mkdir( $dest, $path_to )
	*   > $dest - path from application to
	*             file is beeing saved
	******************************************
	*  This function this function creates
	*  folder structure similar to file is
	*  usually placed in.
	*****************************************/
	
	function mkdir( $dest, $path_to ) {
		$this->errno = 0;
		$this->errinfo = "";
		if( !is_writeable($path_to) || !is_dir($path_to) ){
			$this->errno = -1;
			return false;
		}
		$dest = explode("/",$dest);
		for( $i = 1; $i <= count($dest); $i++ ){
			$dir = array_slice( $dest, 0, $i);
			$dir = implode("/",$dir);
			if(!is_dir($path_to."/".$dir))
				mkdir($path_to."/".$dir);
		}
		return true;
	}
	/*****************************************
	*  MODparser::process( $path_from, $path_to )
	*   > $path_from - derictory with
	*                  unmodified files
	*   > $path_to - derictory where modified
	*                files will be placed in
	******************************************
	*  This function processes files
	*****************************************/
	function process( $path_from, $path_to ) {
		$this->errno = 0;
		$this->errinfo = "";
		
		if(!$this->flagParsed) {
			$this->errno = -3;
			return false;
		}
		
		$current = "";
		for( $i = 0; $i < count($this->commands); $i++ ){
			$this->errinfo = "File: ".$current.
			                 "\nCommand: ".$this->commands[$i]['cmd'].
			                 "\nArgument: \"".$this->commands[$i]['arg']."\"";
			switch( $this->commands[$i]['cmd'] ) {
				case 'OPEN':
					if( $this->io->flagOpen ) {
						$this->mkdir( dirname( $current ), $path_to );
						$this->io->saveas($path_to."/".$current);
						$this->io->unload();
						$current = "";
					}
					if(!$this->io->load($path_from."/".trim($this->commands[$i]['arg']))){
						$this->errno = $this->io->errno;
						return false;
					}
					$this->files[] = $current = trim($this->commands[$i]['arg']);
					break;
				case 'FIND':
					if( !$this->io->find( $this->commands[$i]['arg'] ) ){
						$this->errno = $this->io->errno;
						return false;
					}
					break;
				case 'AFTERADD':
					if( !$this->io->afterAdd( $this->commands[$i]['arg'] ) ){
						$this->errno = $this->io->errno;
						return false;
					}
					break;
				case 'BEFOREADD':
					if( !$this->io->beforeAdd( $this->commands[$i]['arg'] ) ){
						$this->errno = $this->io->errno;
						return false;
					}
					break;
				case 'REPLACE':
					if( !$this->io->replace( $this->commands[$i]['arg'] ) ){
						$this->errno = $this->io->errno;
						return false;
					}
					break;
				case 'INFIND':
					if( !$this->io->inlineFind( trim($this->commands[$i]['arg']) ) ){
						$this->errno = $this->io->errno;
						return false;
					}
					break;
				case 'INAFTERADD':
					if( !$this->io->inlineAfter( trim($this->commands[$i]['arg']) ) ){
						$this->errno = $this->io->errno;
						return false;
					}
					break;
				case 'INBEFOREADD':
					if( !$this->io->inlineBefore( trim($this->commands[$i]['arg']) ) ){
						$this->errno = $this->io->errno;
						return false;
					}
					break;
				case 'INREPLACE':
					if( !$this->io->inlineReplace( trim($this->commands[$i]['arg']) ) ){
						$this->errno = $this->io->errno;
						return false;
					}
					break;
				case 'CLOSE':
					if( $this->io->flagOpen ) {
						$this->mkdir( dirname( $current ), $path_to );
						$this->io->saveas($path_to."/".$current);
						$this->io->unload();
						$current = "";
					}
					break;
				case 'COPY':
					$lines = explode( "\n", $this->commands[$i]['arg'] );
					for( $j = 0; $j < count($lines); $j++ ) {
						$line = trim( $lines[$j] );
						if( empty( $line ) )
							continue;
						if(substr($line,0,5) == "copy ") {
							$line = substr($line,5);
						}
						$paths = explode( " to ", $line );
						if( count($paths) != 2 ) {
							$paths = explode( "\tto\t", $line );
						}
						if( count($paths) != 2 ) {
							$this->errno = -4;
							return false;
						}
						$paths[0] = trim($paths[0]);
						$paths[1] = trim($paths[1]);
						
						if( !is_file( $this->basedir."/".$paths[0] ) ){
							$this->errinfo .= "\nCould not find required file {$this->basedir}/{$paths[0]} in installer package.";
							$this->errno = -4;
							return false;
						}
						
						$this->mkdir( dirname( $paths[1] ), $path_to );
						$res = copy( $this->basedir."/".trim($paths[0]), $path_to."/".trim($paths[1]) );
						$this->files[] = trim($paths[1]);
					}
					break;
				case 'SQL':
					break;
				case 'INCREMENT':
					if( !$this->io->increment( trim($this->commands[$i]['arg']) ) ){
						$this->errno = $this->io->errno;
						return false;
					}
					break;
			}
		}
		$this->errinfo="";
		return true;
	}
	
	/*****************************************
	*  MODparser::install( $path_from, $path_to )
	*   > $path_from - derictory with
	*                  modified files
	*   > $path_to - destination derictory
	******************************************
	*  This function installes modified files
	*  to application
	*****************************************/
	function install( $path_from, $path_to ) {
		$this->errno = 0;
		$this->errinfo = "";
		
		foreach( $this->files as $file ) {
			$this->errinfo = "File: $file";
			if( (is_file($path_to."/".$file) &&
			     !is_writeable($path_to."/".$file)) ||
			    !is_readable($path_from."/".$file) ) {
				$this->errno = -6;
				return false;
			}
		}
		foreach( $this->files as $file ) {
			$this->mkdir( dirname( $file ), $path_to );
			rename( $path_from."/".$file, $path_to."/".$file );
		}
		foreach( $this->files as $file ) {
			if( is_dir( $path_from."/".dirname( $file ) ) && (realpath( $path_from."/".dirname( $file ) ) != realpath( $path_from )) ) {
				@rmdir( $path_from."/".dirname( $file ) );
			}
		}
		$this->errinfo = "";
		return true;
	}
	
	/*****************************************
	*  MODparser::getError()
	******************************************
	*  This function returnes last error
	*  code
	*****************************************/
	
	function getError() {
		return $this->errno;
	}
	/*****************************************
	*  MODparser::getErrorInfo()
	******************************************
	*  This function returnes some debugging
	*  information about last error
	*****************************************/
	
	function getErrorInfo() {
		return $this->errinfo;
	}
	
	/*****************************************
	*  MODparser::isSQL()
	******************************************
	*  This function returnes true if there
	*  are any SQL requests in MOD
	*****************************************/
	
	function isSQL() {
		return $this->flagSQL;
	}

	/*****************************************
	*  MODparser::runSQL( $host, $user, $pass, $db)
	*   >
	******************************************
	*  This function executes any SQL statements
	*  provided with MOD
	*****************************************/
	
	function runSQL($host, $user, $pass, $db) {
		$this->errno = 0;
		$this->errinfo = "";
		if ( !$this->flagSql )
			return true;
		$h = mysql_connect( $host, $user, $pass );
		if( !$h ) {
			$this->errno = -7;
			$this->errinfo = "MySQL error(".mysql_errno($h)."): ".mysql_error( $h ).". Could not connect to database";
		}
		if( !mysql_select_db( $db, $h ) ) {
			$this->errno = -7;
			$this->errinfo = "MySQL error(".mysql_errno($h)."): ".mysql_error( $h ).". Could not connect to database";
		}
		
		foreach ( $this->sql as $sql ){
			if( !mysql_query( $sql, $h ) ) {
				$this->errinfo .= "MySQL error(".mysql_errno($h)."): ".mysql_error( $h )."; Query:\n".trim($sql)."\n\n";
				$this->errno = -7;
			}
		}
		mysql_close($h);
		return true;
	}
}
?>