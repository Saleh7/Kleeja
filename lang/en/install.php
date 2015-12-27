<?php
// English language file
//  By:NK , Email: n.k@cityofangelz.com 


	if(!isset($lang) || !is_array($lang)) $lang = array();

	$lang['DIR']									= 'ltr';
	$lang['INST_INSTALL_WIZARD']					= 'Kleeja installing wizard';
	$lang['INST_INSTALL_CLEAN_VER']					= 'New Installation';
	$lang['INST_UPDATE_P_VER']						= 'Update ';
	$lang['INST_AGR_LICENSE']						= 'I agree to terms and agreements';
	$lang['INST_NEXT']								= 'Next';
	$lang['INST_PREVIOUS']							= 'back';
	$lang['INST_SITE_INFO']							= 'Site Info';
	$lang['INST_ADMIN_INFO']						= 'Admin Info';
	$lang['INST_CHANG_CONFIG']						= 'Missing requirements ... make sure you have edited the config.php file.';
	$lang['INST_CONNCET_ERR']						= 'Cannot connect ..';
	$lang['INST_SELECT_ERR']						= 'Cannot select database';
	$lang['INST_NO_WRTABLE']						= 'The directory is not writeable';
	$lang['INST_GOOD_GO']							= 'Everything seems to be OK .... continue';
	$lang['INST_MSGINS']							= 'Welcome to our uploading service, here you can upload anything as long as it does not violate our terms.';
	$lang['INST_CRT_CALL']							= 'Comments table created.';
	$lang['INST_CRT_ONL']							= 'Online users table created.';
	$lang['INST_CRT_REPRS']							= 'Reports table created.';
	$lang['INST_CRT_STS']							= 'Statistics table created.';
	$lang['INST_CRT_USRS']							= 'Users table created.';
	$lang['INST_CRT_ADM']							= 'Admin details created.';
	$lang['INST_CRT_FLS']							= 'Files table created.';
	$lang['INST_CRT_CNF']							= 'Settings table created.';
	$lang['INST_CRT_EXT']							= 'Extensions table created.';
	$lang['INST_CRT_HKS']							= 'Hacks table created';
	$lang['INST_CRT_LNG']							= 'Language table created';
	$lang['INST_CRT_LSTS']							= 'Lists table created';
	$lang['INST_CRT_PLG']							= 'Plugins table created';
	$lang['INST_CRT_TPL']							= 'Templates table created';
	$lang['INST_SQL_OK']							= 'SQL Executed Successfully ..';
	$lang['INST_SQL_ERR']							= 'Error Executing SQL .. ';
	$lang['INST_FINISH_SQL']						= 'Kleeja was installed successfully';
	$lang['INST_NOTES']								= 'Installation Notes ..!';
	$lang['INST_END']								= 'The installation wizard is finished ,, Please remove the INSTALL directory..!';
	$lang['INST_NOTE_D']							= 'Any observations or problems , please contact with the developers kleeja..!!';
	$lang['INST_FINISH_ERRSQL']						= 'Oops! there seems to be a problem, try again.';
	$lang['INST_KLEEJADEVELOPERS']					= 'Thank you for using Kleeja and good luck from our development team';
	$lang['SITENAME']								= 'Website title';
	$lang['SITEURL']								= 'Website URL';
	$lang['SITEMAIL']								= 'Website Email';
	$lang['USERNAME']								= 'Username';
	$lang['PASSWORD']								= 'Password';
	$lang['PASSWORD2']								= 'Password Again';
	$lang['EMAIL']									= 'Email';
	$lang['INDEX']									= 'Home';
	$lang['ADMINCP']								= 'Control Panel';
	$lang['EMPTY_FIELDS']							= 'Some important fields were left blank!';
	$lang['WRONG_EMAIL']							= 'Incorrect Email Address!';
	//
	
	$lang['DB_INFO_NW']								= 'Enter the database information correctly .. Then press Next and the wizard will export the config.php file and put it in a directory the main script !';
	$lang['DB_INFO']								= 'Enter the database information ..!';
	$lang['DB_SERVER']								= 'Host';
	$lang['DB_TYPE']								= 'Database type';
	$lang['DB_TYPE_MYSQL']							= 'MySQL Standard';
	$lang['DB_TYPE_MYSQLI']							= 'MySQL Improved';
	$lang['DB_USER']								= 'Database Username';
	$lang['DB_PASSWORD']							= 'Database Password';
	$lang['DB_NAME']								= 'Database Name';
	$lang['DB_PREFIX']								= 'Tables prefix';
	$lang['VALIDATING_FORM_WRONG']					= 'A required field was left blank!';
	$lang['CONFIG_EXISTS']							= 'Config.php was found, Continue...';
	$lang['INST_SUBMIT_CONFIGOK']					= 'Upload the file in the main directory';
	$lang['INST_EXPORT']							= 'Export File';
	$lang['INST_OTHER_INFO']						= 'Other info';
	$lang['URLS_TYPES']								= 'Style of File urls';
	$lang['DEFAULT']								= 'Default';
	$lang['FILENAME_URL']							= 'Filenames';
	$lang['DIRECT_URL']								= 'Direct links';
	$lang['LIKE_THIS']								= 'Example : ';

	//
	$lang['FUNCTIONS_CHECK']						= 'Functions Check';
	$lang['RE_CHECK']								= 'ReCheck';
	$lang['FUNCTION_IS_NOT_EXISTS']					= 'The function %s is disabled.';
	$lang['FUNCTION_IS_EXISTS']						= 'The function %s is enabled.';
	$lang['FUNCTION_DISC_UNLINK']					= 'The function Unlink is used to remove and update cache files.';
	$lang['FUNCTION_DISC_GD']						= 'The function imagecreatetruecolor is function of GD library that is used to create thumbnails & control photos.';
	$lang['FUNCTION_DISC_FOPEN']					= 'The function fopen is used to control styles & files in kleeja.';
	$lang['FUNCTION_DISC_MUF']						= 'The function move_uploaded_file is used to upload files and it\'s the most important function in the script.';
	//
	$lang['ADVICES_CHECK']							= 'Advanced check (Optional)';
	$lang['ADVICES_REGISTER_GLOBALS']				= '<span style="color:red;padding:0 6px;">register_globals function is enabled ..!</span><br /> its recommended that you disable it.';
	$lang['ADVICES_MAGIC_QUOTES']					= '<span style="color:red;padding:0 6px;">magic_quotes function is enabled ..!</span><br /> it is recommended that you disable it.';
	
	//UPDATOR
	$lang['INST_CHOOSE_UPDATE_FILE']				= 'Choose the appropriate update file';
	$lang['INST_ERR_NO_SELECTED_UPFILE_GOOD']		= 'Inappropriate update file, or it is missing!';
	$lang['INST_UPDATE_CUR_VER_IS_UP']				= 'Your current version is newer than this update.';
	
	$lang['INST_NOTES_UPDATE']						= 'Update Notes';
	$lang['INST_NOTE_RC6_TO_1.5']					= 
	$lang['INST_NOTE_1.0_TO_1.5']					= 'You need to replace all new the script files with the old ones !.';
	$lang['RC6_1_CNV_CLEAN_NAMES']					= 'Cleaning every username ...';
	$lang['INST_UPDATE_IS_FINISH']					= 'Installation completed! you can now delete the INSTALL directory...';
	$lang['IN_INFO']								= 'Fill in the fields below if you want to integrate kleeja with your script . Ignore this step if you do not wish to do it<br /><span style="color:red;">you should change user system from admin cp after installing kleeja</span>';
	$lang['IN_PATH']								= 'Path of script';
	$lang['INST_PHP_LESSMIN']						= 'You need PHP %1$s or above to install Kleeja, your current version is %2$s';
	$lang['INST_MYSQL_LESSMIN']						= 'You need MySQL %1$s or above to install Kleeja, your current version is %2$s';
	$lang['IS_IT_OFFICIAL']							= 'Did you get your copy from Kleeja.com (Kleeja official site) ?';
	$lang['IS_IT_OFFICIAL_DESC']					= 'We receive a lot of complaints and questions about the cause of some bugs and issues which occur in kleeja and probably we can\'t figure out what the problem is . After we have checked we have found that there are some unofficially copies released from untrusted publishers .<span class="sure"> So are you sure of this copy is downloaded from kleeja official site ?</span>';
	$lang['IS_IT_OFFICIAL_YES']						= 'Yes, my copy is officially and I downloaded it from the official site (Kleeja.com) ?';
	$lang['IS_IT_OFFICIAL_NO']						= 'No, I\'ve downloaded it from other site, Go ahead now and download it from Kleeja official site';
	$lang['INST_WHAT_IS_KLEEJA_T']					= 'What is Kleeja ?';
	
	$lang['INST_WHAT_IS_KLEEJA']					= 'Keeja is a free, features rich, files and images upload system. Kleeja is developed to help webmasters to provide a decent files hosting service on their sites . Kleeja comes with a simple source code and powerful User system , also with easy template system so you can easily customize your styles ';
	
	$lang['INST_SPECIAL_KLEEJA']					= 'Some Kleeja features .. !';
	$lang['INST_WHAT_IS_KLEEJA_ONE']				= 'Kleeja has a simple and powerful user system which can be easily integrated with many boards . Kleeja provide simple admin control panel that enables you to control over everything in your site . Also you can customize Kleeja\'s style and install a lot of add-ons  ....  <a target="_blank" href="http://www.kleeja.com/about/">more details in Kleeja site </a>';
	$lang['YES']									= 'Yes';
	$lang['NO']										= 'No';
	$lang['PASS_X_CHARS']							= 'You must enter a minimum of %d characters';
	$lang['PASS_X_TYPE']							= array('Weak', 'Normal', 'Medium', 'Strong', 'Very Strong');
	$lang['KLEEJA_TEAM_MSG_NAME']					= 'Kleeja Development Team';
	$lang['KLEEJA_TEAM_MSG_TEXT']					= "Thank you for choosing Kleeja to empower your website,\n We really hope you enjoy the unique experince that Kleeja offers to you to control the files and images.\nDon't forget to visit http://kleeja.com for support, styles and plugins ..";

	//PLUGINS
	$lang['PLUGINS_KLEEJA']							= 'Plugins For Kleeja .. !';
	$lang['PLUGINS_BUILT_IN']						= 'These Plugins are built in Kleeja originally  . For more Plugins visit Kleeja.com';
	$lang['PLUGINS_NAME']							= 'Plugin name';
	$lang['PLUGINS_VER']							= 'version';
	$lang['PLUGINS_DES']							= 'description';	
	$lang['PLUGINS_INSTALLED']						= 'Plugins have been successfully installed ! .';	

//<-- EOF
