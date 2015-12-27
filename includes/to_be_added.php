<?php
/**
*
* @package Kleeja
* @version $Id: usr.php 1889 2012-08-21 07:54:23Z saanina $
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


//no for directly open
if (!defined('IN_COMMON'))
{
	exit();
}


# check entries functions 
# to be added at 1.7 or later
function ig($name)
{
	return isset($_GET[$name]) ? true : false;
}

function ip($name)
{
	return isset($_POST[$name]) ? true : false;
}

function _g($name, $type = 'str')
{
	if(isset($_GET[$name]))
	{
		return $type == 'str' ? htmlspecialchars($_GET[$name]) : intval($_GET[$name]);
	}
	return false;
}

function _p($name, $type = 'str')
{
	if(isset($_POST[$name]))
	{
		return $type == 'str' ? htmlspecialchars($_POST[$name]) : intval($_POST[$name]);
	}
	return false;
}


#function_exists('exif_read_data') ? 
#http://www.developer.nokia.com/Community/Wiki/Extract_GPS_coordinates_from_digital_camera_images_using_PHP
function get_gps_from_image($image_path)
{
	if(!function_exists('exif_read_data'))
	{
		return false;
	}
	
	$exif = exif_read_data($image_path, 0, true);
	
	if(!$exif || !is_array($exif))
	{
		return false;
	}
	else
	{
		if(!in_array('GPS', $exif))
		{
			return false;
		}

		$lat_ref = $exif['GPS']['GPSLatitudeRef'];
		$lat = $exif['GPS']['GPSLatitude'];
		list($num, $dec) = explode('/', $lat[0]);
		$lat_s = $num / $dec;
		list($num, $dec) = explode('/', $lat[1]);
		$lat_m = $num / $dec;
		list($num, $dec) = explode('/', $lat[2]);
		$lat_v = $num / $dec;
		$lon_ref = $exif['GPS']['GPSLongitudeRef'];
		$lon = $exif['GPS']['GPSLongitude'];
		list($num, $dec) = explode('/', $lon[0]);
		$lon_s = $num / $dec;
		list($num, $dec) = explode('/', $lon[1]);
		$lon_m = $num / $dec;
		list($num, $dec) = explode('/', $lon[2]);
		$lon_v = $num / $dec;
		$lat_int = ($lat_s + $lat_m / 60.0 + $lat_v / 3600.0);
		// check orientaiton of latitude and prefix with (-) if S
		$lat_int = ($lat_ref == "S") ? '-' . $lat_int : $lat_int;
		$lon_int = ($lon_s + $lon_m / 60.0 + $lon_v / 3600.0);
		// check orientation of longitude and prefix with (-) if W
		$lon_int = ($lon_ref == "W") ? '-' . $lon_int : $lon_int;
		$gps_int = array($lat_int, $lon_int);
		return $gps_int;
	}
}
