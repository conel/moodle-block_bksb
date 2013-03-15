<?php 
//  Users entered incorrect IDs when they used BKSB
//  This page allows staff to update user details

require('../../../config.php');
require('../BksbReporting.class.php');
$bksb = new BksbReporting();

$old_username = optional_param('old_username', '', PARAM_RAW);
$new_username = optional_param('new_username', '', PARAM_RAW);
$firstname = optional_param('firstname', '', PARAM_RAW);
$lastname = optional_param('lastname', '', PARAM_RAW);
$action	= optional_param('action', '', PARAM_RAW);
$row = optional_param('row', '', PARAM_RAW);

require_login();

if (!has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM))) {
    error("Only the administrator can access this page!", $CFG->wwwroot);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-AU" xml:lang="en-AU">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Update BKSB User</title>
<script type="text/javascript">
var jQ = jQuery.noConflict();
function removeUpdatedRow(row) {
	jQ('#user_' + row).css('background-color', '#FBF586');
	jQ('#user_' + row).fadeOut(2000);
}

// Make sure it plays with other js frameworks
jQ("#bksb_update").submit(function() {
	/* JavaScript form checking */
	
	// check username is not blank
	var username = jQ("#update_username").val();
	if (username == "") {
		alert('Username is a required field');
		jQ("#update_username").focus();
		return false;
	}
	// check firstname is not blank
	var firstname = jQ("#update_firstname").val();
	if (firstname == "") {
		alert('Firstname is a required field');
		jQ("#update_firstname").focus();
		return false;
	}
	// check lastname is not blank
	var lastname = jQ("#update_lastname").val();
	if (lastname == "") {
		alert('Lastname is a required field');
		jQ("#update_lastname").focus();
		return false;
	}
	
	// Ajax update
	var dataString = jQ("#bksb_update").serialize();
	var row_to_update = <?php echo $row; ?>;
	
	jQ.ajax({
	  type: "POST",
	  url: 'update_ajax.php',
	  data: dataString,
	  success: function(data) {
		var result = jQ.trim(data);

		if (result == 'Success!') {
			jQ('#bksb_update').hide();
			jQ('#update_message').html('<b class=\"success\">Successfully updated BKSB user!</b>');
			jQ.colorbox.resize();
			jQ(document).bind('cbox_closed', function(){
				var row = <?php echo $row; ?>;
				removeUpdatedRow(row);
			});
			
		} else {
			jQ('#update_message').html(data);
			jQ.colorbox.resize();
		}
	  }
	});
	return false;

});
</script>
</head>
<body>

<?php
if ($action == 'update') {

    $error = FALSE;
    $errors = array();
    
    $old_username = trim($old_username);
    $new_username = trim($new_username);
    $firstname = trim($firstname);
    $lastname = trim($lastname);
    
    /* Validate data */
    
    // No fields can be blank
    if ($old_username == '' || $new_username == '' || $firstname == '' || $lastname == '') {
        $error = TRUE;
        $errors[] = '<b>Error:</b> All fields are required';
    }
    
    // Username can only be numbers
    if (!is_numeric($new_username)) {
        $error = TRUE;
        $errors[] = '<b>Error:</b> Username must only contain numbers';
    }
    
    // Check it's a valid idnumber by looking it up in mdl_user
    /* Removed this on Scott's request
    if (!$exists = get_record('user', 'idnumber', $new_username)) {
        $error = TRUE;
        $errors[] = '<b>Error:</b> No match found on username in Moodle';	
    }
    */
    
    // Check firstname isn't numeric
    if (is_numeric($firstname)) {
        $error = TRUE;
        $errors[] = '<b>Error:</b> Invalid firstname';
    }
    
    // Check lastname isn't numeric
    if (is_numeric($lastname)) {
        $error = TRUE;
        $errors[] = '<b>Error:</b> Invalid lastname';
    }
    
    // Uppercase words in first and lastname
    $firstname = ucwords($firstname);
    $lastname  = ucwords($lastname);
    
    // Let's assume the data is valid, now we can update it in our tables
    if ($error === FALSE) {
    
        $update = $bksb->updateBksbData($old_username, $new_username, $firstname, $lastname);
        
        if ($update) {
            echo "<b>Successfully updated BKSB user</b>";
            echo "</body></html>";
            exit;
        } else {
            $errors[] = "SQL Update failed";
        }
    
    }
    
} // If form posted

echo '<div id="update_bksb_user">';
echo "<h2>BKSB - Update User</h2>";
echo '<div id="update_message"></div>';

if (isset($errors) && count($errors) > 0) {
	echo '<div class="errors">';
	echo '<p>Update failed</p>';
	echo '<h3>Errors</h3>';
	echo '<ul>';
	foreach ($errors as $error) {
		echo "<li>$error</li>";
	}
	echo '</ul>';
	echo '</div>';
}

// Work out which username to fill new username with
if ($action == 'update') {
	$username = $new_username;
} else {
	$username = $old_username;
}

echo '<form action="update.php" id="bksb_update" method="post">
<table>
	<tr>
		<td><b>Username:<br /><span style="font-size:0.95em; font-weight:normal;">(ID number)</span></b></td>
		<td><input type="text" value="'.$username.'" name="new_username" id="update_username" /></td>
	</tr>
	<tr>
		<td><b>Firstname:</b></td>
		<td><input type="text" value="'.$firstname.'" name="firstname" id="update_firstname" /></td>
	</tr>
	<tr>
		<td><b>Lastname:</b></td>
		<td><input type="text" value="'.$lastname.'" name="lastname" id="update_lastname" /></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
			<input type="hidden" name="old_username" value="'.$old_username.'" />
			<input type="hidden" name="row" value="'.$row.'" />
			<input type="hidden" name="action" value="update" />
			<input type="submit" value="Update User" />
		</td>
	</tr>
</table>
</form>';
?>
</div>
</body>
</html>
