<?php

namespace Drupal\custom_punkapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;
use \Drupal\Core\Database\Database;

/**
 * Class MyController.
 *
 * @package Drupal\custom_punkapi\Controller
 */
class GetBeers extends ControllerBase {

  /**
   * Posts route callback.
   *
   *
   * @return array
   *   A render array used to show the Posts list.
   */
  public function getBeers() {

    // check if tables exists and create them if not
    self::create_tables();

    // request api
    $client = new \GuzzleHttp\Client();
    $request = $client->request('GET', 'https://api.punkapi.com/v2/beers?per_page=80');

    // check request status
    if ($request->getStatusCode() != 200) {
      return;
    }

    // get body contents and decode json
    $beers = $request->getBody()->getContents();
    $data = json_decode( $beers );

    // create connection to databse and add beers/ingredients
    $connection = \Drupal::service('database');
    $beers_list = self::add_beers($connection, $data);
    //$ingredients_list = self::add_ingredients($connection, $data);

    // construct variable to be displayed
    $markup = '';
    if(!empty($beers_list)){
      $markup = '<ul>';
      foreach ($beers_list as $beer){
        $markup .= '<li>'.$beer['name'].' brewed on '.date('m/Y', $beer['date_brewed']).'</li>';
      }
      $markup .= '</ul>';
    }else{
      $markup = 'There are no beers to add';
    }

    // add logger
    \Drupal::logger('custom_punkapi')->notice('@type: '.$markup,
        array(
            '@type' => 'beers added',
        ));

    return [
      '#theme' => 'api_results',
      '#list_beer' => $markup,
      '#attached' => [
        'library' => [
          'custom_punkapi/api_styling',
        ],
      ],
    ];
  }

  /**
   * Implements table creation.
   * Private as only this class should call the function
   */
  private function create_tables(){
    // creating database table happens using the schema API
    // initiate schema
    $database = \Drupal::database();
    $schema = $database->schema();

    // check if table beers exists
    if(!$database->schema()->tableExists('beers')){
      // create table beers with an autoincrement primary key column "id"
      // two data columns: name and date of brewery
      $table_name = 'beers';
      $table_schema = [
        'fields' => [
          'id' => [
            'type' => 'serial',
            'not null' => TRUE,
          ],
          'name' => [
            'type' => 'varchar',
            'not null' => TRUE,
            'length' => 255,
          ],
          'date_brewed' => [
            'type' => 'int',
            'not null' => FALSE,
          ],
          'ingredients' => [
            'type' => 'text',
            'not null' => FALSE,
          ],
        ],
        'primary key' => ['id'],
      ];
      $schema->createTable($table_name, $table_schema);
    }

    // check if table ingredients exists
    // this is option two just to show skills and knowledge in table relations
    // we will call this "option 2"
    /*if(!$database->schema()->tableExists('ingredients')){
      // create table ingredients with an autoincrement primary key column "id"
      // two data columns: name and beer id (foreign key) though not necessary in Drupal
      $table_name = 'ingredients';
      $table_schema = [
        'fields' => [
          'id' => [
            'type' => 'serial',
            'not null' => TRUE,
          ],
          'name' => [
            'type' => 'varchar',
            'not null' => TRUE,
            'length' => 255,
          ],
          'id_beer' => [
            'type' => 'int',
            'not null' => TRUE,
          ],
        ],
        'primary key' => ['id'],
        'foreign keys' => [
          'beer' => [
            'table' => 'beers',
            'columns' => [
              'id_beer' => 'id',
            ],
          ],
        ],
      ];

      $schema->createTable($table_name, $table_schema);
    }*/

    // another way of implementing this is by creating a table ingredients and ingredients data
    // ingredients would contain the name and info for each ingredient with name being a unique value
    // ingredients data contains id beer and id ingredient to reference them
    // this can allow the use of ingredients as "taxonomies" in our case it is not essentiel but this approach would be more scalable
    // allowing filtering in views as select list
    /*if(!$database->schema()->tableExists('ingredients')){
      $table_name = 'ingredients';
      $table_schema = [
        'fields' => [
          'id' => [
            'type' => 'int',
            'not null' => TRUE,
          ],
          'name' => [
            'type' => 'varchar',
            'not null' => TRUE,
            'length' => 255,
          ],
        ],
        'primary key' => ['id'],
      ];

      $schema->createTable($table_name, $table_schema);
    }

    if(!$database->schema()->tableExists('ingredients_data')){
      $table_name = 'ingredients_data';
      $table_schema = [
        'fields' => [
          'id_beer' => [
            'type' => 'int',
            'not null' => TRUE,
          ],
          'id_ingredient' => [
            'type' => 'int',
            'not null' => TRUE,
          ],
        ],
      ];

      $schema->createTable($table_name, $table_schema);
    }*/
  }

  /**
   * Adds beers to table.
   */
  public function add_beers($connection, $data){
    // create empty array to be filled with list of beers added
    $beers_list = array();

    foreach ($data as $beer) {
      // check if beer exists in table: return beer id when exists, 0 when not
      $result_count = self::check_beer($connection, $beer->name);

      if($result_count == 0){
        // if option 2 adopdet this code is not need as ingredients would be added seperately
        // check add_ingredients() function
        $ingredients = '';
        foreach ($beer->ingredients as $ingredient_type) {
          foreach ($ingredient_type as $value) {
            $ingredients .= $value->name;
            if (next($ingredient_type) == true) $ingredients .= ", ";
          }
        }

        // add beer to beers list to be added as beer do not exist in database
        $single_beer = array(
          'name' => $beer->name,
          'date_brewed' => strtotime(date('Y-d-m', strtotime('01/' . str_replace('-', '/', $beer->first_brewed)))),
          //date('Y-m-d', strtotime(date('Y-d-m', strtotime('01/' . str_replace('-', '/', $beer->first_brewed)))))
          'ingredients' => $ingredients
        );
        array_push($beers_list, $single_beer);
      }
    }

    // use connection to insert beers from list
    $query = $connection->insert('beers')->fields(['name', 'date_brewed', 'ingredients']);
    foreach ($beers_list as $record) {
      $query->values($record);
    }
    $query->execute();

    // return the list of beers added to be diplayed
    return $beers_list;
  }

  /**
   * Add ingredients to table.
   * this is option 2 code to add ingredients in seperate relationed table
   * this code has been added to show skills in php and object-oriented architecture
   */
  public function add_ingredients($connection, $data){
    // create emty list of ingredients to be added
    $ingredients_list = array();
    //$ingredients_data = array();

    foreach ($data as $beer) {
      // get the beer id, beers are added first to get their id and insert it as foreign key
      $beer_id = self::get_beer_id($connection, $beer->name);

      foreach ($beer->ingredients as $ingredient_type) {
        foreach ($ingredient_type as $value) {

          // check if ingredient already exists in table ingredients, name column is not unique thus check foreign key also
          $result_count = self::check_ingredient($connection, $value->name, $beer_id);

          if($result_count == 0){
            // add ingredient to ingredients list to be added
            $single_ingredient = array(
              'name' => $value->name,
              'id_beer' => $beer_id
            );
            array_push($ingredients_list, $single_ingredient);
          }
        }
      }
    }

    // use connection to insert ingredients from list
    $query = $connection->insert('ingredients')->fields(['name', 'id_beer']);
    foreach ($ingredients_list as $record) {
      $query->values($record);
    }
    $query->execute();

    // return the list of ingredients added to be diplayed
    return $ingredients_list;
  }

  /**
   * Check if beer exists in table.
   * Parameters are the database connection and name of beer
   * Returns beer id when exist, count (equals 0) otherwise
   */
  public function check_beer($connection, $name){
    $query = $connection->select('beers', 'b');
    $result_count = $query->condition('b.name', $name)->countQuery()->execute()->fetchField();

    if($result_count > 0){
      // get the beer id in case exists
      return self::get_beer_id($connection, $name);
    }

    return $result_count;
  }

  /**
   * Check if ingredient exists in table. (valid for option 2)
   * Parameters are the database connection and name of ingredient and beer id in the databse
   * Returns count, 1 if exists and 0 otherwise
   */
  public function check_ingredient($connection, $name, $beer_id){
    $query = $connection->select('ingredients', 'i');
    $query = $query->condition('i.name', $name);
    $query = $query->condition('i.id_beer', $beer_id);
    $result_count = $query->countQuery()->execute()->fetchField();
    return $result_count;
  }

  /**
   * Get beer id.
   * Parameters are the database connection and name of beer
   * Returns beer id
   */
  public function get_beer_id($connection, $name){
    $query = $connection->select('beers', 'b');
    $query->condition('b.name', $name);
    $query->addField('b', 'id', 'id');
    $result = $query->execute()->fetchAll();

    foreach ($result as $key => $beer){
      return $beer->{'id'};
    }
  }
}
