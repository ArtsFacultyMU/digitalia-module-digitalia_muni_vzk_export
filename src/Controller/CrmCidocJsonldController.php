<?php

declare(strict_types=1);

namespace Drupal\digitalia_muni_vzk_export\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for VZK jsonld routes.
 */
class CrmCidocJsonldController extends ControllerBase {

  public function export($nid, Request $request) {
    $output["@context"] = $this->getContext();
    $output["@graph"] = $this->getGraph($nid);

    $response = new CacheableJsonResponse($output, 200);
    $response->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    $response->headers->set('Content-Type', 'application/ld+json');

    return $response;
  }

  protected function getContext(): array {
    $context = [
      "crm" => "http://www.cidoc-crm.org/cidoc-crm/",
      "bp" => "https://www.beauty-patterns.org/ontology/",
      "dcterms" => "http://purl.org/dc/terms/",
      "rdfs" => "http://www.w3.org/2000/01/rdf-schema#",
      "xsd" => "http://www.w3.org/2001/XMLSchema#",
      "schema" => "https://schema.org/",
      "sourceField" => "bp:sourceField",
      "sourceValue" => "bp:sourceValue",
      "crmMappingPath" => "bp:crmMappingPath",
      "mappingNote" => "bp:mappingNote",
    ];
    return $context;
  }

  protected function getGraph($nid): array {
    $node = \Drupal\node\Entity\Node::load($nid);
    $local_path = $node->toUrl()->toString();
    $site_url = \Drupal::request()->getSchemeAndHttpHost();

    $fields = [
      "title" => array(),
      "field_handle" => array(),
      "field_image_refid" => array(),
      "field_reference_download_links" => array(),
      "field_date_of_creation_last" => array(),
      "field_date_of_creation_first" => array(),
      "field_style_period" => array(),
      "field_object_type_detailed" => array(),
      "field_object_description" => array(),
      "field_relation" => array(),
      "field_pattern_type" => array(),
      "field_pattern_subject" => array(),
      "field_pattern_subject_detailed" => array(),
      "field_pattern_description_short" => array(),
      "field_pattern_description_long" => array(),
      "field_measurements_height_cm" => array(),
      "field_measurements_width_cm" => array(),
      "field_technique_base_type" => array(),
      "field_technique_base_colour" => array(),
      "field_material" => array(),
      "field_technique_structure" => array(),
      "field_technique_base_type_desc" => array(),
      "field_technique_effect" => array(),
      "field_technique_density" => array(),
      "field_technique_warp" => array(),
      "field_technique_broche" => array(),
      "field_technique_lance" => array(),
      "field_technique_lisere" => array(),
      "field_technique_metal" => array(),
      "field_technique_riant" => array(),
      "field_technique_couvert" => array(),
      "field_technique_onde" => array(),
      "field_technique_plate" => array(),
      "field_source" => array(),
      "field_rights" => array(),
      "field_date_of_documentation" => array(),
      "field_reference_entry_author" => array(),
      "field_object_type_basic" => array(),
      "field_cultural_context" => array(),
      "field_textref" => array(),
      "field_image_id" => array(),
      "field_pinterest_pin" => array(),
      "field_source_orcid" => array(),
    ];

    $references = [
      "field_relation",
    ];

    $taxonomies = [
      "field_style_period",
      "field_object_type_detailed",
      "field_pattern_type",
      "field_pattern_subject",
      "field_pattern_subject_detailed",
      "field_technique_base_type",
      "field_technique_base_colour",
      "field_technique_warp",
      "field_rights",
      "field_object_type_basic",
      "field_cultural_context",
    ];

    $booleans = [
      "field_technique_broche",
      "field_technique_lance",
      "field_technique_lisere",
      "field_technique_metal",
      "field_technique_riant",
      "field_technique_couvert",
      "field_technique_onde",
      "field_technique_plate",
    ];

    $dates = [
      "field_date_of_documentation",
    ];

    $download_links = [
      "field_reference_download_links",
    ];

    $decimal = [
      "field_measurements_height_cm",
      "field_measurements_width_cm",
    ];

    foreach ($fields as $machine_name => &$value) {
      \Drupal::logger("DEBUG_LABEL")->debug(print_r($node->getFieldDefinition($machine_name)->getLabel(), TRUE));
      if (in_array($machine_name, $booleans)) {
        $elements = array();
        foreach ($node->get($machine_name) as $single_value) {
          $element = \Drupal::service("renderer")->render($single_value->view([
              "type" => "boolean",
              "label" => "hidden",
              "settings" => ["format" => "yes-no"],
          ]));

          array_push($elements, trim(strip_tags((string) $element)));
        }

        $value["value"] = implode(", ", $elements);
        $value["value_list"] = $elements;

      } else if (in_array($machine_name, $taxonomies)) {
        $elements = array();
        foreach ($node->get($machine_name) as $single_value) {
          $element = \Drupal::service("renderer")->render($single_value->view([
              "type" => "entity_reference_label",
              "label" => "hidden",
              "settings" => ["link" => FALSE],
          ]));

          array_push($elements, trim(strip_tags((string) $element)));
        }

        $value["value"] = implode(", ", $elements);
        $value["value_list"] = $elements;

      } else if (in_array($machine_name, $references)) {
        $elements = array();
        foreach ($node->get($machine_name) as $single_value) {
          $element = trim(strip_tags((string) \Drupal::service("renderer")->render($single_value->view([
              "type" => "entity_reference_label",
              "label" => "hidden",
              "settings" => ["link" => FALSE],
          ]))));

          $element_id = trim(strip_tags((string) \Drupal::service("renderer")->render($single_value->view([
              "type" => "entity_reference_entity_id",
              "label" => "hidden",
              "settings" => ["link" => FALSE],
          ]))));

          
          $ref_node = \Drupal\node\Entity\Node::load($element_id);
          $local_link = $ref_node->toUrl()->toString();

          $element = "{$element}; {$site_url}{$local_link}";

          array_push($elements, $element);
        }

        $value["value"] = implode(" | ", $elements);
        $value["value_list"] = $elements;

      } else if (in_array($machine_name, $dates)) {
        $elements = array();
        foreach ($node->get($machine_name) as $single_value) {
          $element = \Drupal::service("renderer")->render($single_value->view([
              "type" => "datetime_default",
              "label" => "hidden",
              "settings" => ["format_type" => "short_date_only"],
          ]));

          array_push($elements, trim(strip_tags((string) $element)));
        }

        $value["value"] = implode(", ", $elements);
        $value["value_list"] = $elements;

      } else if (in_array($machine_name, $decimal)) {
        $elements = array();
        foreach ($node->get($machine_name) as $single_value) {
          $element = \Drupal::service("renderer")->render($single_value->view([
              "type" => "number_decimal",
              "label" => "hidden",
              "settings" => ["decimal_separator" => ",", "scale" => "1"],
          ]));

          array_push($elements, trim(strip_tags((string) $element)));
        }

        $value["value"] = implode(", ", $elements);
        $value["value_list"] = $elements;

      } else if (in_array($machine_name, $download_links)) {
        $medias = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['field_media_of' => $nid]);
        $media_urls = array();

        foreach ($medias as $medium) {
          $media_source_field_name = $medium->get("bundle")->entity->get("source_configuration")["source_field"];
          $file_url = $medium->get($media_source_field_name)->entity->createFileUrl(FALSE);
          array_push($media_urls, $file_url);
        }

        $value["value"] = implode(", ", $media_urls);

      } else {
        $elements = array();
        foreach ($node->get($machine_name) as $single_value) {
          $element = trim(strip_tags((string) \Drupal::service("renderer")->render($single_value->view())));

          array_push($elements, $element);
        }

        $value["value"] = implode(", ", $elements);
        $value["value_list"] = $elements;
      }
    }

    $graph = [
      [                                                                                                                                                           
        "@id" => "{$site_url}{$local_path}#cidoc-crm-mapping",
        "@type" => "crm:E31_Document", 
        "rdfs:label" => array([
            "@value" => "Mapování metadatového záznamu Beauty Patterns {$nid} na CIDOC CRM 7.1.3",
            "@language" => "cs",
        ]),
        "dcterms:source" => array([
            "@id" => "{$site_url}{$local_path}",
        ]),
        "dcterms:conformsTo" => array([
            "@id" => "https://cidoc-crm.org/Version/version-7.1.3"
        ]),
        "bp:crmVersionUsed" => "CIDOC CRM 7.1.3 (Official / ISO Correspondence)",
        "bp:sourceRecord" => array([
            "@id" => "{$site_url}{$local_path}"
        ]),  
        "crm:P70_documents" => array([
            "@id" => "https://hdl.handle.net/11222/{$fields['field_handle']['value']}"
        ]),
        "bp:modelingNote" => array([
            "@value" => "Mapování je implementační návrh pro ukázkový záznam. Tam, kde CIDOC-CRM nemá přímou vlastnost pro detailní textilně-technické položky nebo záporné binární hodnoty, jsou původní prvky zachovány jako E33 Linguistic Object a/nebo v bp:fieldMapping.",
            "@language" => "cs"
        ]),  

        "bp:fieldMapping" => [
          [
            "sourceField" => "Identifikační číslo / field_image_id",
            "sourceValue" => $fields['field_image_id']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P48_has_preferred_identifier → E42_Identifier",
          ],
          [
            "sourceField" => "Pinterest pin / field_pinterest_pin",
            "sourceValue" => $fields['field_pinterest_pin']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P48_has_preferred_identifier → E42_Identifier",
          ],
          [
            "sourceField" => "Název / title",
            "sourceValue" => $fields['title']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P102_has_title → E35_Title",
            //"mappingNote" => "Název je zároveň použit jako rdfs:label hlavního objektu."
          ],
          [
            "sourceField" => "Odkazy ke stažení / field_reference_download_links",
            "sourceValue" => $fields['field_reference_download_links']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P138i_has_representation → E36_Visual_Item",
            //"mappingNote" => "Digitální reprezentace je modelována jako informační/obrazový objekt; přesné technické metadatové vlastnosti souboru jsou ponechány v Dublin Core."
          ],
          [
            "sourceField" => "Stable URL (handle) / field_handle",
            "sourceValue" => "https://hdl.handle.net/11222/{$fields['field_handle']['value']}",
            "crmMappingPath" => "E22_Human-Made_Object/@id; E22_Human-Made_Object → P1_is_identified_by → E42_Identifier",
            //"mappingNote" => "Handle je použit jako hlavní identifikátor entity a zároveň jako identifikátor CIDOC."
          ],
          [
            "sourceField" => "Katalogové číslo / field_image_refid",
            "sourceValue" => $fields['field_image_refid']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P48_has_preferred_identifier → E42_Identifier",
            //"mappingNote" => "Katalogové číslo je preferovaný lokální identifikátor."
          ],
          [
            "sourceField" => "Datace / field_date_of_creation_first, field_date_of_creation_last",
            "sourceValue" => "{$fields['field_date_of_creation_first']['value']}–{$fields['field_date_of_creation_last']['value']}",
            "crmMappingPath" => "E22_Human-Made_Object → P108i_was_produced_by → E12_Production → P4_has_time-span → E52_Time-Span",
            //"mappingNote" => "Interval je modelován jako časové rozpětí produkce."
          ],
          [
            "sourceField" => "Stylové období / field_style_period",
            "sourceValue" => $fields['field_style_period']['value'],
            "crmMappingPath" => "E12_Production → P2_has_type → E55_Type",
            //"mappingNote" => "Stylové období je chápáno jako typ/kategorizace produkčního kontextu."
          ],
          [
            "sourceField" => "Typ textilie / field_object_type_detailed",
            "sourceValue" => $fields['field_object_type_detailed']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P2_has_type → E55_Type",
            //"mappingNote" => "Typ textilie je modelován jako typ hlavního objektu."
          ],
          [
            "sourceField" => "Způsob použití / field_object_description",
            "sourceValue" => $fields['field_object_description']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P129i_is_subject_of → E33_Linguistic_Object",
            //"mappingNote" => "Obecné užití je klasifikováno jako kasule; plný text je zachován jako jazykový objekt."
          ],
          [
            "sourceField" => "Souvisí s dekorem / field_relation",
            "sourceValue" => $fields['field_relation']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P130_shows_features_of → E22_Human-Made_Object",
            //"mappingNote" => "Vztah je interpretován jako sdílené/odpovídající rysy dekoru."
          ],
          [
            "sourceField" => "Typ dekoru / field_pattern_type",
            "sourceValue" => $fields['field_pattern_type']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P65_shows_visual_item → E36_Visual_Item → P2_has_type → E55_Type",
            //"mappingNote" => "Typ dekoru je typem vizuálního motivu neseného textilií."
          ],
          [
            "sourceField" => "Motivy dekoru / field_pattern_subject",
            "sourceValue" => $fields['field_pattern_subject']['value'],
            "crmMappingPath" => "E36_Visual_Item → P2_has_type → E55_Type",
            //"mappingNote" => "Motivy jsou klasifikace vizuálního prvku."
          ],
          [
            "sourceField" => "Rostlinné motivy / field_pattern_subject_detailed",
            "sourceValue" => $fields['field_pattern_subject_detailed']['value'],
            "crmMappingPath" => "E36_Visual_Item → P2_has_type → E55_Type",
            //"mappingNote" => "Specializovaná motivická kategorie je ponechána jako typ vizuálního prvku."
          ],
          [
            "sourceField" => "Charakteristika dekoru / field_pattern_description_short",
            "sourceValue" => $fields['field_pattern_description_short']['value'],
            "crmMappingPath" => "E36_Visual_Item → P129i_is_subject_of → E33_Linguistic_Object",
            //"mappingNote" => "Krátká charakteristika je použita jako typ i jako zachovaný textový popis."
          ],
          [
            "sourceField" => "Popis dekoru / field_pattern_description_long",
            "sourceValue" => $fields['field_pattern_description_long']['value'],
            "crmMappingPath" => "E36_Visual_Item → P129i_is_subject_of → E33_Linguistic_Object",
            //"mappingNote" => "Volný popis dekoru je modelován jako jazykový objekt o vizuálním prvku."
          ],
          [
            "sourceField" => "Výška dekoru (min.) / field_measurements_height_cm",
            "sourceValue" => $fields['field_measurements_height_cm']['value'],
            "crmMappingPath" => "E36_Visual_Item → P43_has_dimension → E54_Dimension → P90_has_value/P91_has_unit",
            //"mappingNote" => "Minimální výška dekoru je dimenze vizuálního prvku."
          ],
          [
            "sourceField" => "Šířka dekoru (min.) / field_measurements_width_cm",
            "sourceValue" => $fields['field_measurements_width_cm']['value'],
            "crmMappingPath" => "E36_Visual_Item → P43_has_dimension → E54_Dimension → P90_has_value/P91_has_unit",
            //"mappingNote" => "Minimální šířka dekoru je dimenze vizuálního prvku."
          ],
          [
            "sourceField" => "Technika základu / field_technique_base_type",
            "sourceValue" => $fields['field_technique_base_type']['value'],
            "crmMappingPath" => "E12_Production → P32_used_general_technique → E55_Type",
            //"mappingNote" => "Technika základu je modelována jako obecná technika produkce."
          ],
          [
            "sourceField" => "Barva základu / field_technique_base_colour",
            "sourceValue" => $fields['field_technique_base_colour']['value'],
            "crmMappingPath" => "E25_Human-Made_Feature → P2_has_type → E55_Type",
            //"mappingNote" => "Základ tkaniny je modelován jako lidsky vytvořený rys/část nesená objektem; barva je klasifikační typ tohoto rysu."
          ],
          [
            "sourceField" => "Materiál / field_material",
            "sourceValue" => $fields['field_material']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P129i_is_subject_of → E33_Linguistic_Object",
            //"mappingNote" => "Rozpoznatelné materiály jsou modelovány jako E57; plný technický text je zachován jako E33."
          ],
          [
            "sourceField" => "Struktura tkaniny / field_technique_structure",
            "sourceValue" => $fields['field_technique_structure']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P129i_is_subject_of → E33_Linguistic_Object",
            //"mappingNote" => "Specifický textilně-technický popis je zachován jako jazykový objekt."
          ],
          [
            "sourceField" => "Základ tkaniny / field_technique_base_type_desc",
            "sourceValue" => $fields['field_technique_base_type_desc']['value'],
            "crmMappingPath" => "E22_Human-Made_Object →  P129i_is_subject_of → E33_Linguistic_Object",
            //"mappingNote" => "Základ je modelován jako rys/část objektu a detail je zachován textově."
          ],
          [
            "sourceField" => "Efekty tkaniny / field_technique_effect",
            "sourceValue" => $fields['field_technique_effect']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P129i_is_subject_of → E33_Linguistic_Object",
            //"mappingNote" => "Technický popis efektu je zachován jako jazykový objekt."
          ],
          [
            "sourceField" => "Dostava / field_technique_density",
            "sourceValue" => $fields['field_technique_density']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P129i_is_subject_of → E33_Linguistic_Object",
            //"mappingNote" => "Komplexní údaj o dostavě a raportu je ponechán jako strukturovaný text; jednotlivé měřitelné hodnoty lze případně dále rozložit na E16 Measurement/E54 Dimension."
          ],
          [
            "sourceField" => "Typ osnovy / field_technique_warp",
            "sourceValue" => $fields['field_technique_warp']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P2_has_type → E55_Type ",
            //"mappingNote" => "Neurčená hodnota je zachována textově, bez pozitivní typové klasifikace."
          ],
          [
            "sourceField" => "Broché útek / field_technique_broche",
            "sourceValue" => $fields['field_technique_broche']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P129i_is_subject_of → E33_Linguistic_Object",
            //"mappingNote" => "Pozitivní binární hodnota je interpretována jako typ/technický rys objektu."
          ],
          [
            "sourceField" => "Lancé útek / field_technique_lance",
            "sourceValue" => $fields['field_technique_lance']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P129i_is_subject_of → E33_Linguistic_Object",
            //"mappingNote" => "CIDOC-CRM nemá přímý obecný mechanismus pro negaci klasifikace; záporná hodnota je zachována jako textová evidence."
          ],
          [
            "sourceField" => "Liseré útek / field_technique_lisere",
            "sourceValue" => $fields['field_technique_lisere']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P129i_is_subject_of → E33_Linguistic_Object",
            //"mappingNote" => "Záporná hodnota je zachována jako textová evidence."
          ],
          [
            "sourceField" => "Pokovené nitě / field_technique_metal",
            "sourceValue" => $fields['field_technique_metal']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P129i_is_subject_of → E33_Linguistic_Object",
            //"mappingNote" => "Pozitivní hodnota je promítnuta do materiálu i technické klasifikace."
          ],
          [
            "sourceField" => "Filé riant / field_technique_riant",
            "sourceValue" => $fields['field_technique_riant']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P129i_is_subject_of → E33_Linguistic_Object",
            //"mappingNote" => "Pozitivní binární hodnota je klasifikována jako technický typ."
          ],
          [
            "sourceField" => "Filé couvert / field_technique_couvert",
            "sourceValue" => $fields['field_technique_couvert']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P129i_is_subject_of → E33_Linguistic_Object",
            //"mappingNote" => "Záporná hodnota je zachována textově."
          ],
          [
            "sourceField" => "Ondé / field_technique_onde",
            "sourceValue" => $fields['field_technique_onde']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P129i_is_subject_of → E33_Linguistic_Object",
            //"mappingNote" => "Záporná hodnota je zachována textově."
          ],
          [
            "sourceField" => "Lamela / field_technique_plate",
            "sourceValue" => $fields['field_technique_plate']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P129i_is_subject_of → E33_Linguistic_Object",
            //"mappingNote" => "Pozitivní hodnota je promítnuta do materiálu i technické klasifikace."
          ],
          [
            "sourceField" => "Typ předmětu / field_object_type_basic",
            "sourceValue" => $fields['field_object_type_basic']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P2_has_type → E55_Type",
            //"mappingNote" => "Typ předmětu je modelován jako typ hlavního objektu."
          ],
          [
            "sourceField" => "Země původu / field_cultural_context",
            "sourceValue" => $fields['field_cultural_context']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P94i_was_created_by → E12_Production → P7_took_place_at → E53_Place",
          ],
          [
            "sourceField" => "Prameny popisu / field_textref",
            "sourceValue" => $fields['field_object_type_basic']['value'],
            "crmMappingPath" => "E22_Human-Made_Object → P140i_was_attributed_by → E13_Attribute_Assignment → P16_used_specific_object → E31_Document",
          ],
          [
            "sourceField" => "Autor popisu / field_source",
            "sourceValue" => $fields['field_source']['value'],
            "crmMappingPath" => "E33_Linguistic_Object → P94i_was_created_by → E65_Creation → P14_carried_out_by → E21_Person",
            //"mappingNote" => "Autor popisu je modelován jako osoba, která vytvořila popisný jazykový objekt."
          ],
          [
            "sourceField" => "ORCID / field_source_orcid",
            "sourceValue" => "ORCID {$fields['field_source_orcid']['value']}",
            "crmMappingPath" => "E21_Person → P48_has_preferred_identifier → E42_Identifier",
          ],
          [
            "sourceField" => "Licence / field_rights",                                                                                                                              
            "sourceValue" => $fields['field_rights']['value'],
            "crmMappingPath" => "E36_Visual_Item → P104_is_subject_to → E30_Right",
          ],
          [
            "sourceField" => "Záznam zpracován / field_date_of_documentation",
            "sourceValue" => $fields['field_date_of_documentation']['value'],
            "crmMappingPath" => "E33_Linguistic_Object → P94i_was_created_by → E65_Creation → P4_has_time-span → E52_Time-Span",
            //"mappingNote" => "Datum zpracování je modelováno jako čas vytvoření záznamu."
          ],
          [
            "sourceField" => "Autor záznamu / field_reference_entry_author",
            "sourceValue" => $fields['field_reference_entry_author']['value'],
            "crmMappingPath" => "E31_Document → P94i_was_created_by → E65_Creation → P14_carried_out_by → E39_Actor",
            //"mappingNote" => "Uživatel systému je modelován jako aktér vytvoření metadatového záznamu."
          ]
        ]
      ],
    ];

    return $graph;
  }
}
