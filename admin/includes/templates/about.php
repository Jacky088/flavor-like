<?php
/**
 * Overview — WordPress-native dashboard (free + Pro via filters).
 *
 * @package WP_Flavor Like
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

$data = class_exists( 'Flavor_Like_Overview' ) ? Flavor_Like_Overview::get_about_view_data() : array();

$import_flash   = isset( $_GET['flavor_like_import'] ) ? sanitize_key( wp_unslash( $_GET['flavor_like_import'] ) ) : '';
$repair_flash   = isset( $_GET['flavor_like_repair'] ) ? sanitize_key( wp_unslash( $_GET['flavor_like_repair'] ) ) : '';
$stats_flash    = isset( $_GET['flavor_like_stats_cache'] ) ? sanitize_key( wp_unslash( $_GET['flavor_like_stats_cache'] ) ) : '';
$import_open = in_array( $import_flash, array( 'error_upload', 'error_json', 'error_payload', 'error' ), true );
$is_pro         = false;
$health         = isset( $data['health'] ) ? $data['health'] : array();
$status_groups  = class_exists( 'Flavor_Like_Overview' ) ? Flavor_Like_Overview::group_status_rows( $data['status_rows'] ?? array() ) : array();
$group_labels   = $data['status_groups'] ?? array();
$group_order    = array( 'engagement', 'setup' );
?>

<div class="wrap flavor-like-about">

	<h1 class="flavor-like-about__title">
		<?php esc_html_e( 'Overview', 'flavor-like' ); ?>
		<span class="flavor-like-about__badge"><?php echo esc_html( FLAVOR_LIKE_VERSION ); ?></span>
	</h1>

	<p class="flavor-like-about__lead">
		<?php esc_html_e( 'Like buttons and a Statistics dashboard for your WordPress site. Open Statistics for charts and growth tips, or use the shortcuts below to configure display and check status.', 'flavor-like' ); ?>
	</p>

	<?php if ( 'success' === $import_flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings imported and saved successfully!', 'flavor-like' ); ?></p></div>
	<?php elseif ( 'error_upload' === $import_flash ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'No settings file was uploaded. Choose a JSON file and try again.', 'flavor-like' ); ?></p></div>
	<?php elseif ( 'error_json' === $import_flash ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Invalid JSON format. Please check your JSON syntax.', 'flavor-like' ); ?></p></div>
	<?php elseif ( 'error_payload' === $import_flash ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'This file does not look like a Flavor Like settings export. Use a file exported from Settings backup in the Overview sidebar.', 'flavor-like' ); ?></p></div>
	<?php elseif ( 'error' === $import_flash ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Settings import failed. Please try again.', 'flavor-like' ); ?></p></div>
	<?php endif; ?>

	<?php if ( 'success' === $repair_flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Database tables repaired successfully.', 'flavor-like' ); ?></p></div>
	<?php elseif ( 'failed' === $repair_flash ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Some database tables could not be created. Please contact your host or try deactivating and reactivating the plugin.', 'flavor-like' ); ?></p></div>
	<?php endif; ?>

	<?php if ( 'flushed' === $stats_flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Statistics cache refreshed. Totals and charts will rebuild on the next view.', 'flavor-like' ); ?></p></div>
	<?php endif; ?>

	<div class="flavor-like-about__layout">

		<div class="flavor-like-about__main">

			<?php $storage_upgrade = $data['storage_upgrade'] ?? null; ?>
			<?php if ( ! empty( $storage_upgrade ) ) : ?>
				<?php
				$task_modifier = 'cleanup' === ( $storage_upgrade['phase'] ?? '' )
					? ' flavor-like-about-card--task-cleanup'
					: ' flavor-like-about-card--task-optional';
				?>
				<div class="flavor-like-about-card<?php echo esc_attr( $task_modifier ); ?>" role="region" aria-label="<?php echo esc_attr( $storage_upgrade['title'] ?? '' ); ?>">
					<div class="flavor-like-about-task__header">
						<h2 class="flavor-like-about-card__title"><?php echo esc_html( $storage_upgrade['title'] ?? '' ); ?></h2>
					</div>
					<?php if ( ! empty( $storage_upgrade['intro'] ) ) : ?>
						<p class="flavor-like-about-task__intro"><?php echo esc_html( $storage_upgrade['intro'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $storage_upgrade['reassurance'] ) && is_array( $storage_upgrade['reassurance'] ) ) : ?>
						<ul class="flavor-like-about-task__reassurance">
							<?php foreach ( $storage_upgrade['reassurance'] as $point ) : ?>
								<li><?php echo esc_html( $point ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<div class="flavor-like-about-status flavor-like-about-task__status" role="list">
						<div class="flavor-like-about-status__item flavor-like-about-status__item--<?php echo esc_attr( $storage_upgrade['state'] ?? 'neutral' ); ?>" role="listitem">
							<span class="flavor-like-about-status__label"><?php esc_html_e( 'Status', 'flavor-like' ); ?></span>
							<span class="flavor-like-about-status__value"><?php echo esc_html( $storage_upgrade['status'] ?? '' ); ?></span>
							<?php if ( ! empty( $storage_upgrade['progress'] ) ) : ?>
								<span class="flavor-like-about-status__hint"><?php echo esc_html( $storage_upgrade['progress'] ); ?></span>
							<?php endif; ?>
						</div>
					</div>
					<p class="flavor-like-about-task__actions">
						<a class="button button-primary" href="<?php echo esc_url( $storage_upgrade['url'] ?? '#' ); ?>">
							<?php echo esc_html( $storage_upgrade['cta_label'] ?? 'Get started' ); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

			<!-- Status -->
			<div class="flavor-like-about-card">
				<div class="flavor-like-about-card__header">
					<h2 class="flavor-like-about-card__title"><?php esc_html_e( 'At a glance', 'flavor-like' ); ?></h2>
					<span class="flavor-like-about-card__links">
						<a class="flavor-like-about-card__link" href="<?php echo esc_url( $health['statistics_url'] ?? admin_url( 'admin.php?page=flavor-like-statistics' ) ); ?>"><?php esc_html_e( 'Statistics', 'flavor-like' ); ?></a>
						<?php if ( class_exists( 'Flavor_Like_Health' ) ) : ?>
							<a class="flavor-like-about-card__link" href="<?php echo esc_url( Flavor_Like_Health::get_site_health_url() ); ?>"><?php echo esc_html( 'Site Health' ); ?></a>
						<?php endif; ?>
					</span>
				</div>
				<?php if ( ! empty( $data['summary'] ) ) : ?>
					<p class="flavor-like-about-summary"><?php echo wp_kses_post( $data['summary'] ); ?></p>
				<?php endif; ?>
				<?php foreach ( $group_order as $group_key ) : ?>
					<?php if ( empty( $status_groups[ $group_key ] ) ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<div class="flavor-like-about-status-group">
						<?php if ( ! empty( $group_labels[ $group_key ] ) ) : ?>
							<h3 class="flavor-like-about-status-group__title"><?php echo esc_html( $group_labels[ $group_key ] ); ?></h3>
						<?php endif; ?>
						<div class="flavor-like-about-status" role="list">
							<?php foreach ( $status_groups[ $group_key ] as $row ) : ?>
								<?php $state = isset( $row['state'] ) ? $row['state'] : 'neutral'; ?>
								<div class="flavor-like-about-status__item flavor-like-about-status__item--<?php echo esc_attr( $state ); ?>" role="listitem">
									<span class="flavor-like-about-status__label"><?php echo esc_html( $row['label'] ?? '' ); ?></span>
									<span class="flavor-like-about-status__value"><?php echo esc_html( $row['value'] ?? '' ); ?></span>
									<?php if ( ! empty( $row['hint'] ) ) : ?>
										<span class="flavor-like-about-status__hint"><?php echo esc_html( $row['hint'] ); ?></span>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
				<?php if ( empty( $health['tables_ok'] ) ) : ?>
					<div class="flavor-like-about-card__hint flavor-like-about-card__hint--warn" role="alert">
						<p>
							<strong><?php esc_html_e( 'Database tables need repair', 'flavor-like' ); ?></strong>
							<?php if ( ! empty( $health['missing_tables'] ) ) : ?>
								<?php
								echo esc_html(
									sprintf(
										/* translators: %s: comma-separated table labels */
										__( 'Missing tables: %s.', 'flavor-like' ),
										implode( ', ', (array) $health['missing_tables'] )
									)
								);
								?>
							<?php else : ?>
								<?php esc_html_e( 'One or more Flavor Like tables are missing.', 'flavor-like' ); ?>
							<?php endif; ?>
						</p>
						<?php if ( ! empty( $data['repair_tables_url'] ) ) : ?>
							<p>
								<a class="button button-secondary" href="<?php echo esc_url( $data['repair_tables_url'] ); ?>">
									<?php esc_html_e( 'Repair database tables', 'flavor-like' ); ?>
								</a>
							</p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php if ( ! empty( $data['flush_stats_cache_url'] ) ) : ?>
					<p>
						<a class="button button-secondary" href="<?php echo esc_url( $data['flush_stats_cache_url'] ); ?>">
							<?php esc_html_e( 'Refresh statistics cache', 'flavor-like' ); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>

		<!-- Quick actions -->
		<div class="flavor-like-about-card">
			<h2 class="flavor-like-about-card__title"><?php esc_html_e( 'Quick actions', 'flavor-like' ); ?></h2>
			<div class="flavor-like-about-actions">
				<?php foreach ( (array) ( $data['quick_actions'] ?? array() ) as $action ) : ?>
					<?php
					$btn_class = ! empty( $action['primary'] ) ? 'button-primary' : 'button-secondary';
					$external  = ! empty( $action['external'] );
					$icon      = ! empty( $action['icon'] ) ? $action['icon'] : 'arrow-right-alt';
					?>
					<a
						class="button <?php echo esc_attr( $btn_class ); ?> flavor-like-about-actions__btn"
						href="<?php echo esc_url( $action['url'] ?? '#' ); ?>"
						<?php echo $external ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
					>
						<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
						<?php echo esc_html( $action['label'] ?? '' ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>

		</div>

		<aside class="flavor-like-about__aside" aria-label="<?php esc_attr_e( 'Plugin details and settings tools', 'flavor-like' ); ?>">
			<div class="flavor-like-about-card">
				<h2 class="flavor-like-about-card__title"><?php esc_html_e( 'Plugin info', 'flavor-like' ); ?></h2>
				<dl class="flavor-like-about-meta">
					<div>
						<dt><?php esc_html_e( 'Flavor Like', 'flavor-like' ); ?></dt>
						<dd><?php echo esc_html( FLAVOR_LIKE_VERSION ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'WordPress', 'flavor-like' ); ?></dt>
						<dd><?php echo esc_html( $data['wp_version'] ?? '' ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Database schema', 'flavor-like' ); ?></dt>
						<dd><?php echo esc_html( $health['db_version'] ?? FLAVOR_LIKE_DB_VERSION ); ?></dd>
					</div>
					<?php foreach ( (array) ( $data['sidebar_meta'] ?? array() ) as $meta ) : ?>
						<div>
							<dt><?php echo esc_html( $meta['label'] ?? '' ); ?></dt>
							<dd>
								<?php if ( ! empty( $meta['url'] ) ) : ?>
									<a href="<?php echo esc_url( $meta['url'] ); ?>"><?php echo esc_html( $meta['value'] ?? '' ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $meta['value'] ?? '' ); ?>
								<?php endif; ?>
							</dd>
						</div>
					<?php endforeach; ?>
				</dl>
			</div>

			<div class="flavor-like-about-card flavor-like-about-card--muted flavor-like-about-backup" id="flavor-like-settings-backup">
				<h2 class="flavor-like-about-card__title"><?php esc_html_e( 'Settings backup', 'flavor-like' ); ?></h2>
				<div class="flavor-like-about-backup__actions">
					<p class="flavor-like-about-backup__intro"><?php echo esc_html( $data['backup_intro'] ?? '' ); ?></p>
					<a class="button button-secondary" href="<?php echo esc_url( $data['export_url'] ?? '#' ); ?>"><?php esc_html_e( 'Export', 'flavor-like' ); ?></a>
					<details class="flavor-like-about-backup__import"<?php echo $import_open ? ' open' : ''; ?>>
						<summary><?php esc_html_e( 'Import settings', 'flavor-like' ); ?></summary>
						<form class="flavor-like-about-backup__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" onsubmit='return window.confirm(<?php echo wp_json_encode( $data['backup_import_confirm'] ?? __( 'Import will replace your current Flavor Like settings and customizer values. Continue?', 'flavor-like' ) ); ?>);'>
							<input type="hidden" name="action" value="flavor_like_import_settings" />
							<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $data['import_nonce'] ?? '' ); ?>" />
							<label class="flavor-like-about-backup__label" for="flavor-like-settings-file"><?php esc_html_e( 'JSON file', 'flavor-like' ); ?></label>
							<input id="flavor-like-settings-file" class="flavor-like-about-backup__file" type="file" name="settings_file" accept="application/json,.json" required />
							<button type="submit" class="button button-secondary"><?php esc_html_e( 'Import', 'flavor-like' ); ?></button>
						</form>
					</details>
				</div>
			</div>
		</aside>

	</div>
</div>
