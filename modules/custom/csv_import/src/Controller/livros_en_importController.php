<?php

namespace Drupal\csv_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;


class livros_en_importController extends ControllerBase
{

    public function import_livros_en()
    {

        function getTidByName($name = NULL, $vid = NULL)
        {
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
        $publicacoes = $filePath . "/livros-payload-en.csv";
        $h = fopen($publicacoes, "r");

        while (($data = fgetcsv($h, 100000, "|")) !== FALSE) {
            $base[] = $data;
        }
        fclose($h);
//        kint($base);
//        exit();
        $head = array_shift($base);
        $autor = array();
        $count = 0;
        foreach ($base as $value) {

            $files = explode('#', $value[6]);
            $description = explode('#', $value[7]);
            if (count($files) != count($description)) {
                echo "A quantidade de descrição não está igual a quantiade de arquivos: linha: " . $count;
            }
            {
                $file_entity = array_combine($files, $description);
            }
            $count++;
            $node = Node::load($value[0]);
            $translated_fields = [];
            $documentos = [];

            foreach ($file_entity as $file => $descricao) {

                $file_source = $filePath . "/publicacoes-migrated-files-mari/" . $file;

                if (file_exists($file_source) && is_file($file_source)) {
                    $uri = file_unmanaged_copy($file_source, 'public://' . $file, FILE_EXISTS_REPLACE);
                    $files = File::Create(['uri' => $uri]);
                    $files->save();
                    $documentos[] = [
                        'target_id' => $files->id(),
                        'description' => $descricao,
                    ];
                    $translated_fields['field_publicacoes_arquivo'] = $documentos;
                }
                if (file_exists($file_source)) {
                    $log = $filePath . '/logs/arquivos-faltantes-publicacoes.txt';
                    $current = file_get_contents($log);
                    $current .= "{$file}\n";
                    file_put_contents($log, $current);
                }
            }
            if (!empty($value[1])) {
                $translated_fields['title'] = $value[1];
            } else {
                $translated_fields['title'] = 'SEM TITULO';
            }
            $body = [
                'value' => $value[2],
                'format' => 'full_html',
            ];
            $link = [
                'uri' => $value[4],
                'title' => $value[5],
            ];
            if ($value[9] != "") {
                $file_source = $filePath . "/publicacoes-migrated-files-mari/" . $value[9];
                $uri = file_unmanaged_copy($file_source, 'public://' . $value[9], FILE_EXISTS_REPLACE);
                $files = File::Create(['uri' => $uri]);
                $files->save();
                $imagem_capa = [
                    'target_id' => $files->id(),
                    'alt' => 'Imagem Capa do Livro',
                    'title' => 'Imagem Capa do Livro'
                ];
                $translated_fields['field_publicacoes_thumbnail'] = $imagem_capa;
            }
            $translated_fields['field_publicacoes_autores'] = $value[8];
            $translated_fields['body'] = $body;
            $translated_fields['field_publicacoes_link'] = $link;
            $translated_fields['field_ano_de_publicacao'] = $value[3];

            $node->addTranslation('en', $translated_fields)->save();
        }
        drupal_set_message("Foram registrados" . $count . " nodes!\n");
        return [
            '#type' => 'markup',
            '#markup' => $this->t('Importação realizada com sucesso!'),
        ];
    }
}



