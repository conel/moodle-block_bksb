<?php 

/**
 * Global config file for the BKSB block
 */

// Allow deleting cache files from the settings page
$clear_cache = optional_param('clearcache', 0, PARAM_INT);
if ($clear_cache === 1) {
    include('Cache.class.php');
    Cache::clearCache();
}

// Settings stored in the 'mdl_config_plugins' table.

/* BKSB Database Settings */
$bksb_settings = new admin_setting_heading(
    'block_bksb/bksb_settings', 
    get_string('bksb_settings', 'block_bksb'),
    ''
);
$settings->add($bksb_settings);

$db_server = new admin_setting_configtext(
    'block_bksb/db_server', 
    get_string('db_server', 'block_bksb'), 
    get_string('set_db_server', 'block_bksb'),
    '',
    PARAM_RAW
);
$settings->add($db_server);

$db_name = new admin_setting_configtext(
    'block_bksb/db_name', 
    get_string('db_name', 'block_bksb'), 
    get_string('set_db_name', 'block_bksb'),
    '',
    PARAM_RAW
);
$settings->add($db_name);

$db_user = new admin_setting_configtext(
    'block_bksb/db_user', 
    get_string('db_user', 'block_bksb'), 
    get_string('set_db_user', 'block_bksb'),
    '',
    PARAM_RAW
);
$settings->add($db_user);

$db_password = new admin_setting_configpasswordunmask(
    'block_bksb/db_password', 
    get_string('db_password', 'block_bksb'), 
    get_string('set_db_password', 'block_bksb'),
    '',
    PARAM_RAW
);
$settings->add($db_password);

$bksb_stats = new admin_setting_heading(
    'block_bksb/bksb_stats', 
    get_string('bksb_stats', 'block_bksb'),
    ''
);


/* MIS Database Settings */
$mis_settings = new admin_setting_heading(
    'block_bksb/mis_settings', 
    get_string('mis_settings', 'block_bksb'),
    ''
);
$settings->add($mis_settings);

$mis_db_server = new admin_setting_configtext(
    'block_bksb/mis_db_server', 
    get_string('mis_db_server', 'block_bksb'), 
    get_string('mis_set_db_server', 'block_bksb'),
    '',
    PARAM_RAW
);
$settings->add($mis_db_server);

$mis_db_name = new admin_setting_configtext(
    'block_bksb/mis_db_name', 
    get_string('mis_db_name', 'block_bksb'), 
    get_string('mis_set_db_name', 'block_bksb'),
    '',
    PARAM_RAW
);
$settings->add($mis_db_name);

$mis_db_user = new admin_setting_configtext(
    'block_bksb/mis_db_user', 
    get_string('mis_db_user', 'block_bksb'), 
    get_string('mis_set_db_user', 'block_bksb'),
    '',
    PARAM_RAW
);
$settings->add($mis_db_user);

$mis_db_password = new admin_setting_configpasswordunmask(
    'block_bksb/mis_db_password', 
    get_string('mis_db_password', 'block_bksb'), 
    get_string('mis_set_db_password', 'block_bksb'),
    '',
    PARAM_RAW
);
$settings->add($mis_db_password);

/* Cache */
$bksb_cache = new admin_setting_heading(
    'block_bksb/bksb_cache', 
    get_string('bksb_cache', 'block_bksb'),
    ''
);
$settings->add($bksb_cache);

$bksb_cache_life = new admin_setting_configtext(
    'block_bksb/cache_life_seconds', 
    get_string('cache_life_seconds', 'block_bksb'), 
    get_string('set_cache_life_seconds', 'block_bksb'),
    604800,
    PARAM_INT
);
$settings->add($bksb_cache_life);

$cc_link = new moodle_url('settings.php?section=blocksettingbksb&clearcache=1');
$cc_link_html = '<a href="'.$cc_link.'">'.get_string('clear_cache', 'block_bksb').'</a>';
$settings->add(new admin_setting_heading(
    'block_bksb/clear_cache', 
    '', 
    $cc_link_html
));


/* Links to Statistic Pages */
$bksb_stats = new admin_setting_heading(
    'block_bksb/bksb_stats', 
    get_string('bksb_stats', 'block_bksb'),
    ''
);
$settings->add($bksb_stats);

$ia_link = new moodle_url('/blocks/bksb/stats/initial_assessments.php');
$ia_link_html = '<a href="'.$ia_link.'">'.get_string('bksb_stats_ia', 'block_bksb').'</a>';
$settings->add(new admin_setting_heading(
    'block_bksb/bksb_stats_ia', 
    '', 
    $ia_link_html
));

$da_link = new moodle_url('/blocks/bksb/stats/diagnostic_assessments.php');
$da_link_html = '<a href="'.$da_link.'">'.get_string('bksb_stats_da', 'block_bksb').'</a>';
$settings->add(new admin_setting_heading(
    'block_bksb/bksb_stats_da', 
    '', 
    $da_link_html
));

/* Links to Unmatched Users */
$bksb_unmatched = new admin_setting_heading(
    'block_bksb/bksb_unmatched', 
    get_string('bksb_unmatched', 'block_bksb'),
    ''
);
$settings->add($bksb_unmatched);

$unmatch_link = new moodle_url('/blocks/bksb/admin/unmatched_users.php');
$unmatch_link_html = '<a href="'.$unmatch_link.'">'.get_string('bksb_unmatched_link', 'block_bksb').'</a>';
$settings->add(new admin_setting_heading(
    'block_bksb/bksb_unmatched_link', 
    '', 
    $unmatch_link_html
));

?>
