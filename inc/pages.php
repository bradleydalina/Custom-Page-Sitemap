<?php

defined( 'ABSPATH' ) or die( 'Why?' );

if(!function_exists('cps_pages')):
    add_action('', 'cps_pages');
    function cps_pages($content="",$args){	
		if(!empty($args['links']) || $args['links']!=''):
			if(strpos($args['links'], 'p')!== false):
				cps_print_pages($content='',$args);
			endif;
		else:		
			cps_print_pages($content='',$args);
		endif;	
    }
endif;

if(!function_exists('cps_print_pages')):
	function cps_print_pages($content='',$args){
		$title_wrap = preg_replace('/[^a-z0-9]/mi', '', $args['title_wrap']);
		$title_id ='';
		$title_class='';
		if($args['title']){
			$title = CustomPageSitemap::title_identifier($args['title']);
			if(strtolower(gettype($title))=='array'):
				$title = ($title['p']) ? $title['p'] : 'Pages' ;
			endif;
		}
		else{
			$title='Pages';
		}
		if(preg_match_all('/,/', $args['twic']) == 2){
			$twic = explode(',', $args['twic']);
			$title_wrap = CustomPageSitemap::cps_clean('/[^a-z0-9]/mi', '', $twic[0], $title_wrap);
			$title_id = CustomPageSitemap::cps_clean('/[^a-z0-9-_]/mi', '', $twic[1], $title_id, ' id="');
			$title_class = CustomPageSitemap::cps_clean('/[^a-z0-9-_]/mi', '', $twic[2], $title_class, ' class="');
		}
		echo "<{$title_wrap}{$title_id}{$title_class}>{$title}</{$title_wrap}>";
		
		
		$insert_link = (strpos($args['insert_link'], 'p:')!==FALSE ? "<li>".$args['icon'].CustomPageSitemap::get_insert_link($args['insert_link'])['p']."</li>" : '');
		add_filter( 'wp_nav_menu_items',['CustomPageSitemap','page_insertlink'], 10, 2 );
		CustomPageSitemap::$insert_link = $insert_link;
		
		wp_nav_menu(
		  array(
			  'menu'            => $args['menu'],
			  'theme_location'  => $args['theme_location'],
			  'container'       => $args['wrap'],
			  'container_class' => $args['wrap_class'],
			  'container_id'    => $args['wrap_id'],
			  'menu_class'      => "cps-menu".($args['menu_class']) ? " ".$args['menu_class'] : '',
			  'menu_id'         => $args['menu_id'],
			  'fallback_cb'     => 'wp_page_menu',
			  'link_before'     => $args['icon'], 
			  'link_after'      => $insert_link,
			  'depth'           => $args['depth'],
			  'items_wrap'      => '<ul cps-menu=true order="'.$args['order'].'" id="%1$s" class="%2$s">%3$s</ul>'
			  )
		 );
		echo $content;
	}
endif;
