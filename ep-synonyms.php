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
 * Setup settings page
 */
function ep_synonyms_menu()
{
    $capability  = 'manage_options';

    if (defined( 'EP_IS_NETWORK' ) && EP_IS_NETWORK) {
        $capability  = 'manage_network';
    }
    add_submenu_page(
            'elasticpress',
            'ElasticPress Synonyms',
            'Synonyms',
            'manage_options',
            'ep-synonyms',
            'ep_synonyms_options_page'
        );
}

function ep_synonyms_settings_init(  ) {
    
	register_setting( 'ep_synonyms', 'ep_synonyms_synonyms' ); 

	add_settings_section(
		'ep_synonyms_synonyms_section', 
		__( 'Synonyms', 'wordpress' ), 
		'ep_synonyms_settings_section_callback', 
		'ep_synonyms'
	);

	add_settings_field( 
		'ep_synonyms_synonyms', 
		__( 'Insert your synonyms:', 'wordpress' ), 
		'ep_synonyms_textarea_field_0_render', 
		'ep_synonyms', 
		'ep_synonyms_synonyms_section' 
	);

}


function ep_synonyms_textarea_field_0_render(  ) { 

	$options = get_option( 'ep_synonyms_synonyms' );
	?>
	<textarea cols='80' rows='5' name='ep_synonyms_synonyms'><?php echo $options; ?></textarea>
	<?php

}


function ep_synonyms_settings_section_callback(  ) { 
    echo __('Remember to reindex the posts after you finish modifying the synonyms.', 'wordpress');
}


function ep_synonyms_options_page(  ) { 

	?>
	<form action='options.php' method='post'>

		<h2>ElasticPress Synonyms</h2>

		<?php
		settings_fields( 'ep_synonyms' );
		do_settings_sections( 'ep_synonyms' );
		submit_button();
		?>

	</form>
	<?php

}

/**
 * Setup all module filters
 */
function ep_synonyms_setup()
{
    add_filter( 'ep_config_mapping', 'ep_synonyms_config_mapping' );
    add_action( 'admin_menu', 'ep_synonyms_menu');
    add_action( 'admin_init', 'ep_synonyms_settings_init' );
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

    $synonyms = get_option( 'ep_synonyms_synonyms' );
    
    // define the custom filter
    $mapping['settings']['analysis']['filter']['secret_client_synonym_filter'] = array(
        'type' => 'synonym',
        'synonyms' => preg_split('/$\R?^/m', $synonyms)
    );

    // tell the analyzer to use our newly created filter
    $mapping['settings']['analysis']['analyzer']['default_search'] = $mapping['settings']['analysis']['analyzer']['default'];
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
    echo '<p>' . esc_html__( 'Enable synonyms queries.', 'ep-synonyms' ) . '</p>';
}

/**
 * Output module box long
 */
function ep_synonyms_box_long()
{
    echo '<p>' . esc_html__( 'Open the', 'ep-synonyms' ) . ' <strong>Synonyms</strong> ' . esc_html__('ElasticPress submenu to update the synonyms. Then reindex your posts.', 'ep-synonyms' ) . '</p>';
}

ep_register_module( 'ep_synonyms', array(
    'title'                    => 'Synonyms',
    'setup_cb'                 => 'ep_synonyms_setup',
    'module_box_summary_cb'    => 'ep_synonyms_box_summary',
    'module_box_long_cb'       => 'ep_synonyms_box_long',
    'requires_install_reindex' => true,
) );
