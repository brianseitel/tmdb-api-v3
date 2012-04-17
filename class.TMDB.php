<?

/** @preserve
*
* TMDB API Wrapper v3.0
* A PHP wrapper to interface with The Movie Database's API
* http://github.com/brianseitel/tmdb-v3-api
*
* Copyright 2011, Brian Seitel
* Dual licensed under the MIT or GPL Version 2 licenses.
* http://jquery.org/license
*
* For API documentation, see the README file
* http://github.com/brianseitel
*
* Date: April 12, 2012
*/

class TMDBv3 {
	
	const VERSION = 3;
	const BASE_URL = 'api.themoviedb.org';

	protected static $api_key = '';
	public $image_base_path;
	public $sizes = array(
		'poster' => '',
		'backdrop' => '',
		'profile' => ''
	);

	public function __construct() {
		self::$api_key = TMDB_API_KEY;
		$this->fetch_configuration();
	}

	public function fetch_configuration() {
		$results = $this->send_request('/configuration');
		$results = $results['images'];
		// Get poster sizes
		$poster_sizes = $results['poster_sizes'];

		if (in_array('w185', $poster_sizes))
			$this->sizes['poster'] = 'w185';
		else
			$this->sizes['poster'] = array_shift(array_shift($poster_sizes));
		
		// Get backdrop sizes
		$backdrop_sizes = $results['backdrop_sizes'];

		// Get the biggest non-original one for bandwidth or whatever,
		// and fall back on original
		if (in_array('w1280', $backdrop_sizes))
			$this->sizes['backdrop'] = 'w1280';
		else
			$this->sizes['backdrop'] = 'original';

		// Get profile sizes. Start with w185 and fall back to original.
		$profile_sizes = $results['profile_sizes'];
		if (in_array('w185', $profile_sizes))
			$this->sizes['profile'] = 'w185';
		else
			$this->sizes['profile'] = 'original';

		$this->image_base_path = $results['base_url'];
	}
	
	/**
	 * Search for a movie
	 * @param string $query The search query
	 * @return Array - a json_decoded result set
	 */
	public function search_for_movie($query) {
		$params = array('query' => $query);
		return $this->send_request('/search/movie', $params);
	}

	/**
	 * Search for a person
	 * @param string $query the search term
	 * @return Array - a json_decoded result set
	 */
	public function search_for_person(string $query) {
		$params = array('query' => $query);
		return $this->send_request('/search/person', $params);
	}

	/**
	 * Helper function to get the results of more than one part
	 * in one method
	 * @param int $id The movie ID
	 * @param mixed $parts The different parts to request
	 */
	public function get_movie_batch(int $id, array $parts) {
		$data = array('core' => $this->get_movie($id));
		foreach ($parts as $part) {
			$data[$part] = $this->get_movie($id, $part);
		}
		return $data;
	}
	/**
	 * Fetches a collection of movies (e.g., The Indiana Jones collection or the Star Wars collection)
	 * @param int $id The ID of the collection to fetch
	 * @return Array - a json_decoded result set
	 */ 
	public function get_collection(int $id) {
		return $this->__request($id, 'collection');
	}

	/**
	 * Fetches info for a movie
	 * @param int $id - The ID of the movie to fetch
	 * @param string $part - The part of the movie to fetch. Defaults to core.
	 * @return  Array - a json_decoded result set
	 */
	public function get_movie(int $id, string $part = null) {
		return $this->__request($id, 'movie', $part);
	}

	/**
	 * Fetches info for a person
	 * @param int $id - The ID of the person to fetch
	 * @param string $part - The part of the person to fetch. Defaults to core.
	 * @return  Array - a json_decoded result set
	 */

	public function get_person(int $id, string $part = null) {
		return $this->__request($id, 'person', $part);
	}

	/**
	 * Fetches info for a company
	 * @param int $id - The ID of the company to fetch
	 * @param string $part - The part of the company to fetch. Defaults to core.
	 * @return  Array - a json_decoded result set
	 */
	public function get_company(int $id, string $part = null) {
		return $this->__request($id, 'company', $part);
	}

	/**
	 * Add a rating to a movie
	 * @param int $movie_id - The ID of the movie to rate
	 * @param float $rating - the rating between 1 and 10
	 * @return  Array - a json_decoded result set
	 */
	public function add_rating(int $movie_id, float $rating) {
		if (!is_numeric($rating) || $rating < 1 || $rating > 10) 
			return array('error' => 'Rating must be a float value between 1 an 10');
		
		return $this->send_request('/movie/'.$movie_id.'/rating', array('value' => $rating), 'POST');
	}

	/**
	 * Constructs the image path
	 * @param String $path Path provided by API response
	 * @return  String full image path
	 */
	public function get_image_path($path, $type = 'poster') {
		if (!$path) return '';
		return preg_replace('/http:\/\/([\/\/]+)/', '/', $this->image_base_path.'/'.$this->sizes[$type].'/'.$path);
	}

	/**
	 * Get the latest movie
	 * @return  Array - a json_decoded result set
	 */
	public function get_latest_movie() {
		return $this->send_request('/latest/movie');
	}

	/**
	 * Get any movies that are now playing
	 * @return  Array - a json_decoded result set
	 */
	public function get_playing_movies() {
		return $this->send_request('/movie/now-playing');
	}

	/**
	 * Get the top rated movies
	 * @return  Array - a json_decoded result set
	 */
	public function get_toprated_movies() {
		return $this->send_request('/movie/top-rated');
	}

	/**
	 * Get the most popular movies
	 * @return  Array - a json_decoded result set
	 */
	public function get_popular_movies() {
		return $this->send_request('/movie/popular');
	}

	/**
	 * A generic request
	 * @param int $id The object ID
	 * @param string $object The object to query
	 * @param string $part The part of the API to query
	 * @return Array a json_decoded result set
	 */
	public function __request(int $id, string $object, string $part = null) {
		return $this->send_request('/'.$object.'/'.$id.($part ? '/'.$part : ''));
	}
	
	/**
	 * Make an API request to TMDB
	 * @param $path - The path in the API (e.g, movie/search)
	 * @param $params - The parameters to pass in
	 * @param $method - Whether to POST, GET, PUT or DELETE. Defaults to GET
	 * @return Array - a json_decoded array of the result set
	 */
	public function send_request(string $path, array $params = array(), string $method = 'GET') {
		$url = $this->url($path);

		$ch = curl_init();

		if($method == 'POST') {
			curl_setopt($ch,CURLOPT_POST, 1);
			curl_setopt($ch,CURLOPT_POSTFIELDS, $params);
		} else {
			foreach ($params as $key => $value)
				$url .= '&'.urlencode($key).'='.urlencode($value);
		}

		// urlencode
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);

		$results = curl_exec($ch);
		$headers = curl_getinfo($ch);

		$error_number = curl_errno($ch);
		$error_message = curl_error($ch);

		if ($error_message) {
			$data = array(
				'headers' => $headers,
				'results' => $results,
				'error_number' => $error_number,
				'error_message' => $error_message
			);
			return $data;
		}
		curl_close($ch);
		return json_decode($results, 1);
	}


	/**
	 * A helper function to generate the full URL
	 * @param $path - the path in the API (e.g., movie/search')
	 * @return  String - the full URL
	 */
	public function url(string $path) {
		$url = self::BASE_URL.'/'.self::VERSION."{$path}?api_key=".self::$api_key;
		return str_replace('//', '/', $url);
	}
}