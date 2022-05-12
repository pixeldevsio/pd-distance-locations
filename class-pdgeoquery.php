<?php
// Check if ACF is installed.
if ( has_action( 'acf/update_value/type=google_map' ) ) {
	add_action( 'acf/update_value/type=google_map', 'wpq_update_lng_and_lat', 99, 3 );
}

/**
 * Wpq_update_lng_and_lat
 *
 * @param  array   $value Latitude and longitude.
 * @param  int     $post_id Post ID.
 * @param  varchar $field Field name.
 * @return array $value Latitude and longitude.
 */
function wpq_update_lng_and_lat( $value, $post_id, $field ) {
	update_post_meta( $post_id, 'loc_lat', $value['lat'] );
	update_post_meta( $post_id, 'loc_lng', $value['lng'] );
	return $value;
}

if ( ! class_exists( 'PDGeoQuery' ) ) {
	/**
	 * PDGeoQuery
	 */
	class PDGeoQuery {

		/**
		 * Instance
		 *
		 * @return self
		 */
		public static function instance() {
			static $instance = null;
			if ( null === $instance ) {
				$instance = new self();
			}
			return $instance;
		}

		/**
		 * __construct
		 *
		 * @return void
		 */
		private function __construct() {
			add_filter( 'posts_fields', array( $this, 'posts_fields' ), 10, 2 );
			add_filter( 'posts_join', array( $this, 'posts_join' ), 10, 2 );
			add_filter( 'posts_where', array( $this, 'posts_where' ), 10, 2 );
			add_filter( 'posts_orderby', array( $this, 'posts_orderby' ), 10, 2 );
		}

		/**
		 * Posts_fields
		 *
		 * @param  SQL   $sql SQL.
		 * @param  array $query Query.
		 * @return SQL
		 * add a calculated "distance" parameter to the sql query, using a haversine formula
		 */
		public function posts_fields( $sql, $query ) {
			global $wpdb;
			$geo_query = $query->get( 'geo_query' );
			if ( $geo_query ) {

				if ( $sql ) {
					$sql .= ', ';
				}
				$sql .= $this->haversine_term( $geo_query ) . ' AS geo_query_distance';
			}
			return $sql;
		}

		/**
		 * Posts_join
		 *
		 * @param  SQL   $sql SQL statement.
		 * @param  array $query WP_Query object.
		 * @return SQL
		 */
		public function posts_join( $sql, $query ) {
			global $wpdb;
			$geo_query = $query->get( 'geo_query' );
			if ( $geo_query ) {

				if ( $sql ) {
					$sql .= ' ';
				}
				$sql .= 'INNER JOIN ' . $wpdb->prefix . 'postmeta AS geo_query_lat ON ( ' . $wpdb->prefix . 'posts.ID = geo_query_lat.post_id ) ';
				$sql .= 'INNER JOIN ' . $wpdb->prefix . 'postmeta AS geo_query_lng ON ( ' . $wpdb->prefix . 'posts.ID = geo_query_lng.post_id ) ';
			}
			return $sql;
		}

		/**
		 * Posts_where
		 *
		 * @param  SQL      $sql SQL statement.
		 * @param  WP_Query $query WP_Query object.
		 * @return SQL
		 * match on the right metafields, and filter by distance
		 */
		public function posts_where( $sql, $query ) {
			global $wpdb;
			$geo_query = $query->get( 'geo_query' );
			if ( $geo_query ) {
				$lat_field = 'latitude';
				if ( ! empty( $geo_query['lat_field'] ) ) {
					$lat_field = $geo_query['lat_field'];
				}
				$lng_field = 'longitude';
				if ( ! empty( $geo_query['lng_field'] ) ) {
					$lng_field = $geo_query['lng_field'];
				}
				$distance = 20;
				if ( isset( $geo_query['distance'] ) ) {
					$distance = $geo_query['distance'];
				}
				if ( $sql ) {
					$sql .= ' AND ';
				}
				$haversine = $this->haversine_term( $geo_query );
				$new_sql   = '( geo_query_lat.meta_key = %s AND geo_query_lng.meta_key = %s AND ' . $haversine . ' <= %f )';
				$sql      .= $wpdb->prepare( $new_sql, $lat_field, $lng_field, $distance );
			}
			return $sql;
		}

		/**
		 * Posts_orderby
		 *
		 * @param  SQL      $sql SQL statement.
		 * @param  WP_Query $query WP_Query object.
		 * @return SQL
		 * handle ordering by distance
		 */
		public function posts_orderby( $sql, $query ) {
			$geo_query = $query->get( 'geo_query' );
			if ( $geo_query ) {
				$orderby = $query->get( 'orderby' );
				$order   = $query->get( 'order' );
				if ( 'distance' == $orderby ) {
					if ( ! $order ) {
						$order = 'ASC';
					}
					$sql = 'geo_query_distance ' . $order;
				}
			}
			return $sql;
		}

		/**
		 * The_distance
		 *
		 * @param  WP_Post_Object $post_obj WP_Post object.
		 * @param  booleon        $round Round the distance.
		 * @return void
		 */
		public static function the_distance( $post_obj = null, $round = false ) {
			echo esc_html( self::get_the_distance( $post_obj, $round ) );
		}

		/**
		 * Get_the_distance
		 *
		 * @param  WP_Post_Object $post_obj WP_Post object.
		 * @param  booleon        $round Round the distance.
		 * @return int or false on failure.
		 */
		public static function get_the_distance( $post_obj = null, $round = false ) {
			global $post;
			if ( ! $post_obj ) {
				$post_obj = $post;
			}
			if ( property_exists( $post_obj, 'geo_query_distance' ) ) {
				$distance = $post_obj->geo_query_distance;
				if ( false !== $round ) {
					$distance = round( $distance, $round );
				}
				return $distance;
			}
			return false;
		}

		/**
		 * Haversine_term
		 *
		 * @param  Query $geo_query Geo_query object.
		 * @return SQL
		 */
		private function haversine_term( $geo_query ) {
			global $wpdb;
			$units = 'miles';
			if ( ! empty( $geo_query['units'] ) ) {
				$units = strtolower( $geo_query['units'] );
			}
			$radius = 3959;
			if ( in_array( $units, array( 'km', 'kilometers' ), true ) ) {
				$radius = 6371;
			}
			$lat_field = 'geo_query_lat.meta_value';
			$lng_field = 'geo_query_lng.meta_value';
			$lat       = 0;
			$lng       = 0;
			if ( isset( $geo_query['latitude'] ) ) {
				$lat = $geo_query['latitude'];
			}
			if ( isset( $geo_query['longitude'] ) ) {
				$lng = $geo_query['longitude'];
			}
			$haversine  = '( ' . $radius . ' * ';
			$haversine .= 'acos( cos( radians(%f) ) * cos( radians( ' . $lat_field . ' ) ) * ';
			$haversine .= 'cos( radians( ' . $lng_field . ' ) - radians(%f) ) + ';
			$haversine .= 'sin( radians(%f) ) * sin( radians( ' . $lat_field . ' ) ) ) ';
			$haversine .= ')';
			$haversine  = $wpdb->prepare( $haversine, array( $lat, $lng, $lat ) );
			return $haversine;
		}
	}
	PDGeoQuery::instance();
}

if ( ! function_exists( 'the_distance' ) ) {
	/**
	 * The_distance
	 *
	 * @param  WP_POST_OBJECT $post_obj Post object or post ID.
	 * @param  booleon        $round Round the distance.
	 * @return void
	 */
	function the_distance( $post_obj = null, $round = false ) {
		PDGeoQuery::the_distance( $post_obj, $round );
	}
}

if ( ! function_exists( 'get_the_distance' ) ) {
	/**
	 * Get_the_distance
	 *
	 * @param  WP_POST_OBJECT $post_obj Post object or post ID.
	 * @param  booleon        $round Round the distance.
	 * @return int or false on failure.
	 */
	function get_the_distance( $post_obj = null, $round = false ) {
		return PDGeoQuery::get_the_distance( $post_obj, $round );
	}
}
