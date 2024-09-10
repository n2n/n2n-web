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

	use n2n\core\err\ThrowableModel;
	use n2n\impl\web\ui\view\html\HtmlView;
	use n2n\util\uri\Query;
	
	$view = HtmlView::view($this);
	$html = HtmlView::html($this);
	$request = HtmlView::request($this);
	
	$throwableModel = $view->getParam('throwableModel'); 
	$view->assert($throwableModel instanceof ThrowableModel);

	$html->meta()->setTitle($throwableModel->getTitle());
	
	$throwableInfos = $throwableModel->getThrowableInfos();
	
	$html->meta()->addJs('js/jquery-2.1.4.min.js');
	$html->meta()->addJs('js/exceptionhandler.js');
	
	$html->meta()->addJs('js/sh/scripts/shCore.js');
	$html->meta()->addJs('js/sh/scripts/shBrushPhp.js');
	$html->meta()->addJs('js/sh/scripts/shBrushSql.js');
	$html->meta()->addJs('js/sh/scripts/shBrushXml.js');
	
	$html->meta()->addCss('js/sh/styles/shCore.css');
	$html->meta()->addCss('js/sh/styles/shThemeDefault.css');
	$html->meta()->addCss('css/errorpage-500-development.css');
	$html->meta()->addCss('css/font-awesome.css');
	
	$html->meta()->addJsCode('SyntaxHighlighter.all();');
?>
<!DOCTYPE html>
<html>
	<?php $html->headStart() ?>
	<?php $html->headEnd() ?>
	<?php $html->bodyStart() ?>
		<div id="container">
			<div id="intro">
				<h1><i class="fa fa-exclamation-triangle"></i><?php $html->esc($throwableModel->getTitle()) ?></h1>
				<ul id="help-nav">
					<?php if (count($documentIds = $throwableModel->getDocumentIds()) > 0): ?>
						<li>
							<?php $html->linkStart(ThrowableModel::EXCEPTION_DOCUMENTATION_URL . '?' 
									. Query::create(array('ids' => $documentIds)), array('target' => '_blank')) ?>
								<i class="fa fa-exclamation-circle"></i>
								Get help for this exception stack
							<?php $html->linkEnd() ?>
						</li>
					<?php endif ?>
					<li><a href="<?php $html->out(ThrowableModel::N2N_DOCUMENTATION_URL) ?>"><i class="fa fa-book"></i>Documentation</a></li>
				</ul>
			</div>
			
			<div id="exception-meta-container">
				<?php $html->imageAsset('img/exception-screen-icon.png', 'n2n exception logo', array('id' => 'exception-logo'))?>
				<?php if (1 < count($throwableInfos)): ?>
					<nav id="exception-navi">
						<ul>
							<?php foreach ($throwableInfos as $key => $throwableInfo): ?>
								<li class="exception-link">
									<?php $html->link('#exception-' . $key, $throwableInfo->getNavTitle(),
											array('title' => $throwableInfo->getNavTitle())) ?>
									<?php if (null !== ($documentId = $throwableInfo->getDocumentId())): ?>
										<?php $html->linkStart(ThrowableModel::EXCEPTION_DOCUMENTATION_URL . '?id=' . urlencode($documentId), array('target' => '_blank')) ?>
											<span><i class="fa fa-book"></i></span>
										<?php $html->linkEnd() ?>
									<?php endif ?>
								</li>
							<?php endforeach ?>
							<?php if (0 < mb_strlen($output = $throwableModel->getOutput())): ?>
								<li class="exception-link">
									<?php $html->link('#output', 'Output') ?>
								</li>
							<?php endif ?>
						</ul>
					</nav>
					
					<div id="exception-help">
						The above fancy magic scroll navigation allows you to open the exception - stacktrace (by clicking) as well as open the exception documentation (by doubleclicking) 
					</div>
				<?php endif ?>
			</div>
			
			<div id="exception-container">
				<?php foreach ($throwableInfos as $key => $throwableInfo): ?>
					<div id="exception-<?php $html->out($key) ?>" class="exception">
						<h2 class="exception-title">
							<?php if ($key > 0): ?>
								<span>caused by:</span> 
							<?php endif ?>
							<?php $html->out($throwableInfo->getTitle()) ?>
							<?php if (null !== ($documentId = $throwableInfo->getDocumentId())): ?>
								<?php $html->linkStart(ThrowableModel::EXCEPTION_DOCUMENTATION_URL . '?id=' . urlencode($documentId), array('target' => '_blank')) ?>
									<span><i class="fa fa-book"></i> Get help</span>
								<?php $html->linkEnd() ?>
							<?php endif ?>
						</h2>
						
						<?php if (null !== ($message = $throwableInfo->getMessage())): ?>
							<p class="message"><i class="fa fa-exclamation-triangle"></i><?php $html->esc($message) ?></p>
						<?php else: ?>
							<p class="message no-message">[no error message]</p>
						<?php endif ?>
						
						<?php foreach ($throwableInfo->getCodeInfos() as $codeInfo): ?>
							<div class="snippet debug-info">
								<?php if (count($throwableInfo->getCodeInfos()) > 1) : ?>
									<h3>Error Location</h3>
								<?php endif ?>
								<?php if (null !== ($description = $codeInfo->getDescription())): ?>
									<p class="error-description"><?php $html->out($description)?></p>
								<?php endif ?>
								
								<table class="error-info">
									<?php if (null !== ($filePath = $codeInfo->getFilePath())): ?>
										<tr>
											<th>File:</th>
											<td><?php $html->esc($filePath) ?></td>
										</tr>
									<?php endif ?>
									<?php if (null !== ($lineNo = $codeInfo->getLineNo())): ?>
										<tr>
											<th>Line:</th>
											<td><?php $html->esc($lineNo) ?></td>
										</tr>
									<?php endif ?>
								</table>
								
								<?php if (null !== $snippedCodeHtml = $codeInfo->getSnippetCodeHtml()): ?>
									<div class="error-lines">
										<div class="code">
											<pre class="brush: php; first-line: <?php $html->out($codeInfo->getStartLineNo()) ?>; highlight: [<?php $html->out($codeInfo->getLineNo()) ?>]; <?php $html->out($codeInfo->containsHtml() ? " html-script: true;" : "") ?>"><?php $html->out($snippedCodeHtml) ?></pre>
										</div>
									</div>
								<?php endif ?>
							</div>
						<?php endforeach ?>
						<?php if (null !== ($queryString = $throwableInfo->getStatementString())):?>
							<div class="debug-info query">
								<h3>Query</h3>
								<div class="code">
									<pre class="brush: sql;"><?php $html->out($queryString) ?></pre>
								</div>
								<?php if (count($boundValues = $throwableInfo->getBoundValues()) > 0): ?>
									<h4>Bound Values</h4>
									<table class="bound-values">
										<?php foreach($boundValues as $name => $value): ?>
											<tr>
												<td class="name"><?php $html->esc($name) ?></td>
												<td class="value"><?php $html->esc($value) ?></td>
											</tr>
										<?php endforeach ?>
									</table>
								<?php endif ?>
							</div>
						<?php endif ?>
						
						<div class="debug-info stack-trace">
							<h3>Stack Trace</h3>
							<pre><?php $html->esc($throwableInfo->getStackTraceString())?></pre>
						</div>
					</div>
				<?php endforeach ?>
				
				<?php if (0 < mb_strlen($output = $throwableModel->getOutput())): ?>
					<div id="output" class="output">
						<h2>Output</h2>
						<div class="code">
							<pre class="brush: xml;"><?php $html->esc($output)?></pre>
						</div>
					</div>
				<?php endif ?>		
			</div>
		</div>
	<?php $html->bodyEnd() ?>
</html>
