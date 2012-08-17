<?php
/*
	Plugin Name: Search Plugin Contents
	Plugin URI: http://wordpress.org/extend/plugins/search-plugin-contents/
	Description: Allows developers to search the contents of every file in a given WordPress plugin
	Version: 1.1.1
	Author: ITS Alaska
	Author URI: http://ITSCanFixThat.com/
	
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Needed dependencies

if (!function_exists('get_plugins'))
			require_once (ABSPATH."wp-admin/includes/plugin.php");

class search_plugin_contents {
	
	// __construct
	// Adds a hook to the admin menu that executes the function
	// that adds the sidebar instance.
	
	function __construct() {
		
		add_action(
			'admin_menu',				// When the admin_menu hook is caught...
			array(						// Call this method
				$this,
				'register_submenu_page'
			)
		);
		
	}
	
	// register_submenu_page
	// Adds the sidebar instance to the page as a submenu
	// entry underneath "Plugins"
	
	function register_submenu_page() {
		
		add_submenu_page(
			'plugins.php',				// Under "Plugins"
			'Search Plugin Contents',	// Set page title
			'Search Plugin Contents',	// Set menu title
			'edit_plugins',				// Needed permission to access menu
			'plugin-search',			// Menu slug shown in URL
			array(						// Method to call
				$this,
				'starting_point'
			)
		);
		
	}
	
	// starting_point
	// The starting point of the plugin -- determines if the user
	// has submitted a search or selected a plugin
	
	function starting_point() {
		
		$this -> page_start();					// Start the page
		
		if( isset( $_POST['plugin'] ) ) {		// Has the user submitted a form to the site?
			$this -> search();					// If so, forward to the search method
		}
		else {						
			if ( isset( $_GET['plugin'] ) )		// Has the user selected a plugin?
				$this -> form();				// If so, forward to the form method
			
			else								// If none of these have happened,
				$this -> select();				// forward to the select method
		}
		
		$this -> page_end();					// End the page
		
	}
	
	// error
	// Handles error reporting by this plugin, used in situations
	// where output is expected to be malformed.
	
	function error($error,$kill=true) {
		
		$error_css = "
		<style>
		
			.its-error {
				width: auto;
				background-color: #FAD9E6;
				border: 2px solid #BA004B;
				padding: 1em;
				margin: 2em 2em 0 0;
			}
			
			.its-error h1 {
				font-size: larger;
				color: #BA004B;
				margin: 0;
				padding: 0;
			}
			
			.its-error blockquote {
				font-family: monospace;
			}
		
		</style>
		";
		
		$error_prefix = "
		<div class='its-error'>
		<h1>Error:</h1>
		<p>
			An error has occurred. Please make sure your page was properly formatted and not tampered. If you continue to see this error, notify your website administrator about the issue.
		</p>
		<p>
			The error that was returned was:
		</p>
		<blockquote>
		";
		
		$error_suffix = "
		</blockquote>
		</div>
		";
		
		echo $error_css; // Print the error CSS just defined
		echo $error_prefix.$error.$error_suffix; // Print the error
		
		if ($kill) die(); // Kill the page (default), or keep it alive if the function specifies.
		
	}
	
	// page_start
	// Handles the starting of the page, including
	// styling and all that good stuff
	
	function page_start() {
		
		// Page stylesheet
		echo "<link rel='StyleSheet' type='text/css' href='". plugins_url( 'style.css', __FILE__ ) ."'>";
		
		// Start page wrap
		echo "<div id='plugin-search-wrap'>";
		
	}
	
	function page_end() {
		
		// End page wrap
		echo "</div>";
		
		// Branding
		echo "<hr>";
		echo "<div id='plugin-search-branding'>";
		echo "Powered by <a href='http://itscanfixthat.com/'><img src='". plugins_url( 'ITS.png', __FILE__ ) ."' /></a>";
		echo "</div>";
		
	}
	
	// find_all_files
	// Recursively scans a directory and returns
	// an array of all files
	// CREDIT FOR THIS FUNCTION goes to:
	// kodlee@kodleeshare.net
	
	function find_all_files($dir) {
		$root = scandir($dir);
		foreach($root as $value) {
			if($value === '.' || $value === '..') {continue;}
			if(is_file("$dir/$value")) {$result[]="$dir/$value";continue;}
			foreach($this -> find_all_files("$dir/$value") as $value){
				$result[]=$value;
			}
		}
		return $result;
	} 
	
	// select
	// Allows the user to select from a list of plugins
	
	function select() {
		
		echo "<h1>Select Plugin to Search</h1>";
		echo "
			<p>
				Select a plugin from the table below to search that plugin for a specified string or pattern
			</p>
		";
		
		$plugins = get_plugins();
		
		echo "<table id='plugin-search-select-table' cellpadding='4'>";
		echo "
			<tr>
				<th>Plugin Name</td>
				<th>Description</td>
			</tr>
		";
		
		foreach( $plugins as $file => $plugin ) {
			
			$file = explode( '/',$file );
			
			$file = $file[0];
			
			?>
				<tr>
					<td><a href="<?php echo $_SERVER['PHP_SELF'];?>?page=plugin-search&plugin=<?php echo $file;?>"><?php echo $plugin['Name'];?></a></td>
					<td><?php echo $plugin['Description'];?></td>
				</tr>
			<?php
			
		}
		
		echo "</table>";
		
	}
	
	// form
	// When the user has selected a plugin to search,
	// Present him with a form to input a string or pattern to match
	
	function form() {
		
        // Validation using str_replace
        // Protects against LFI
        
		$plugin = str_replace('..','',$_GET['plugin']);
		
		// Validation using RegEx
		// Protects completely against XSS
		
		if ( !preg_match( '/[a-zA-Z0-9\-\_\.]*/', $plugin ) )
			$this -> error("Possible malformed plugin name!");
		
		// Validation using folder checking
		// Prevents opening nonexistant folders
		
		if (!file_exists(ABSPATH.'wp-content/plugins/'.$plugin))
			$this -> error("Plugin specified doesn't exist!");
		
		echo "<h1>Search Plugin</h1>";
		
		?>
			<form action="<?php echo $_SERVER['PHP_SELF'];?>?page=plugin-search" method="POST" >
			<input type="hidden" name="plugin" value="<?php echo $plugin;?>" />
				<table>
					<tr>
						<td>Plugin:</td>
						<td><?php echo $plugin;?></td>
					</tr>
					<tr>
						<td>Search input:</td>
						<td><input type="text" name="pattern" size=120 placeholder="e.g. function generate_css(" /></td>
					</tr>
					<tr>
						<td>Search method:</td>
						<td>
							<select name="method">
								<option value="string">String</option>
								<option value="regex">Regular Expression</option>
							</select>
						</td>
					</tr>
					<tr>
						<td></td>
						<td><input type="submit" value="Search Plugin" /></td>
					</tr>
				</table>
			</form>
		<?php
		
	}
	
	// search
	// Search the selected plugin for the given pattern and return results
	
	function search() {
		
		$plugin = $_POST['plugin'];
		$method = $_POST['method'];
		
		// Validation against method using string comparison
		
		if ($method !== "string" && $method !== "regex")
			$this -> error("Illegal method type!");
		
		// Validation against plugin using RegEx
		// Protects completely against XSS
		
		if ( !preg_match( '/[a-zA-Z0-9\-\_\.]*/', $plugin ) )
			$this -> error("Possible malformed plugin name!");
		
		// Validation against plugin using folder checking
		// Protects against LFI
		
		if (!file_exists(ABSPATH.'wp-content/plugins/'.$plugin))
			$this -> error("Plugin specified doesn't exist!");
		
		$file_list = $this -> find_all_files(ABSPATH.'wp-content/plugins/'.$plugin);
		
		$results = array();
		
		foreach($file_list as $file) {
			$file_contents = file_get_contents($file);
			
			// String handling
			if ($method == "string") {
				$pos = strpos($file_contents,$_POST['pattern']);
				if ($pos) {
					array_push($results,str_replace(ABSPATH.'wp-content/plugins/','',$file));
				}
			}
			
			// RegEx handling
			if ($method == "regex") {
				if (preg_match('/'.$_POST['pattern'].'/',$file)) {
					array_push($results,str_replace(ABSPATH.'wp-content/plugins/','',$file));
				}
			}
		}
		
		echo "<h1>Search Results</h1>";
		$plural = "";
		if (count($results) !== 1) $plural = "s";
		echo "Found <b>".count($results)."</b> result".$plural." for \"<b>".htmlspecialchars($_POST['pattern'])."</b>\" in <b>".$plugin."</b>:<br>";
		
		if (count($results) > 0) {
			
			echo "<table>
				<tr><th>File</th></tr>";
				
			foreach($results as $result) {
				?>
					<tr>
						<td><a href="<?php echo admin_url('plugin-editor.php?file='.$result);?>"><?php echo $result;?></a></td>
					</tr>
				<?php
			}
			
			echo "</table>";
			
		}
		
	}
	
	
}

new search_plugin_contents();

?>