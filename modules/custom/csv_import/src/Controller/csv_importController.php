<?php

namespace Drupal\csv_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionForm;
use \Symfony\Component\HttpFoundation\Response;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;
use ParseCsv\Csv;

class csv_importController extends ControllerBase {
  
  public function import_webform_csv() {
    $filePath = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $filename = $filePath . '/tabela_usuarios_cem4.csv';
    $csv = new Csv($filename);
    $webform_id = 'cadastro_curso';
    $values = array();
    foreach($csv->data as $value) {
      $values[] = [
        'webform_id' => $webform_id,
        'entity_type' => NULL,
        'entity_id' => NULL,
        'in_draft' => FALSE,
        'uid' => 1,
        'langcode' => 'pt-br',
        'token' => 'O56qkNgoP9IOH6ohhYriW7SI3rgZdCnhEZFRkJqvZUE',
        'uri' => '/pt-br/webform/cadastro_curso/api',
        'remote_addr' => '::1',
        'data' => array(
          'nome' => $value['nome'],
          'e_mail' => $value['email'],
          'instituicao' => $value['instituicao'],
          'cidade' => $value['municipio'],
        ),
      ];
    }
    // Check webform is open.
    $webform = Webform::load($webform_id);
    if ($webform != NULL) {
      $is_open = WebformSubmissionForm::isOpen($webform);
      if ($is_open === TRUE) {
        // Submit values and get submission ID.
        foreach($values as $var) {
          $errors = \Drupal\webform\WebformSubmissionForm::validateFormValues($var);
          // Check there are no validation errors.
          if(empty($errors)) {
            $webform_submission = \Drupal\webform\WebformSubmissionForm::submitFormValues($var);
          }
        }
      }
    }
   return [
      '#type' => 'markup',
      '#markup' => $this->t('Importação realizada com sucesso!'),
    ];
  }
  
  public function import_base_dados() {
    
    function getTidByName($name = NULL, $vid = NULL) {
      $properties = [];
      if (!empty($name)) {
        $properties['name'] = $name;
      }
      if (!empty($vid)) {
        $properties['vid'] = $vid;
      }
      $terms = \Drupal::entityManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties($properties);
      $term = reset($terms);
      return !empty($term) ? $term->id() : 0;
    }
    
    $filePath = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $base_de_dados = $filePath . "/dados_1.csv";
    $h =fopen($base_de_dados, "r");
    
    while (($data = fgetcsv($h, 100000, ";")) !== FALSE) {
      $base[] = $data;
    }
    fclose($h);
 //   kint($base);
//    foreach ($base as $value){
//      kint($value);
//    }
//    die();
    $head = array_shift($base);
    $tag   = array();
    $fonte = array();
    $tema  = array();
    $tipo  = array();
    
      foreach($base as $value) {
        $files       = explode('#', $value[8]);
        $description = explode('#', $value[7]);
        $combine     = array_combine($files, $description);
  
        foreach($combine as $file => $descricao){
          $file_source = $filePath . "/arquivos-para-migrar/dados/" . $file;
          $uri   = file_unmanaged_copy($file_source, 'public://' . $file, FILE_EXISTS_REPLACE);
          kint($uri);
          $files = File::Create(['uri' => $uri]);
          $files->save();
       
          kint($files);
          die();
          $documentos[] = [
            'target_id' => $files->id(),
            'description' => $descricao,
          ];
        }
        $node = Node::create(['type' => 'documentos']);
        $node->set('field_documento', $documentos);
        
        $node->set('title', $value[1]);
        $body = [
          'value' => $value[5],
          'format' => 'basic_html',
        ];
        $node->set('body', $body);
        $node->set('uid', 1);
  
        $node->set('field_documento_data_ref', $value[9]);
        $node->set('field_documento_data_lancamento', $value[10]);
//        $tags = [118, 123, 122];
  
        $tags   = explode('#', $value[4]);
        $fontes = explode('#', $value[3]);
        $temas  = explode('#', $value[2]);
        $tipos  = explode('#', $value[6]);
  
        foreach($tags as $value){
          $tag[] = getTidByName($value, 'tags_base_dados');
        }
        foreach($fontes as $value){
          $fonte[] = getTidByName($value, 'fontes');
        }
        foreach($temas as $value){
          $tema[] = getTidByName($value, 'temas');
        }
        foreach($tipos as $value){
          $tipo[] = getTidByName($value, 'tipos');
        }
   
        $node->set('field_documento_tags', $tag);
        $node->set('field_documento_fontes', $fonte);
        $node->set('field_documento_temas', $tema);
        $node->set('field_documento_tipos', $tipo);
        
        $documentos = array();
        $tag        = array();
        $fonte      = array();
        $tema       = array();
        $tipo       = array();
        $node->status = 1;
        $node->langcod = 'pt-br';
        $node->enforceIsNew();
        $node->save();
        die();
      }
      drupal_set_message("Node with nid " . $node->id() . " saved!\n");
    
      return [
        '#type' => 'markup',
        '#markup' => $this->t('Importação realizada com sucesso!'),
      ];
    
    }
  public function import_mapoteca() {
    
    function getTidByName($name = NULL, $vid = NULL) {
      $properties = [];
      if (!empty($name)) {
        $properties['name'] = $name;
      }
      if (!empty($vid)) {
        $properties['vid'] = $vid;
      }
      $terms = \Drupal::entityManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties($properties);
      $term = reset($terms);
      
      return !empty($term) ? $term->id() : 0;
    }
    $filePath = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $mapoteca = $filePath . "/mapoteca.csv";
    $h =fopen($mapoteca, "r");
    
    while (($data = fgetcsv($h, 100000, ";")) !== FALSE) {
      $base[] = $data;
    }
    fclose($h);
//    kint($base);
    $head = array_shift($base);
    $fonte      = array();
    $tema       = array();
    $tipo       = array();
    $divisao    = array();
    $documentos = array();
    $cont = 0;
    foreach($base as $value) {
      $files       = explode('#', $value[8]);
      $description = explode('#', $value[7]);
      $combine     = array_combine($files, $description);
      foreach($combine as $file => $descricao){
        $file_source = $filePath . "/arquivos-para-migrar/mapoteca/" . $file;
        $uri   = file_unmanaged_copy($file_source, 'public://' . $file, FILE_EXISTS_REPLACE);
        $files = File::Create(['uri' => $uri]);
        $files->save();
//        kint($uri);
//        die();
//        $files = file_save_data($data, 'public://' . $file, FILE_EXISTS_REPLACE);
        $documentos[] = [
          'target_id' => $files->id(),
          'description' => $descricao,
        ];
      }
      $node = Node::create(['type' => 'mapas_prontos']);
      $node->set('title', $value[1]);
      $body = [
        'value' => $value[5],
        'format' => 'basic_html',
      ];
      $node->set('body', $body);
      $node->set('uid', 1);
 
      $node->set('field_documento', $documentos);
      $node->set('field_documento_data_ref', $value[9]);
      $node->set('field_documento_data_lancamento', $value[10]);
      //        $tags = [118, 123, 122];
      
//      $tags     = explode('#', $value[4]);
      $fontes   = explode('#', $value[3]);
      $temas    = explode('#', $value[2]);
      $tipos    = explode('#', $value[6]);
      $divisoes = explode('#', $value[4]);
      
//      foreach($tags as $value){
//        $tag[] = getTidByName($value, 'tags_base_dados');
//      }
      foreach($fontes as $value){
        $fonte[] = getTidByName($value, 'fontes_mapas_prontos');
      }
      foreach($temas as $value){
        $tema[] = getTidByName($value, 'temas_mapas_prontos');
      }
      foreach($tipos as $value){
        $tipo[] = getTidByName($value, 'tipos_mapas_prontos');
      }
      foreach($divisoes as $value){
        $divisao[] = getTidByName($value, 'divisao_territorial_do_brasil');
      }
//      $node->set('field_documento_tags', $tag);
      $node->set('field_documento_fontes', $fonte);
      $node->set('field_documento_temas', $tema);
      $node->set('field_documento_tipos', $tipo);
      $node->set('field_documento_divisao', $divisao);
      //        $node->set('field_documento_data_lancamento', '1981-01-17');
      $documentos   = array();
      $fonte        = array();
      $tema         = array();
      $tipo         = array();
      $node->status = 1;
      $node->langcod = 'pt-br';
      $node->enforceIsNew();
      $node->save();
//      die();
      $cont++;
    }
//    die();
    drupal_set_message("foram registrados " . $cont . " nodes!\n");
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Importação realizada com sucesso!'),
    ];
    
  }
  
  }












