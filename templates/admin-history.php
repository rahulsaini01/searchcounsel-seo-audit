<?php
/**
 * Audit history admin template.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Recent SEO Audits', 'searchcounsel-seo-audit' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Saved reports include the public URL, score, result summary, and timestamp. Downloaded page HTML and visitor IP addresses are never stored.', 'searchcounsel-seo-audit' ); ?></p>

	<?php if ( empty( $rows ) ) : ?>
		<p><?php esc_html_e( 'No completed audits have been stored yet.', 'searchcounsel-seo-audit' ); ?></p>
	<?php else : ?>
		<table class="widefat striped" style="margin-top: 20px; max-width: 1100px;">
			<thead><tr><th><?php esc_html_e( 'Date (UTC)', 'searchcounsel-seo-audit' ); ?></th><th><?php esc_html_e( 'Website', 'searchcounsel-seo-audit' ); ?></th><th><?php esc_html_e( 'SEO score', 'searchcounsel-seo-audit' ); ?></th><th><?php esc_html_e( 'Summary', 'searchcounsel-seo-audit' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<?php $summary = json_decode( $row->summary, true ); ?>
					<tr>
						<td><?php echo esc_html( $row->created_at ); ?></td>
						<td style="overflow-wrap:anywhere; max-width: 460px;"><?php echo esc_html( $row->url ); ?></td>
						<td><strong><?php echo esc_html( absint( $row->score ) ); ?>/100</strong><?php if ( ! empty( $summary['grade'] ) ) : ?> — <?php echo esc_html( $summary['grade'] ); ?><?php endif; ?></td>
						<td><?php echo esc_html( sprintf( __( '%1$d passed, %2$d warnings, %3$d critical', 'searchcounsel-seo-audit' ), isset( $summary['summary']['pass'] ) ? absint( $summary['summary']['pass'] ) : 0, isset( $summary['summary']['warning'] ) ? absint( $summary['summary']['warning'] ) : 0, isset( $summary['summary']['critical'] ) ? absint( $summary['summary']['critical'] ) : 0 ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
