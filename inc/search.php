<?php

defined( 'ABSPATH' ) or die( 'Why?' );

if(!function_exists('cps_search')):
    add_action('', 'cps_search');
    function cps_search($content="", $args=[]){
        if($args['search']):
            $wrap = CustomPageSitemap::cps_clean('/[^a-z0-9]/mi', '', $args['wrap'] , 'div');
			$wrap_id="";
            $wrap_id = CustomPageSitemap::cps_clean('/[^a-z0-9_-]/mi', '', $args['wrap_id'] , '', ' id="');
            $wrap_class = CustomPageSitemap::cps_clean('/[^a-z0-9_-]/mi', '', $args['wrap_class'] , ' class="cps-search"', ' class="cps-search ');

                $title_wrap = preg_replace('/[^a-z0-9]/mi', '', $args['title_wrap']);
                $title = ($args['search_title']!=='') ? $args['search_title'] : 'Search';
                $title_id ='';
                $title_class='';
                if(preg_match_all('/,/', $args['twic']) == 2){
					$twic = explode(',', $args['twic']);                  
					$title_wrap = CustomPageSitemap::cps_clean('/[^a-z0-9]/mi', '', $twic[1], $title_wrap);
					$title_id = CustomPageSitemap::cps_clean('/[^a-z0-9-_]/mi', '', $twic[2], $title_id, ' id="');
					$title_class = CustomPageSitemap::cps_clean('/[^a-z0-9-_]/mi', '', $twic[3], $title_class, ' class="');
                }
            echo "<{$title_wrap}{$title_id}{$title_class}>{$title}</{$title_wrap}>";
            echo "<{$wrap}{$wrap_id}{$wrap_class}>";
            get_search_form();
            echo "</{$wrap}>";

        endif;
    }
endif;
