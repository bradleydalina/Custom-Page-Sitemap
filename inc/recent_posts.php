<?php

defined( 'ABSPATH' ) or die( 'Why?' );

if(!function_exists('cps_recent_posts')):
    add_action('', 'cps_recent_posts');
    function cps_recent_posts($content="", $args=[])
    {
        if(strpos($args['links'], 'r')!== false):
            $wrap = CustomPageSitemap::cps_clean('/[^a-z0-9]/mi', '', $args['wrap'] , 'div');
			$wrap_id = CustomPageSitemap::cps_clean('/[^a-z0-9_-]/mi', '', $args['wrap_id'] , '', ' id="');
            $wrap_class = CustomPageSitemap::cps_clean('/[^a-z0-9_-]/mi', '', $args['wrap_class'] , '', ' class="');

                $title_wrap = preg_replace('/[^a-z0-9]/mi', '', $args['title_wrap']);
                if($args['title']){
					$title = CustomPageSitemap::title_identifier($args['title']);
					if(strtolower(gettype($title))=='array'):
						$title = ($title['r']) ? $title['r'] : 'Recent Posts' ;
					else:
						$title='Recent Posts';
					endif;
				}
				else{
					$title='Recent Posts';
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
						'type'      => 'postbypost',//$args['recent_type'],//postbypost',
						'post_type' => $args['recent_posttype'],
						'limit'     => $args['recent_postlimit'],
						'format'    => 'html',    // This is actually a default setting						
						'before'    => $args['icon'],
						'order'		=> $args['order']
					]
				);
				
				?>
				<?php echo (strpos($args['insert_link'],'r:')!==false) ? CustomPageSitemap::get_insert_link($args['insert_link'],$args['icon'])['r'] : ''; ?>
            </ul>
        <?php
            echo "</{$wrap}>";

        endif;
    }
endif;
