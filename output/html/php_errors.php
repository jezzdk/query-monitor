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

class QM_Output_Html_PHP_Errors extends QM_Output_Html {

	public function output() {

		$data = $this->component->get_data();

		if ( empty( $data['errors'] ) )
			return;

		echo '<div class="qm" id="' . $this->component->id() . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">' . __( 'PHP Error', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'File', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Line', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Call Stack', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Component', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		$types = array(
			'warning' => __( 'Warning', 'query-monitor' ),
			'notice'  => __( 'Notice', 'query-monitor' ),
			'strict'  => __( 'Strict', 'query-monitor' ),
		);

		foreach ( $types as $type => $title ) {

			if ( isset( $data['errors'][$type] ) ) {

				echo '<tr>';
				if ( count( $data['errors'][$type] ) > 1 )
					echo '<td rowspan="' . count( $data['errors'][$type] ) . '">' . $title . '</td>';
				else
					echo '<td>' . $title . '</td>';
				$first = true;

				foreach ( $data['errors'][$type] as $error ) {

					if ( !$first )
						echo '<tr>';

					$stack = $error->trace->get_stack();
					$component = QM_Util::get_backtrace_component( $error->trace );

					if ( empty( $stack ) )
						$stack = '<em>' . __( 'none', 'query-monitor' ) . '</em>';
					else
						$stack = implode( '<br />', $stack );

					$message = str_replace( "href='function.", "target='_blank' href='http://php.net/function.", $error->message );

					echo '<td>' . $message . '</td>';
					echo '<td title="' . esc_attr( $error->file ) . '">' . esc_html( $error->filename ) . '</td>';
					echo '<td>' . esc_html( $error->line ) . '</td>';
					echo '<td class="qm-ltr">' . $stack . '</td>';
					echo '<td>' . $component->name . '</td>';
					echo '</tr>';

					$first = false;

				}

			}

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

}

function register_qm_php_errors_output_html( QM_Output $output = null, QM_Component $component ) {
	return new QM_Output_Html_PHP_Errors( $component );
}

add_filter( 'query_monitor_output_html_php_errors', 'register_qm_php_errors_output_html', 10, 2 );
