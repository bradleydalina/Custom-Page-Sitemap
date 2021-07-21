<?php

defined( 'ABSPATH' ) or die( 'Why?' );

if(!function_exists('cps_categories')):
    add_action('', 'cps_categories');
    function cps_categories($content="",$args=[])
    {
        if(strpos($args['links'], 'c')!== false):

            $inherit_usage = (preg_match_all('/1/im', '', $args['links'])==1);

            $wrap = CustomPageSitemap::cps_clean('/[^a-z0-9]/mi', '', $args['wrap'] , 'div');
			$wrap_id = CustomPageSitemap::cps_clean('/[^a-z0-9_-]/mi', '', $args['wrap_id'] , '', ' id="');
            $wrap_class = CustomPageSitemap::cps_clean('/[^a-z0-9_-]/mi', '', $args['wrap_class'] , '', ' class="');

                $title_wrap = preg_replace('/[^a-z0-9]/mi', '', $args['title_wrap']);
                if($args['title']){
					$title = CustomPageSitemap::title_identifier($args['title']);
					if(strtolower(gettype($title))=='array'):
						$title = ($title['c']) ? $title['c'] : 'Categories' ;
					else:
						$title =  'Categories';
					endif;
				}
				else{
					$title='Categories';
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
            <ul cps-menu=true order="<?php echo esc_html($args['order']); ?>" <?php echo CustomPageSitemap::cps_clean('/[^a-z0-9_-]/mi', '', $args['menu_class'], '','class="cps-menu '); ?> <?php echo CustomPageSitemap::cps_clean('/[^a-z0-9_-]/mi', '', $args['menu_id'], '', ' id="'); ?>>
             <?php  
				ob_start();
				wp_list_categories( array(
                    'title_li'  => $title,
					'depth' => $args['categories_depth'],
                    'style'       => $args['icon'],
					'orderby'	=>$args['orderby'],
					'include'	=>$args['include'],
					'child_of'  =>$args['child_of'],
					'exclude'	=>$args['exclude'],
					'exclude_tree'=>$args['exclude_tree'],
					'current_category'=>$args['current_category'],
					'hide_title_if_empty'=>$args['hide_title_if_empty'],
					'use_desc_for_title'=>$args['use_desc_for_title'],
					'taxonomy'=>$args['taxonomy']
                )); 
				$categories= ob_get_contents();
				ob_end_clean();
				preg_match_all('/<a\s[^>].*<\/a>/smiU', $categories, $matches);
				if(count($matches)>0){
					$all_match = $matches[0];
					for($i=0; $i < count($all_match); $i++){
						$categories= str_replace($all_match[$i] ,sprintf("<li>%s</li>", $all_match[$i]), $categories);
					}
					echo str_replace("<br />", (strpos($args['insert_link'],'c:')!==false) ? CustomPageSitemap::get_insert_link($args['insert_link'],$args['icon'])['c']: '', $categories);
				}	
				?>
            </ul>
        <?php
            echo "</{$wrap}>";

        endif;
    }
endif;

if(!function_exists('cps_list_categories')):    
	function cps_list_categories( $output, $args ) {
			$pattern = '/(<a.*?>)/';
			$replacement = '$1'.$args['style'];
			return preg_replace( $pattern, $replacement, $output );
	}
	add_filter( 'wp_list_categories', 'cps_list_categories', 10, 2 );
endif;	
