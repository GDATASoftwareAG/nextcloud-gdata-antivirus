<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2012-2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
/** @var array $_ */
/** @var \OCP\IL10N $l */

style('core', ['styles', 'header', 'exception']);

function print_exception(Throwable $e, \OCP\IL10N $l): void {
	print_unescaped('<pre>');
	p($e->getTraceAsString());
	print_unescaped('</pre>');

	if ($e->getPrevious() !== null) {
		print_unescaped('<br />');
		print_unescaped('<h4>');
		p($l->t('Previous'));
		print_unescaped('</h4>');

		print_exception($e->getPrevious(), $l);
	}
}

?>
<div class="guest-box wide">
	<h2><?php p($l->t($_['title'])) ?></h2>
	<p><?php p($l->t($_['message'])) ?></p>

	<h3><?php p($l->t('Technical details')) ?></h3>
	<ul>
		<li><?php p($l->t('Remote Address: %s', [$_['remoteAddr']])) ?></li>
		<li><?php p($l->t('Request ID: %s', [$_['requestID']])) ?></li>
		<?php if (isset($_['debugMode']) && $_['debugMode'] === true): ?>
			<li><?php p($l->t('Type: %s', [$_['errorClass']])) ?></li>
			<li><?php p($l->t('Code: %s', [$_['errorCode']])) ?></li>
			<li><?php p($l->t('Message: %s', [$_['errorMsg']])) ?></li>
			<li><?php p($l->t('File: %s', [$_['file']])) ?></li>
			<li><?php p($l->t('Line: %s', [$_['line']])) ?></li>
		<?php endif; ?>
	</ul>

	<?php if (isset($_['debugMode']) && $_['debugMode'] === true): ?>
		<br />
		<h3><?php p($l->t('Trace')) ?></h3>
		<?php print_exception($_['exception'], $l); ?>
	<?php endif; ?>
</div>
