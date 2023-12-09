<?php
/**
 * Plugin Name:     Ultimate Member - Sort Users Columns
 * Description:     Extension to Ultimate Member for Sorting Users Columns.
 * Version:         2.5.0
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica
 * Text Domain:     ultimate-member
 * Domain Path:     /languages
 * UM version:      2.8.0
 */

 if ( ! defined( 'ABSPATH' ) ) exit; 
 if ( ! class_exists( 'UM' ) ) return;
 
 class UM_Sort_Users_Columns {

    public $active_options = array();

    function __construct() {

        add_filter( 'um_settings_structure', array( $this, 'um_settings_structure_sort_users_columns' ), 10, 1 );

        $sort_users_columns = UM()->options()->get( 'sort_users_columns' );
        if ( ! empty( $sort_users_columns )) {

            $this->active_options = array_map( 'sanitize_text_field', maybe_unserialize( $sort_users_columns ));
            if ( ! empty( $this->active_options )) {

                add_filter( 'manage_users_sortable_columns', array( $this, 'register_sortable_columns_custom' ), 1, 1 );
                add_filter( 'users_list_table_query_args',   array( $this, 'users_list_table_query_args_custom' ), 1, 1 );

                add_filter( 'manage_users_columns',          array( $this, 'manage_users_columns_custom_um' ), 10, 1 );
                add_filter( 'manage_users_custom_column',    array( $this, 'manage_users_custom_column_um' ), 10, 3 );

                if ( in_array( 'um_number_logins', $this->active_options )) {
                    add_action( 'um_on_login_before_redirect', array( $this, 'um_store_number_of_logins' ), 10, 1 );
                }

                if ( in_array( 'um_approved_membership_by', $this->active_options )) {
                    add_action( 'um_admin_user_action_um_approve_membership_hook', array( $this, 'um_admin_user_action_approved_membership_by' ), 10 );
                }

                if ( in_array( 'um_last_editing_user', $this->active_options )) {
                    add_action( 'um_after_user_updated', array( $this, 'um_after_user_updated_last_editing_user' ), 10, 3 );
                }

            }
        }
    }

    public function manage_users_columns_custom_um( $columns ) {

        $headers['_um_last_login']            = __( 'UM Last Login',            'ultimate-member' );
        $headers['user_registered']           = __( 'UM User Registration',     'ultimate-member' );
        $headers['um_number_logins']          = __( 'UM Number of Logins',      'ultimate-member' );
        $headers['um_approved_membership_by'] = __( 'UM User Approved by',      'ultimate-member' );
        $headers['um_last_editing_user']      = __( 'Last User edting Profile', 'ultimate-member' );

        if ( UM()->options()->get( 'enable_reset_password_limit' ) ) {

            $limit = UM()->options()->get( 'reset_password_limit_number' );
            $headers['password_rst_attempts'] = sprintf( __( 'UM Number of Password attempts (max %d)', 'ultimate-member' ), $limit );
        }

        foreach( $this->active_options as $column ) {
            $columns[$column] = $headers[$column];
        }

        return $columns;
    }

    public function manage_users_custom_column_um( $value, $column_name, $user_id ) {

        if ( in_array( $column_name, $this->active_options )) {

            um_fetch_user( $user_id );
            $value = um_user( $column_name );
            um_reset_user();

            switch( $column_name ) {

                case '_um_last_login':
                case 'user_registered':             if ( ! empty( $value )) {
                                                        $value = wp_date( get_option( 'date_format', 'F j, Y' ) . ' ' . get_option( 'time_format', 'g:i a' ), strtotime( $value ));
                                                    }
                                                    break;

                case 'um_number_logins':
                case 'password_rst_attempts':       if ( empty( $value )) $value = 0;
                                                    break;

                case 'um_last_editing_user':
                case 'um_approved_membership_by':   if ( ! empty( $value )) {
                                                        $user = new WP_User( $value );
                                                        $value = $user->user_login;
                                                    } else {
                                                        $value = '';
                                                    }
                                                    break;

                default:
            }
        }

        return $value;
    }

    public function um_store_number_of_logins( $user_id ) {

        um_fetch_user( $user_id );

        $current_number = um_user( 'um_number_logins' );
        if ( empty( $current_number )) $current_number = 1;
        else $current_number++;

        update_user_meta( $user_id, 'um_number_logins', $current_number );
        UM()->user()->remove_cache( $user_id );
        um_fetch_user( $user_id );
    }

    public function um_admin_user_action_approved_membership_by() {

        global $current_user;

        update_user_meta( um_user( 'ID' ), 'um_approved_membership_by', $current_user->ID );
    }

    public function um_after_user_updated_last_editing_user( $user_id, $args, $to_update ) {

        global $current_user;

        update_user_meta( $user_id, 'um_last_editing_user', $current_user->ID );
    }


    public function register_sortable_columns_custom( $columns ) {

        foreach( $this->active_options as $column ) {
            $columns[$column] = $column;
        }

        return $columns;
    }

    public function users_list_table_query_args_custom( $args ) {

        if ( isset( $args['orderby'] ) && is_string( $args['orderby'] )) {

            if ( in_array( $args['orderby'], $this->active_options )) {

                switch( $args['orderby'] ) {

                    case '_um_last_login':              $args['meta_key'] = '_um_last_login';
                                                        break;

                    case 'user_registered':             break;

                    case 'um_number_logins':
                    case 'password_rst_attempts':       $args['meta_key'] = $args['orderby'];
                                                        $args['orderby'] = 'meta_value_num';
                                                        break;

                    case 'um_approved_membership_by':   $args['meta_key'] = 'um_approved_membership_by';
                                                        $args['orderby'] = 'meta_value_num';
                                                        break;

                    case 'um_last_editing_user':        $args['meta_key'] = 'um_last_editing_user';
                                                        $args['orderby'] = 'meta_value_num';
                                                        break;
                    default:
                }
            }
        }

        return $args;
    }

    public function um_settings_structure_sort_users_columns( $settings_structure ) {

        $settings_structure['']['sections']['users']['fields'][] =

        array(
                'id'          => 'sort_users_columns',
                'type'        => 'select',
                'multi'       => true,
                'label'       => __( 'Sort Users Columns - Multi Select Columns', 'ultimate-member' ),
                'tooltip'     => __( 'Select the columns you want to add to the WP Users Page.', 'ultimate-member' ),
                'size'        => 'medium',
                'options'     => array( '_um_last_login'            =>  __( 'Last User Login',           'ultimate-member' ),
                                        'user_registered'           =>  __( 'User Registration',         'ultimate-member' ),
                                        'um_number_logins'          =>  __( 'Number of User Logins',     'ultimate-member' ),
                                        'password_rst_attempts'     =>  __( 'Number of password resets', 'ultimate-member' ),
                                        'um_approved_membership_by' =>  __( 'User approved by',          'ultimate-member' ),
                                        'um_last_editing_user'      =>  __( 'Last User edting Profile',  'ultimate-member' ),
                                        ),
            );

        return $settings_structure;
    }

}

 new UM_Sort_Users_Columns();
