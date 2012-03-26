<?php
/*
Plugin Name: Author Category
Plugin URI: http://en.bainternet.info
Description: simple plugin limit authors to post just in one category.
Version: 0.2
Author: Bainternet
Author URI: http://en.bainternet.info
*/
/*
        *   Copyright (C) 2012  Ohad Raz
        *   http://en.bainternet.info
        *   admin@bainternet.info

        This program is free software; you can redistribute it and/or modify
        it under the terms of the GNU General Public License as published by
        the Free Software Foundation; either version 2 of the License, or
        (at your option) any later version.

        This program is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.

        You should have received a copy of the GNU General Public License
        along with this program; if not, write to the Free Software
        Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Disallow direct access to the plugin file */
if (basename($_SERVER['PHP_SELF']) == basename (__FILE__)) {
    die('Sorry, but you cannot access this page directly.');
}

if (!class_exists('author_category')){
    class author_category{

        /**
         * class constractor
         * @author Ohad Raz
         * @since 0.1
         */
        public function __construct(){
            add_action( 'add_meta_boxes', array( &$this, 'add_meta_box' ) );
            //save metabox
            add_action( 'save_post', array( &$this, 'author_cat_save_meta' ));
            // save user field
            add_action( 'personal_options_update', array( &$this,'save_extra_user_profile_fields' ));
            add_action( 'edit_user_profile_update', array( &$this,'save_extra_user_profile_fields' ));
            // add user field
            add_action( 'show_user_profile', array( &$this,'extra_user_profile_fields' ));
            add_action( 'edit_user_profile', array( &$this,'extra_user_profile_fields' ));

            //remove quick and bulk edit
            global $pagenow;
            if (is_admin() && 'edit.php' == $pagenow)
                add_action('admin_print_styles',array(&$this,'remove_quick_edit'));

        }

        /**
         * remove_quick_edit description
         * @author Ohad   Raz
         * @since 0.1
         * @return void
         */
        public function remove_quick_edit(){
           global $current_user;
            get_currentuserinfo();
            $cat = get_user_meta($current_user->ID,'_author_cat',true);
            if (!empty($cat) && $cat > 0){
                echo '<style>.inline-edit-categories{display: none !important;}</style>';
            }
        }

        /**
         * Adds the meta box container
         * @author Ohad Raz
         * @since 0.1
         */
        public function add_meta_box(){

            global $current_user;
            get_currentuserinfo();

            //get author categories
            $cat = get_user_meta($current_user->ID,'_author_cat',true);
            if (!empty($cat) && $cat > 0){
                //remove default metabox
                remove_meta_box('categorydiv', 'post', 'side');
                add_meta_box( 
                     'author_cat'
                    ,__( 'author category','author_cat' )
                    ,array( &$this, 'render_meta_box_content' )
                    ,'post' 
                    ,'side'
                    ,'low'
                );
            }
        }


        /**
         * Render Meta Box content
         * @author Ohad   Raz
         * @since 0.1
         * @return Void
         */
        public function render_meta_box_content(){
            global $current_user;
            get_currentuserinfo();
            $cat = get_user_meta($current_user->ID,'_author_cat',true);
            // Use nonce for verification
            wp_nonce_field( plugin_basename( __FILE__ ), 'author_cat_noncename' );
            if (!empty($cat) && $cat > 0){
                $c = get_category($cat);
                echo __('this will be posted in: <strong>','author_cat') . $c->name .__('</strong> Category');
                echo '<input name="author_cat" type="hidden" value="'.$c->term_id.'">';
            }
        }

        /**
         * This will generate the category field on the users profile
         * @author Ohad   Raz
         * @since 0.1
         * @param  (object) $user 
         * @return void
         */
         public function extra_user_profile_fields( $user ){ 
            //only admin can see and save the categories
            if ( !current_user_can( 'manage_options' ) ) { return false; }
            global $current_user;
            get_currentuserinfo();
            if ($current_user->ID == $user->ID) { return false; }
            echo '<h3>'.__('Author Category', 'author_cat').'</h3>
            <table class="form-table">
                <tr>
                    <th><label for="author_cat">'.__('Category').'</label></th>
                    <td>
                        '.wp_dropdown_categories(array(
                            'show_count' => 0,
                            'hierarchical' => 1,
                            'hide_empty' => 0,
                            'echo' => 0,
                            'name' => 'author_cat',
                            'selected' => get_user_meta($user->ID, '_author_cat', true ))).'
                        <br />
                    <span class="description">'.__('select a category to limit an author to post just in that category.','author_cat').'</span>
                    </td>
                </tr>
            </table>';
        }


        /**
         * This will save category field on the users profile
         * @author Ohad   Raz
         * @since 0.1
         * @param  (int) $user_id 
         * @return VOID
         */
        public function save_extra_user_profile_fields( $user_id ) {
            //only admin can see and save the categories
            if ( !current_user_can( 'manage_options') ) { return false; }

            update_user_meta( $user_id, '_author_cat', intval($_POST['author_cat']) );
        }

        /**
         * save category on post 
         * @author Ohad   Raz
         * @since 0.1
         * @param  (int) $post_id 
         * @return Void
         */
        public function author_cat_save_meta( $post_id ) {
            // verify if this is an auto save routine. 
            // If it is our form has not been submitted, so we dont want to do anything
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
                return;

            // verify this came from the our screen and with proper authorization,
            // because save_post can be triggered at other times

            if ( !wp_verify_nonce( $_POST['author_cat_noncename'], plugin_basename( __FILE__ ) ) )
                return;

            // Check permissions
            if ( 'page' == $_POST['post_type'] ){
                if ( !current_user_can( 'edit_page', $post_id ) )
                    return;
            }else{
                if ( !current_user_can( 'edit_post', $post_id ) )
                    return;
            }

            // OK, we're authenticated: we need to find and save the data

            if (isset($_POST['author_cat'])){
                wp_set_object_terms( $post_id, intval($_POST['author_cat']), 'category' );
            }
        }

    }//end class
}
//initiate the class on admin pages only
if (is_admin()){
    $ac = new author_category();
}