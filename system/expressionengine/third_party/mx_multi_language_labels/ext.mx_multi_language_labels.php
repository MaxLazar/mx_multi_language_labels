<?php

if ( !defined( 'BASEPATH' ) )
	exit( 'No direct script access allowed' );

require_once PATH_THIRD . 'mx_multi_language_labels/config.php';


/**
 * MX Multi Language Labels
 *
 * MX Multi Language Labels allows creating a multi language labels for custom fields.
 *
 * @package  ExpressionEngine
 * @category Extension
 * @author    Max Lazar <max@eec.ms>
 * @copyright Copyright (c) 2014 Max Lazar (http://eec.ms)
 * @license   http://creativecommons.org/licenses/MIT/  MIT License
 */


class Mx_multi_language_labels_ext {
	var $settings = array();

	var $addon_name     = MX_MLL_NAME;
	var $name           = MX_MLL_NAME;
	var $version        = MX_MLL_VER;
	var $description    = MX_MLL_DESC;
	var $settings_exist = 'y';
	var $docs_url       = MX_MLL_DOCS;

	/**
	 * Defines the ExpressionEngine hooks that this extension will intercept.
	 *
	 * @since Version 1.0.0
	 * @access private
	 * @var mixed an array of strings that name defined hooks
	 * @see http://codeigniter.com/user_guide/general/hooks.html
	 * */
	private $hooks = array( 'cp_js_end' => 'cp_js_end' );

	// -------------------------------
	// Constructor
	// -------------------------------

	public function __construct( $settings = FALSE ) {
		// define a constant for the current site_id rather than calling $PREFS->ini() all the time
		if ( defined( 'SITE_ID' ) == FALSE )
			define( 'SITE_ID', ee()->config->item( 'site_id' ) );

		// set the settings for all other methods to access
		$this->settings = ( $settings == FALSE ) ? $this->_getSettings() : $this->_saveSettingsToSession( $settings );
	}


	/**
	 * Prepares and loads the settings form for display in the ExpressionEngine control panel.
	 *
	 * @since Version 1.0.0
	 * @access public
	 * @return void
	 * */
	public function settings_form() {
		ee()->lang->loadfile( 'mx_multi_language_labels' );
		ee()->load->model( 'channel_model' );

		// Create the variable array
		$vars = array(
			'addon_name' => $this->addon_name,
			'error' => FALSE,
			'input_prefix' => __CLASS__,
			'message' => FALSE,
			'settings_form' => FALSE,
			'channel_data' => ee()->channel_model->get_channels()->result(),
			'language_packs' => '',
			'field_packs' => ''
		);

		$vars['settings']      = $this->settings;
		$vars['settings_form'] = TRUE;

		if ( $new_settings = ee()->input->post( __CLASS__ ) ) {
			$vars['settings'] = $new_settings;
			$this->_saveSettingsToDB( $new_settings );
			$vars['message'] = ee()->lang->line( 'extension_settings_saved_success' );
		}

		$vars['language_packs'] = $this->language_packs();
		$vars['field_packs']    = $this->field_packs();


		return ee()->load->view( 'form_settings', $vars, true );

	}
	// END

	function cp_js_end() {
		$out = ee()->extensions->last_call;

		ee()->load->helper( 'array' );

		parse_str( parse_url( @$_SERVER['HTTP_REFERER'], PHP_URL_QUERY ), $get );

		if ( element( 'D', $get ) == 'cp' && element( 'C', $get ) == 'content_publish' && element( 'M', $get ) == 'entry_form' && element( 'channel_id', $get ) ) {

			ee()->load->library( 'security' );

			ee()->db->select( 'channel_fields.field_id AS id, channel_fields.field_name AS name' )->from( 'channel_fields' )->join( 'channels', 'channels.field_group = channel_fields.group_id' )->where( 'channels.channel_id', ee()->security->xss_clean( element( 'channel_id', $get ) ) );

			$query = ee()->db->get();

			if ( $query->num_rows() ) {
				$query     = $query->result_array();
				$lang      = strtolower( ee()->session->userdata( 'language' ) );
				$len_field = strlen( $lang );
				$data      = $this->settings;

				if ( !isset( $data[$lang] ) ) {
					foreach ( $query as $id => $field ) {
						if ( isset( $data[$lang . "_" . $field['id']] ) ) {
							if ( $data[$lang . "_" . $field['id']] != "" ) {
								$field['name'] = $data[$lang . "_" . $field['id']];
							}
						}
						$query[$id] = $field;
					}


					ee()->load->library( 'javascript' );
					$out .= '
						$(function(){
							$.each(' . ee()->javascript->generate_json( $query ) . ',function(i,f){
								el =  $("#hold_field_"+f.id+" label:first span img").clone();
								$("#hold_field_"+f.id+" label:first span").html(" "+f.name);
								$("#hold_field_"+f.id+" label:first span").prepend(el);
								});
							});' . "\r\n";
				}
				;

			}
		}

		return $out;
	}



	function field_packs() {
		$out = ee()->db->query( "SELECT field_id, group_id, field_label, field_name
							   FROM exp_channel_fields
							   WHERE site_id = " . SITE_ID . "
								ORDER BY group_id" );
		return $out;
	}

	function language_packs() {
		static $languages;

		if ( !isset( $languages ) ) {
			ee()->load->helper( 'directory' );

			$source_dir = APPPATH . 'language/';

			if ( ( $list = directory_map( $source_dir, TRUE ) ) !== FALSE ) {
				foreach ( $list as $file ) {
					if ( is_dir( $source_dir . $file ) && $file[0] != '.' ) {
						$languages[$file] = ucfirst( $file );
					}
				}

				ksort( $languages );
			}
		}

		return $languages;
	}

	// --------------------------------
	//  Activate Extension
	// --------------------------------

	function activate_extension() {
		$this->_createHooks();
	}

	/**
	 * Saves the specified settings array to the database.
	 *
	 * @since Version 1.0.0
	 * @access protected
	 * @param array   $settings an array of settings to save to the database.
	 * @return void
	 * */
	private function _getSettings( $refresh = FALSE ) {
		$settings = FALSE;
		if ( isset( ee()->session->cache[$this->addon_name][__CLASS__]['settings'] ) === FALSE || $refresh === TRUE ) {
			$settings_query = ee()->db->select( 'settings' )->where( 'enabled', 'y' )->where( 'class', __CLASS__ )->get( 'extensions', 1 );

			if ( $settings_query->num_rows() ) {
				$settings = unserialize( $settings_query->row()->settings );
				$this->_saveSettingsToSession( $settings );
			}
		} else {
			$settings = ee()->session->cache[$this->addon_name][__CLASS__]['settings'];
		}
		return $settings;
	}

	/**
	 * Saves the specified settings array to the session.
	 *
	 * @since Version 1.0.0
	 * @access protected
	 * @param array   $settings an array of settings to save to the session.
	 * @param array   $sess     A session object
	 * @return array the provided settings array
	 * */
	private function _saveSettingsToSession( $settings, &$sess = FALSE ) {
		// if there is no $sess passed and EE's session is not instaniated
		if ( $sess == FALSE && isset( ee()->session->cache ) == FALSE )
			return $settings;

		// if there is an EE session available and there is no custom session object
		if ( $sess == FALSE && isset( ee()->session ) == TRUE )
			$sess =& ee()->session;

		// Set the settings in the cache
		$sess->cache[$this->addon_name][__CLASS__]['settings'] = $settings;

		// return the settings
		return $settings;
	}


	/**
	 * Saves the specified settings array to the database.
	 *
	 * @since Version 1.0.0
	 * @access protected
	 * @param array   $settings an array of settings to save to the database.
	 * @return void
	 * */
	private function _saveSettingsToDB( $settings ) {
		ee()->db->where( 'class', __CLASS__ )->update( 'extensions', array(
				'settings' => serialize( $settings )
			) );
	}
	/**
	 * Sets up and subscribes to the hooks specified by the $hooks array.
	 *
	 * @since Version 1.0.0
	 * @access private
	 * @param array   $hooks a flat array containing the names of any hooks that this extension subscribes to. By default, this parameter is set to FALSE.
	 * @return void
	 * @see http://codeigniter.com/user_guide/general/hooks.html
	 * */
	private function _createHooks( $hooks = FALSE ) {
		if ( !$hooks ) {
			$hooks = $this->hooks;
		}

		$hook_template = array(
			'class' => __CLASS__,
			'settings' => '',
			'priority' => '1',
			'version' => $this->version
		);

		$hook_template['settings']['multilanguage'] = 'n';

		foreach ( $hooks as $key => $hook ) {
			if ( is_array( $hook ) ) {
				$data['hook'] = $key;

				$data['method'] = ( isset( $hook['method'] ) === TRUE ) ? $hook['method'] : $key;
				$data           = array_merge( $data, $hook );
			} else {
				$data['hook'] = $data['method'] = $hook;
			}

			$hook             = array_merge( $hook_template, $data );
			$hook['settings'] = serialize( $hook['settings'] );
			ee()->db->query( ee()->db->insert_string( 'exp_extensions', $hook ) );
		}
	}

	/**
	 * Removes all subscribed hooks for the current extension.
	 *
	 * @since Version 1.0.0
	 * @access private
	 * @return void
	 * @see http://codeigniter.com/user_guide/general/hooks.html
	 * */
	private function _deleteHooks() {
		ee()->db->query( "DELETE FROM `exp_extensions` WHERE `class` = '" . __CLASS__ . "'" );
	}


	// END




	// --------------------------------
	//  Update Extension
	// --------------------------------

	function update_extension( $current = '' ) {
		if ( $current == '' or $current == $this->version ) {
			return FALSE;
		}

		if ( $current < '2.1.0' ) {
			ee()->db->delete( 'exp_extensions', array( 'class' => get_class( $this ) ) );
			$this->_createHooks();
			$this->_saveSettingsToDB( $this->settings );

		}
		ee()->db->query( "UPDATE exp_extensions SET version = '" . ee()->db->escape_str( $this->version ) . "' WHERE class = '" . get_class( $this ) . "'" );

	}
	// END

	// --------------------------------
	//  Disable Extension
	// --------------------------------

	function disable_extension() {
		ee()->db->delete( 'exp_extensions', array(
				'class' => get_class( $this )
			) );
	}
	// END
}

/* End of file ext.mx_multi_language_labels.php */
/* Location: ./system/expressionengine/third_party/mx_multi_language_labels/ext.mx_multi_language_labels.php */
