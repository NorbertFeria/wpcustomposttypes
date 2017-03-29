<?php
/*
Plugin Name: LC Posttypes
Plugin URI: http://www.localhostph.com
Description: Posttypes plugin enbales you to create custom posttypes from the dashboard.
Author: Norbert Feria
Version: 1.0
Author URI: http://www.localhostph.com
*/
if(!defined('ABSPATH')) exit;

include('lc_posttypes_list_table.php');

$lcpt = new lc_posttypes(__FILE__);

class lc_posttypes {
	
	var $tblname = 'custom_posttypes';
	var $tblid = 'unik';
	var $metainherit = FALSE;
	
	public function __construct($file) { 
		register_activation_hook( $file, array($this,'_activate') );
		register_deactivation_hook( $file,array($this,'_deactivate') );
		
		add_action('admin_menu', array($this,'_menu_item') );
		
		add_action('init',array($this,'_lcpt_register_post_type'));		
	}
	
	function get_tblname(){
		global $wpdb;
		$table_name = $wpdb->prefix . $this->tblname;
		return $table_name;
	}
	
	function _activate(){
		if(!current_user_can('activate_plugins' )) return;
		if(NULL == get_role('devsupport')){
			$adminrole = get_role( 'administrator' ); 
			$result = add_role('devsupport',__( 'Development Support' ),$adminrole->capabilities);
			$devsupport = get_role( 'devsupport' );
			$devsupport->add_cap( 'manage_advanced_options' ); 
	   }
		global $wpdb;
		$table_name = $this->get_tblname();
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {		
			$sql = "CREATE TABLE `".$table_name."` (
			  `unik` bigint(10) NOT NULL AUTO_INCREMENT,
			  `cpt_slug` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `cpt_name` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `cpt_single` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `cpt_plural` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `urlprefixslug` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `use_custom_categories` int(1) NOT NULL DEFAULT '0',
			  `taxonomy_label` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `taxonomy_urlprefix` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `ispublic` int(1) NOT NULL DEFAULT '1',
			  `excludefromsearch` int(1) NOT NULL DEFAULT '1',
			  `taxonomies` varchar(100) CHARACTER SET utf8 NOT NULL,
			  `isshowui` int(1) NOT NULL DEFAULT '1',
			  `showinnavmenus` int(1) NOT NULL DEFAULT '1',
			  `capabilitytype` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `hierarchical` int(1) NOT NULL DEFAULT '0',
			  `menuposition` int(3) NOT NULL DEFAULT '5',
			  `edit_link` varchar(100) CHARACTER SET utf8 NOT NULL,
			  `hasarchive` int(1) NOT NULL DEFAULT '1',
			  `withfront` int(1) NOT NULL DEFAULT '1',
			  `builtin` int(1) NOT NULL DEFAULT '0',
			  `queryvar` int(1) NOT NULL DEFAULT '1',
			  `supports` varchar(250) CHARACTER SET utf8 NOT NULL,
			  `enabled` INT( 1 ) NOT NULL DEFAULT '0',
			  `posttype_description` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
			  PRIMARY KEY (`unik`)
			)";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}#if no table
	}#function activate
		
	function _deactivate() {
		# data will be cleared on uninstall instead of deactivate
	}#function deactivate
	
	function _menu_item(){
		add_submenu_page("edit.php", "", "LC Custom Posttypes", "manage_advanced_options", "manage-posttypes", array($this,'_admin_page'));
	}#function
	
	function _admin_page(){
		echo  '<div class="wrap"><h2>Manage Custom Posttypes
		<a class="add-new-h2" href="?page='. $_REQUEST['page'].'&action=lcpt_add">Add New Custom Posttype</a>
		</h2>
		<BR>';
		if(isset($_GET["action"]) && !isset($_GET["action2"])){
			$this->{$_GET["action"]}();
		}else{
			$this->lcpt_posttype_list();
		}
		echo '</div>';
		/*
		echo "test";
		$terms = get_terms('industry',array( 'hide_empty' => 0 ));
		 $count = count($terms);
		 if ( $count > 0 ){
			 foreach ( $terms as $term ) {
				 echo $term->term_id;
			 }
		 }
		
		 echo "endtest";
		 */
	}#function
	
	
	function get_cpts_support(){
		$cpts = array();
		$fldnames = array('cpts_title','cpts_editor','cpts_author','cpts_thumbnail','cpts_excerpt','cpts_trackbacks','cpts_custom-fields','cpts_comments','cpts_revisions');
		foreach($fldnames as $fldname):
			if($_POST[$fldname] == 'on'):
			    $fld = explode('_',$fldname);
				array_push($cpts,$fld[1]);
			endif;
		endforeach;
		return $cpts;
	}
	
	function lcpt_saveposttype(){
		if(isset($_POST['addsetting_nonce'])){
			if(wp_verify_nonce($_POST['addsetting_nonce'],'addsetting_nonce')){
				$cpts = serialize($this->get_cpts_support());
				if($_POST['use_custom_categories'] == 'on'){
					$use_custom_categories = 1;
				}else{
					$use_custom_categories = 0;
				}
				global $wpdb;
				$table_name = $this->get_tblname();
				$sql = "INSERT INTO ".$table_name." 
				(cpt_name,cpt_slug,urlprefixslug,posttype_description,cpt_single,cpt_plural,capabilitytype,edit_link,supports,
				use_custom_categories,taxonomy_label,taxonomy_urlprefix) VALUES 
				('".$_POST['cpt_name']."','".$_POST['cpt_slug']."','".$_POST['urlprefixslug']."','".$_POST['posttype_description']."','".$_POST['cpt_single']."',
				'".$_POST['cpt_plural']."','".$_POST['capabilitytype']."','".$_POST['edit_link']."','".$cpts."',".$use_custom_categories.",
				'".$_POST['taxonomy_label']."','".$_POST['taxonomy_urlprefix']."');";
				$wpdb->query($sql);
				$sdstr = "Custom Post type successfully saved. ";
				echo $sdstr;
			}
		}
		$this->lcpt_posttype_list();
	}#save sidebar
	
	function lcpt_add(){
		$sdstr .= '<a class="add-new-h2" href="?page='.$_REQUEST['page'].'">View Custom Post Types List</a>';
		$sdstr .= '<BR><BR><div class="form-wrap">';
		$sdstr .= '<form id="contact-settings" method="POST" action="?page='.$_REQUEST['page'].'&action=lcpt_saveposttype">';
		
		$sdstr .= '<div class="form-field">
		<label for="cpt_name">Name :</label>
		<input type="text" name="cpt_name" id="cpt_name" value="" size=40>
		<p>Name of your custom post type</p>
		</div>
		';
		$sdstr .='
		<div class="form-field">
		<label for="cpt_slug">Slug</label>
		<input id="cpt_slug" type="text" size="40" value="" name="cpt_slug">
		<p>The "slug" is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.</p>
		</div>
		';
		
		$sdstr .= '<div class="form-field">
		<label for="urlprefixslug">URL Prefix :</label>
		<input type="text" name="urlprefixslug" id="urlprefixslug" value="" size=40>
		<p>This will be the prefix of your post type pages e.g. www.website.com/URLPREFIX/</p>
		</div>
		';
		
		$sdstr .='
		<div class="form-field">
		<label for="posttype_description">Description</label>
		<textarea id="posttype_description" class="widefat" name="posttype_description"></textarea>
		<p>Enter the description for new custom posttype. </p>
		</div>
		';
			
		$sdstr .= '<div style="width:50%; padding:20px; margin:20px; border:1px solid #ececec;">';
		$sdstr .= '<div>
		Custom categories:<BR>
		<input type="checkbox"" checked="checked" name="use_custom_categories" id="use_custom_categories"> Use Custom Categories<BR>
		</div>
		';
		
		$sdstr .= '<div class="form-field">
		<label for="taxonomy_label">Cateogry Label :</label>
		<input type="text" name="taxonomy_label" id="taxonomy_label" value="" size=40>
		<p>Label to use for your custom categories e.g. Industries(for business directory posttypes)</p>
		</div>
		';
		
		$sdstr .= '<div class="form-field">
		<label for="taxonomy_urlprefix">Cateogry URL Prefix :</label>
		<input type="text" name="taxonomy_urlprefix" id="taxonomy_urlprefix" value="" size=40>
		</div>
		';
		$sdstr .= '</div>';
		
		$sdstr .= '<div style="width:50%; padding:20px; margin:20px; border:1px solid #ececec;">';
		$sdstr .= '<div class="form-field">
		<label for="cpt_single">Singular Label :</label>
		<input type="text" name="cpt_single" id="cpt_single" value="" size=40>
		<p>Singular label for the post type. e.g. Event</p>
		</div>
		';
		
		$sdstr .= '<div class="form-field">
		<label for="cpt_plural">Plural Label :</label>
		<input type="text" name="cpt_plural" id="cpt_plural" value="" size=40>
		<p>Plural label for the post type. e.g. Events</p>
		</div>
		';
		$sdstr .= '</div>';
		
		$sdstr .= '<div style="width:50%; padding:20px; margin:20px; border:1px solid #ececec;">';
		$sdstr .= '<div>
		Supports:<BR>
		<input type="checkbox"" checked="checked" name="cpts_title" id="cpts_title"> Title<BR>
		<input type="checkbox"" checked="checked" name="cpts_editor" id="cpts_editor"> Editor<BR>
		<input type="checkbox"" name="cpts_author" id="cpts_author"> Author<BR>
		<input type="checkbox"" name="cpts_thumbnail" id="cpts_thumbnail"> Thumbnail<BR>
		<input type="checkbox"" name="cpts_excerpt" id="cpts_excerpt"> Excerpt<BR>
		<input type="checkbox"" name="cpts_trackbacks" id="cpts_trackbacks"> Trackbacks<BR>
		<input type="checkbox"" name="cpts_customfields" id="cpts_custom-fields"> Custom-fields<BR>
		<input type="checkbox"" name="cpts_comments" id="cpts_comments"> Comments<BR>
		<input type="checkbox"" name="cpts_revisions" id="cpts_revisions"> Revisions<BR>
		</div>
		';
		$sdstr .= '</div>';
			
		$sdstr .= '<input type="hidden" name="capabilitytype" id="capabilitytype" value="post">';
		$sdstr .= '<input type="hidden" name="edit_link" id="edit_link" value="post.php?post=%d">';
		$sdstr .= '<input type="hidden" name="addsetting_nonce" id="addsetting_nonce" value="'.wp_create_nonce('addsetting_nonce').'">';	
		$sdstr .= '<BR><input class="button button-primary" type="submit" value="Save">';
		$sdstr .= '</form></div>';
		$sdstr .= '<BR><BR>';
		$sdstr .= '<a class="add-new-h2" href="?page='.$_REQUEST['page'].'">View Custom Post Types List</a>';
		$sdstr .= $this->lcpt_form_javascript();
		echo $sdstr;
	}#function lcpt_add
	
	function lcpt_form_javascript(){
		$sdstr .='
			<script>
				jQuery(document).ready(function() {
											
					jQuery( "#cpt_name" ).change(function() {
						var Text = jQuery(this).val();
						Text = Text.replace(/[^a-zA-Z 0-9-]+/g,\'\');
						Text = Text.toLowerCase();
						Text = Text.replace(/ /g, \'-\');
						jQuery("#cpt_slug").val(Text);    
						jQuery("#urlprefixslug").val(Text);    
					});
					jQuery( "#taxonomy_label" ).change(function() {
						var Text = jQuery(this).val();
						Text = Text.replace(/[^a-zA-Z 0-9-]+/g,\'\');
						Text = Text.toLowerCase();
						Text = Text.replace(/ /g, \'-\');
						jQuery("#taxonomy_urlprefix").val(Text);    
					});
					jQuery( "#use_custom_categories" ).on( "click", function(){
							if(jQuery("#use_custom_categories").prop("checked")){
								jQuery("#taxonomy_label").prop("disabled", false);
								jQuery("#taxonomy_urlprefix").prop("disabled", false);
							}else{
								jQuery("#taxonomy_label").prop("disabled", true);
								jQuery("#taxonomy_urlprefix").prop("disabled", true);
							}
						});
				});
			</script>
		';
		return $sdstr;
	}
	
	function lcpt_updatecpt(){
		if(isset($_POST['updatesetting_nonce'])){
			if(wp_verify_nonce($_POST['updatesetting_nonce'],'updatesetting_nonce')){
				global $wpdb;
				$cpts = serialize($this->get_cpts_support());
				if($_POST['use_custom_categories'] == 'on'){
					$use_custom_categories = 1;
				}else{
					$use_custom_categories = 0;
				}
				
				$table_name = $this->get_tblname();
				$sql = "UPDATE ".$table_name." SET cpt_name = '".$_POST["cpt_name"]."', 
				cpt_slug = '".$_POST["cpt_slug"]."', 
				urlprefixslug = '".$_POST["urlprefixslug"]."',
				posttype_description = '".$_POST["posttype_description"]."',
				cpt_single = '".$_POST["cpt_single"]."',
				use_custom_categories = ".$use_custom_categories.",
				taxonomy_label = '".$_POST["taxonomy_label"]."',
				taxonomy_urlprefix = '".$_POST["taxonomy_urlprefix"]."',
				cpt_plural  = '".$_POST["cpt_plural"]."',
				supports = '".$cpts."',
				enabled = ".$_POST["enabled"]."
				WHERE ".$this->tblid." = ".$_POST["unik"]."";
				$wpdb->query($sql);
				$sdstr = "Custom Sidebar successfully updated.";
				echo $sdstr;
			}
		}
		$this->lcpt_posttype_list();
	}#update sidebar
	
	
	function lcpt_statusupdate(){
		global $wpdb;
		$table_name = $this->get_tblname();
		if(isset($_GET['stype'])){
			$new_status = $_GET['stype'] ? 0 : 1;
			$sql = "UPDATE ".$table_name." SET enabled = ".$new_status." WHERE ".$this->tblid." = ".$_GET["id"]."";
			$wpdb->query($sql);
			$sdstr = "Custom Post type status updated.";
			echo $sdstr;
		}
		$this->lcpt_posttype_list();
	}#function lcpt_statusupdate
	
	function lcpt_edit(){
		global $wpdb;
		$cpts = array();
		
		$table_name = $this->get_tblname();
		$sql = "SELECT * FROM ".$table_name." 
			WHERE ".$table_name.".".$this->tblid." = ".$_GET['id'];
			
		$posttype_row = $wpdb->get_row($sql);
		$sdstr .= '<a class="add-new-h2" href="?page='.$_REQUEST['page'].'">View Custom Post Type List</a>';
		$sdstr .= '<BR><BR><div class="form-wrap">';
		$sdstr .= '<form id="contact-settings" method="POST" action="?page='.$_REQUEST['page'].'&action=lcpt_updatecpt">';
		
		$sdstr .= '<div class="form-field">
		<label for="cpt_name">Name :</label>
		<input type="text" name="cpt_name" id="cpt_name" value="'.$posttype_row->cpt_name.'" size=40>
		<p>Name of your custom post type</p>
		</div>
		';
		$sdstr .='
		<div class="form-field">
		<label for="cpt_slug">Slug</label>
		<input id="cpt_slug" type="text" size="40" value="'.$posttype_row->cpt_slug.'" name="cpt_slug">
		<p>The "slug" is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.</p>
		</div>
		';
		
		$sdstr .= '<div class="form-field">
		<label for="urlprefixslug">URL Prefix :</label>
		<input type="text" name="urlprefixslug" id="urlprefixslug" value="'.$posttype_row->urlprefixslug.'" size=40>
		<p>This will be the prefix of your post type pages e.g. www.website.com/URLPREFIX/</p>
		</div>
		';
		
		$sdstr .='
		<div class="form-field">
		<label for="posttype_description">Description</label>
		<textarea id="posttype_description" class="widefat" name="posttype_description">'.$posttype_row->posttype_description.'</textarea>
		<p>Enter the description for new custom posttype. </p>
		</div>
		';
		
		$chk = ($posttype_row->use_custom_categories) ? 'checked="checked"' : '' ;
		$sdstr .= '<div style="width:50%; padding:20px; margin:20px; border:1px solid #ececec;">';
		$sdstr .= '<div>
		Custom categories:<BR>	
		<input type="checkbox"" '.$chk.' name="use_custom_categories" id="use_custom_categories"> Use Custom Categories<BR>
		</div>
		';
		
		$sdstr .= '<div class="form-field">
		<label for="taxonomy_label">Cateogry Label :</label>
		<input type="text" name="taxonomy_label" id="taxonomy_label" value="'.$posttype_row->taxonomy_label.'" size=40>
		<p>Label to use for your custom categories e.g. Industries(for business directory posttypes)</p>
		</div>
		';
		
		$sdstr .= '<div class="form-field">
		<label for="taxonomy_urlprefix">Cateogry URL Prefix :</label>
		<input type="text" name="taxonomy_urlprefix" id="taxonomy_urlprefix" value="'.$posttype_row->taxonomy_urlprefix.'" size=40>
		</div>
		';
		$sdstr .= '</div>';
		
		$sdstr .= '<div style="width:50%; padding:20px; margin:20px; border:1px solid #ececec;">';
		$sdstr .= '<div class="form-field">
		<label for="cpt_single">Singular Label :</label>
		<input type="text" name="cpt_single" id="cpt_single" value="'.$posttype_row->cpt_single.'" size=40>
		<p>Singular label for the post type. e.g. Event</p>
		</div>
		';
		
		$sdstr .= '<div class="form-field">
		<label for="cpt_plural">Plural Label :</label>
		<input type="text" name="cpt_plural" id="cpt_plural" value="'.$posttype_row->cpt_plural.'" size=40>
		<p>Plural label for the post type. e.g. Events</p>
		</div>
		';
		$sdstr .= '</div>';
		
		$cpts = unserialize($posttype_row->supports);	
		if($cpts == FALSE){
			$cpts = array();
		}
		$sdstr .= '<div style="width:50%; padding:20px; margin:20px; border:1px solid #ececec;">';
		$sdstr .= '<div>
		Supports:<BR>';
		$cbs = (in_array('title',$cpts)) ? 'checked="checked"' : '' ;
		$sdstr .= '<input type="checkbox"" '.$cbs.' name="cpts_title" id="cpts_title"> Title<BR>';
		$cbs = (in_array('editor',$cpts)) ? 'checked="checked"' : '' ;
		$sdstr .= '<input type="checkbox"" '.$cbs.' name="cpts_editor" id="cpts_editor"> Editor<BR>';
		$cbs = (in_array('author',$cpts)) ? 'checked="checked"' : '' ;
		$sdstr .= '<input type="checkbox"" '.$cbs.' name="cpts_author" id="cpts_author"> Author<BR>';
		$cbs = (in_array('thumbnail',$cpts)) ? 'checked="checked"' : '' ;
		$sdstr .= '<input type="checkbox"" '.$cbs.' name="cpts_thumbnail" id="cpts_thumbnail"> Thumbnail<BR>';
		$cbs = (in_array('excerpt',$cpts)) ? 'checked="checked"' : '' ;
		$sdstr .= '<input type="checkbox"" '.$cbs.' name="cpts_excerpt" id="cpts_excerpt"> Excerpt<BR>';
		$cbs = (in_array('trackbacks',$cpts)) ? 'checked="checked"' : '' ;
		$sdstr .= '<input type="checkbox"" '.$cbs.' name="cpts_trackbacks" id="cpts_trackbacks"> Trackbacks<BR>';
		$cbs = (in_array('custom-fields',$cpts)) ? 'checked="checked"' : '' ;
		$sdstr .= '<input type="checkbox"" '.$cbs.' name="cpts_custom-fields" id="cpts_custom-fields"> Custom-fields<BR>';
		$cbs = (in_array('comments',$cpts)) ? 'checked="checked"' : '' ;
		$sdstr .= '<input type="checkbox"" '.$cbs.' name="cpts_comments" id="cpts_comments"> Comments<BR>';
		$cbs = (in_array('revisions',$cpts)) ? 'checked="checked"' : '' ;
		$sdstr .= '<input type="checkbox"" '.$cbs.' name="cpts_revisions" id="cpts_revisions"> Revisions<BR>';
		$sdstr .= '</div>';
		
		$sdstr .= '</div>';
		
		$sdstr .='
		<div class="form-field">
		<label for="enabled">Enable</label>
		<select name="enabled" id="enabled" class="widefat">';
		$selected = $posttype_row->enabled ? 'selected' : '';
		$sdstr .= '<option value=0>No</option>';
		$sdstr .= '<option value=1 '.$selected.'>Yes</option>';
		$sdstr .= '</select>
		<p>Disabled Custom post type.</p>
		</div>
		';
		
		$sdstr .= '<input type="hidden" name="updatesetting_nonce" id="updatesetting_nonce" value="'.wp_create_nonce('updatesetting_nonce').'">';	
		$sdstr .= '<input type="hidden" name="'.$this->tblid.'" id="'.$this->tblid.'" value="'.$posttype_row->unik.'">';	
		$sdstr .= '<BR><input class="button button-primary" type="submit" value="Save">';
		$sdstr .= '</form></div>';
		$sdstr .= '<BR><BR>';
		$sdstr .= '<a class="add-new-h2" href="?page='.$_REQUEST['page'].'">View Custom Post Type List</a>';
		$sdstr .= $this->lcpt_form_javascript();
		echo $sdstr;
	}#edit sidebar
	
	function lcpt_delete_cpts($posttype){
	   $cpts = get_posts( array( 'post_type' => $posttype) );
	   foreach( $cpts as $cpt) {
		 wp_delete_post( $cpt->ID, true);
	   }

       global $wp_post_types;
	   if ( isset( $wp_post_types[ $posttype ] ) ) {
		 unset( $wp_post_types[ $posttype ] );
	   }
	}#function
	
	function lcpt_delete_taxonomy($taxonomy){
		 $terms = get_terms($taxonomy,array( 'hide_empty' => 0 ));
		 $count = count($terms);
		 if ( $count > 0 ){
			 foreach ( $terms as $term ) {
				wp_delete_term( $term->term_id, $taxonomy );
			 }
		 }
		 global $wp_taxonomies;
		 unset($wp_taxonomies[$taxonomy]);
	}#function
	
	function lcpt_delete_confirmed(){
		global $wpdb;
		$table_name = $this->get_tblname();
		
		$sql = "SELECT * FROM ".$table_name." WHERE ".$this->tblid."=".$_GET["id"];
		$custom_posttype = $wpdb->get_row($sql);

		$this->lcpt_delete_cpts($custom_posttype->cpt_slug);
		$this->lcpt_delete_taxonomy($custom_posttype->taxonomy_urlprefix);
		
		flush_rewrite_rules(true);
		
		$wpdb->query( $wpdb->prepare( 
							"DELETE FROM ".$table_name."
							 WHERE unik = %d",
								$_GET["id"]));
		$sdstr = "Custom Post Type successfully deleted.";
		echo $sdstr;
		$this->lcpt_posttype_list();
	}#function
	
	function lcpt_delete(){
		$sdstr .=  'This action will delete your post type,<BR>
		 all posts under this post types,<BR>
		  additional custom fields associated with those post types, <BR>
		  the custom category if it has a custom category and all its categories.<BR><BR>';
		$sdstr .=  'Are you sure you want to continue deleting the post type?<BR><BR>';
		
		$sdstr .= '<a class="add-new-h2" href="?page='.$_REQUEST['page'].'&action=lcpt_delete_confirmed&id='.$_GET['id'].'">Yes, Delete my Post type and all its data</a><BR><BR>';
		$sdstr .= '<a class="add-new-h2" href="?page='.$_REQUEST['page'].'">Cancel and Go back to List View</a>';
		echo $sdstr;
		
	}#function lcpt_delete
	
	function lcpt_posttype_list(){
		$table = new lcpt_posttype_list_table($this->tblname,$this->tblid);
		$table->prepare_items();
		
		echo '<form id="posttype-table" method="GET">
			<input type="hidden" name="page" value="'.$_REQUEST['page'].'"/>';
			
		$table->display();
		
		echo '</form>';
		
	}#function

	function lcpt_get_bool($intvariable){
		if($intvariable == 1){ return TRUE;}
		if($intvariable == 0){ return FALSE;}
	}
	
	function _lcpt_register_post_type() {
		flush_rewrite_rules(true);
		global $wpdb;
		$table_name = $this->get_tblname();
		$sql = "SELECT * FROM ".$table_name." WHERE enabled=1";
		$custom_posttypes = $wpdb->get_results($sql);
		
		foreach($custom_posttypes as $custom_posttype){
		
			$cpts = unserialize($custom_posttype->supports);
			
			$taxes = array();
			array_push($taxes,'post_tag');
			if($custom_posttype->use_custom_categories==1){
				array_push($taxes,$custom_posttype->taxonomy_urlprefix);
			}
			
			$labels = array(
				'name'               => _x( $custom_posttype->cpt_name, 'post type general name', 'lcpt-textdomain' ),
				'singular_name'      => _x( $custom_posttype->cpt_single, 'post type singular name', 'lcpt-textdomain' ),
				'menu_name'          => _x( $custom_posttype->cpt_plural, 'admin menu', 'lcpt-textdomain' ),
				'name_admin_bar'     => _x( $custom_posttype->cpt_single, 'add new on admin bar', 'lcpt-textdomain' ),
				'add_new'            => _x( 'Add New', $custom_posttype->cpt_single, 'lcpt-textdomain' ),
				'add_new_item'       => __( 'Add New '.$custom_posttype->cpt_single, 'lcpt-textdomain' ),
				'new_item'           => __( 'New '.$custom_posttype->cpt_single, 'lcpt-textdomain' ),
				'edit_item'          => __( 'Edit '.$custom_posttype->cpt_single, 'lcpt-textdomain' ),
				'view_item'          => __( 'View '.$custom_posttype->cpt_single, 'lcpt-textdomain' ),
				'all_items'          => __( 'All '.$custom_posttype->cpt_plural, 'lcpt-textdomain' ),
				'search_items'       => __( 'Search '.$custom_posttype->cpt_plural, 'lcpt-textdomain' ),
				'parent_item_colon'  => __( 'Parent '.$custom_posttype->cpt_plural.':', 'lcpt-textdomain' ),
				'not_found'          => __( 'No '.$custom_posttype->cpt_plural.' found.', 'lcpt-textdomain' ),
				'not_found_in_trash' => __( 'No '.$custom_posttype->cpt_plural.' found in Trash.', 'lcpt-textdomain' )
			);
	
			$args = array(
				'labels'             => $labels,
				'public'             => $this->lcpt_get_bool($custom_posttype->ispublic),
				'show_ui'            => $this->lcpt_get_bool($custom_posttype->isshowui ),
				'exclude_from_search' => $this->lcpt_get_bool($custom_posttype->excludefromsearch),
				'show_in_menu'       => $this->lcpt_get_bool($custom_posttype->showinnavmenus),
				'_edit_link'         => $custom_posttype->edit_link,
				'taxonomies' 		 => $taxes,
				'menu_position'      => $custom_posttype->menuposition,
				'query_var'          => $this->lcpt_get_bool($custom_posttype->queryvar),
				'rewrite'            => array( 'slug' => $custom_posttype->urlprefixslug ),
				'capability_type'    => $custom_posttype->capabilitytype,
				'has_archive'        => $this->lcpt_get_bool($custom_posttype->hasarchive),
				'hierarchical'       => $this->lcpt_get_bool($custom_posttype->hierarchical),
				'with_front'         => $this->lcpt_get_bool($custom_posttype->withfront),
				'_builtin'           => $this->lcpt_get_bool($custom_posttype->builtin),
				'supports'           => $cpts
			);
			
			register_post_type( $custom_posttype->cpt_slug, $args );
			if($custom_posttype->use_custom_categories==1){
				register_taxonomy(
					$custom_posttype->taxonomy_urlprefix, $custom_posttype->cpt_slug, array(
					'hierarchical' => true,
					'label' => $custom_posttype->taxonomy_label,
					'query_var' => true,
					'show_ui' => true,
					'rewrite' => array(
						'slug' => $custom_posttype->taxonomy_urlprefix,
						'with_front' => false // Don't display the category base before 
						)
					)
				);
			}#if use custom categories
		}#foreach
	}#function
	
}#class