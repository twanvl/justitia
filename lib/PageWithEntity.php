<?php

require_once('../lib/bootstrap.inc');

// -----------------------------------------------------------------------------
// A page that has entity navigation
// -----------------------------------------------------------------------------

abstract class PageWithEntity extends Template {
	protected $entity;
	
	function __construct() {
		// find active entity
		$user = Authentication::require_user();
		$this->entity = Entity::get(@$_SERVER['PATH_INFO'], !$user->is_admin);
	}
	
	function title() {
		return $this->entity->title();
	}
	
	// ---------------------------------------------------------------------
	// Navigation
	// ---------------------------------------------------------------------
	
	// which script to use for items from the navigation tree
	function nav_script($entity) {
		return 'index.php';
	}
	
	function get_nav_children($e) {
		$result = array();
		foreach ($e->children() as $e) {
			if (!$e->visible()) continue;
			
			$class = '';
			if ($e == $this->entity) {
				$class .= 'current ';
				if ($e->has_visible_children()) {
					$class .= 'ancestor ';
				}
			} else if ($e->is_ancestor_of($this->entity)) {
				$class .= 'ancestor ';
			}
			if ($e->submitable()) {
				$subm = Authentication::current_user()->status_of_last_submission_to($e);
				$class .= Status::to_css_class($subm) . ' ';
			}
			if (!$e->active()) {
				$class .= 'inactive ';
			}
			
			$result []= array(
				'title' => $e->title(),
				'url'   => $this->nav_script($e) . $e->path(),
				'class' => $class
			);
		}
		return $result;
	}
	function get_nav() {
		$result = array();
		foreach ($this->entity->ancestors() as $e) {
			$on_this_level = $this->get_nav_children($e);
			if ($on_this_level) $result []= $on_this_level;
		}
		return $result;
	}
	
}
