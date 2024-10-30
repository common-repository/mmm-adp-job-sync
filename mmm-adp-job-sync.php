<?php
/*
Plugin Name: Mmm ADP Job Sync
Plugin URI: https://www.mediamanifesto.com/plugins
Description: Allows you to read public ADP job listings from workforcenow.adp.com and sync them as posts on your site.
Version: 1.0.0
Author: Adam Bissonnette
Author URI: https://www.mediamanifesto.com/
*/
include_once('includes/form_helpers.php');
use adpjsformtools as FormTools;

define( 'ADPJOBSYNC__OPTIONS', 'adpjs_options' );
define( 'ADPJOBSYNC__DOMAIN', 'adpjobsync_plugin' );
define( 'ADPJOBSYNC__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ADPJOBSYNC__PLUGIN_URL', plugin_dir_url( __FILE__ ) );

class ADPJobSync {
	public static $config_template				= array('companyKey' => "",
														'defaultCategory' => "",
														'defaultAuthor' => "",
														'defaultFeaturedImage' => "",
														'defaultPostStatus' => "publish",
														'postContentTemplate' => "",
													);
	protected static $class_config 				= array();
	protected $plugin_ajax_nonce				= 'adpjs_handler-ajax-nonce';
	protected $plugin_path						= ADPJOBSYNC__PLUGIN_DIR;
	protected $plugin_url						= ADPJOBSYNC__PLUGIN_URL;
	protected $plugin_textdomain				= ADPJOBSYNC__DOMAIN;
	protected $plugin_options					= ADPJOBSYNC__OPTIONS;

	function __construct( $config = array() ) {
		self::$class_config = $config;
		add_action( 'after_setup_theme', array($this, 'plugin_textdomain') );
		
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'admin_page_init' ) );
		add_action( 'admin_init', array($this, 'admin_init_plugin') );

		add_action('wp_ajax_adpjobsync', array($this, 'adpjobsync_ajax') );
		add_action('wp_ajax_nopriv_adpjobsync', array($this, 'adpjobsync_ajax') );

		register_activation_hook(__FILE__, array($this, 'plugin_activation'));
		register_deactivation_hook(__FILE__, array($this, 'plugin_deactivation'));
		add_action('job_sync_event', array($this, 'job_sync_func'));
	}

	private function plugin_activation() {
	    if (! wp_next_scheduled ( 'job_sync_event' )) {
			wp_schedule_event(time(), 'hourly', 'job_sync_event');
	    }
	}

	private function job_sync_func() {
		$this->get_adp_jobs();
	}

	private function plugin_deactivation() {
		wp_clear_scheduled_hook('job_sync_event');
	}

	public function plugin_textdomain() {
		load_plugin_textdomain( $this->plugin_textdomain, FALSE, $this->plugin_path . '/languages/' );		
	}

	function admin_init_plugin()
	{
		$options 		= self::$class_config;
		
		if( is_admin() ) {
			
			//Enqueue admin scripts
			add_action( 'admin_enqueue_scripts', array($this, 'enqueue_admin_scripts') );	
		}
	}

	public function enqueue_admin_scripts() {
		$js_inc_path 	= $this->plugin_url . 'js/';
		$css_inc_path 	= $this->plugin_url . 'css/';

		wp_enqueue_script( 'adpjs_jsadmin',
			$js_inc_path . 'admin.js',
			array( 'jquery' ),
			'1.0',
			TRUE
		);

		wp_enqueue_style( 'adpjs_forms',
			$css_inc_path . 'pure-min.css',
			'1.0',
			TRUE
		);
	}

	function adpjobsync_ajax()
	{
		if (is_admin())
		{
			switch($_REQUEST['fn']){
				case 'get':
					$this->get_adp_jobs();
					die;
				break;
				case 'save':
                    $this->_update_config($_REQUEST);
					die;
				break;
			}
		}
	}

	function _update_config($values=array())
    {
        $args = array();
        foreach (ADPJobSync::$config_template as $key => $value) {
            if (!is_array($values[$key]))
            {
                $args[$key] = urldecode($values[$key]);
            }
            else
            {
                $args[$key] = ($values[$key]);
            }
        }
        $new_config = array_merge(self::$class_config, $args);
        update_option( ADPJOBSYNC__OPTIONS, $new_config );
        echo 1;
    }

	private function get_adp_jobs()
	{
		$companyKey = $this->_get_option("companyKey");

		if (!empty($companyKey))
		{
			$get_params = array('client' => $companyKey);

			$data = '{"ccid":"19000101_000001","inputSearch":"","startIndex":1,"lastIndex":50,"sortBy":"POSTED_ON","sortOrder":"DESC","locationOidList":[],"employeeTypeList":[],"jobClassList":[],"postedType":"","minSalary":0,"maxSalary":100000000,"acceptApplications":1,"acceptReferrals":1}';

			$cookies = array();
		 	$cookies[] = new WP_Http_Cookie( array( 'name' => 'ADPPORTAL',
		 											'value' => 'WFNPortal^App^PORTALWFN48',
		 										 ) );

			$headers = array();
			$headers["Content-Type"] = "application/json;charset=UTF-8";


			$request_args = array( 'headers' => $headers, 'body' => $data, 'cookies' => $cookies, 'method' => 'POST' );

			$url = $this->_formatUrl("https://workforcenow.adp.com/jobs/apply/metaservices/JobSearchService/legacySearch/en_CA/E?", $get_params);
			$this->_do_rpc($url, $request_args, array($this, 'get_adp_jobs_cb'));
		}
	}

	private function get_adp_jobs_cb($response)
	{
		$data = json_decode($response)->data;
		$job_ids = array();

		foreach ($data->results as $job) {
			$job_ids[] = $job->requisitionOid;
			$this->get_adp_job_details($job->requisitionOid);
		}

		//Find listings on our site that aren't on ADP anymore
		$query_args = array(
             'posts_per_page' => -1,
             'post_type' => 'post',
             'meta_query'  => array(
                    array(
                        'key' => 'jobID',
                        'value' => $job_ids,
                        'compare' => 'NOT IN'
                    )
                )
        	);

        $post_search_results = query_posts($query_args);

        //Set missing jobs to pending
        foreach ($post_search_results as $post) {
        	$post->post_status = "pending";
        	wp_update_post($post);
        }

		echo 1;
	}

	private function get_adp_job_details($jobid)
	{
		$url = $this->get_job_url($jobid);
		$this->_do_rpc($url, array('method' => 'GET'), array($this, 'get_adp_job_details_cb'));
	}

	private function get_job_url($jobid, $json=true)
	{
		$companyKey = $this->_get_option("companyKey");
		$get_params = array(
						'requisitionOid' => $jobid,
						'ccRefId' => '19000101_000001',
						'client' => $companyKey,
					);

		return $this->_formatUrl("https://workforcenow.adp.com/jobs/apply/metaservices/careerCenter/jobDetails/E/en_CA?", $get_params);
	}

	private function get_adp_job_details_cb($response)
	{
		$data = json_decode($response)->data;

		$companyKey = $this->_get_option("companyKey");
		$jobID = $data->recOid;
		$title = $data->jobName;

		//Remove inline styles from description - ADP + HR is bad at html
		$description = preg_replace('(style\=".+?")', '', $data->description);
		//Only keep whitelisted tags
		$description = strip_tags($description, '<p><ul><li><strong>');

		$query_args = array(
             'posts_per_page' => -1,
             'post_type' => 'post',
             'meta_query'  => array(
                    array(
                        'key' => 'jobID',
                        'value' => $jobID
                    )
                )
        	);

        $post_search_results = query_posts($query_args);
        $post_id = -1;

        $postContentTemplate = $this->_get_option("postContentTemplate");

        $formattedJobDescription = $description;
        if (!empty($postContentTemplate))
        {
        	$formattedJobDescription = stripcslashes($postContentTemplate);
        	$joburl = sprintf("https://workforcenow.adp.com/jobs/apply/posting.html?client=%s&jobId=%s", $companyKey, $jobID);
        	$jobAtts = array("title" => $title, "description" => $description, "joburl" => $joburl);

        	foreach ($jobAtts as $key => $value) {
        		$formattedJobDescription = str_replace(sprintf("{{%s}}", $key), $value, $formattedJobDescription);
        	}
        }

        $postArgs = array(
                        'post_title' => $title,
                        'post_type' => 'post',
                        'post_content' => $formattedJobDescription
                    );

        if (empty($post_search_results))
        {
	        $defaultAuthor = $this->_get_option("defaultAuthor");
	        if (!empty($defaultAuthor))
	        {
	        	$postArgs["post_author"] = $defaultAuthor;
	        }

        	$postArgs["post_status"] = $this->_get_option("defaultPostStatus");
            $post_id = wp_insert_post( $postArgs );
            update_post_meta( $post_id, "jobID", $jobID );

            $defaultCategory = $this->_get_option("defaultCategory");
	        if (!empty($defaultCategory))
	        {
				wp_set_post_categories( $post_id, $defaultCategory );        	
	        }
        }
        else
        {
            $post_id = $post_search_results[0]->ID;
            $postArgs["ID"] = $post_id;
            wp_update_post($postArgs);
        }

        update_post_meta( $post_id, "jobTitle", $title );
        update_post_meta( $post_id, "jobDescription", $description );
	}

	//Helper functions
	private function _formatUrl($url, $args)
	{
		$query = http_build_query($args);
		$query = preg_replace("/\%5B\d{1,}\%5D/", "", $query);

		return $url . $query;
	}

	private function _do_rpc($url, $args, $success_callback=null, $error_callback=null)
	{
		$response = wp_remote_post($url, $args);

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( !in_array( $response_code, array(200,201) ) || is_wp_error( $response_body ) )
		{
		    if (isset($error_callback))
		    {
		    	$error_callback($response);
		    }
		    else
		    {
		    	echo -1;
		    }
		} else {
			if (isset($success_callback))
		    {
				$success_callback($response_body);
		    }
		    else
		    {
		    	echo 1;
		    }
		}
	}

	//Admin Page
	public function add_plugin_page()
    {
        add_options_page(
            'ADPJS Settings', 
            'ADPJS Settings', 
            'manage_options', 
            'adpjs-admin.php', 
            array( $this, 'create_admin_page' )
        );
    }

	public function create_admin_page()
    {
    	include_once('includes/admin_page.php');
    }

	public function admin_page_init()
    {        
        register_setting(
            'adpjs_options_fields',
            'connection_settings'
        );
        add_settings_section(
            'public_section',
            'ADP Settings', // Title
            array( $this, 'print_adpjs_info' ), // Callback
            'adpjs-admin' // Page
        );
    }
    
    private function _get_option($name)
    {
        global $adpjs_options;
        $defaultValue = ADPJobSync::$config_template[$name];

        return isset($adpjs_options[$name])?$adpjs_options[$name]:$defaultValue;
    }

    public function print_adpjs_info()
    {
        $content = '<p>Required info get your job details</p>';

        $companyKey = $this->_get_option("companyKey");
        $defaultCategory = $this->_get_option("defaultCategory");
        $defaultAuthor = $this->_get_option("defaultAuthor");
        $postContentTemplate = $this->_get_option("postContentTemplate");

        $authorOptions = array();
        $users = get_users();
        foreach ($users as $user) {
        	$authorOptions[] = array("label" => $user->user_nicename, "value" => $user->ID);
        }
        $authorArgs = array("selected" => $defaultAuthor, "options" => $authorOptions);

        $catOptions = array();
        $cats = get_terms( 'category', array(
				    'hide_empty' => false,
				) );

        foreach ($cats as $cat) {
        	$catOptions[] = array("label" => $cat->name, "value" => $cat->term_id);
        }
        $categoryArgs = array("selected" => $defaultCategory, "options" => $catOptions);

        $defaultPostStatus = $this->_get_option("defaultPostStatus");
        $postStatuses = array(
        		array("label" => "Publish", "value" => "publish"),
        		array("label" => "Pending", "value" => "pending"),
        		array("label" => "Draft", "value" => "draft")
        );

        $defaultPostStatusArgs = array("selected" => $defaultPostStatus, "options" => $postStatuses);

        $content .= FormTools\FormHelpers::gen_field("Company Key", "companyKey", $companyKey, "text");

        $content .= '<span class="pure-form-message">Pre-populate your new jobs with this information</span>';
        $content .= FormTools\FormHelpers::gen_field("Default Category", "defaultCategory", $categoryArgs, "select");
        $content .= FormTools\FormHelpers::gen_field("Default Author", "defaultAuthor", $authorArgs, "select");
        $content .= FormTools\FormHelpers::gen_field("Default Post Status", "defaultPostStatus", $defaultPostStatusArgs, "select");

        $content .= FormTools\FormHelpers::gen_field("Content Template", "postContentTemplate", stripcslashes($postContentTemplate), "textarea");
        $content .= '<span class="pure-form-message">Include {{title}}, {{description}} or {{joburl}} in your template.</span>';

        echo $content;
    }
}

adpjs_handler_init();
function adpjs_handler_init() {
	global $adpjs_options;
	
	$config_options = ADPJobSync::$config_template;
	
	$adpjs_options = get_option( ADPJOBSYNC__OPTIONS );

	foreach ($config_options as $key => $value) {
		if (isset($adpjs_options[$key]))
		{
			$config_options[$key] = $value;
		}
	}

	new ADPJobSync( $config_options );
}