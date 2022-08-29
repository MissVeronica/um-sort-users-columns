<?php

add_filter( 'manage_users_sortable_columns', 'register_sortable_columns_custom', 10, 1 );
add_filter( 'users_list_table_query_args',   'users_list_table_query_args_custom', 10, 1 );

add_filter( 'manage_users_columns',        'manage_users_columns_custom_um' );
add_filter( 'manage_users_custom_column',  'manage_users_custom_column_um', 10, 3 );
add_action( 'um_on_login_before_redirect', 'um_store_number_of_logins', 10, 1 );

function manage_users_columns_custom_um( $columns ) {

    $columns['um_last_login']         = __( 'UM Last Login',                   'ultimate-member' );
    $columns['user_registered']       = __( 'UM User Registration',            'ultimate-member' );
    $columns['um_number_logins']      = __( 'UM Number of Logins',             'ultimate-member' );
    $columns['password_rst_attempts'] = __( 'UM Number of Password attempts',  'ultimate-member' );

    return $columns;
}

function manage_users_custom_column_um( $value, $column_name, $user_id ) {

    switch( $column_name ) {

        case 'um_last_login':       um_fetch_user( $user_id );
                                    $value = um_user( '_um_last_login' );

                                    if( empty( $value )) {
                                        $value = '';
                                    } else {
                                        $time_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
                                        $value = date_i18n( $time_format, $value );
                                    }
                                    um_reset_user();
                                    break;

        case 'user_registered':     um_fetch_user( $user_id );
                                    $value = um_user( 'user_registered' );
                                    um_reset_user();
                                    break;

        case 'um_number_logins':    um_fetch_user( $user_id );
                                    $value = um_user( 'um_number_logins' );
                                    if( empty( $value )) $value = 0;
                                    um_reset_user();
                                    break;

        case 'password_rst_attempts':   um_fetch_user( $user_id );
                                        $value = um_user( 'password_rst_attempts' );
                                        if( empty( $value )) $value = 0;
                                        um_reset_user();
                                        break;

        default:
    }

    return $value;
}

function um_store_number_of_logins( $user_id ) {

    um_fetch_user( $user_id );     

    $current_number = um_user( 'um_number_logins' );
    if( empty( $current_number )) $current_number = 1;
    else $current_number++;

    update_user_meta( $user_id, 'um_number_logins', $current_number );
    UM()->user()->remove_cache( $user_id );
    um_fetch_user( $user_id );
}   



function register_sortable_columns_custom( $columns ) {

    $columns['um_last_login']         = 'um_last_login';
    $columns['user_registered']       = 'user_registered';
    $columns['um_number_logins']      = 'um_number_logins';
    $columns['password_rst_attempts'] = 'password_rst_attempts';

    return $columns;
}

function users_list_table_query_args_custom( $args ) {
    
    UM()->classes['um_html_view_function']->debug_cpu_update_profile( $args, __FUNCTION__, 'args', basename($_SERVER['PHP_SELF']), __line__ );

    switch( $args['orderby'] ) {
    
        case 'um_last_login':       $args['meta_key'] = '_um_last_login';        
                                    $args['type']     = 'numeric';
                                    break;

        case 'user_registered':     break;

        case 'um_number_logins':    $args['meta_key'] = 'um_number_logins';
                                    $args['type']     = 'numeric';
                                    break;

        case 'password_rst_attempts':   $args['meta_key'] = 'password_rst_attempts';
                                        $args['type']     = 'numeric';
                                        break;

        default:
    }
    return $args;
}
  
