<?php
wp_enqueue_style( 'pods-form' );

/**
 * @var array<string|array> $item Item data
 * @var PodsUI              $obj
 */
?>

<div class="wrap pods-ui">
	<div id="icon-edit-pages" class="icon32"<?php if ( false !== $obj->icon ) { ?> style="background-position:0 0;background-size:100%;background-image:url(<?php echo $obj->icon; ?>);"<?php } ?>>
		<br /></div>
	<h2>
		<?php
		echo $obj->do_template( $obj->header['view'] );

		if ( ! in_array( 'add', $obj->actions_disabled ) && ! in_array( 'add', $obj->actions_hidden ) ) {
			$link = pods_query_arg( [
				'action' . $obj->num => 'add',
				'id' . $obj->num     => '',
				'do' . $obj->num = '',
			], PodsUI::$allowed, $obj->exclusion() );

			if ( ! empty( $obj->action_links['add'] ) ) {
				$link = $obj->action_links['add'];
			}
			?>
			<a href="<?php echo $link; ?>" class="add-new-h2"><?php echo $obj->heading['add']; ?></a>
			<?php
		} elseif ( ! in_array( 'manage', $obj->actions_disabled ) && ! in_array( 'manage', $obj->actions_hidden ) ) {
			$link = pods_query_arg( [
				'action' . $obj->num => 'manage',
				'id' . $obj->num     => '',
			], PodsUI::$allowed, $obj->exclusion() );

			if ( ! empty( $obj->action_links['manage'] ) ) {
				$link = $obj->action_links['manage'];
			}
			?>
			<a href="<?php echo $link; ?>" class="add-new-h2">&laquo; <?php echo sprintf( __( 'Back to %s', 'pods' ), $obj->heading['manage'] ); ?></a>
			<?php
		}
		?>

	</h2>

	<div class="pods-submittable-fields">
		<div id="poststuff" class="metabox-holder has-right-sidebar"> <!-- class "has-right-sidebar" preps for a sidebar... always present? -->
			<div id="side-info-column" class="inner-sidebar">
				<div id="side-sortables" class="meta-box-sortables ui-sortable">
					<!-- BEGIN PUBLISH DIV -->
					<div id="submitdiv" class="postbox">
						<div class="handlediv" title="Click to toggle"><br /></div>
						<h3 class="hndle"><span><?php _e( 'Manage', 'pods' ); ?></span></h3>

						<div class="inside">
							<div class="submitbox" id="submitpost">
								<div id="major-publishing-actions">
									<?php
									if ( pods_is_admin( [ 'pods' ] ) && ! in_array( 'delete', $obj->actions_disabled ) && ! in_array( 'delete', $obj->actions_hidden ) ) {
										?>
										<div id="delete-action">
											<a class="submitdelete deletion" href="<?php echo pods_query_arg( [ 'action' => 'delete' ] ) ?>" onclick="return confirm('You are about to permanently delete this item\n Choose \'Cancel\' to stop, \'OK\' to delete.');"><?php _e( 'Delete', 'pods' ); ?></a>
										</div>
										<!-- /#delete-action -->
										<?php
									}

									if ( pods_is_admin( [ 'pods' ] ) && ! in_array( 'process_job', $obj->actions_disabled ) && ! in_array( 'process_job', $obj->actions_hidden ) && 'queued' == $item['status'] ) {
										?>
										<div id="preview-action">
											<a class="preview button" href="<?php echo pods_query_arg( [ 'action' => 'process_job' ] ) ?>"><?php _e( 'Process Job', 'pods-jobs-queue' ); ?></a>
										</div>
										<!-- /#delete-action -->
										<?php
									}
									?>

									<div class="clear"></div>
								</div>
								<!-- /#major-publishing-actions -->
							</div>
							<!-- /#submitpost -->
						</div>
						<!-- /.inside -->
					</div>
					<!-- /#submitdiv --><!-- END PUBLISH DIV -->
				</div>
				<!-- /#side-sortables -->
			</div>
			<!-- /#side-info-column -->

			<div id="post-body">
				<div id="post-body-content">
					<div id="titlediv">
						<div id="titlewrap">
							<h3><?php
								if ( is_array( $item['callback'] ) ) {
									if ( is_object( $item['callback'][0] ) ) {
										echo esc_html( get_class( $item['callback'][0] ) . '->' );
									} else {
										echo esc_html( $item['callback'][0] . '::' );
									}

									echo esc_html( $item['callback'][1] );
								} else {
									echo esc_html( $item['callback'] );
								}

								echo '();';
								?></h3>
						</div>
						<!-- /#titlewrap -->
					</div>
					<!-- /#titlediv -->

					<div id="normal-sortables" class="meta-box-sortables ui-sortable">
						<div id="pods-meta-box" class="postbox" style="">
							<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="hndle">
								<span><?php _e( 'Stats', 'pods-jobs-queue' ); ?></span>
							</h3>

							<div class="inside">
								<table class="form-table pods-metabox">
									<?php
									foreach ( $item as $field => $value ) {
										if ( ! isset( $obj->fields['view'][ $field ] ) ) {
											continue;
										}

										$field = $obj->fields['view'][ $field ];
										?>
										<tr class="form-field pods-field <?php echo 'pods-form-ui-row-type-' . $field['type'] . ' pods-form-ui-row-name-' . PodsForm::clean( $field['name'], true ); ?>">
											<th scope="row" valign="top">
												<strong><?php echo $field['label']; ?></strong>
											</th>
											<td>
												<?php
												if ( is_array( $value ) || is_object( $value ) ) {
													print_r( $value, true );
												} else {
													ob_start();

													$field_value = PodsForm::field_method( $field['type'], 'ui', $obj->id, $value, $field['name'], array_merge( $field, pods_var_raw( 'options', $field, [], null, true ) ), $obj->fields['view'] );

													$field_output = trim( (string) ob_get_clean() );

													if ( false === $field_value ) {
														$value = '';
													} elseif ( 0 < strlen( trim( (string) $field_value ) ) ) {
														$value = trim( (string) $field_value );
													} elseif ( 0 < strlen( $field_output ) ) {
														$value = $field_output;
													}

													echo $value;
												}
												?>
											</td>
										</tr>
										<?php
									}
									?>
								</table>
							</div>
							<!-- /.inside -->
						</div>
						<!-- /#pods-meta-box -->
					</div>
					<!-- /#normal-sortables -->

				</div>
				<!-- /#post-body-content -->

				<br class="clear" />
			</div>
			<!-- /#post-body -->

			<br class="clear" />
		</div>
		<!-- /#poststuff -->
	</div>
	<!-- /#pods-record -->

</div>