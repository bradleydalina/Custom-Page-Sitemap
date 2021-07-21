<?php

defined( 'ABSPATH' ) or die( 'Why?' );

if(!function_exists('cps_archives')):
    add_action('', 'cps_archives');
    function cps_archives($content="", $args=[])
    {
        if(strpos($args['links'], 'a')!== false):

            $inherit_usage = (preg_match_all('/1/im', '', $args['links'])==1);

            $wrap = CustomPageSitemap::cps_clean('/[^a-z0-9]/mi', '', $args['wrap'] , 'div');
            $wrap_id = CustomPageSitemap::cps_clean('/[^a-z0-9_-]/mi', '', $args['wrap_id'] , '', ' id="');
            $wrap_class = CustomPageSitemap::cps_clean('/[^a-z0-9_-]/mi', '', $args['wrap_class'] , '', ' class="');

                $title_wrap = preg_replace('/[^a-z0-9]/mi', '', $args['title_wrap']);
                if($args['title']){
					$title = CustomPageSitemap::title_identifier($args['title']);
					if(strtolower(gettype($title))=='array'):
						$title = ($title['a']) ? $title['a'] : 'Archives' ;
					else:
						$title =  'Archives';
					endif;
				}
				else{
					$title='Archives';
				}
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
        ?>
            <ul cps-menu=true order="<?php echo $args['order']; ?>" class="<?php echo CustomPageSitemap::cps_clean('/[^a-z0-9_-]/mi', '', $args['menu_class'], ''); ?>" <?php echo CustomPageSitemap::cps_clean('/[^a-z0-9_-]/mi', '', $args['menu_id'], '', ' id="'); ?>>
                <?php 
				wp_get_archives(
					[
						'type'      	  => $args['archive_type'],
						'format' 		  => 'html',    // This is actually a default setting
						'limit' 		  => 12,
						'before'		  => $args['icon'],
						'order'			  => $args['order'],
						'year'            => get_query_var(  $args['year'] ),
						'monthnum'        => get_query_var(  $args['monthnum'] ),
						'day'             => get_query_var(  $args['day'] ),
						'w'               => get_query_var(  $args['w'] )
					]
				);
				?>
				<?php echo (strpos($args['insert_link'],'a:')!==false) ? "<li>".$args['icon'].CustomPageSitemap::get_insert_link($args['insert_link'])['a']."</li>" : ''; ?>
            </ul>
        <?php
            echo "</{$wrap}>";
        endif;
    }
endif;