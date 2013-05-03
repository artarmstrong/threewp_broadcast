<?php

namespace plainview\wordpress\tabs;

/**
	@brief		Handles creation of tabs in the Wordpress admin panel.
	@author		Edward Plainview	edward@plainview.se
	@since		20130503
	@version	20130503
**/
class tabs
{
	/**
		@brief		\\plainview\\wordpress\\base object that created these tabs.
		@var		$base
	**/
	public $base;
	
	/**
		@brief		_GET variable to use. The default is the actual _GET.
		@var		$get
	**/
	public $get;
	
	/**
		@brief		Which key in the _GET variable contains the ID of the current tab.
		@var		$get_key
	**/
	public $get_key = 'tab';
	
	/**
		@brief		The ID of the default tab, if none is selected in the _GET.
		@details	If no default tab is set, the first added tab is assumed to be the default.
		@var		$default_tab
	**/
	public $default_tab = '';
	
	/**
		@brief		Display the selected tab?
		@var		$display_tab
	**/
	public $display_tab = true;
	
	/**
		@brief		Display the name / heading of the selected tab?
		@var		$display_tab_name
	**/
	public $display_tab_name = true;
	
	/**
		@brief		The default prefix of the displayed tab name.
		@details	The tab inherits this value upon creation.
		@var		$tab_prefix
	**/
	public $tab_prefix = '<h2>';
	
	/**
		@brief		The default suffix of the displayed tab name.
		@details	The tab inherits this value upon creation.
		@var		$tab_suffix
	**/
	public $tab_suffix = '</h2>';
	
	/**
		@brief		Array of \\plainview\\wordpress\\tabs\\tab objects.
		@var		$tabs
	**/
	public $tabs = array();
	
	/**
		@brief		Array of _GET keys to preserve when creating tab links.
		@var		$valid_get_keys
	**/
	public $valid_get_keys = array( 'page' );
	
	public function __construct( $base )
	{
		$this->base = $base;
	}
	
	public function __tostring()
	{
		if ( $this->get === null )
			$this->get = $_GET;
		$get = $this->get;					// Conv
		$get_key = $this->get_key;			// Conv
		
		// No tabs? Do nothing.
		if ( count( $this->tabs ) < 1 )
			return '';
		
		// Check that the default exists.
		if ( ! is_object( $this->tab( $this->default_tab ) ) )
			$this->default_tab = key( $this->tabs );
		
		// Select the default tab if none is selected.
		if ( ! isset( $get[ $get_key ] ) )
			$get[ $get_key ] = $this->default_tab;
		$selected = $get[ $get_key ];
		
		$r = '';
		
		if ( count( $this->tabs ) > 1 )
		{
			// Step 1: display all the tabs
			$r .= '<ul class="subsubsub">';
			$original_link = $_SERVER['REQUEST_URI'];
	
			foreach($get as $key => $value)
				if ( ! in_array( $key, $this->valid_get_keys ) )
					$original_link = remove_query_arg( $key, $original_link );
			
			$counter = 1;
			foreach( $this->tabs as $tab_id => $tab )
			{
				// Make the link.
				// If we're already on that tab, just return the current url.
				if ( $get[ $get_key ] == $tab_id )
					$link = remove_query_arg( time() );
				else
				{
					if ( $tab_id == $this->default_tab )
						$link = remove_query_arg( $get_key, $original_link );
					else
						$link = add_query_arg( $get_key, $tab_id, $original_link );
				}
				
				$text = $tab->name;
				
				if ( $tab->count != '' )
					$text .= sprintf( ' <span class="count">%s</span>', $tab->count );
				
				$separator = ( $counter < count( $this->tabs ) ? '&nbsp;|&nbsp;' : '' );
				$current = ( $tab_id == $selected ? ' class="current"' : '' );
				
				$title = '';
				if ( $tab->title != '' )
					$title = sprintf( ' title="%s"', $tab->title );
				
				$r .= sprintf( '<li><a%s%s href="%s">%s</a>%s</li>',
					$current,
					$title,
					$link,
					$text,
					$separator
				);
				$counter++;
			}
			$r .= '</ul>';
		}
		
		// Step 2: maybe display the tab itself
		if ( $this->display_tab )
		{
			$tab = $this->tab( $selected );
			ob_start();
			echo '<div class="wrap">';
			if ( $this->display_tab_name )
			{
				$name = ( $tab->heading != '' ? $tab->heading : $tab->name );
				echo $tab->prefix . $name . $tab->suffix;
			}
			
			echo $r;
			echo '<div style="clear: both"></div>';
	
			if ( is_array( $tab->callback ) )
			{
				$c = $tab->callback[ 0 ];
				$f = $tab->callback[ 1 ];
				$c->$f();
			}
			else
			{
				$c = $tab->callback;
				$c();
			}
			echo '</div>';
			
			ob_end_flush();
			return '';
		}
		else
			return $r;
	}
	
	/**
		@brief		Sets the current tab.
		@param		string		$id		ID of tab to make the default.
		@return		tabs				Object itself.
	**/
	public function default_tab( $id )
	{
		$this->default_tab = $id;
		return $this;
	}
	
	/**
		@brief		Creates a new tab / retrieves an existing tab.
		@param		string		$id		ID of tab to create / retrieve.
		@return		tab					Tab object.
	**/
	public function tab( $id )
	{
		if ( $id == '' )
			return false;
		if ( isset( $this->tabs[ $id ] ) )
			return $this->tabs[ $id ];
		$tab = new tab( $this );
		$tab->id = $id;
		$tab->callback_this( $id );		// Usually the tab's callback is the same as the ID.
		$this->tabs[ $id ] = $tab;
		return $this->tabs[ $id ];
	}
}

/**
	@brief		Actual tab that tabs contains.
**/
class tab
{
	/**
		@brief		Tab callback function.
		@details	An array of (class, function_name) or just a function name.
					The default callback is the ID of the tab.
		@see		tabs::tab
		@var		$callback
	**/
	public $callback;
	
	/**
		@brief		Optional count to be displayed after the tab name. Default is no count.
		@var		$count
	**/
	public $count = '';
	
	/**
		@brief		Optional heading to display as the page heading instead of the tab name.
		@var		$heading
	**/
	public $heading;
	
	/**
		@brief		The ID of the tab.
		@var		$id
	**/
	public $id;
	
	/**
		@brief		Displayed name of tab.
		@var		$name
	**/
	public $name;
	
	/**
		@brief		Prefix that is displayed before displaying the tab name.
		@var		$prefix
	**/
	public $prefix;
	
	/**
		@brief		Suffix that is displayed after displaying the tab name.
		@var		$suffix
	**/
	public $suffix;
	
	/**
		@brief		The \\plainview\\wordpress\\tabs\\tabs object this tab belongs to.
		@var		$tabs
	**/
	public $tabs;
	
	/**
		@brief		The HTML title associated with the tab name.
		@var		$title
	**/
	public $title;
	
	public function __construct( $tabs )
	{
		$this->tabs = $tabs;
		$this->prefix = $tabs->tab_prefix;
		$this->suffix = $tabs->tab_suffix;
		return $this;
	}
	
	/**
		@brief		Sets the callback for this tab.
		@detail		Either a class + function combination or just the function.
		@param		mixed		$callback		A class or function name.
		@param		string		$function		If $callback is a class, this is the method within the class to be called.
		@return		object						This tab.
	**/
	public function callback( $callback, $function = '' )
	{
		if ( $function != '' )
			$callback = array( $callback, $function );
		$this->callback = $callback;
		return $this;
	}
	
	/**
		@brief		Convenience function to call a method of the base object.
		@param		string		$method		Name of method to call.
		@return		object					This tab.
	**/
	public function callback_this( $method )
	{
		return $this->callback( $this->tabs->base, $method );
	}
	
	/**
		@brief		Set the page heading for this tab.
		@details	Optionally display this heading instead of the tab name as the page heading.
		@param		string		$heading		The page heading to set.
		@return		object						This tab.
	**/
	public function heading( $heading )
	{
		$this->heading = $heading;
		return $this;
	}
	
	/**
		@brief		Translate and set the page heading for this tab.
		@param		Almost the same as heading(), except the string is translated first.
		@return		object					This tab.
		@see		heading()
	**/
	public function heading_( $heading )
	{
		return $this->heading( call_user_func_array( array( $this->tabs->base, '_' ), func_get_args() ) );
	}
	
	/**
		@brief		Set the name of this tab.
		@details	The name is displays in the tab list and as the page heading, if no specific page heading is set.
		@param		string		$name		The new name of the tab.
		@return		object					This tab.
	**/
	public function name( $name )
	{
		$this->name = $name;
		return $this;
	}
	
	/**
		@brief		Translate and set the name of this tab.
		@param		string		$name		String to translate and set as the name.
		@return		object					This tab.
		@see		name()
	**/
	public function name_()
	{
		return $this->name( call_user_func_array( array( $this->tabs->base, '_' ), func_get_args() ) );
	}
	
	/**
		@brief		Set the HTML title of the page name in the tab list.
		@param		string		$title		Title to set.
		@return		object					This tab.
	**/
	public function title( $title )
	{
		$this->title = $title;
		return $this;
	}
	
	/**
		@brief		Translate and set the HTML title of the page name.
		@param		string		$title		Title to translate and set.
		@return		object					This tab.
		@see		title()
	**/
	public function title_( $title )
	{
		return $this->title( call_user_func_array( array( $this->tabs->base, '_' ), func_get_args() ) );
	}
	
}

