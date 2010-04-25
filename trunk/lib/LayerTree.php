<?php

// -----------------------------------------------------------------------------
// Utility class
// Turn a tree into a series of layer, for outputing a HTML table header
// 
// This is used in the admin_results page
// -----------------------------------------------------------------------------

class LayerItem {
	public $value,$type,$colspan,$rowspan;
	
	const LEAF   = 0;
	const PARENT = 1;
	const MERGED = 2;
	
	function __construct($value,$type) {
		$this->value   = $value;
		$this->type    = $type;
		$this->colspan = 1;
		$this->rowspan = 1;
	}
}

class LayerTree {
	private $array;
	private $width;
	private $leaf_count; // # of leafs on each depth, of currently open parents and the root
	
	function __construct() {
		$this->height = 0;
		$this->width  = 1;
		$this->array = array();
		$this->leaf_count = array(0);
	}
	
	// The current depth, i.e. the layer in which new leafs will be placed
	function depth() {
		return count($this->leaf_count) - 1;
	}
	
	// Add a leaf here
	function add_leaf($value) {
		$depth = $this->depth();
		$it = new LayerItem($value,LayerItem::LEAF);
		$this->put_value($it, $depth);
		$this->leaf_count[$depth]++;
	}
	
	// Enter a parent
	function parent_begin($value) {
		$depth = $this->depth();
		$it = new LayerItem($value,LayerItem::PARENT);
		$this->put_value($it, $depth);
		$this->leaf_count []= 0; // this level has no children
	}
	
	// Exit a parent, add parent if it is not empty.
	// optionally merge parent with a single child
	function parent_end($value, $merge_single = true, $pop_empty = true) {
		$leaf_count = array_pop($this->leaf_count);
		$depth = $this->depth();
		// remove or merge?
		if ($leaf_count == 0) {
			if ($pop_empty) {
				$this->unput_value($depth);
				return 0;
			} else {
				$leaf_count = 1;
			}
		} else if ($merge_single && $leaf_count == 1 && $this->array[$depth+1][$this->width-1]->type == LayerItem::LEAF) {
			$this->array[$depth+1][$this->width-1] = '^';
			$this->array[$depth  ][$this->width-1]->type = LayerItem::MERGED;
		}
		// set leaf count as colspan
		$this->array[$depth][$this->width-$leaf_count]->colspan = $leaf_count;
		// add leafs to parent level
		$this->leaf_count[$depth] += $leaf_count;
		return $leaf_count;
	}
	
	// Get the layers as an array of arrays
	//  these array contain LayerItem objects and '<','^' strings
	//  use  if (!is_object($it)) continue;
	function get() {
		$this->delete_superflous_rows();
		$this->update_rowspans();
		return $this->array;
	}
	
	private function delete_superflous_rows() {
		while (count($this->array) > 0) {
			// is superflous?
			$superflous = true;
			$last = $this->array[count($this->array)-1];
			foreach ($last as $v) {
				if ($v !== '^') {
					$superflous = false;
					break;
				}
			}
			// remove?
			if ($superflous) {
				array_pop($this->array);
			} else {
				break;
			}
		}
	}
	private function update_rowspans() {
		$height = count($this->array);
		foreach($this->array as $i=>$layer) {
			foreach($layer as $j=>$it) {
				if (!is_object($it)) continue;
				$rowspan = $i+1 < $height && $this->array[$i+1][$j] === '^' ? $height-$i : 1;
				$this->array[$i][$j]->rowspan = $rowspan;
			}
		}
	}
	
	// put a value at $depth in the last column, adds a new column if there is already something there.
	private function put_value($value, $depth) {
		if ($depth >= count($this->array)) {
			$this->add_row();
		} else if ($this->array[$depth][$this->width-1] !== '^') {
			$this->add_col($depth);
		}
		$this->array[$depth][$this->width-1] = $value;
		
	}
	private function add_row() {
		$new = array();
		for ($j = 0 ; $j < $this->width ; ++$j) $new []= '^';
		$this->array []= $new;
	}
	private function add_col($depth) {
		for ($i = 0 ; $i < $depth ; ++$i) {
			$this->array[$i] []= '<';
		}
		for ($i = $depth ; $i < count($this->array) ; ++$i) {
			$this->array[$i] []= '^';
		}
		$this->width++;
	}
	
	// undo put_value()
	// except for the add_row, which is undone by delete_superflous_rows()
	private function unput_value($depth) {
		if ($depth == 0 ||$this->array[$depth-1][$this->width-1] === '<') {
			$this->del_col();
		} else {
			$this->array[$depth][$this->width-1] = '^';
		}
	}
	private function del_col() {
		for ($i = 0 ; $i < count($this->array) ; ++$i) {
			array_pop($this->array[$i]);
		}
		$this->width--;
	}
	
	// Debug dump as HTML table
	function dump() {
		echo "<table border=1>";
		foreach ($this->array as $layer) {
			echo "<tr>";
			foreach ($layer as $it) {
				echo "<td>";
				if ($it === '^') {
					echo "^";
				} else if ($it === '<') {
					echo "&lt;";
				} else {
					if (is_object($it->value)) {
						echo $it->value->title();
					} else {
						print_r($it->value);
					}
					echo ' ',$it->type;
					echo ' ',$it->colspan;
				}
				echo "</td>";
			}
			echo "</tr>";
		}
		echo "</table>";
	}
}
