# Geocoded Post Query Params Plugin
 Add a Geocode param to the WP_Query to pull back geocoded posts / custom post types
 
 Also has an update function to update ACF Google Map enabled Posts / Pages to work with this plugin.
 
```php
'geo_query' => array(
 'lat_field' => 'loc_lat',  // this is the name of the meta field storing latitude
 'lng_field' => 'loc_lng', // this is the name of the meta field storing longitude 
 'latitude'  => $origin_lat_lng['lat'],    // this is the latitude of the point we are getting distance from
 'longitude' => $origin_lat_lng['lng'],   // this is the longitude of the point we are getting distance from
 'distance'  => $proximity,           // this is the maximum distance to search
 'units'     => 'miles'       // this supports options: miles, mi, kilometers, km
),
```