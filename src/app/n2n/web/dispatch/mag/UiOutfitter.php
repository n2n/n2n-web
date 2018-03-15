<?php
namespace n2n\web\dispatch\mag;

use n2n\impl\web\ui\view\html\HtmlElement;
use n2n\impl\web\ui\view\html\HtmlView;
use n2n\web\dispatch\map\PropertyPath;
use n2n\web\ui\UiComponent;

interface UiOutfitter {
	const NATURE_MAIN_CONTROL = 1;
	const NATURE_TEXT = 2;
	const NATURE_CHECK = 4;
	const NATURE_CHECK_LABEL = 8;
	const NATURE_TEXT_AREA = 16;
	const NATURE_MASSIVE_ARRAY_ITEM = 32;
	const NATURE_MASSIVE_ARRAY_ITEM_CONTROL = 64;
	const NATURE_MASSIVE_ARRAY = 128;
	const NATURE_LEGEND = 256;
	const NATURE_BTN_PRIMARY = 512;
	const NATURE_BTN_SECONDARY = 1024;
	const NATURE_BTN_FULL_WIDTH = 2048;
	const NATURE_SELECT = 4096;
	const NATURE_MASSIVE_ARRAY_ITEM_STRUCTURE = 8192;
	const NATURE_CONTROL_GROUP = 16384;
	const NATURE_CONTROL_GROUP_ADDON = 32768;
	const NATURE_CONTROL_WRAPPER = 65536;
	
	const EL_NATRUE_CONTROL_ADDON_SUFFIX_WRAPPER = 1;
	const EL_NATURE_CONTROL_ADDON_WRAPPER = 2;
	const EL_NATURE_CONTROL_ADD = 4;
	const EL_NATURE_CONTROL_REMOVE = 8;
	const EL_NATURE_ARRAY_ITEM_CONTROL = 16;
	const EL_NATURE_CHECK_WRAPPER = 32;
	const EL_NATURE_HELP_TEXT = 64;
	const EL_NATURE_CONTROL_LIST = 128;
	const EL_NATURE_CONTROL_LIST_ITEM = 256;

	const ICON_NATURE_ADD = 'fa fa-plus';
	const ICON_NATURE_REMOVE = 'fa fa-times';

	/**
	 * @param string $nature
	 * @return array
	 */
	public function createAttrs(int $nature): array;

	/**
	 * @param int $elemNature
	 * @return HtmlElement
	 */
	public function createElement(int $elemNature, array $attrs = null, $contents = ''): UiComponent;

	/**
	 * @param PropertyPath $propertyPath
	 * @param HtmlView $contextView
	 * @return UiComponent
	 */
	public function createMagDispatchableView(PropertyPath $propertyPath = null, HtmlView $contextView): UiComponent;
}