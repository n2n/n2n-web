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
	 * Bert Hofmänner.......: Idea, Community Leader, Marketing
	 * Thomas Günther.......: Developer, Hangar
	 */

	use n2n\dispatch\map\PropertyPath;
	use n2n\ui\view\impl\html\HtmlBuilderMeta;

	use n2n\ui\view\impl\html\HtmlView;
	
	/**
	 * @var \n2n\ui\view\View $view
	 */
	$view = HtmlView::view($view);
	$html = HtmlView::html($view);
	$formHtml = HtmlView::formHtml($view);

	$propertyPath = $view->getParam('propertyPath');
	$view->assert($propertyPath instanceof PropertyPath);

	$num = $view->getParam('num');
	$min = $view->getParam('min');
	$numExisting = $view->getParam('numExisting');
	
	$html->meta()->addJs('js/array-option.js', null, false, false, null, HtmlBuilderMeta::TARGET_BODY_END);
?>

<?php $formHtml->optionalObjectActivator($propertyPath) ?>
<ul class="n2n-option-collection-array" data-num-existing="<?php $html->out($numExisting) ?>" data-min="<?php $html->out($min)?>">
	<?php $formHtml->meta()->arrayProps($propertyPath, function() use ($formHtml, $view) { ?>
		<li class="n2n-array-option-element">
			<?php $formHtml->optionalObjectEnabledHidden() ?>
			
			<div>
				<?php $view->import('magForm.html', array(
						'magForm' => $formHtml->meta()->getMapValue()->getObject(), 
						'propertyPath' => $formHtml->meta()->createPropertyPath())) ?>
			</div>
		</li>
	<?php }, $num) ?>
</ul>
