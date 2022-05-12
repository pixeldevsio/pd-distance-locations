<?php
if ( ! defined( 'ABSPATH' ) ) {
	die(); } // Include in all php files, to prevent direct execution
/**
 * Plugin Name: WP Geo Query
 * Plugin URI: https://pixeldevs.io
 * Description: Adds location search support to WP_Query, making it easy to create completely custom "Find Location" pages. Also works with ACF Google Map field!
 * Author: PixelDevs
 * Author URI: https://pixeldevs.io
 * Version: 1.0.0
 */

/**
* Geocoded Post Query Params Plugin
*  Add a Geocode param to the WP_Query to pull back geocoded posts / custom post types
*
* Also has an update function to update ACF Google Map enabled Posts / Pages to work with this plugin.
*
* 'geo_query' => array(
*  'lat_field' => 'loc_lat',  // this is the name of the meta field storing latitude
*  'lng_field' => 'loc_lng', // this is the name of the meta field storing longitude
*  'latitude'  => $origin_lat_lng['lat'],    // this is the latitude of the point we are getting distance from
*  'longitude' => $origin_lat_lng['lng'],   // this is the longitude of the point we are getting distance from
*  'distance'  => $proximity,           // this is the maximum distance to search
*  'units'     => 'miles'       // this supports options: miles, mi, kilometers, km
* ),
*/

require plugin_dir_path( __FILE__ ) . 'class-pdgeoquery.php';
