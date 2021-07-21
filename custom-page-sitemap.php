<?php
/**
 * Plugin Name:       Custom Page Sitemap
 * Plugin URI:        http://wordpress.org/plugins/custom-page-sitemap/
 * Description:       This plugin generate a custom sitemap with search field, page, categories, archives and recent posts links option.
 * Version:           1.0
 * Requires at least: 4.6
 * Tested up to:      5.8
 * Stable tag:        1.0
 * Requires PHP:      7.2
 * Author:            Bradley B. Dalina
 * Author URI:        https://www.bradley-dalina.tk/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       custom-page-sitemap
 */

/**
 * Restrict Direct Access
 */
defined( 'ABSPATH' ) or die( 'You\'re in the wrong way of access...' );
/**
 * Inlcudes Required Files
 */
if(!function_exists('get_plugin_data')) require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class Custom_Page_Sitemap{
    /**
    * Plugin main class
    * @since 1.0
    */
	public static $insert_link;
    public function __construct(){
        /**
        * Call plugin initialization
        */
        $this->init();		
    }

    public static function get($string=''){
        /**
        * Plugin initialization
        * @param  string url part string
        * @param  integer $attachment_id
        * @param boolean file
        * @return object
        */
        $string = trim($string,'/');
        $defination = (object) array();
        /**
        * Define Plugin Domain
        */
        $domain = pathinfo(__FILE__)['filename'];
        
        /**
        * Define Constant Directory Paths
        */
        $realpath = trailingslashit(realpath(plugin_dir_path(__FILE__)));
        $abspath = trailingslashit( plugin_dir_url(__FILE__) );
        $defination->realpath = $realpath.$string;
        $defination->abspath = $abspath.$string;
        /**
        * Plugin Info
        */
        $info = get_plugin_data( realpath($realpath)."/{$domain}.php" );
        $defination->name = $info['Name'];
        $defination->uri = $info['PluginURI'];
        $defination->version = $info['Version'];
        $defination->description = $info['Description'];
		
		
		$defination->text_domain = $info['TextDomain'];
		
		$str_domain="";
		foreach(explode('-', $info['TextDomain']) as $index ){
			$str_domain.=substr($index, 0, 1);
		};
        $defination->prefix = $str_domain."_";
		
        return $defination;
    }
    public function init(){
        /**
        * Plugin initialization
        * @return void
        */				
		add_action( "admin_menu", array($this,"registerMenu"));
		add_action('', [$this,'cps_clean']);
		add_action('', [$this,'cps_escape']);

        if(get_option('cps_shortcode')!==false){
            add_shortcode(get_option('cps_shortcode'), array($this, 'c_sitemap'));
			add_shortcode('custom_sitemap', array($this, 'c_sitemap'));
        }		
        add_shortcode('c_sitemap', array($this, 'c_sitemap'));
		
        add_filter('widget_text', 'do_shortcode');
        add_shortcode( 'shortcodetag', '__return_false' );               
        add_filter( 'no_texturize_shortcodes', array($this,'exempt_wptexturize') );		
        add_action( 'admin_head', array( __CLASS__,'cps_filter_screen_tab'));

		add_filter( 'get_archives_link', [$this, 'wpse_get_archives_link'], 10, 6 );
		remove_filter( 'get_archives_link', [$this,'wpse_get_archives_link'], 10, 6 );
		
		//add_action( 'wp', array($this,'detect_usage') );
		//add_action( 'save_post', array($this,'detect_usage'));
		
        add_action( 'admin_head', array($this, 'csstyle') );
		
		add_action( 'admin_enqueue_scripts', array($this,"admin_jscripts") );
		add_action( 'admin_enqueue_scripts', array($this,'cps_enqueue_admin_script') );
		
		wp_enqueue_script('cps-jscript', self::get('cps-jscript.js')->abspath, null, 1.0, true);
		
		add_action( 'wp_ajax_cps_formrequest', array($this,"cps_handle_form") );
        add_action( 'wp_ajax_nopriv_cps_formequest', array($this,"cps_handle_form") );
		
		add_action( 'wp_ajax_cps_formdemo', array($this,"cps_handle_demoform") );
        add_action( 'wp_ajax_nopriv_formdemo', array($this,"cps_handle_demoform") );
		
        register_activation_hook(  __FILE__, [__CLASS__,'activate'] );
    	register_uninstall_hook(  __FILE__ , [__CLASS__,'uninstall'] );
				
		require_once self::get('/inc/search.php')->realpath;
        require_once self::get('/inc/pages.php')->realpath;
        require_once self::get('/inc/categories.php')->realpath;
        require_once self::get('/inc/archives.php')->realpath;
        require_once self::get('/inc/recent_posts.php')->realpath;
    }
	public function cps_enqueue_admin_script( ) {
		wp_enqueue_script('cps-jscript', self::get('cps-jscript.js')->abspath, array(), 1.0, true);	
	}
	
	public static function page_insertlink($items, $args){
		$items.= self::$insert_link;
		return $items;		
	}
	
	public static function cps_filter_screen_tab() {
        $screen = get_current_screen();
       if ( 'pages_page_custom-page-sitemap' != $screen->id )
            return;

        $help_args = [
                        'id'      => 'cps-plugin-issues',
                        'title'   => __('Plugin Issues'),
                        'content' =>
                                '<h3>'. __( 'Plugin Issues' ) . '</h3>' .
                                '<p>' . __( 'If by any chance you encountered plugin issues, please submit a brief report and provide a screenshot if possible so that I may be able to include them in the next plugin update, thank you.') . '</p>'
                     ];
        $user_support_args = array(
                        'id'      => 'cps-plugin-user-support',
                        'title'   => __('User Support'),
                        'content' =>
                                '<h3>' . __('User Support') . '</h3>' .
                                '<p>'  . __('For as long as there are users who found this plugin helpful, by doing there part thru feedback and reviews the development support will continue.') . '</p>'
                        );
        $development_support_args = array(
                        'id'      => 'cps-plugin-development-support',
                        'title'   => __('Development Support'),
                        'content' =>
                                '<h3>' . __('Development Support'). '</h3>' .
                                '<p>'  . __('Your positive reviews and ratings can be very helpful to me, it would also be nice if you can <a rel="referrer" target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=QX8K5XTVBGV42&amp;source=url">donate</a> any amount for funding.') . '</p>'
                        );

        $screen->add_help_tab( $help_args );
        $screen->add_help_tab( $user_support_args );
        $screen->add_help_tab( $development_support_args );
    	$screen->set_help_sidebar(
    		'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
    		'<p>' . __( '<a target="_blank" href="http://wordpress.org/plugins/custom-page-sitemap/">Documentation Page</a>' ) . '</p>' .
    		'<p>' . __( '<a href="mailto:bradleydalina@gmail.com">bradleydalina@gmail.com</a>' ) . '</p>'
    	);
    }
	
	/**
	 * Helper functions	
	 */
	public static function cps_escape($string, $start, $end){
        $content = $string;
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strrpos($string, $end, $ini) - $ini;
        $center_string = substr($string, $ini, $len);
        $raw_center =$center_string;

        $new =preg_replace('/>/', '&#62;', $center_string);
        $new =preg_replace('/&gt;/', '&#62;', $new);
        $new =preg_replace('/&gt/', '&#62;', $new);
        $new =preg_replace('/</', '&#60;', $new);
        $new =preg_replace('/&lt;/', '&#60;', $new);
        $new =preg_replace('/&lt/', '&#60;', $new);
        $new =preg_replace('/\]/', '&#93;', $new);
        $new =preg_replace('/\[/', '&#91;', $new);
        $new =str_replace([ "[" , "]" ] , [ "&#91;" , "&#93;" ] , $new);

        return str_replace($raw_center, $new, $content);
    }
	public static function cps_clean($pattern = '/[^a-z0-9-_]/mi', $replacement='', $subject, $default='', $attribute=''){
        $result = (strlen(trim(preg_replace($pattern, $replacement, $subject))) > 0) ? preg_replace($pattern, $replacement, $subject) : $default;
        if($result && $attribute){
           return $attribute.$result.'"';
        }
        return $result;
    }
	public function wpse_get_archives_link(  $link_html, $url, $text, $format, $before, $after ){
		if( 'html' === $format )
			 $link_html = "\t<li><a href='$url'><span>$before</span>$text</a>$after</li>\n";
		return $link_html;
	}	

	public static function title_identifier($str="", $link="p"){	
		$array_title= [];
		if($str==""){
			return $str;
		}
		if(strpos(",",$str)!==FALSE){
			$arr_str = explode(',', $str);
			$arr_link = str_split($link);
			for($i=0; $i < count($arr_link); $i++){
				$array_title[$arr_link[$i]]=CustomPageSitemap::cps_clean('/[^a-z0-9-_.()]/mi', '',$arr_str[$i]);
			}
			return $array_title;
		}
		return $str;
	}
	public static function activate(){
        /**
        * Plugin activate hook
        * @return void
        */
		$deprecated = false;
        $autoload = null;
		
		if ( is_multisite() ) {
            /*
            *   Basic Settings
            */
            if ( get_network_option( null, 'cps_title_wrap' ) === false ){ add_network_option( null, 'cps_title_wrap', 'h3',$deprecated, $autoload );}
            if ( get_network_option( null, 'cps_title_id' ) === false ){ add_network_option( null, 'cps_title_id', '',$deprecated, $autoload );}
            if ( get_network_option( null, 'cps_title_class' ) === false ){ add_network_option( null, 'cps_title_class', '',$deprecated, $autoload );}
            if ( get_network_option( null, 'cps_menu_id' ) === false ){ add_network_option( null, 'cps_menu_id', '',$deprecated, $autoload );}			
			if ( get_network_option( null, 'cps_menu_class' ) === false ){ add_network_option( null, 'cps_menu_class', '',$deprecated, $autoload );}
            if ( get_network_option( null, 'cps_icon' ) === false ){ add_network_option( null, 'cps_icon', '',$deprecated, $autoload );}			
			if ( get_network_option( null, 'cps_order' ) === false ){ add_network_option( null, 'cps_order', 'ASC',$deprecated, $autoload );}
            if ( get_network_option( null, 'cps_orderby' ) === false ){ add_network_option( null, 'cps_orderby', 'name',$deprecated, $autoload );}
			
			if ( get_network_option( null, 'cps_archive_type' ) === false ){ add_network_option( null, 'cps_archive_type', 'monthly',$deprecated, $autoload );}		
			if ( get_network_option( null, 'cps_archive_limit' ) === false ){ add_network_option( null, 'cps_archive_limit', 12,$deprecated, $autoload );}			
			if ( get_network_option( null, 'cps_recent_posttype' ) === false ){ add_network_option( null, 'cps_recent_posttype', 'post',$deprecated, $autoload );}
			if ( get_network_option( null, 'cps_recent_postlimit' ) === false ){ add_network_option( null, 'cps_recent_postlimit', '',$deprecated, $autoload );}
            if ( get_network_option( null, 'cps_shortcode' ) === false ){ add_network_option( null, 'cps_shortcode','c_sitemap',$deprecated, $autoload );}
            
		}
        		
        if ( get_option( 'cps_title_wrap' ) === false ){ add_option( 'cps_title_wrap', 'h3',$deprecated, $autoload );}
        if ( get_option( 'cps_title_id' ) === false ){ add_option( 'cps_title_id', '' ,$deprecated, $autoload );}
        if ( get_option( 'cps_title_class' ) === false ){ add_option( 'cps_title_class', '' ,$deprecated, $autoload );}
		
        if ( get_option( 'cps_menu_id' ) === false ){ add_option( 'cps_menu_id', '',$deprecated, $autoload );}        
        if ( get_option( 'cps_menu_class' ) === false ) {	add_option( 'cps_menu_class', '' ,$deprecated, $autoload );}
		
        if ( get_option( 'cps_icon' ) === false ) {	add_option( 'cps_icon', '' ,$deprecated, $autoload );}
        if ( get_option( 'cps_order' ) === false ) {	add_option( 'cps_order', 'ASC' ,$deprecated, $autoload );}
        if ( get_option( 'cps_orderby' ) === false ) {	add_option( 'cps_orderby', 'name' ,$deprecated, $autoload );}
        
		if ( get_option( 'cps_archive_type' ) === false ) {	add_option( 'cps_archive_type', 'monthly' ,$deprecated, $autoload );}
		if ( get_option( 'cps_archive_limit' ) === false ) {	add_option( 'cps_archive_limit', 12 ,$deprecated, $autoload );}
        if ( get_option( 'cps_recent_postlimit' ) === false ) {	add_option( 'cps_recent_postlimit', '' ,$deprecated, $autoload );}
		
		if ( get_option( 'cps_recent_posttype' ) === false ) {	add_option( 'cps_recent_posttype', 'post' ,$deprecated, $autoload );}
        if ( get_option( 'cps_shortcode' ) === false ) {	add_option( 'cps_shortcode', 'c_sitemap' ,$deprecated, $autoload );}
    }
    public static function uninstall(){
        /**
        * Plugin uninstall hook
        * @return void
        */
		
		 if ( is_multisite() ) {
            delete_network_option( null, 'cps_title_wrap' );
            delete_network_option( null, 'cps_title_id' );
            delete_network_option( null, 'cps_title_class' );
			
            delete_network_option( null, 'cps_menu_id' );
            delete_network_option( null, 'cps_menu_class' );
			
            delete_network_option( null, 'cps_icon' );
            delete_network_option( null, 'cps_order' );
            delete_network_option( null, 'cps_orderby' );
			
            delete_network_option( null, 'cps_archive_type' );
			delete_network_option( null, 'cps_archive_limit' );
			
			delete_network_option( null, 'cps_recent_posttype' );
            delete_network_option( null, 'cps_recent_postlimit' );
			
            delete_network_option( null, 'cps_shortcode' );
		}
        /**
        * Delete database entries
        *
        * @since		1.0
        */
        delete_option( 'cps_title_wrap' );
        delete_option( 'cps_title_id' );
        delete_option( 'cps_title_class' );
		
        delete_option( 'cps_menu_id' );        
        delete_option( 'cps_menu_class' );
		
        delete_option( 'cps_icon' );
        delete_option( 'cps_order' );
        delete_option( 'cps_orderby' );
		
        delete_option( 'cps_archive_type' );
		delete_option( 'cps_archive_limit' );
		
		delete_option('cps_recent_posttype');
        delete_option( 'cps_recent_postlimit' );
		
        delete_option( 'cps_shortcode' );
    }   
	public function registerMenu(){
        add_submenu_page(
            "edit.php?post_type=page",
            "Custom Page Sitemap",
            "Custom Page Sitemap",
            "manage_options",
            "custom-page-sitemap",
            array($this,"pageSettings")
        );
    }
	public function removeMenu(){
        remove_submenu_page( array($this,'custom-page-sitemap') );
    }   
	public function exempt_wptexturize( $shortcodes ){
        $shortcodes[] = 'c_sitemap';
        if(get_option('t_alias')!==false){
            $shortcodes[] = get_option('t_alias');
        }
        return $shortcodes;
    }	
	public static function cps_authorize(){
        /**
        * Check User Capability
        */
        if ( ! is_user_logged_in() ) {
            add_action( 'admin_menu', array($this,'itm_remove_menu') );
            wp_die( ( '<span class="error notice is-dismissible"><p>You do not have sufficient permissions to access this page.</p></span>' ) );
        }
        if ( !current_user_can( 'manage_options' ) ) {
            add_action( 'admin_menu', array($this,'itm_remove_menu') );
            wp_die( ( '<span class="error notice is-dismissible"><p>You do not have sufficient permissions to access this page.</p></span>' ) );
        }
        if ( ! is_admin() ) {
            add_action( 'admin_menu', array($this,'itm_remove_menu') );
            wp_die( ( '<span class="error notice is-dismissible"><p>You do not have sufficient permissions to access this page. Please contact your administrator.</p></span>' ) );
        }
    }
	public static function clean_input($str=""){
		if(empty($str)){
			return;
		}			
		return preg_replace('/[^a-zA-Z0-9_-]/','', $str);
	}
	public static function cps_handle_form() {
		/**
        * Verify user permission and access level
        */
        self::cps_authorize();
        /**
        * Verify nonce submitted within the form
        */
        if ( ! empty( $_POST ) && !isset( $_POST['cps_plugin_nonce'] ) || !wp_verify_nonce( $_POST['cps_plugin_nonce'], 'cps_plugin_action' ) || !check_ajax_referer( 'cps_plugin_action', 'cps_plugin_nonce' )) {
            ?>
			<div id="message" class="error notice is-dismissible">
				<p>Sorry, your nonce was incorrect. Please try again.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
			</div>
            <?php
			exit;
        }else{
			
			/**
            * Validate Post values
            */
            $cps_title_wrap =  preg_replace('/[^a-zA-Z0-9]/','',(isset($_POST['cps_title_wrap']) ? $_POST['cps_title_wrap'] : 'h3'));
			
            $cps_title_id =  self::clean_input((isset($_POST['cps_title_id']) ? $_POST['cps_title_id'] : ''));
			$cps_title_class =  self::clean_input((isset($_POST['cps_title_class']) ? $_POST['cps_title_class'] : ''));
			
            $cps_menu_id =  self::clean_input((isset($_POST['cps_menu_id']) ? $_POST['cps_menu_id'] : ''));
            $cps_menu_class =  self::clean_input((isset($_POST['cps_menu_class']) ? $_POST['cps_menu_class'] : ''));
			
            $cps_icon =  (isset($_POST['cps_icon']) ? $_POST['cps_icon'] : '');
			$cps_icon = preg_replace('/[^a-zA-Z0-9&;_]/','', $cps_icon);
			
            $cps_order =  preg_replace('/[^A-Z]/','',(isset($_POST['cps_order']) ? $_POST['cps_order'] : 'ASC'));
            $cps_orderby= self::clean_input((isset($_POST['cps_orderby']) ? $_POST['cps_orderby'] : 'name'));

			
			$cps_archive_type= (isset($_POST['cps_archive_type']) ? $_POST['cps_archive_type'] : 'monthly');
            $cps_archive_limit= preg_replace('/[^0-9\s]/','',(isset($_POST['cps_archive_limit']) ? $_POST['cps_archive_limit'] : 12));
			
			$cps_archive_limit = ($cps_archive_limit == 0) ? "" : $cps_archive_limit;
		
			$cps_recent_posttype=self::clean_input((isset($_POST['cps_recent_posttype']) ? $_POST['cps_recent_posttype'] : ''));
            $cps_recent_postlimit =  preg_replace('/[^0-9\s]/','',(isset($_POST['cps_recent_postlimit']) ? $_POST['cps_recent_postlimit'] : ''));
			
			$cps_recent_postlimit= ($cps_recent_postlimit == 0) ? "" : $cps_recent_postlimit;

            $cps_shortcode= self::clean_input((isset($_POST['cps_shortcode']) ? $_POST['cps_shortcode'] : 'c_sitemap' ));


            /**
            * Update Plugin Options
            */
            if ( is_multisite() ) {
                update_network_option( null, 'cps_title_wrap', $cps_title_wrap );
                update_network_option( null, 'cps_title_id', $cps_title_id );
                update_network_option( null, 'cps_title_class', $cps_title_class);
				
                update_network_option( null, 'cps_menu_id', $cps_menu_id );
                update_network_option( null, 'cps_menu_class', $cps_menu_class );
				
                update_network_option( null, 'cps_icon', $cps_icon );
                update_network_option( null, 'cps_order', $cps_order );
                update_network_option( null, 'cps_orderby', $cps_orderby );

				update_network_option( null, 'cps_archive_type', $cps_archive_type);
                update_network_option( null, 'cps_archive_limit', $cps_archive_limit);
				
				update_network_option( null, 'cps_recent_posttype', $cps_recent_posttype );
                update_network_option( null, 'cps_recent_postlimit', $cps_recent_postlimit );
				
                update_network_option( null, 'cps_shortcode', $cps_shortcode );				
            }

            update_option( 'cps_title_wrap', $cps_title_wrap );
            update_option( 'cps_title_id', $cps_title_id );
            update_option( 'cps_title_class', $cps_title_class);
			
            update_option( 'cps_menu_id', $cps_menu_id );
			update_option( 'cps_menu_class', $cps_menu_class );
			
			update_option( 'cps_icon', $cps_icon );
			update_option( 'cps_order', $cps_order );
			update_option( 'cps_orderby', $cps_orderby );

			update_option( 'cps_archive_type', $cps_archive_type);
			update_option( 'cps_archive_limit', $cps_archive_limit);
			
			update_option( 'cps_recent_posttype', $cps_recent_posttype );
			update_option( 'cps_recent_postlimit', $cps_recent_postlimit );
			
			update_option( 'cps_shortcode', $cps_shortcode );		
            ?>
			<div id="message" class="updated notice is-dismissible">
				<p>Your plugin settings were saved!</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
			</div>
            <?php
			exit;
        }		
	}
	
	public static function cps_handle_demoform() {		
        /**
        * Verify nonce submitted within the form
        */
		if(check_ajax_referer( 'cps_livedemo_action', 'cps_livedemo_nonce' )){
			if ( ! empty( $_POST ) && !isset( $_POST['cps_livedemo_nonce'] ) || !wp_verify_nonce( $_POST['cps_livedemo_nonce'], 'cps_livedemo_action' )) {
				?>
				<div id="message" class="error notice is-dismissible">
					<p>Sorry, your nonce was incorrect. Please try again.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
				</div>
				<?php
				exit;
			}else{
				
				/**
				* Validate Post values
				*/
				$cps_live_demo =  stripslashes((isset($_POST['cps_live_demo']) ? $_POST['cps_live_demo'] : '[c_sitemap]'));
				if ( is_user_logged_in() ) {
					if ( current_user_can( 'manage_options' ) ) {
						if ( is_admin() ) {
							if ( is_multisite() ) {
								update_network_option( null, 'cps_live_demo', $cps_live_demo );
							}
							update_option( 'cps_live_demo', $cps_live_demo );
						}				
					}
				}
				
				echo do_shortcode($cps_live_demo);
				exit;
			}	
		}		
	}
	public function admin_jscripts(){
        /*
        * Javascript registration
        * @param string $page
        * @return void
        */
        ?>
        <script id="cps-adminscript">
            (function(w, d){
				w.addEventListener("load", function(e){
					
					var getallnav = d.querySelectorAll('a.cps-navlink');
					for(let i =0; i < getallnav.length; i++){
						getallnav[i].addEventListener("click", function(e){
							e.preventDefault();
							getallnav.forEach(function(nav){
								if(nav !=e.target){
									nav.classList.remove('active');
									nav.classList.add('inactive');
									d.getElementById(nav.getAttribute("href").replace(/\#/,'')).classList.remove('show');
									d.getElementById(nav.getAttribute("href").replace(/\#/,'')).classList.add('hide');
								}
								else{
									nav.classList.add("active");
									nav.classList.remove("inactive");
									
									d.getElementById(nav.getAttribute("href").replace(/\#/,'')).classList.add('show');
									d.getElementById(nav.getAttribute("href").replace(/\#/,'')).classList.remove('hide');
								}										
							});
						});
					}
					
					function notice_clear(notice){
						if(notice){
							setTimeout(function(){
								notice.remove();
							}, 3000);
						}
					}
					//let cdata = JSON.stringify(fdata);
					
					//console.warn(contactData);
					//var req = new XMLHttpRequest();
					//req.send(data);
					if(d.getElementById('cps-settings-submit')){
						d.getElementById('cps-settings-submit').addEventListener("click", function(e){
							var form = document.getElementById('cps-settings-form');
							data = new FormData(form);	
							
							let fdata = {};
							for(let pair of data.entries()) {
								fdata[pair[0]] = pair[1]; 
							}
							var url = Object.keys(fdata).map(function(k) {
								return encodeURIComponent(k) + '=' + encodeURIComponent(fdata[k])
							}).join('&');
							e.preventDefault();
							const xhttp = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");

							xhttp.open('post', "<?php echo admin_url( 'admin-ajax.php' ); ?>" , true);
								xhttp.onreadystatechange = function() {
									if (this.readyState == 4 && this.status == 200) {
										let hold = d.createElement('div');
										hold.innerHTML = this.responseText;
										insertAfter(hold,d.querySelector('#next_message'))
									   
										notice_clear(hold);
										
										if(d.querySelector('button.notice-dismiss')){
											d.querySelector('button.notice-dismiss').addEventListener("click", function(e){
												hold.remove();
											});
										}
										data=null;
										fdata={};
										url=null;
									}
								};
							xhttp.setRequestHeader("Content-type", 'application/x-www-form-urlencoded; charset=UTF-8');
							xhttp.send(url);
						});
					}
					if(d.getElementById('cps-livedemo-submit')){
						d.getElementById('cps-livedemo-submit').addEventListener("click", function(e){
							var demoform = document.querySelector('form#live-demo');
							var all = demoform.getElementsByTagName('INPUT');
							let fdata2 = {};
							for (var i = -1, l = all.length; ++i < l;) {
								console.log(all[i].value);
								fdata2[all[i].name] = all[i].value;
							}
							e.preventDefault();
							const xhttp = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
							xhttp.open('post', "<?php echo admin_url( 'admin-ajax.php' ); ?>" , true);
								xhttp.onreadystatechange = function() {
									if (this.readyState == 4 && this.status == 200) {									
										d.getElementById('cps-livedemo-view').innerHTML = this.responseText;
									}
								};
							xhttp.setRequestHeader("Content-type", 'application/x-www-form-urlencoded; charset=UTF-8');
							xhttp.send(new URLSearchParams(fdata2).toString());
						});
					}
					w.insertAfter = (newNode, referenceNode)=>{
						referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
					}
					w.insertBefore = (newNode, referenceNode)=>{
						referenceNode.parentNode.insertBefore(newNode, referenceNode);
					}
					w.appendElem = (newElem, referenceNode)=>{ //'beforebegin', 'afterbegin', 'beforeend', 'afterend'
						referenceNode.insertAdjacentElement(position, newElem);
					}
				});
			})(window, document);    
        </script>
        <?php
    }
    public function csstyle(){
        global $pagenow;
    	if ($pagenow === 'edit.php' || $GLOBALS['hook_suffix'] === 'pages_page_custom-page-sitemap') {
            ?>			
			<script type="text/javascript" language="javascript">
				var cps_order = "<?php echo (is_multisite() ? (get_network_option(null, 'cps_order')!==false ? get_network_option(null, 'cps_order') : ''): (get_option('cps_order')!==false ? get_option('cps_order') : '') ); ?>";
			</script>
            <style type="text/css" rel="stylesheet">                
			input,
			input[type='text'],
			input[type='number'],
			input[type='email'],
			input[type='url'],
			select, textarea{ border: solid 1px #bbb; resize:none; outline:0; padding:3.5px 10px;line-height: 22px;}
			input:focus,
			input[type='text']:focus,
			input[type='number']:focus,
			input[type='email']:focus,
			input[type='url']:focus,
			select:focus, textarea:focus{ border: solid 1px #999; resize:none; outline:0; box-shadow: unset; }
			input[type='checkbox'], input[type='color']{margin-right:10px;}

			div#message span.notice{display: flex;align-items: center;}
			div#message span.notice p {margin: 6px 0;}
			div#message span.notice.success{border-left-color: #46b450;}
			div#message span.notice.error{border-left-color: #ff0000;;}
			
			.t-section{padding:20px 30px;margin-right:20px;display:flex;}
			.t-container{width: 100%;} 
			#t-main .t-container{width: 100%;display:flex; }
			.t-no-toppad{padding-top:0px !important;}
			.t-no-bottompad{padding-bottom:0px !important;}
			.t-tab{text-decoration: none; display:inline-block; padding: 20px 10px; outline: 0px; cursor:pointer; color:#fff;  background-color: #7d9bb5; border: 0px;}
			.t-tab:hover{color:#103042;}
			.t-tab:active,.t-tab:focus {outline:0; box-shadow: unset;}
			.t-tab.t-active-tab{background-color:#ddd; color:#000; font-weight:bold;}

			.t-tabs b{color:#ff0000;}
			.t-tabs strong{color:#426ae0;}

			.t-formgroup{display:flex; flex-direction: column; width:100%; margin: 10px 0;}
			.t-formgroup.t-inline {flex-direction: row-reverse;align-items: center;justify-content: flex-end;}
			.t-formgroup label{margin-bottom:5px; font-weight:500;}
			.t-hide{display:none !important;}
			.t-show{display:block !important;}
			.t-mtop5{margin-top:5px !important;}

			header#t-header{background-color:#103042;color:#fff;margin-top:20px; width: calc(100% - 80px);}
			header#t-header h1{color:#fff; font-weight:500; display:flex; align-items: center; width:100%; justify-content: space-between;}
			header#t-header h1 span {display: flex;align-items: center;}
			header#t-header h1 button {margin-left: 10px;}
			header#t-header h1 small{font-size:0.9rem; justify-self: flex-end; }
			nav#t-nav.t-section{background-color: #7d9bb5; padding: 0 30px;}
			nav#t-nav .t-container ul{display:flex; align-items: center; justify-content: flex-start;}
			nav#t-nav .t-container ul li{margin: 0;}
			nav#t-nav a{color:#fff; text-decoration: none; margin-right:10px; padding: 10px 0; display:block;}
			nav#t-tabcontrols {background-color: #d0d0d0;}
			main#t-main{background-color:#99bbce;;}
			main#t-main h2{font-size:1.6em;}
			main#t-main select{width:25em;}

			pre.t-code { margin: 0; padding: 20px;background-color: #f1f1f1;}
			div.t-demo { padding: 20px;background-color: #e6eef1; max-height:400px; overflow: auto;}
			div.t-demo ul, div.t-demo h3:first-child{padding-top:0px; margin-top:0px;}
			small.t-guide{display:block; padding: 5px 0; font-size:.7.5rem;}
			small.t-guide b{font-weight:700; color:#ff0000;}

			.media-widget-buttons .button:first-child {margin-right: 0px;}
			.media-widget-buttons .selected{display:inline-block;}
			.media-widget-buttons .not-selected{display:none;}
			.media-widget-preview .placeholder {background: none;border:dashed 1px #888;}
			.form-table hr{ border-top: solid 1px #b7b2b2;}
			.pad-fluid{margin: 0 -15px;}
			.pad-vertical{padding: 15px 0;}
			.t-float-right{float:right;}
			.t-message-box .notice, .t-message-box .error{
				margin: 15px 2px;
			}
			
			
			aside.cps-menu{
				display: flex;
				flex-direction: column;
				width:20%;
			}
			aside.cps-menu a {
				display: block;
				padding: 10px;
				background-color: #b6d1e0;
				color: #000;
				border: solid 1px #84a3b5;
				margin-bottom: 1px;
				cursor:pointer;
				text-decoration:none;
			}
			aside.cps-menu a:hover {
				background-color:#c7e1ef;
			}
			aside.cps-menu a.active {background-color:#fff;}
			div.cps-content{
				padding: 10px;
				background-color: #fff;
				width: 80%;
				border: solid 1px #84a3b5;
				margin-bottom:1px;
			}
			
			.cps-content .cps-box.hide{display:none;}
			.cps-content .cps-box.show{display:block;}
			
			.cps-formgroup {
				display: flex;
				width: 100%;
				margin: 10px 0 0 0;
				flex-direction: column;
			}
			.cps-formgroup label {
				margin-bottom: 5px;
				font-weight: 500;
			}
			.cps-formgroup input, .cps-formgroup select
			{max-width: 300px;}
			.notice, div.error, div.updated{margin:0;}
			a.paypal-donation{display: inline-block;
				background-color: transparent;
				border: 0;
				padding: 0;
				padding-right: 2px;
				}
			a.paypal-donation img{ width: 100%;}
            </style>
            <?php
        }
    }

	public function pageSettings($hook_suffix){
		?>
		<header id="t-header" class="t-section">
			<div class="t-container">
				<h1><span><?php echo self::get()->name; ?> </span><small>Version ( <?php print_r(self::get()->version); ?> ) </small></h1>
				<p><?php echo self::get()->description; ?></p>
			</div>
		</header>
		<main id="t-main" class="t-section">
			<div class="t-container">
				<aside class="cps-menu">
					<a href="#usage-content" id="usage-menu" class="cps-navlink active">Shortcode Usage</a>
					<a href="#settings-content" id="settings-menu" class="cps-navlink inactive">Settings</a>
					<a href="#arguments-content" id="arguments-menu" class="cps-navlink inactive">Full Arguments</a>
					<a href="#demo-content" id="demo-menu" class="cps-navlink inactive">Live Demo</a>
					<a href="#support-content" id="support-menu" class="cps-navlink inactive">Support</a>
					<a class="paypal-donation" style="display: inline-block;" rel="referrer" target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=QX8K5XTVBGV42&amp;source=url"><img style="border:solid 1px #ddd;" src="<?php echo self::get('btn_donateCC_LG.gif')->abspath; ?>" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button"></a>
				</aside>
				<div class="cps-content">
					<div id="usage-content" class="cps-box show">
						<ol style="margin-right:15px;">
							<li>
								<h3>Basic Usage</h3>
								<small class="t-guide">Shortcode ( This will generate menu links for the site pages )</small>
								<pre class="t-code">[c_sitemap order="ASC"]</pre>
								<small class="t-guide">Live Result</small>
								<div class="t-demo"><?php echo do_shortcode('[c_sitemap order="ASC"]');?></div>
							</li>
							<li>
								<h3>Basic Attributes</h3>
								<ul>
									<li><b>title</b> ( Generate title text for menu )</li>
									<li><b>title_wrap</b> ( Wrapper for title, can be set to h1, h2, h3, h4, h5 or other tags such as p, the deafult value is h3 )</li>
									<li><b>icon</b> ( Any character or icon before the link text)</li>
									<li><b>depth</b> (By default is set to zero means generate all parent menus, 1 means generate parent and first child level menu and 3 generate all levels menu)</li>
									<li><b>limit</b> by default set to zero means no limit ( number limits of menu to be generated)</li>
								</ul>
								<br/>
								<small class="t-guide">Shortcode ( This will add title at the top of the generated menu links, <b>title_wrap</b> is optional and will be set to the default <b>h3</b> )</small>
								<pre class="t-code">[c_sitemap links="pcar" title="Page Sitemap" title_wrap="h3" icon ="&amp;raquo;"]</pre>
								<small class="t-guide">Live Result</small>
								<div class="t-demo"><?php echo do_shortcode('[c_sitemap links="pcar" title="Pages" title_wrap="h3" icon ="&raquo;"]');?></div>
							</li>
						</ol>
					</div>
					<div id="settings-content" class="cps-box hide">
						<ol style="margin-right:15px;">
							<?php
								self::cps_authorize();
								/**
								* Check for post action
								*/
								if ( ! empty( $_POST ) && isset( $_POST['cps_plugin_nonce'] ) ) {
									self::cps_handle_form();
								}
							?>
							<form id="cps-settings-form" class="cps-flex-right cps-settings-content" method="POST">
							<?php wp_nonce_field( 'cps_plugin_action','cps_plugin_nonce'); ?>
							<input type="hidden" name="action" value="cps_formrequest" />
							<div id="basic-block" class="cps-settings-block hide">
								<h3 id="next_message" style="margin-top:0;">General Settings</h3>
								<div class="cps-settings-scope">
									<span class="cps-formgroup">
										<label for="cps_shortcode"> Custom Shortcode <small>Add your own Alias to this shortcode</small></label>
										<input placeholder="c_sitemap" size="20" name="cps_shortcode" id="cps_shortcode" type="text" value="<?php echo is_multisite() ? (get_network_option(null, 'cps_shortcode')!=FALSE ? get_network_option(null, 'cps_shortcode') : 'c_sitemap'): (get_option('cps_shortcode')!=FALSE ? get_option('cps_shortcode') : 'c_sitemap') ; ?>" class=""/>
									</span>
								</div>
								<br/><br/>
								<span class="cps-settings-label">Element tag for title in the menu links</span>
								<div class="cps-settings-scope">
									<span class="cps-formgroup">
										<label for="cps_title_wrap"> Title Wrap <small> ( Tags )</small></label>
										<input placeholder="h3" size="20" name="cps_title_wrap" id="cps_title_wrap" type="text" value="<?php echo (is_multisite() ? (get_network_option(null, 'cps_title_wrap')!=false ? get_network_option(null, 'cps_title_wrap') : ''): (get_option('cps_title_wrap')!=false ? get_option('cps_title_wrap') : '') ); ?>" class=""/>
									</span>
									<span class="cps-formgroup">
										<label for="cps_title_id"> Title ID</label>
										<input placeholder="title-id" size="20" name="cps_title_id" id="cps_title_id" type="text" value="<?php echo (is_multisite() ? (get_network_option(null, 'cps_title_id')!=false ? get_network_option(null, 'cps_title_id') : ''): (get_option('cps_title_id')!=false ? get_option('cps_title_id') : '') ); ?>" class=""/>
									</span>
									<span class="cps-formgroup">
										<label for="cps_title_class"> Title Class</label>
										<input placeholder="title-class" size="20" name="cps_title_class" id="cps_title_class" type="text" value="<?php echo (is_multisite() ? (get_network_option(null, 'cps_title_class')!=false ? get_network_option(null, 'cps_title_class') : ''): (get_option('cps_title_class')!=false ? get_option('cps_title_class') : '') ); ?>" class=""/>
									</span>
								</div>
								<br/><br/>
								<span class="cps-settings-label">Element that wraps the menu "ul"</span>
								<div class="cps-settings-scope">
									<span class="cps-formgroup">
										<label for="cps_menu_id"> Menu ID<small> ( Tags )</small></label>
										<input placeholder="menu-id" size="20" name="cps_menu_id" id="cps_menu_id" type="text" value="<?php echo (is_multisite() ? (get_network_option(null, 'cps_menu_id')!=false ? get_network_option(null, 'cps_menu_id') : ''): (get_option('cps_menu_id')!=false ? get_option('cps_menu_id') : '') ); ?>" class=""/>
									</span>
									<span class="cps-formgroup">
										<label for="cps_menu_class"> Wrap Class</label>
										<input placeholder="menu-class" size="20" name="cps_menu_class" id="cps_menu_class" type="text" value="<?php echo (is_multisite() ? (get_network_option(null, 'cps_menu_class')!=false ? get_network_option(null, 'cps_menu_class') : ''): (get_option('cps_menu_class')!=false ? get_option('cps_menu_class') : '') ); ?>" class=""/>
									</span>
								</div>
								<br/><br/>
								<span class="cps-settings-label">Icon and Orders</span>
								<div class="cps-settings-scope">
									<span class="cps-formgroup">
										<label for="cps_icon"> Icon <small>HTML Entity Unicode <a href="https://unicode-table.com/en/html-entities/" target="_blank">Please check this out!</a></small></label>
										<input placeholder="&amp;raquo;" size="20" name="cps_icon" id="cps_icon" type="text" value="<?php echo (is_multisite() ? (get_network_option(null, 'cps_icon')!=false ? get_network_option(null, 'cps_icon') : ''): (get_option('cps_icon')!=false ? get_option('cps_icon') : '') ); ?>" class=""/>
									</span>
									<span class="cps-formgroup">
										<label for="cps_order"> Order </label>
										<?php 
										$cps_order =(is_multisite() ? (get_network_option(null, 'cps_order')!=false ? get_network_option(null, 'cps_order') : ''): (get_option('cps_order')!=false ? get_option('cps_order') : '') ); 
										?>
										<select name="cps_order" id="cps_order">
											<option <?php if(strtoupper($cps_order) =='ASC'){echo 'selected';} ?> value='ASC'>ASC</option>
											<option <?php if(strtoupper($cps_order) =='DESC'){echo 'selected';} ?> value='DESC'>DESC</option>
										</select>
									</span>
									<span class="cps-formgroup">
										<label for="cps_orderby"> Orderby </label>
										<input placeholder="name" size="20" name="cps_orderby" id="cps_orderby" type="text" value="<?php echo (is_multisite() ? (get_network_option(null, 'cps_orderby')!=false ? get_network_option(null, 'cps_orderby') : ''): (get_option('cps_orderby')!=false ? get_option('cps_orderby') : '') ); ?>" class=""/>
									</span>
								</div>
								<br/><br/>
								<span class="cps-settings-label">Archive Option</span>
								<div class="cps-settings-scope">									
									<span class="cps-formgroup">
										<label for="cps_archive_type"> Archive Type </label>
										<?php 
										$cps_archive_type =is_multisite() ? (get_network_option(null, 'cps_archive_type')!=false ? get_network_option(null, 'cps_archive_type') : ''): (get_option('cps_archive_type')!=false ? get_option('cps_archive_type') : ''); 
										?>
										<select name="cps_archive_type" id="cps_archive_type">
											<option <?php if($cps_archive_type =='daily'){echo 'selected';} ?> value='daily'>daily</option>
											<option <?php if($cps_archive_type =='weekly'){echo 'selected';} ?> value='weekly'>weekly</option>
											<option <?php if($cps_archive_type =='monthly'){echo 'selected';} ?> value='monthly'>monthly</option>
											<option <?php if($cps_archive_type =='yearly'){echo 'selected';} ?> value='yearly'>yearly</option>
											<option <?php if($cps_archive_type =='postbypost'){echo 'selected';} ?> value='postbypost'>postbypost</option>
											<option <?php if($cps_archive_type =='alpha'){echo 'selected';} ?> value='alpha'>alpha</option>
										</select>
									</span>
									<span class="cps-formgroup">
										<label for="cps_archive_limit"> Archive Limit <small></small></label>
										<input placeholder="12" size="20" name="cps_archive_limit" id="cps_archive_limit" type="number" value="<?php echo (is_multisite() ? (get_network_option(null, 'cps_archive_limit')!=false ? get_network_option(null, 'cps_archive_limit') : 12): (get_option('cps_archive_limit')!=false ? get_option('cps_archive_limit') : 12) ); ?>" class=""/>
									</span>
								</div>
								<div class="cps-settings-scope">
									<span class="cps-formgroup">
										<label for="cps_recent_posttype"> Recent Post Type <small></small></label>
										<input placeholder="post" size="20" name="cps_recent_posttype" id="cps_recent_posttype" type="text" value="<?php echo (is_multisite() ? (get_network_option(null, 'cps_recent_posttype')!=false ? get_network_option(null, 'cps_recent_posttype') : 'post'): (get_option('cps_recent_posttype')!=false ? get_option('cps_recent_posttype') : 'post') ); ?>" class=""/>
									</span>
									<span class="cps-formgroup">
										<label for="cps_recent_postlimit"> Recent Post Limit <small></small></label>
										<input placeholder="" size="20" name="cps_recent_postlimit" id="cps_recent_postlimit" type="number" value="<?php echo (is_multisite() ? (get_network_option(null, 'cps_recent_postlimit')!=false ? get_network_option(null, 'cps_recent_postlimit') : ''): (get_option('cps_recent_postlimit')!=false ? get_option('cps_recent_postlimit') : '') ); ?>" class=""/>
									</span>
								</div>
								
							</div>
							<p class="cps-submit">
								<input type="submit" name="cps-settings-submit" id="cps-settings-submit" class="button button-primary" value="Save Settings">
							</p>
							</form>
						</ol>
					</div>
					<div id="arguments-content" class="cps-box hide">
						<ol>
							<h3>Full Arguments</h3>
							<?php
							echo "<pre>";
							print_r(array(            
							#Menu Attribute & Wrapper
							#Search          
							'search'				=> "true or false",
							'search_title'			=> "Search",			
							
							#Link Section
							'order'           		=>'ASC or DESC',
							'icon'     				=> '&amp;raquo;',
							'insert_link'     		=> 'p:{title:http://},c:{title:http://}',  
							'links'          		=> 'pcar #interchangeable p=pages, c=category, a=archives, r=recent post<br/>',
							
							'Wrap-section ignore this'=> '----------------------------------------------------------',
							
							'wrap'            		=> 'div',
							'wrap_id'         		=> 'wrap-id',			
							'wrap_class'     		=> 'wrap-class',
							'wic'             		=> 'div, wrap-id, wrap-class shortcut for the 3 above args<br/>', //priority, can be use in all
							
							'Menu-section ignore this'=> '----------------------------------------------------------',
							'menu_class'      		=> 'menu-class',
							'menu_id'         		=> 'menu-id',
							'mic'            		=> 'menu-id, menu-class shortcut for the 2 above args<br/>', //priority can be use to 4 menus but not in search
							
							'Title-section ignore this'=> '----------------------------------------------------------',
							#Title Section
							'title'           		=>'Title separated by comma for the pcar', //true, false, "Title text", 
							'title_wrap'      		=>'h3',
							'title_id'        		=>'title-id',
							'title_class'     		=>'title-class',
							'twic'            		=> 'h3,title-id, title-class shortcut for the above args<br/>', //priority
							
							
							'<a title="click" target="_blank" href="https://developer.wordpress.org/reference/functions/wp_get_archives/">Recent Posts Reference</a> [ignore this'=> '----------------------------------------------------------',
							#Recent Posts
							'recent_type' 			=>'postbypost',
							'recent_posttype' 		=>'post',
							'recent_postlimit'		=>'',
							'limit'					=>'<br/>',
							
							'<a title="click" target="_blank" href="https://developer.wordpress.org/reference/functions/wp_get_archives/">Archives Reference</a> [ignore this'=> '---------------------------------------------------------------',
							#Archive Posts
							'archive_type'			=>'monthly',
							'archive_limit'   		=> 12,
							'year'            		=> '',//get_query_var( 'year' ),
							'monthnum'        		=> '',//get_query_var( 'monthnum' ),
							'day'             		=> '',//get_query_var( 'day' ),
							'w'               		=> '<br/>',//get_query_var( 'w' ),							
							
							'<a title="click" target="_blank" href="https://developer.wordpress.org/reference/functions/wp_list_categories/">Categories Reference</a> [ignore this]'=> '------------------------------------------------------------',
							#Categories Posts
							'categories_depth'		=>0,
							'exclude' =>'',
							'current_category'		=>'',
							'use_desc_for_title'	=>0,
							'taxonomy'=>'category',
							'exclude_tree'=>'',
							'hide_title_if_empty'	=>false,
							'orderby'				=>'name, id',
							'include'				=>'',
							'child_of'  			=>'<br/>',
							
							'<a title="click" target="_blank" href="https://developer.wordpress.org/reference/functions/wp_nav_menu/">Pages Reference</a> [ignore this]'=> '-----------------------------------------------------------------',
							#Pages
							'item_spacing'    		=> 'preserve',
							'depth'          		=> 0,
							'menu'            		=> '',//Menu Location
							'theme_location'  		=> ''//Theme Location
						));
						echo "</pre>";
						?>
						For your reference You may click the links provided.
						</ol>
						
					</div>
					<div id="demo-content" class="cps-box hide">
						<?php
							/**
							* Check for post action
							*/
							if ( ! empty( $_POST ) && isset( $_POST['cps_livedemo_action'] ) ) {
								self::cps_handle_demoform();
							}
						?>
						<form name="live-demo" id="live-demo">							
							<?php wp_nonce_field( 'cps_livedemo_action', 'cps_livedemo_nonce'); ?>
							<input type="hidden" name="action" value="cps_formdemo" />
							<span class="cps-formgroup">
								<label for="cps_live_demo"> Enter the shortcode </label>
								<input style="max-width:unset;" placeholder="[c_sitemap]" name="cps_live_demo" id="cps_live_demo" type="text" class="" value="[c_sitemap]"/>
							</span>
							<p class="cps-submit">
								<input type="submit" name="cps-livedemo-submit" id="cps-livedemo-submit" class="button button-primary" value="Run Shortcode">
							</p>
						</form>
						<small class="t-guide">Live Result</small>
						<div class="cps-livedemo-view t-demo" id="cps-livedemo-view"><?php echo do_shortcode('[c_sitemap]');?></div>
					</div>
					<div id="support-content" class="cps-box hide">
						<ol>
							<h3>Plugin Issues</h3>
							If by any chance you encountered plugin issues, please submit a brief report and provide a screenshot if possible so that I may be able to include them in the next plugin update, thank you.
							<br/><br/>
							<h3>User Support</h3>
							For as long as there are users who found this plugin helpful, by doing there part thru feedback and reviews the development support will continue.
							
							<br/><br/>
							<h3>Development Support</h3>
							Your positive reviews and ratings can be very helpful to me, it would also be nice if you can <a rel="referrer" target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=QX8K5XTVBGV42&amp;source=url">donate</a> any amount for funding.
						</ol>
					</div>
					<!--End -->
				</div>
			</div>
		</main>			
		<?php
    }
    
	public static function isJson($string) {
		$str = json_decode($string, true);
		return (json_last_error() == JSON_ERROR_NONE) ? $str : array("Invalid Parameter Shortcode - Custom Page Sitemap"=>"javascript:void(0);");
	}
	
	public static function get_insert_link($str=""){
		if(empty($str)){
			return $str;
		}
		$final_array_bylink=[];
		$raw_array_bylink = preg_split('/\,(?=\w)/i', $str, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		foreach($raw_array_bylink as $index){
			$link = substr($index, 0, 1);
			$to_insert = substr($index, 2, strlen($index));
			 $final_insert='';
			try{
				$raw_insert2 = self::isJson($to_insert);
				foreach($raw_insert2 as $obj => $value){
					if(empty($obj) || empty($value)){
						continue;
					}
					$title ='';
					if(strpos($value, "javascript:void(0);")!==false){
						$title ='title="ex.  p:{&quot;Link Name&quot;:&quot;http://&quot;}"';
					}
				$final_insert.="<a href=\"{$value}\" {$title}>".self::cps_clean('/[^a-z0-9-_ .()]/mi', '',$obj)."</a>";
				}
			}
			catch(Exception $e){
				$raw_insert = "<a href='javascript:(void);' title=\"ex.  p:{&quot;Link Name&quot;:&quot;http://&quot;}\">Invalid Parameter Shortcode - Custom Page Sitemap</a>";
			}
			
			$final_array_bylink[$link] =  $final_insert;
		}
		return $final_array_bylink;
	}
	
	
	public function c_sitemap( $atts, $content = ""){
        $c_links= array('p', 'c', 'a', 'r');
		
		$order = (is_multisite() ? (get_network_option(null, 'cps_order')!==false ? get_network_option(null, 'cps_order') : ''): (get_option('cps_order')!==false ? get_option('cps_order') : '') );
		
		
        $args = shortcode_atts( array(            
            #Menu Attribute & Wrapper
            'wrap'            		=> '',
            'wrap_id'         		=> '',			
            'wrap_class'     		=> '',
            'wic'             		=> '', //priority, can be use in all

            'menu_class'      		=> is_multisite() ? (get_network_option(null, 'cps_menu_class')!=false ? get_network_option(null, 'cps_menu_class') : ''): (get_option('cps_menu_class')!=false ? get_option('cps_menu_class') : '') ,
            'menu_id'         		=> is_multisite() ? (get_network_option(null, 'cps_menu_id')!=false ? get_network_option(null, 'cps_menu_id') : ''): (get_option('cps_menu_id')!=false ? get_option('cps_menu_id') : ''),
            'mic'            		=> '', //priority can be use to 4 menus but not in search
			
            #Title Section
            'title'           		=>false, //true, false, "Title text", 
            'title_wrap'      		=>is_multisite() ? (get_network_option(null, 'cps_title_wrap')!=false ? get_network_option(null, 'cps_title_wrap') : 'h3'): (get_option('cps_title_wrap')!=false ? get_option('cps_title_wrap') : 'h3'),
			'title_id'        		=>is_multisite() ? (get_network_option(null, 'cps_title_id')!=false ? get_network_option(null, 'cps_title_id') : ''): (get_option('cps_title_id')!=false ? get_option('cps_title_id') : ''),
			
			'title_class'     		=>is_multisite() ? (get_network_option(null, 'cps_title_class')!=false ? get_network_option(null, 'cps_title_class') : ''): (get_option('cps_title_class')!=false ? get_option('cps_title_class') : '') ,
            'twic'            		=> '', //priority
			
			
			'order'           		=> $order,
			#Recent Posts
			'recent_type' 			=>'postbypost',
			'recent_posttype' 		=>(is_multisite() ? (get_network_option(null, 'cps_recent_posttype')!=false ? get_network_option(null, 'cps_recent_posttype') : ''): (get_option('cps_recent_posttype')!=false ? get_option('cps_recent_posttype') : '') ),
			'recent_postlimit'		=>(is_multisite() ? (get_network_option(null, 'cps_recent_postlimit')!=false ? get_network_option(null, 'cps_recent_postlimit') : ''): (get_option('cps_recent_postlimit')!=false ? get_option('cps_recent_postlimit') : '') ),
			'limit'					=>'',
			
			#Archive Posts
			'archive_type'			=>(is_multisite() ? (get_network_option(null, 'cps_archive_type')!=false ? get_network_option(null, 'cps_archive_type') : 'monthly'): (get_option('cps_archive_type')!=false ? get_option('cps_archive_type') : 'monthly') ),
			'archive_limit'   		=> (is_multisite() ? (get_network_option(null, 'cps_archive_limit')!=false ? get_network_option(null, 'cps_archive_limit') : 12): (get_option('cps_archive_limit')!=false ? get_option('cps_archive_limit') : 12) ),
			'year'            		=> '',//get_query_var( 'year' ),
			'monthnum'        		=> '',//get_query_var( 'monthnum' ),
			'day'             		=> '',//get_query_var( 'day' ),
			'w'               		=> '',//get_query_var( 'w' ),
			
			#Categories Posts
			'categories_depth'		=>0,
			'exclude' =>'',
			'current_category'		=>'',
			'use_desc_for_title'	=>0,
			'taxonomy'=>'category',
			'exclude_tree'=>'',
			'hide_title_if_empty'	=>false,
			'orderby'				=>(is_multisite() ? (get_network_option(null, 'cps_orderby')!=false ? get_network_option(null, 'cps_orderby') : 'name'): (get_option('cps_orderby')!=false ? get_option('cps_orderby') : 'name') ),
			'include'				=>'',
			'child_of'  			=>'',
			
			#Pages
			'item_spacing'    		=> 'preserve',
            'depth'          		=> 0,
            'menu'            		=> '',//Menu Location
            'theme_location'  		=> '',//Theme Location
			
            #Search          
			'search'				=> false,
			'search_title'			=> false,			
			
			#Link Section
            'icon'     				=> (is_multisite() ? (get_network_option(null, 'cps_icon')!=false ? get_network_option(null, 'cps_icon') : ''): (get_option('cps_icon')!=false ? get_option('cps_icon') : '') ),
            'insert_link'     		=> '',//p:{text:link,text2:link2}  
            'links'          		=> ''
        ), $atts );
		
		$t_iconwrap = 'span';
		$t_iconmargin = 10;	

		if(is_multisite()){
			$icon = (get_network_option( null, 'cps_icon' )!=false) ? get_network_option( null, 'cps_icon' ) : '';
		}else{
			$icon = (get_option('cps_icon')!=false) ? get_option('cps_icon') : '';
		}
		if( !empty(trim($args['icon'])) || $args['icon']!==''){
			$icon = $args['icon'];
		}
			
		if( !empty(trim($icon)) || $icon!==''){
			$args['icon'] = "<{$t_iconwrap} style='display:inline-block; margin-right:{$t_iconmargin}px;'>$icon</{$t_iconwrap}>";
		}
		
		
        ob_start();
            //All can be switch with there sequence of position
			if($args['search']!==false){
				cps_search($content,$args);
			}
            if($args['links']):
				$reshuffle = array_unique(str_split($args['links']));
                    for($i =0; $i < count($reshuffle); $i++){
                        if($reshuffle[$i]=='p'){
                            cps_pages($content,$args);
                        }
                        if($reshuffle[$i]=='c'){
                            cps_categories($content,$args);
                        }
                        if($reshuffle[$i]=='a'){
                            cps_archives($content,$args);
                        }
                        if($reshuffle[$i]=='r'){
                            cps_recent_posts($content,$args);
                        }
					}
					else:
						 cps_pages($content,$args);
					endif;
        return ob_get_clean();
    }
}
class_alias('Custom_Page_Sitemap','CustomPageSitemap');
$CustomPageSitemap  = new CustomPageSitemap();
