<?php
/*
 Plugin Name: Widgets on dashboard
Plugin URI: http://elearn.jp/wpman/column/widgets-on-dashboard.html
Description: This plug-in can update widgets for sidebar on dashboard. This is suitable for your WordPress which updates a few widgets frequently.
Author: tmatsuur
Version: 0.1.0
Author URI: http://12net.jp/
*/

/*
 Copyright (C) 2014 tmatsuur (Email: takenori dot matsuura at 12net dot jp)
This program is licensed under the GNU GPL Version 2.
*/

$plugin_widgets_on_dashboard = new widgets_on_dashboard();
class widgets_on_dashboard {
	var $editable_widgets;

	function __construct() {
		global $pagenow;
		if ( $pagenow == 'index.php' ) {
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
		}
	}
	private function _editable_widgets() {
		$this->editable_widgets = array();
		$sidebar_widgets = wp_get_sidebars_widgets();
		foreach ( $sidebar_widgets as $sidebar_id=>$widgets ) {
			if ( 'wp_inactive_widgets' == $sidebar_id || !is_array( $widgets ) )
				continue;
			foreach ( $widgets as $widget_id )
				$this->editable_widgets[] = (object)array( 'sidebar_id'=>$sidebar_id, 'widget_id'=>$widget_id, 'widget'=>null );
		}
	}
	public function admin_init() {
		if ( !current_user_can( 'edit_theme_options' ) )
			return;	// Except an administrator 
		$this->_editable_widgets();
		if ( count( $this->editable_widgets ) == 0 )
			return;	// Widget is not used

		global $wp_registered_sidebars, $wp_registered_widgets, $wp_registered_widget_controls, $wp_registered_widget_updates;
		if ( isset( $_POST['savewidget'] ) ) {
			$widget_id = $_POST['widget-id'];
			check_admin_referer( "save-delete-widget-{$widget_id}" );

			$number = isset( $_POST['multi_number'] ) ? (int)$_POST['multi_number'] : '';
			if ( $number ) {
				foreach ( $_POST as $key => $val ) {
					if ( is_array( $val ) && preg_match( '/__i__|%i%/', key( $val ) ) ) {
						$_POST[$key] = array( $number => array_shift( $val ) );
						break;
					}
				}
			}
			if ( isset( $wp_registered_widget_updates[$_POST['id_base']] ) ) {
				ob_start();
				call_user_func_array( $wp_registered_widget_updates[$_POST['id_base']]['callback'], $wp_registered_widget_updates[$_POST['id_base']]['params'] );
				ob_end_clean();
				wp_redirect( admin_url( 'index.php' ) );
				exit;
			}
		}
		foreach ( $this->editable_widgets as $no=>$widget ) {
			$widget->widget = $wp_registered_widget_controls[$widget->widget_id];
			add_meta_box( 'dashboard-edit-widget-'.$widget->widget_id,
				$wp_registered_widgets[$widget->widget_id]['name'].' ('.$wp_registered_sidebars[$widget->sidebar_id]['name'].')',
				array( &$this, 'meta_box' ), 'dashboard', 'side', 'high', $widget );
		}
	}
	public function meta_box( $object, $box ) {
		$multi_number = isset( $box['args']->widget['params'][0]['number'] ) ? $box['args']->widget['params'][0]['number'] : '';
		$id_base = isset( $box['args']->widget['id_base'] ) ? $box['args']->widget['id_base'] : $box['args']->widget['id'];
?>
<form action="" method="post">
<div class="widget-content">
<?php
		call_user_func_array( $box['args']->widget['callback'], $box['args']->widget['params'] );
?>
</div>
<div class="widget-control-actions">
<div class="alignright">
<?php submit_button( __( 'Save' ), 'button alignright', "savewidget", false, array( 'id'=>"{$box['args']->widget_id}-savewidget" ) ); ?>
<input type="hidden" name="widget-id" class="widget-id" value="<?php echo esc_attr( $box['args']->widget_id ); ?>" />
<input type="hidden" name="id_base" class="id_base" value="<?php echo esc_attr( $id_base ); ?>" />
<input type="hidden" name="multi_number" class="multi_number" value="<?php echo esc_attr( $multi_number ); ?>" />
<?php	wp_nonce_field("save-delete-widget-{$box['args']->widget_id}"); ?>
</div>
<br class="clear">
</div>
</form>
<?php
	}
}
