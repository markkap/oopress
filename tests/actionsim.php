<?php

global $actionsim;

function new_action_sim() {
    global $actionsim;
    $actionsim = new actionsim();
}

function do_action($tag, $arg = '') {
    global $actionsim;
    call_user_func_array(array($actionsim,'do_action'),func_get_args());
}

function apply_filters($tag, $arg = '') {
    global $actionsim;
    return call_user_func_array(array($actionsim,'apply_filters'),func_get_args());
}

function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
    global $actionsim;
    $actionsim->add_filter($tag, $function_to_add, $priority, $accepted_args);
}

function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
    global $actionsim;
    $actionsim->add_filter($tag, $function_to_add, $priority, $accepted_args);
}

function current_filter() {
	global $actionsim;
	return end( $actionsim->wp_current_filter );
}

class actionsim {
    public $wp_filter;
    public $wp_actions;
    public $merged_filters;
    public $wp_current_filter;

    public function do_action($tag,$arg='') {

        if ( ! isset($this->wp_actions[$tag]) )
            $this->wp_actions[$tag] = 1;
        else
            ++$this->wp_actions[$tag];

        // Do 'all' actions first
        if ( isset($this->wp_filter['all']) ) {
            $this->wp_current_filter[] = $tag;
            $all_args = func_get_args();
            $this->_wp_call_all_hook($all_args);
        }

        if ( !isset($this->wp_filter[$tag]) ) {
            if ( isset($this->wp_filter['all']) )
                array_pop($this->wp_current_filter);
            return;
        }

        if ( !isset($this->wp_filter['all']) )
            $this->wp_current_filter[] = $tag;

        $args = array();
        if ( is_array($arg) && 1 == count($arg) && isset($arg[0]) && is_object($arg[0]) ) // array(&$this)
            $args[] =& $arg[0];
        else
            $args[] = $arg;
        for ( $a = 2, $num = func_num_args(); $a < $num; $a++ )
            $args[] = func_get_arg($a);

        // Sort
        if ( !isset( $merged_filters[ $tag ] ) ) {
            ksort($this->wp_filter[$tag]);
            $mthis->erged_filters[ $tag ] = true;
        }

        reset( $this->wp_filter[ $tag ] );

        do {
            foreach ( (array) current($this->wp_filter[$tag]) as $the_ )
                if ( !is_null($the_['function']) )
                    call_user_func_array($the_['function'], array_slice($args, 0, (int) $the_['accepted_args']));

        } while ( next($this->wp_filter[$tag]) !== false );

        array_pop($this->wp_current_filter);
    }

    public function apply_filters( $tag, $value ) {

    	$args = array();

    	// Do 'all' actions first.
    	if ( isset($this->wp_filter['all']) ) {
    		$this->wp_current_filter[] = $tag;
    		$args = func_get_args();
    		$this->_wp_call_all_hook($args);
    	}

    	if ( !isset($this->wp_filter[$tag]) ) {
    		if ( isset($this->wp_filter['all']) )
    			array_pop($this->wp_current_filter);
    		return $value;
    	}

    	if ( !isset($this->wp_filter['all']) )
    		$this->wp_current_filter[] = $tag;

    	// Sort.
    	if ( !isset( $this->merged_filters[ $tag ] ) ) {
    		ksort($this->wp_filter[$tag]);
    		$this->merged_filters[ $tag ] = true;
    	}

    	reset( $this->wp_filter[ $tag ] );

    	if ( empty($args) )
    		$args = func_get_args();

    	do {
    		foreach( (array) current($this->wp_filter[$tag]) as $the_ )
    			if ( !is_null($the_['function']) ){
    				$args[1] = $value;
    				$value = call_user_func_array($the_['function'], array_slice($args, 1, (int) $the_['accepted_args']));
    			}

    	} while ( next($this->wp_filter[$tag]) !== false );

    	array_pop( $this->wp_current_filter );

    	return $value;
    }

    public function _wp_call_all_hook($args) {

    	reset( $this->wp_filter['all'] );
    	do {
    		foreach( (array) current($this->wp_filter['all']) as $the_ )
    			if ( !is_null($the_['function']) )
    				call_user_func_array($the_['function'], $args);

    	} while ( next($this->wp_filter['all']) !== false );
    }

    public function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {

    	$idx = $this->_wp_filter_build_unique_id($tag, $function_to_add, $priority);
    	$this->wp_filter[$tag][$priority][$idx] = array('function' => $function_to_add, 'accepted_args' => $accepted_args);
    	unset( $this->merged_filters[ $tag ] );
    	return true;
    }

    function _wp_filter_build_unique_id($tag, $function, $priority) {
    	static $filter_id_count = 0;

    	if ( is_string($function) )
    		return $function;

    	if ( is_object($function) ) {
    		// Closures are currently implemented as objects
    		$function = array( $function, '' );
    	} else {
    		$function = (array) $function;
    	}

    	if (is_object($function[0]) ) {
    		// Object Class Calling
    		if ( function_exists('spl_object_hash') ) {
    			return spl_object_hash($function[0]) . $function[1];
    		} else {
    			$obj_idx = get_class($function[0]).$function[1];
    			if ( !isset($function[0]->wp_filter_id) ) {
    				if ( false === $priority )
    					return false;
    				$obj_idx .= isset($this->wp_filter[$tag][$priority]) ? count((array)$this->wp_filter[$tag][$priority]) : $filter_id_count;
    				$function[0]->wp_filter_id = $filter_id_count;
    				++$filter_id_count;
    			} else {
    				$obj_idx .= $function[0]->wp_filter_id;
    			}

    			return $obj_idx;
    		}
    	} elseif ( is_string( $function[0] ) ) {
    		// Static Calling
    		return $function[0] . '::' . $function[1];
    	}
    }
}
