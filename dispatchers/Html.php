<?php
/*

© 2013 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Output_Dispatcher_Html extends QM_Output_Dispatcher {

	public $id = 'html';

	public function __construct() {

		add_action( 'admin_bar_menu', array( $this, 'action_admin_bar_menu' ), 999 );

		parent::__construct();

	}

	public function action_admin_bar_menu( WP_Admin_Bar $wp_admin_bar ) {

		if ( !$this->qm->show_query_monitor() )
			return;

		$class = implode( ' ', array( 'hide-if-js', QM_Util::wpv() ) );
		$title = __( 'Query Monitor', 'query-monitor' );

		$wp_admin_bar->add_menu( array(
			'id'    => 'query-monitor',
			'title' => $title,
			'href'  => '#qm-overview',
			'meta'  => array(
				'classname' => $class
			)
		) );

		$wp_admin_bar->add_menu( array(
			'parent' => 'query-monitor',
			'id'     => 'query-monitor-placeholder',
			'title'  => $title,
			'href'   => '#qm-overview'
		) );

	}

	public function init( QM_Plugin $qm ) {

		global $wp_locale;

		if ( !defined( 'DONOTCACHEPAGE' ) )
			define( 'DONOTCACHEPAGE', 1 );

		wp_enqueue_style(
			'query-monitor',
			$qm->plugin_url( 'assets/query-monitor.css' ),
			null,
			$qm->plugin_ver( 'assets/query-monitor.css' )
		);
		wp_enqueue_script(
			'query-monitor',
			$qm->plugin_url( 'assets/query-monitor.js' ),
			array( 'jquery' ),
			$qm->plugin_ver( 'assets/query-monitor.js' ),
			true
		);
		wp_localize_script(
			'query-monitor',
			'qm_locale',
			(array) $wp_locale
		);
		wp_localize_script(
			'query-monitor',
			'qm_l10n',
			array(
				'ajax_error' => __( 'PHP Error in AJAX Response', 'query-monitor' ),
			)
		);

	}

	public function before_output( QM_Plugin $qm ) {

		# @TODO document why this is needed
		# Flush the output buffer to avoid crashes
		if ( !is_feed() ) {
			while ( ob_get_length() )
				ob_end_flush();
		}

		foreach ( glob( $qm->plugin_path( 'output/html/*.php' ) ) as $output ) {
			include $output;
		}

		if ( !function_exists( 'is_admin_bar_showing' ) or !is_admin_bar_showing() )
			$class = 'qm-show';
		else
			$class = '';

		$json = array(
			'menu'        => $this->js_admin_bar_menu(),
			'ajax_errors' => array() # @TODO move this into the php_errors component
		);

		echo '<script type="text/javascript">' . "\n\n";
		echo 'var qm = ' . json_encode( $json ) . ';' . "\n\n";
		echo '</script>' . "\n\n";

		echo '<div id="qm" class="' . $class . '">';
		echo '<div id="qm-wrapper">';
		echo '<p>' . __( 'Query Monitor', 'query-monitor' ) . '</p>';

	}

	public function after_output( QM_Plugin $qm ) {

		echo '</div>';
		echo '</div>';

	}

	public function get_outputter( QM_Component $component ) {
		return new QM_Output_Html( $component );
	}

	public function js_admin_bar_menu() {

		$class = implode( ' ', apply_filters( 'query_monitor_class', array( QM_Util::wpv() ) ) );
		$title = implode( ' &nbsp; ', apply_filters( 'query_monitor_title', array() ) );

		if ( empty( $title ) )
			$title = __( 'Query Monitor', 'query-monitor' );

		$admin_bar_menu = array(
			'top' => array(
				'title'     => sprintf( '<span class="ab-icon">QM</span><span class="ab-label">%s</span>', $title ),
				'classname' => $class
			),
			'sub' => array()
		);

		foreach ( apply_filters( 'query_monitor_menus', array() ) as $menu )
			$admin_bar_menu['sub'][] = $menu;

		return $admin_bar_menu;

	}

	public function active( QM_Plugin $qm ) {

		if ( !$qm->show_query_monitor() ) {
			return false;
		}

		# @TODO move this logic into this class
		return $this->qm->did_footer();
	}

}

function register_qm_dispatcher_html( array $dispatchers ) {
	$dispatchers['html'] = new QM_Output_Dispatcher_Html;
	return $dispatchers;
}

add_filter( 'query_monitor_dispatchers', 'register_qm_dispatcher_html' );
