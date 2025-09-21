<?php

/* pre_get_posts function added to include post type blog in author loop */
function add_press_release_to_author_page( $query ) {
    add_action( 'pre_get_posts',function(){

        if ( !is_admin() && $query->is_author() && $query->is_main_query() ) {
            $query->set( 'post_type', array('post', 'press-release' ) );
            }

    });
 
  }


  ?>