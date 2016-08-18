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

	use n2n\web\dispatch\map\PropertyPath;
	use n2n\web\dispatch\mag\Mag;
	use n2n\web\ui\view\impl\html\HtmlElement;
	use n2n\web\ui\view\impl\html\HtmlBuilder;

	$propertyPath = $view->getParam('propertyPath');
	$view->assert($propertyPath instanceof PropertyPath);

	$num = $view->getParam('num');
	$optionalObjects = $view->getParam('optionalObjects'); 
	$fieldOption = $view->getParam('fieldOption');
	$view->assert($fieldOption instanceof Option);
	
	$min = $view->getParam('min');
	$numExisting = $view->getParam('numExisting');
	$dynamicArray = $view->getParam('dynamicArray');

	$html->meta()->addJs('js\array-option.js', null, false, false, null, HtmlBuilder::TARGET_BODY_END);
?>
<ul class="n2n-option-array" data-dynamic-array="<?php $html->out($dynamicArray) ?>"
		data-num-existing="<?php $html->out($numExisting) ?>" data-min="<?php $html->out($min)?>">
	<?php $formHtml->meta()->arrayProps($propertyPath, function() use ($html, $formHtml, $view, $fieldOption, $optionalObjects) { ?>
		<li<?php $view->out(HtmlElement::buildAttrsHtml($fieldOption->getContainerAttrs())) ?>>
			<div>
				<?php $html->out($fieldOption->createUiField($formHtml->meta()->createPropertyPath(), $view)); ?>
			</div>
			<?php if ($optionalObjects): ?>
				<?php $formHtml->objectOptional(null, array('class' => 'n2n-option-object-optional')) ?>
			<?php endif ?>
		</li>
	<?php }, $num) ?>
</ul>
