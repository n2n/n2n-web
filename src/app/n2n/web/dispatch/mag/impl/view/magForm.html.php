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
	use n2n\web\ui\view\impl\html\HtmlView;
	
	/**
	 * @var \n2n\web\ui\view\View $view
	 */
	$view = HtmlView::view($view);
	$formHtml = HtmlView::formHtml($view);
	
	
	$propertyPath = $view->getParam('propertyPath', false);
	$view->assert($propertyPath instanceof PropertyPath);
?>

<ul class="n2n-option-collection">
	<?php $formHtml->meta()->objectProps($propertyPath, function () use ($formHtml) { ?>
		<?php $formHtml->magOpen('li') ?>
			<?php $formHtml->magLabel() ?>
			<div>
				<?php $formHtml->magField() ?>
			</div>
		<?php $formHtml->magClose() ?>
	<?php }) ?>
</ul>
