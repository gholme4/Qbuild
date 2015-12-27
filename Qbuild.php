<?php

/*
 * Qbuild
 * Class that creates a REST API with routes mapped to all tables in a MySQL database. Designed to be used with Qbuild.js client side.
 * Built on Slim framework and Idiorm ORM
 *
 * @copyright Copyright (c) 2016 George Holmes II
 */

class Qbuild {

	/**
	* @param bool $app \Slim\App instance
	*/
	public $app;

	/**
	* @param string $apiKey
	*/
	protected $apiKey;

	/**
	* @param bool $apiKey
	*/
	protected $apiKeyRequired = false;

	/**
     * Constructor
     *
     * @param array $config Associative array of configuration options
     * @return null
     */
	function __construct ($config) {

		$this->apiKey = $config['apiKey'];
		$this->apiKeyRequired = $config['apiKeyRequired'];

		$this->app = new \Slim\App();

		return;

		if ($this->apiKeyRequired == true)
		{
			$this->checkApiKey();
		}
	}

	/**
	* Add middleware to authenticate requests with API key
	*/
	protected function checkApiKey () {
		// API key check middleware
		$this->app->add(function ($request, $response, $next) use ($api_key) {
		    
		    // If API is invalid, send error message in response
		    if ($request->getHeader("x-qbuild-api-key")[0] != $this->apiKey)
		    {   
		        $newResponse = $response;
		        $newResponse->withStatus(403);
		        $newResponse->write(json_encode(array("error" => "Invalid API key")));
		        return $newResponse;
		    }

		    $response = $next($request, $response);

		    return $response;
		});
	}

	public function run () {
		$this->findRowsRoute();
		$this->insertRowRoute();
		$this->updateRowRoute();
		$this->app->run();
	}

	/**
	* Create POST route for updating a row
	*/
	protected function insertRowRoute () {
		// Save Model
		$this->app->post('/{table}/create', function ($request, $response, $args) {

			$newResponse = $response->withHeader('Content-type', 'application/json');

			try {
				
				
				// Create model from query, set new parameters, and save ti
				$model = ORM::for_table($args['table']);
				$model->set($request->getParam('params'));
				$success = $model->save();
				$response_body = array("success" => $success);

			}
			catch (Exception $e ) {
				error_log(ORM::get_last_statement()->queryString);
				error_log($e->getMessage());
				$response_body = array("error" => $e->getMessage());
			}

			// Add data to response in JSON format
			$newResponse->write(json_encode($response_body));

		    return $newResponse;
		});
	}

	/**
	* Create POST route for updating a row
	*/
	protected function updateRowRoute () {
		// Save Model
		$this->app->post('/{table}/save', function ($request, $response, $args) {

			$newResponse = $response->withHeader('Content-type', 'application/json');

			try {
				// Get primary key of table from response
				$primary_key = $request->getParam('primary_key');

				// Get primary key value of this model from response
				$primary_key_value = $request->getParam('params')[$primary_key];

				// Set table's primary key for saving model
				ORM::configure('id_column_overrides', array(
				    $args['table'] => $primary_key
				));
				
				// Create model from query, set new parameters, and save it
				$model = ORM::for_table($args['table'])->where($primary_key, intval(6))->find_many()[0];
				$model->set($request->getParam('params'));
				$success = $model->save();
				$response_body = array("success" => $success);

			}
			catch (Exception $e ) {
				error_log(ORM::get_last_statement()->queryString);
				error_log($e->getMessage());
				$response_body = array("error" => $e->getMessage());
			}

			// Add data to response in JSON format
			$newResponse->write(json_encode($response_body));

		    return $newResponse;
		});
	}

	/**
	* Create POST route for retrieving rows from table and the number of rows from table
	*/
	protected function findRowsRoute () {
		// Run Qbuild query for specific table	
		$this->app->post('/{table}', function ($request, $response, $args) {

			// Make sure response is JSON
			$newResponse = $response->withHeader('Content-type', 'application/json');
			$query = $request->getParam('query');
			$query_method = $request->getParam('query_method');

			// MySQL table to query
			$table = ORM::for_table($args['table']);

			if (!empty($query )):
				foreach($query as $command) {
					$params = $command['params'];

					// Build query
					switch ($command['method']) {
						case 'where':
							$table->where($params[0], $params[1]);
							break;

						case 'where_in':
							$table->where_in($params[0], $params[1]);
							break;

						case 'where_not_in':
							$table->where_not_in($params[0], $params[1]);
							break;

						case 'where_like':
							$table->where_like($params[0], $params[1]);
							break;

						case 'where_not_like':
							$table->where_not_like($params[0], $params[1]);
							break;

						case 'where_any_is':
							$table->where_any_is($params[0], $params[1]);
							break;

						case 'where_not_equal':
							$table->where_not_equal($params[0], $params[1]);
							break;

						case 'where_lt':
							$table->where_lt($params[0], $params[1]);
							break;

						case 'where_gt':
							$table->where_gt($params[0], $params[1]);
							break;

						case 'where_lte':
							$table->where_lte($params[0], $params[1]);
							break;

						case 'where_gte':
							$table->where_gte($params[0], $params[1]);
							break;

						case 'group_by':
							$table->group_by($params);
							break;

						case 'join':
							$table->join($params[0], array($params[1], $params[2], $params[3]) );
							break;

						case 'inner_join':
							$table->inner_join($params[0], array($params[1], $params[2], $params[3]) );
							break;

						case 'left_outer_join':
							$table->left_outer_join($params[0], array($params[1], $params[2], $params[3]) );
							break;

						case 'right_outer_join':
							$table->right_outer_join($params[0], array($params[1], $params[2], $params[3]) );
							break;

						case 'limit':
							$table->limit($params);
							break;

						case 'offset':
							$table->offset($params);
							break;

						case 'order_by_asc':
							$table->order_by_asc($params);
							break;

						case 'order_by_desc':
							$table->order_by_desc($params);
							break;

						case 'select':

							foreach ($params as $column) {
								$table->select($column);
							}
							
							break;

						default:
							
							break;
					}
				};

			endif;
			
			// Attempt to run query
			try {
				switch ($query_method) {
					case 'find':
						$results = array();

						// Returns rows found for "find"
						$rows = $table->find_many();
						foreach ($rows as $row) {
							$result = $row->as_array();
							$results[] = $result;
						}
						
						$response_body = array("results" => $results);

						break;
					case 'count':
						// Get number of rows for "count" and return response
						$count = intval($table->count());
						$response_body = array("count" => $count);
						
						break;

					default:
						break;
				}
				
			}
			catch (Exception $e ) {
				error_log(ORM::get_last_statement()->queryString);
				error_log($e->getMessage());
				$response_body = array("error" => $e->getMessage());
			}

			// Add data to response in JSON format
			$newResponse->write(json_encode($response_body));

		    return $newResponse;
		});

	}
}

?>