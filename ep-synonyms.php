<?php
/**
 * Plugin Name:     ElasticPress Synonyms
 * Plugin URI:      https://github.com/francesconero/ep-synonyms
 * Description:     Module for ElasticPress that supports synonyms
 * Author:          Francesco Nero
 * Author URI:      https://github.com/francesconero
 * License:         GPLv3
 * License URI:     https://www.gnu.org/licenses/gpl.html
 * Text Domain:     ep-synonyms
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Ep_Synonyms
 */

 defined('ABSPATH') or die("Cannot access directly.");

/**
 * Setup all module filters
 */
function ep_synonyms_setup()
{
    add_filter( 'ep_config_mapping', 'ep_synonyms_config_mapping' );
}

/**
 * Alter ES index to add synonyms token filter.
 *
 * @param array $mapping
 *
 * @return array
 */
function ep_synonyms_config_mapping($mapping)
{
    // bail early if $mapping is missing or not array
    if (! isset( $mapping ) || ! is_array( $mapping )) {
        return false;
    }

    // ensure we have filters and is array
    if (! isset( $mapping['settings']['analysis']['filter'] )
            || ! is_array( $mapping['settings']['analysis']['filter'] )
        ) {
        return false;
    }

    // ensure we have analyzers and is array
    if (! isset( $mapping['settings']['analysis']['analyzer']['default']['filter'] )
            || ! is_array( $mapping['settings']['analysis']['analyzer']['default']['filter'] )
        ) {
        return false;
    }

    // define the custom filter
    $mapping['settings']['analysis']['filter']['secret_client_synonym_filter'] = array(
        'type' => 'synonym',
        'synonyms_path' => 'analysis/synonym.txt',
    );

    // tell the analyzer to use our newly created filter
    $mapping['settings']['analysis']['analyzer']['default']['filter'][] = 'secret_client_synonym_filter';

    // increase number of fields indexable
    $mapping['settings']['index']['mapping']['total_fields']['limit'] = 2000;

    return $mapping;
}

/**
 * Output module box summary
 */
function ep_synonyms_box_summary()
{
    echo '<p>' . esc_html_e( 'Enable synonyms queries.', 'ep-synonyms' ) . '</p>';
}

/**
 * Output module box long
 */
function ep_synonyms_box_long()
{
    echo '<p>' . esc_html_e( 'Important note: You should have a ELASTICSEARCH_CONFIG_FOLDER/analysis/synonyms.txt file present on every node of your Elasticsearch cluster, or the creation of the index will fail.', 'ep-synonyms' ) . '</p>';
}

ep_register_module( 'ep_synonyms', array(
    'title'                    => 'Synonyms',
    'setup_cb'                 => 'ep_synonyms_setup',
    'module_box_summary_cb'    => 'ep_synonyms_box_summary',
    'module_box_long_cb'       => 'ep_synonyms_box_long',
    'requires_install_reindex' => true,
) );
