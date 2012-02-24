<?php
/*
Plugin Name: Wordpress Tabs Extended
Plugin URI: http://www.dulabs.com
Description: add tabs and slides to your posts or pages
Version: 1.0.0
Author: Abdul Ibad
Author URI: http://dulabs.com

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
	
*/

// Show tags html on title, 
// REPLACE = < will be replace with &lt;
// STRIP = Strip html tags
// NOFILTER = Don't filter (Not Recommend) 

define('TABS_EXTENDED','mortabs');
define('SHOW_TITLE_HTML','REPLACE');

class Tabs_Extended{
	
	function init(){
		
		$postdisable = $this->getOption("postdisable");
		
		if(empty($postdisable)){
			$this->activation();
		}
		
		add_action('wp_print_scripts', array($this,"addScripts"));
		add_action("wp_print_styles", array($this,"addStyles"));
		add_action('admin_menu', array($this,"admin_menu"));
		/* Use the save_post action to do something with the data entered */
		add_action('save_post', array($this,'savepost'));
		add_filter('the_content', array($this,"formatting"));
		add_filter('the_excerpt', array($this,"formatting"));
		add_filter('widget_text', array($this,"formatting"));
	}
	
	function activation(){
		$options['postdisable'] = "off";
		$options['style'] = "default.css";
		add_option("tabs_extended",$options);
	}
	
	function filter_title( $text ){
		switch(SHOW_TITLE_HTML){
			case 'REPLACE':
				$text = str_replace('<','&lt;',$text);
			break;
			case 'STRIP':
				$text = strip_tags($text);
			break;
			case 'NOFILTER':
				$text = $text;
			break;
		}

		return $text;
	}
	
	function strip_punctuation( $text ){
		 $text = strip_tags($text);
		 $text = ereg_replace("[^A-Za-z0-9]", "", $text );
		 return preg_replace("/[^A-Za-z0-9\s\s+\.\:\-\/%+\(\)\*\&\$\#\!\@\"\';\n\t\r\~]/","",$text);
	}
	
	
	function getSettings( $name ){
		switch( strtoupper( $name ) ){
			case "PLUGIN_URL":
				$dir = self::getSettings("PLUGIN_PATH");
				$home = get_option('siteurl');
				$start = strpos($dir,'/wp-content/');
				$end = strlen($dir);
				$plugin_url = $home.substr($dir,$start,$end);
				return $plugin_url;
			break;
			case "PLUGIN_PATH":
				$dir = str_replace('\\','/',dirname(__FILE__));
				return $dir;
			break;
		}
	}
	
	function getOption( $name ){
		$options = get_option('tabs_extended');
		return $options[ $name ];
	}
	
	function getStyles(){
		
		$dir = self::getSettings("PLUGIN_PATH")."/style";
		
		$opendir = opendir($dir);
		$styles = array();

		while($file = readdir($opendir)){

			if($file != "." && $file != ".."){
				$ext = end(explode(".",$file));
				if(strtoupper($ext) == "CSS"){
					$styles[] = $file;
				}
			}

		}

		closedir($opendir);

		return $styles;
	}
	
	function custom_box(){
		global $post;
		
		echo '<input type="hidden" name="enabletabs_noncename" id="enabletabs_noncename" value="' . 
		    wp_create_nonce( plugin_basename(__FILE__) ) . '" />'; 

			$enabletabs = get_post_meta($post->ID,'enabletabs',true);
			
			if($enabletabs=="on"){
				$checked = ' checked="checked" ';
			}else{
				$checked = '';
			}
			
		?>
		<p><input type="checkbox" id="enabletabs" name="enabletabs" value="on"<?php echo $checked;?>/>&nbsp;<label for="enabletabs"><strong><?php _e('Enable Tabs on this post',TABS_EXTENDED);?></strong></label></p>
		<?php
	}
	
	function old_custom_box(){
		 echo '<div class="dbx-b-ox-wrapper">' . "\n";
		  echo '<fieldset id="myplugin_fieldsetid" class="dbx-box">' . "\n";
		  echo '<div class="dbx-h-andle-wrapper"><h3 class="dbx-handle">' . 
		        __( 'Enable Tabs Extended' ) . "</h3></div>";   

		  echo '<div class="dbx-c-ontent-wrapper"><div class="dbx-content">';

		  // output editing form

		  self::custom_box();

		  // end wrapper

		  echo "</div></div></fieldset></div>\n";
	}
	
	function formatting( $content ){
		
		global $post;
				
	// if post empty (check from the title) then return false
	if(empty($post->post_title)){
		return $content;
	}
	

	return $content;
		
	}
	
	function tabsformat( $content ){
		

		$b=1;
	   if (preg_match_all("/{tab=.+?}{tab=.+?}|{tab=.+?}|{\/tabs}/", $content, $matches, PREG_PATTERN_ORDER) > 0) { 	
	    foreach ($matches[0] as $match) {	
	      if($b==1 && $match!="{/tabs}") {
	    	$tabs[] = 1;
	    	$b=2;
	      }
	      elseif($match=="{/tabs}"){
	      	$tabs[]=3;
	      	$b=1;
	      }
	      elseif(preg_match("/{tab=.+?}{tab=.+?}/", $match)){
	      	$tabs[]=2;
	      	$tabs[]=1;
	      	$b=2;
	      }
	      else {
	      	$tabs[]=2;
	      }
	    }
	   }
	   @reset($tabs);
	   $tabscount = 0;
	  if (preg_match_all("/{tab=.+?}|{\/tabs}/", $content, $matches, PREG_PATTERN_ORDER) > 0) {

	    foreach ($matches[0] as $match) {

	      if($tabs[$tabscount]==1) {

	      	$match = str_replace("{tab=", "", $match);
	        $match = str_replace("}", "", $match);

			$tabid = preg_replace('/[^a-z0-9]/', '', strtolower($match)); 
			$tabid = str_replace(' ','-',strtolower($tabid));

	        $content = str_replace( "{tab=".$match."}", "
			<dl class=\"tabset\" id=\"".$tabid."\">
			<dt class=\"active\">".$match."</dt><dd class=\"active tab-content\">", $content );        
	        $tabid++;

	      } elseif($tabs[$tabscount]==2) {
	      	$match = str_replace("{tab=", "", $match);
	        $match = str_replace("}", "", $match);

			$tabid = preg_replace('/[^a-z0-9]/', '', strtolower($match)); 
			$tabid = str_replace(' ','-',strtolower($tabid));

	      	$content = str_replace( "{tab=".$match."}", "<div class=\"clearfix\">&nbsp;</div></dd><dt id=\"".$tabid."\">".$match."</dt><dd class=\"tab-content\">", $content );
	      } elseif($tabs[$tabscount]==3) {
	      	$content = str_replace( "{/tabs}", "<div class=\"clearfix\">&nbsp;</div></dd></dl><div class=\"clearfix\">&nbsp;</div>", $content );
	      }
	      $tabscount++;
	    }   

	  }
	
		return $content;
		
	}
	
		
	function savepost(){
		global $post;
		
		$post_id = $post->ID;
		// verify this came from the our screen and with proper authorization,
		  // because save_post can be triggered at other times

		  if ( !wp_verify_nonce( $_POST['enabletabs_noncename'], plugin_basename(__FILE__) )) {
		    return $post_id;
		  }

		  // verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
		  // to do anything
		  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
		    return $post_id;


		  // Check permissions
		  if ( 'page' == $_POST['post_type'] ) {
		    if ( !current_user_can( 'edit_page', $post_id ) )
		      return $post_id;
		  } else {
		    if ( !current_user_can( 'edit_post', $post_id ) )
		      return $post_id;
		  }

		  // OK, we're authenticated: we need to find and save the data
		$data =  ($_POST['enabletabs'] == "on") ? "on" : "off";
		
		update_post_meta($post_id, 'enabletabs', $data);

		  

		   return $data;
	}
	
	function optionsAction(){
		
		$options = $newoptions = get_option('tabs_extended');
		
		if(isset($_POST['submit'])){
			$newoptions['postdisable'] = $_POST['postdisable'];
			$newoptions['style'] = $_POST['style'];
			$newoptions['backlink'] = $_POST['backlink'];
			
			if($options != $newoptions){
				update_option('tabs_extended',$newoptions);
				echo '<div class="updated fade" id="message"><p><strong>'.__('Save Options',TABS_EXTENDED).'</strong></p></div>';
			}
		}
		
	}
	
	function optionsView(){
		
			self::optionsAction();
			
			$postdisable = (self::getOption("postdisable") =="on") ? " checked=\"checked\" ":" ";
			$backlink = (self::getOption("backlink") =="on") ? " checked=\"checked\" ":" ";
			$defaultstyle = self::getOption("style");

			$styles = self::getStyles();
		?>
			<div class="wrap">
			<?php
			if(self::getOption("backlink") != "on"){
			?>
			<div class="updated">Please give backlink to support this plugin development or <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=support%40mortgageloaninsight%2ecom&lc=US&item_name=mortgageloaninsight%2ecom&item_number=Tabs%20Extended%20Donation&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted" target="blank">donate</a>
			</div>
			<?php
			}
			?>
			<h2>TABS EXTENDED <?php _e('Settings',TABS_EXTENDED);?></h2>
			<form action="" method="post">
			<table class="widefat fixed">
		<tr valign="top">
		<th scope="row"><?php _e('Disable on Posts/Pages',TABS_EXTENDED);?></th>
		<td><input type="checkbox" name="postdisable" value="on"<?php echo $postdisable;?>/>
			<small><?php _e('Disable tabs on posts/pages',TABS_EXTENDED);?></small></td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Style',TABS_EXTENDED);?></th>
			<td>
				<select name="style">
			<?php 
			foreach($styles as $style):

				if($style == $defaultstyle):
				?>
				<option value="<?php echo strtolower($style);?>" selected="selected"><?php echo $style;?></option>
					<?php
					else:
					?>	
				<option value="<?php echo strtolower($style);?>"><?php echo $style;?></option>
				<?php
				endif;
			endforeach;
			?>
				</select>
			</td>
		</tr>
		<tr valign="top">
		<th scope="row"><?php _e('Give link to developer website',TABS_EXTENDED);?></th>
		<td><input type="checkbox" name="backlink" value="on"<?php echo $backlink;?> /></td>
		</tr>
		</table>
		<p class="submit">
		<input class="button-primary" type="submit" name="submit" value="Save Changes" />
		</p>
		</form>

		</div>
		<?php
	}
	
	function admin_menu(){
		add_options_page('Tabs Extended','Tabs Extended',10,'tabs_extended',array($this,"optionsView"));
		
		$postdisable = (self::getOption("postdisable")=="on") ? true : false;
		
		if($postdisable){
			if( function_exists( 'add_meta_box' ) ) {
	    		add_meta_box( 'myplugin_sectionid', __( 'Tabs Extended' ), array($this,"custom_box"), 'post', 'side','high' );
				//add_meta_box( $id,                  $title,                                      $callback,                  $page, $context, $priority ); 
	    		add_meta_box( 'myplugin_sectionid', __( 'Tabs Extended' ), array($this,"custom_box"), 'page', 'advanced' );
	   		} else {
	    		add_action('dbx_post_advanced', array($this,'old_custom_box') );
	    		add_action('dbx_page_advanced', array($this,'old_custom_box') );
	  		}
	
		}
	
	}
	
	
	function addStyles(){
		$style = $this->getOption('style');

		if(empty($style) or ($style=="")){
			$style = "default.css";
		}	
		
		wp_enqueue_style("tabs_extended",sprintf("%s/style/%s",self::getSettings('plugin_url'),$style),array(),"1.0","screen");
	}
	
	function addScripts(){
		wp_enqueue_script('jquery');
		wp_enqueue_script("tabs_extended",sprintf("%s/tabs.js",self::getSettings('plugin_url')),array("jquery"),'1.0');	
	}
}

add_action('init',array(new Tabs_Extended,"init"));

?>