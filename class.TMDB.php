<?

class TMDB {
	
	const VERSION = 3;
	const BASE_URL = 'http://api.themoviedb.org/';

	protected static $api_key = '';

	public function __construct($api_key) {
		self::$api_key = $api_key;
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
	public function search_for_person($query) {
		$params = array('query' => $query);
		return $this->send_request('/search/person', $params);
	}

	/**
	 * Fetches a collection of movies (e.g., The Indiana Jones collection or the Star Wars collection)
	 * @param int $id The ID of the collection to fetch
	 * @return Array - a json_decoded result set
	 */ 
	public function get_collection($id) {
		return $this->__request($id, 'collection');
	}

	/**
	 * Fetches info for a movie
	 * @param int $id - The ID of the movie to fetch
	 * @param string $part - The part of the movie to fetch. Defaults to core.
	 * @return  Array - a json_decoded result set
	 */
	public function get_movie($id, $part = null) {
		return $this->__request($id, 'movie', $part);
	}

	/**
	 * Fetches info for a person
	 * @param int $id - The ID of the person to fetch
	 * @param string $part - The part of the person to fetch. Defaults to core.
	 * @return  Array - a json_decoded result set
	 */

	public function get_person($id, $part = null) {
		return $this->__request($id, 'person', $part);
	}

	/**
	 * Fetches info for a company
	 * @param int $id - The ID of the company to fetch
	 * @param string $part - The part of the company to fetch. Defaults to core.
	 * @return  Array - a json_decoded result set
	 */
	public function get_company($id, $part = null) {
		return $this->__request($id, 'company', $part);
	}

	/**
	 * Add a rating to a movie
	 * @param int $movie_id - The ID of the movie to rate
	 * @param float $rating - the rating between 1 and 10
	 * @return  Array - a json_decoded result set
	 */
	public function add_rating($movie_id, float $rating = 0.0) {
		if (!is_numeric($rating) || $rating < 1 || $rating > 10) 
			return array('error' => 'Rating must be a float value between 1 an 10');
		
		return $this->send_request('/movie/'.$movie_id.'/rating', array('value' => $rating), 'POST');
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
	public function __request($id, $object, $part = null) {
		return $this->send_request('/'.$object.'/'.$id.($part ? '/'.$part : ''));
	}
	
	/**
	 * Make an API request to TMDB
	 * @param $path - The path in the API (e.g, movie/search)
	 * @param $params - The parameters to pass in
	 * @param $method - Whether to POST, GET, PUT or DELETE. Defaults to GET
	 * @return Array - a json_decoded array of the result set
	 */
	public function send_request($path, $params = array(), $method = 'GET') {
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
	public function url($path) {
		$url = self::BASE_URL.self::VERSION."{$path}?api_key=".self::$api_key;
		return str_replace('//', '/', $url);
	}
}