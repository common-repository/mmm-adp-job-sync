<script type="text/javascript">
    var adpjs_ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
</script>
<div class="adpjs-container pure-form pure-form-stacked wrap">
    <h1>ADP JS Settings</h1>

    <div id="adpjs_options_fields">
        <?php
        // This prints out all hidden setting fields
        settings_fields( 'adpjs_options_fields' );
        do_settings_sections( 'adpjs-admin' );
        ?>
        <br />
        <button class="pure-button pure-button-primary" onclick="javascript: save_adp_config()">Save Options</button>
    </form>
    <div id="adpjs_save_feedback">
        Waiting for settings to be saved
    </div>

    <hr />

    <h2>Sync ADP Jobs Data</h2>
    <button class="pure-button pure-button-secondary" onclick="javascript: sync_adp_jobs()">Sync Jobs</button>
    <div id="adpjs-status">
        No recent syncs
    </div>
</div>