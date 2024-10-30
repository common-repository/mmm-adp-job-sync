function sync_adp_jobs()
{
	jQuery("#adpjs-status").text("Starting jobs sync call");
	jQuery.post(adpjs_ajaxurl, 
	    {
    		'fn' : 'get',
    		'action' : 'adpjobsync',
		}, 
	    function(response){
	    	if (response == 0)
	    	{
	        	jQuery("#adpjs-status").text("Error syncing jobs");
	    	}
	    	else
	    	{
	    		jQuery("#adpjs-status").text("Jobs synced");
	    	}
	    }
	);
}

function save_adp_config()
{
	jQuery("#adpjs_save_feedback").text("Starting save config sync call");

	var data = jQuery('#adpjs_options_fields').find('select, textarea, input').serializeArray();
    data.push({'name' : 'fn', 'value' : 'save'});
    data.push({'name' : 'action', 'value' :'adpjobsync'});

	jQuery.post(adpjs_ajaxurl, 
	    data, 
	    function(response){
	    	if (response == 0)
	    	{
	        	jQuery("#adpjs_save_feedback").text("Error saving config");
	    	}
	    	else
	    	{
	    		jQuery("#adpjs_save_feedback").text("Config saved");
	    	}
	    }
	);
}