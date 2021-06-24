<?php
/**
 * @file
 * Contains \Drupal\custom_punkapi\Form\BeersData.
 */
namespace Drupal\custom_punkapi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\Core\Database\Database;

/**
 * Provides data view and manipulation.
 */
class BeersData extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'beers.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'beers_data_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('beers.adminsettings');

    // get all beers to be displayed in form
    $connection = \Drupal::service('database');
    $query = $connection->select('beers', 'b');
    $query->addField('b', 'id', 'id');
    $query->addField('b', 'name', 'name');
    $query->addField('b', 'date_brewed', 'date');
    $query->addField('b', 'ingredients', 'ingredients');
    $result = $query->execute()->fetchAll();

    // display beers data in suitable form fields with distrinct ids
    foreach ($result as $key => $val){
      $form[$val->id.'-name'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Beer name'),
        //'#description' => $this->t('Enter the name of the beer.'),
        '#required' => TRUE,
        '#default_value' => $val->name,
        '#prefix' => '<div class="beer"><h2>'.$val->name.'</h2>',
      );

      $form[$val->id.'-date_brewed'] = array(
        '#type' => 'date',
        '#title' => $this->t('Beer date'),
        //'#description' => $this->t('Enter the brewery date of the beer.'),
        '#required' => TRUE,
        '#default_value' => date("Y-m-d", $val->date),
      );

      $form[$val->id.'-ingredients'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Beer ingredients'),
        //'#description' => $this->t('Enter the ingredients of the beer.'),
        '#required' => TRUE,
        '#default_value' => $val->ingredients,
        '#suffix' => '</div>',
      );
    }

    // add the submit action
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );
    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // no extra validation is necessary
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $beers_list = array();

    // fetch through form state to get all values from submitted form
    // the following code can be exhaustive if number of beers is very large
    foreach($form_state->getValues() as $key => $val){
      if($key != 'submit' && $key != 'form_build_id' && $key != 'form_token' && $key != 'form_id' && $key != 'op'){
        $pieces = explode("-", $key);

        // add beer to beers list to be updated
        if($pieces[1] == 'date_brewed')
          $val = strtotime($val);
        $beers_list[$pieces[0]][$pieces[1]] = $val;
      }
    }

    // define text to be logged
    $logger_text = '<ul>';

    // use connection to update beers from form
    $connection = \Drupal::service('database');
    foreach ($beers_list as $key => $record) {
      $logger_text .= '<li>'.$record['name'].' brewed on '.date('m/Y', $beer['date_brewed']).'</li>';

      $query = $connection->update('beers');
      $query->fields($record);
      $query->condition('id', $key, '=');
      $query->execute();
    }

    $logger_text .= '</ul>';

    // add logger
    \Drupal::logger('custom_punkapi')->notice('@type: '.$logger_text,
        array(
            '@type' => 'beers modified',
        ));
  }

}
