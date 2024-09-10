<?php
	/*
	 * Copyright (c) 2012-2016, Hofmänner New Media.
	 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
	 *
	 * This file is part of the N2N FRAMEWORK.
	 *
	 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
	 * the GNU Lesser General Public License as published by the Free Software Foundation, either
	 * version 2.1 of the License, or (at your option) any later version.
	 *
	 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
	 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
	 *
	 * The following people participated in this project:
	 *
	 * Andreas von Burg.....: Architect, Lead Developer
	 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
	 * Thomas Günther.......: Developer, Hangar
	 */
	use n2n\impl\web\ui\view\html\HtmlView;
	use n2n\core\err\ThrowableModel;

	$view = HtmlView::view($this);
	$html = HtmlView::html($this);
	
	$throwableModel = $view->getParam('throwableModel');
	$view->assert($throwableModel instanceof ThrowableModel);
	
	$e = $throwableModel->getException();
?>

<div id="status-container">
	<div class="exception">
		<?php if (0 < mb_strlen($message = $e->getMessage())): ?>
			<h2>Message</h2>
			<p class="message">
				<i class="fa fa-exclamation-triangle"></i>
				<?php $html->out($message) ?>
			</p>
		<?php endif ?>
		
		<section>
			<div class="debug-info stack-trace">
				<h3>Stack Trace</h3>
				<pre><?php $html->out(get_class($e) . ': ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString()) ?></pre>
				<?php while (null !== ($e = $e->getPrevious())): ?>
					<pre>caused by <?php $html->out(get_class($e) . ': ' . $e->getMessage() . PHP_EOL 
							. $e->getTraceAsString()) ?></pre>
				<?php endwhile ?>
			</div>
			
			<?php if (0 < mb_strlen($output = (string) $throwableModel->getOutput())): ?>
				<div>
					<h3>Output</h3>
					<pre class="stack-trace"><?php $html->esc($output)?></pre>
				</div>
			<?php endif ?>
		</section>
	</div>
</div>
