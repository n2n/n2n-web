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
namespace n2n\web\ui\view\impl\html;

use n2n\io\managed\File;
use n2n\web\ui\UiComponent;
use n2n\io\managed\img\ThumbStrategy;
use n2n\io\managed\img\ImageFile;
use n2n\io\managed\img\ImageDimension;

class UiComponentFactory {
		
	public static function createImg(File $file, ThumbStrategy $thumbStrategy = null, 
			array $attrs = null, bool $addWidthAttr = true, bool $addHeightAttr = true): UiComponent {
		
		if (!$file->isValid()) {
			return self::createInvalidImg($thumbStrategy !== null ? $thumbStrategy->getImageDimension() : null, 
					$attrs, $addWidthAttr, $addHeightAttr);
		}
				
		$imageFile = new ImageFile($file);
		
		$thumbImageFile = null;
		if ($thumbStrategy === null) {
			$thumbImageFile = $imageFile;
		} else {
			$thumbImageFile = $imageFile->getOrCreateThumb($thumbStrategy);
		}
				
		$elemAttrs = array('src' => self::buildImgSrc($thumbImageFile));
		if ($addWidthAttr) $elemAttrs['width'] = $thumbImageFile->getWidth();
		if ($addHeightAttr) $elemAttrs['height'] = $thumbImageFile->getHeight();
		$attrs = HtmlUtils::mergeAttrs($elemAttrs, (array) $attrs);
		if (!isset($attrs['alt'])) $attrs['alt'] = $file->getOriginalName();
		
		return new HtmlElement('img', $attrs);
	}
	
	
	public static function createImgFromDim(File $file, ImageDimension $imageDimension,
			array $attrs = null, bool $addWidthAttr = true, bool $addHeightAttr = true) {
		if (!$file->isValid()) {
			return self::createInvalidImg($imageDimension, $attrs, $addWidthAttr, $addHeightAttr);
		}
		
		
	}
	
	public static function buildImgSrc(ImageFile $imageFile): string {
		$fileSource = $imageFile->getFile()->getFileSource();
		if ($fileSource->isHttpaccessible()) {
			return $fileSource->getUrl();
		}
		
		return 'data: ' . $imageFile->getImageSource()->getMimeType() . ';base64,'
				. base64_encode($fileSource->createInputStream()->read());
	}
	
	const INVALID_IMG_DEFAULT_ALT = 'missing image';
	const INVALID_IMG_DEFAULT_WIDTH = 200;
	const INVALID_IMG_DEFAULT_HEIGHT = 200;
	
	public static function createInvalidImg(ImageDimension $imageDimension = null, 
			array $attrs = null, bool $addWidthAttr = true, bool $addHeightAttr = true) {
		$width = self::INVALID_IMG_DEFAULT_WIDTH;
		$height = self::INVALID_IMG_DEFAULT_HEIGHT;
		
		if ($imageDimension !== null) {
			$width = $imageDimension->getWidth();
			$height = $imageDimension->getHeight();
		}

		$elemAttrs = array('src' => self::buildInvalidImgSrc($width, $height));
		if ($addWidthAttr) $elemAttrs['width'] = $width;
		if ($addHeightAttr) $elemAttrs['height'] = $height;
		$attrs = HtmlUtils::mergeAttrs($elemAttrs, (array) $attrs);
		if (!isset($attrs['alt'])) $attrs['alt'] = self::INVALID_IMG_DEFAULT_ALT;
		
		return new HtmlElement('img', $attrs);
	}
	
	private static function buildInvalidImgSrc(int $width, int $height) {
		return 'data:image/svg+xml;base64,' . base64_encode(self::buildInvalidImgSvg($width, $height));

		// return 'data:image/svg+xml;base64,'
		//		. base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" baseProfile="full"'
		//		. '		width="' . $width . 'px" height="' . $height . 'px" style="background: #EEE">'
		//		. '	<line x1="0%" y1="0%" x2="100%" y2="100%" stroke="#CCC" stroke-width="2px"/>'
		//		. '	<line x1="100%" y1="0%" x2="0%" y2="100%" stroke="#CCC" stroke-width="2px"/>'
		//		. '</svg>');
	}


	public static function buildInvalidImgSvg(int $width, int $height) {
		$topMargin = ($height / 2) - 100;
		$leftMargin = ($width / 2) - 100;

		$picturesHeight = 200;
		$picturesWidth = 200;
		if ($height < 200) {
			$picturesHeight = $height;
			$topMargin = 1;
		}

		if ($width < 200) {
			$picturesWidth = $width;
			$leftMargin = 1;
		}

		$svg = '
				<svg id="placeholder" xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '">
					<title>rocket-placeholder-image</title>
						<defs>
							<style>
								#background {
									fill: #E6E6E6;
									stroke-opacity: 0.9;
								}
					
								.cross-line {
									fill: none;
									stroke: #CCCCCC;
									stroke-linecap: round;
									stroke-width: 3px;
								}
					
								#picture-front {
									fill: #E6E6E6;
								}
					
								.picture-frame {
									height: 100px;  
									width: 60px;
					
									fill: #B3B3B3;
									stroke: #b3b3b3;
								}
							</style>
						</defs>
						<rect id="background" width="100%" height="100%" />';

		if ($height > 70 && $width > 70) {
			$svg .= '
					<line class="cross-line" x1="10%" y1="10%" x2="30%" y2="30%"/>
					<line class="cross-line" x1="90%" y1="10%" x2="70%" y2="30%"/>
					<line class="cross-line" x1="90%" y1="90%" x2="70%" y2="70%"/>
					<line class="cross-line" x1="10%" y1="90%" x2="30%" y2="70%"/>';
		}

		$svg .= '
					<svg viewBox="0 0 200 200" y="' . $topMargin . '" x="' . $leftMargin . '" width="' . $picturesWidth . 'px" height="' . $picturesHeight . 'px">
						<rect height="100%" width="100%" fill="#E6E6E6"/>
						<g>
							<rect fill="#E6E6E6" width="200" height="200"/>
							
							<path class="picture-frame" d="M7,50.8l19.5,91.7l121.3-25.8l-19.5-91.7L7,50.8z M138,110.4L32.8,132.7l-16-75.5l105.2-22.4
								L138,110.4z"/>
							
							<g>
								<rect x="43" y="58" class="picture-frame" width="124" height="93"/>
								<rect x="51" y="66" class="picture-frame" id="picture-front" width="108" height="77"/>
							</g>
							<g>
								<path fill="#B3B3B3" d="M80.1,78.4c0-1.9-0.3-4.4-1-5.4H58v20.9c2,0.6,3.6,1,5.5,1C72.7,94.9,80.1,87.5,80.1,78.4z"/>
								<polygon fill="#B3B3B3" points="134.4,97.6 122.2,81.3 90,113.4 80.1,103.5 58,126.8 58,136 152,136 152,102.5 142.5,90.5"/>
							</g>
						</g>
					</svg>
				</svg>';

		return $svg;
	}
}
