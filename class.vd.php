<?php
// Released under the GNU General Public License (GPL).
// This software piece is distributed "as is", with no warranties.

/**
 * Variable dumping and inspection class.
 * 
 * Dumps a variable in a clear, nice format. Shows variable type,
 * object class, resource type; and uses different colors for each one.
 * Simply call vd::dump (mixed $var[, string $varname]); and that's all.
 * 
 * Don't use arrays or objects with circular references (a references b
 * and b refences a), as it would cause an infinite loop.
 * If you want to explore $GLOBALS, try with array_keys($GLOBALS).
 * 
 * @author José Luis Salinas
 */

class vd {
	function dump ($var, $name = '') {
		if(defined('SILENT') && SILENT == true)
			return;
		$style = "padding: 10px; overflow: auto; border: 1px solid black;";
		echo	"<div style=\"margin-top:15px\"><b>" . ($name != '' ? "$name : " : '') . "</b>" .
			"<pre style='$style'>" .
			vd::_get_info_var ($var, $name) .
			"</pre></div>";
	}
	
	function get ($var, $name = '') {
		return ($name != '' ? "$name : " : '') . vd::_get_info_var ($var, $name);
	}
	
	function _get_info_var ($var, $name = '', $indent = 0) {
		static $methods = array ();
		$indent > 0 or $methods = array ();
		
		$indent_chars = '  ';
		$spc = $indent > 0 ? str_repeat ($indent_chars, $indent ) : '';
		
		$out = '';
		if (is_array ($var)) {
			$out .= "<span style='color:red;'><b>Array</b></span> " . count ($var) . " (\n";
			foreach (array_keys ($var) as $key) {
				$out .= "$spc  [<span style='color:red;'>$key</span>] => ";
				if (($indent == 0) && ($name != '') && (! is_int ($key)) && ($name == $key)) {
					$out .= "LOOP\n";
				} else {
					$out .= vd::_get_info_var ($var[$key], '', $indent + 1);
				}
			}
			$out .= "$spc)";
		} else if (is_object ($var)) {
			$class = get_class ($var);
			$out .= "<span style='color:purple;'><b>Object</b></span> $class";
			$parent = get_parent_class ($var);
			$out .= $parent != '' ? " <span style='color:purple;'>extends</span> $parent" : '';
			$out .= " (\n";
			$arr = get_object_vars ($var);
			while (list($prop, $val) = each($arr)) {
				$out .= "$spc  " . "-><span style='color:purple;'>$prop</span> = ";
				$out .= vd::_get_info_var ($val, $name != '' ? $prop : '', $indent + 1);
			}
			$arr = get_class_methods ($var);
			$out .= "$spc  " . "$class methods: " . count ($arr) . " ";
			if (in_array ($class, $methods)) {
				$out .= "[already listed]\n";
			} else {
				$out .= "(\n";
				$methods[] = $class;
				while (list($prop, $val) = each($arr)) {
					if ($val != $class) {
						$out .= $indent_chars . "$spc  " . "->$val();\n";
					} else {
						$out .= $indent_chars . "$spc  " . "->$val(); [<b>constructor</b>]\n";
					}
				}
				$out .= "$spc  " . ")\n";
			}
			$out .= "$spc)";
		} else if (is_resource ($var)) {
			$out .= "<span style='color:steelblue;'><b>Resource</b></span> [" . get_resource_type($var) . "] ( <span style='color:steelblue;'>" . $var . "</span> )";
		} else if (is_int ($var)) {
			$out .= "<span style='color:blue;'><b>Integer</b></span> ( <span style='color:blue;'>" . $var . "</span> )";
		} else if (is_float ($var)) {
			$out .= "<span style='color:blue;'><b>Float</b></span> ( <span style='color:blue;'>" . $var . "</span> )";
		} else if (is_numeric ($var)) {
			$out .= "<span style='color:blue;'><b>Numeric string</b></span> " . strlen($var) . " ( \"<span style='color:green;'>" . $var . "</span>\" )";
		} else if (is_string ($var)) {
			$out .= '<span style="color:green;"><b>String</b></span> ' . strlen($var) . ' ( "<span style="color:green;">' . htmlentities($var) . '</span>" )';
		} else if (is_bool ($var)) {
			$out .= "<span style='color:darkorange;'><b>Boolean</b></span> ( <span style='color:darkorange;'>" . ($var ? 'True' : 'False') . "</span> )";
		} else if (! isset ($var)) {
			$out .= "<b>Null</b>";
		} else {
			$out .= "<b>Other</b> ( " . $var . " )";
		}
		
		return $out . "\n";
	}
}
?>
