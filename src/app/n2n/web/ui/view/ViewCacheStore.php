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
namespace n2n\web\ui\view;

use n2n\context\ThreadScoped;
use n2n\core\container\AppCache;

class ViewCacheStore implements ThreadScoped {
	private $cacheStore;
	
	private function _init(AppCache $appCache) {
		$this->cacheStore = $appCache->lookupCacheStore(ViewCacheStore::class);
	}
	
	public function store(string $name, array $characteristics, $data, \DateTime $lastMod = null) {
		$this->cacheStore->store($name, $characteristics, $data, $lastMod);
	}
	
	public function get(string $name, array $characteristics) {
		return $this->cacheStore->get($name, $characteristics);
	}
	
	public function clear() {
		$this->cacheStore->clear();
	}
}
// class ViewCacheStore implements ThreadScoped {
// 	private $cacheDirPath;
// 	private $enabled;
// 	private $request;
	
// 	const VIEW_CONTENTS_FILE_SUFFIX = '.contents';
// 	const VIEW_ATTRIBUTES_FILE_SUFFIX = '.attrs';
// 	const VIEW_CHARACTERISTIC_FILE_SUFFIX = '.charact';
// 	/**
// 	 * 
// 	 * @param unknown_type $cacheDirPath
// 	 * @param unknown_type $enabled
// 	 * @param Request $request
// 	 */
// 	public function __construct($cacheDirPath, $enabled, Request $request) {
// 		$this->cacheDirPath = (string) $cacheDirPath;
// 		$this->enabled = (boolean) $enabled;
// 		$this->request = $request;
// 	}
// 	/**
// 	 * 
// 	 * @return string
// 	 */
// 	private function buildNamespaceHash($viewName) {
// 		return HashUtils::base36Md5Hash($viewName, 9);
// 	}
// 	/**
// 	 * 
// 	 * @param unknown_type $charaName
// 	 * @param unknown_type $charaValue
// 	 * @return string
// 	 */
// 	public static function buildCharacteristicsHash($charaName, $charaValue) {
// 		return HashUtils::base36Md5Hash(serialize(array($charaName => $charaValue)), 9);
// 	}
// 	/**
// 	 * 
// 	 * @param View $view
// 	 * @param array $characteristics
// 	 * @return string
// 	 */
// 	private function buildBaseFilePath(View $view, array $characteristics = null) {
// 		$characteristicsHash = null;
// 		if (null === $characteristics) {
// 			$characteristicsHash = HashUtils::base36Md5Hash($this->request->getHostUrl($this->request->getPath()), 9);
// 		} else {
// 			$characteristicsHash = HashUtils::base36Md5Hash((string)serialize($characteristics), 9);
// 		}
// 		return $this->cacheDirPath . DIRECTORY_SEPARATOR . $this->buildNamespaceHash($view->getName()) . '.' . 
// 				$this->buildNamespaceHash((string) $view->getModule()) . '.' . $characteristicsHash;
// 	}
// 	/**
// 	 * 
// 	 * @param View $view
// 	 * @param array $characteristics
// 	 */
// 	public function add(View $view, array $characteristics = null) {
// 		if (!$this->enabled) return;
		
// 		$cacheWriter = new ViewCacheWriter($this->buildBaseFilePath($view, $characteristics), (array) $characteristics);
// 		$view->registerStateListener($cacheWriter);
// 		if (!$view->isCached() && $view->isInitialized()) {
// 			$cacheWriter->viewContentsInitialized($view);
// 		}
// 	}
// 	/**
// 	 * 
// 	 * @param View $view
// 	 * @param ViewCacheControl $cacheControl
// 	 */
// 	public function initializeFromCache(View $view, ViewCacheControl $cacheControl) {
// 		$baseFilePath = $this->buildBaseFilePath($view, $cacheControl->getCharacteristics());
// 		if (!$this->enabled || !is_file($baseFilePath . ViewCacheStore::VIEW_CONTENTS_FILE_SUFFIX)) return;
		
// 		if (null !== ($cacheInterval = $cacheControl->getCacheInterval())) {
// 			$freshUntil = \DateTime::createFromFormat('U', 
// 					IoUtils::filemtime($baseFilePath . ViewCacheStore::VIEW_CONTENTS_FILE_SUFFIX));
// 			$freshUntil->add($cacheInterval);
// 			$now = new \DateTime();
			
// 			if ($freshUntil < $now) {
// 				$this->deleteFiles(IoUtils::glob($baseFilePath . '*'));
// 				return;
// 			}
// 		}
		
// 		try {
// 			$cacheReader = new ViewCacheReader($baseFilePath);
// 			$view->readCachedContents($cacheReader);
// 		} catch (OutdatedViewCacheException $e) {
// 			$this->deleteFiles(IoUtils::glob($baseFilePath . '*'));
// 		}
// 	}
// 	/**
// 	 * 
// 	 * @param unknown_type $viewName
// 	 */
// 	public function clearByViewName($viewName) {
// 		$this->deleteFiles(IoUtils::glob($this->cacheDirPath . DIRECTORY_SEPARATOR . $this->buildNamespaceHash($viewName) . '.*'));
// 	} 
// 	/**
// 	 * 
// 	 * @param View $view
// 	 */
// 	public function clearByView(View $view) {
// 		$this->clearByViewName($view->getName());
// 	}
// 	/**
// 	 * 
// 	 * @param unknown_type $module
// 	 * @param array $characteristics
// 	 */
// 	public function clearByFilter($module, array $characteristics = null) {
// 		$pattern = $this->cacheDirPath . DIRECTORY_SEPARATOR . '*.' . (isset($module) ? $this->buildNamespaceHash((string) $module) . '*' : '*');
		
// 		if (null === $characteristics || !sizeof($characteristics)) {
// 			$this->deleteFiles(IoUtils::glob($pattern));
// 			return;
// 		}
		
// 		$pathPatternsToDelete = null;
// 		foreach ($characteristics as $charaName => $charaValue) {
// 			$pathPatterns = array();
// 			foreach (IoUtils::glob($pattern . '.' . self::buildCharacteristicsHash($charaName, $charaValue) . self::VIEW_CHARACTERISTIC_FILE_SUFFIX) as $path) {
// 				$path = substr($path, 0, -mb_strlen(self::VIEW_CHARACTERISTIC_FILE_SUFFIX));
// 				$pathParts = explode('.', $path);
// 				array_pop($pathParts);
// 				$pathPatterns[] = implode('.', $pathParts) . '*'; 
// 			}
			
// 			if (!is_array($pathPatternsToDelete)) {
// 				$pathPatternsToDelete = $pathPatterns;
// 			} else {
// 				$pathPatternsToDelete = array_intersect($pathPatternsToDelete, $pathPatterns);
// 			}
// 		}
		
// 		foreach ($pathPatternsToDelete as $pathPatternToDelete) {
// 			$this->deleteFiles(IoUtils::glob($pathPatternToDelete));
// 		}
// 	}
// 	/**
// 	 * 
// 	 */
// 	public function clear() {
// 		$this->deleteFiles(IoUtils::glob($this->cacheDirPath . DIRECTORY_SEPARATOR . '*'));
// 	}
// 	/**
// 	 * 
// 	 * @param array $filePaths
// 	 */
// 	private function deleteFiles(array $filePaths) {
// 		foreach ($filePaths as $filePath) {
// 			try {
// 				IoUtils::unlink($filePath);
// 			} catch (IoException $e) {
// 				$filePath = IoUtils::createSafeFileOutputStream($filePath);
// 				$filePath->write('');
// 				$filePath->close();
// 			} 
// 		}
// 	}
// }


// class ViewCacheWriter implements ViewStateListener {
// 	private $baseFilePath;
// 	private $cacheControl;
// 	private $contentsWriter;
// 	private $characteristics;
// 	/**
// 	 * 
// 	 * @param unknown_type $baseFilePath
// 	 * @param array $characteristics
// 	 */
// 	public function __construct($baseFilePath, array $characteristics) {
// 		$this->baseFilePath = $baseFilePath;
// 		$this->characteristics = $characteristics;
// 	}
// 	/**
// 	 * (non-PHPdoc)
// 	 * @see n2n\web\ui\view.ViewStateListener::onViewContentsBuffering()
// 	 */
// 	public function onViewContentsBuffering(View $view) { 
		
// 	} 
// 	/**
// 	 * (non-PHPdoc)
// 	 * @see n2n\web\ui\view.ViewStateListener::onPanelImport()
// 	 */
// 	public function onPanelImport(View $view, $panelName) { 
		
// 	}
// 	/**
// 	 * 
// 	 * @throws ViewCacheWriterIsClosedException
// 	 */
// 	private function ensureSafeFileWriterIsOpen() {
// 		if (isset($this->contentsWriter) && $this->contentsWriter->isOpen()) return;
		
// 		throw new ViewCacheWriterIsClosedException(SysTextUtils::get('n2n_error_ui_view_cache_writer_is_closed'));
// 	}
// 	/**
// 	 * (non-PHPdoc)
// 	 * @see n2n\web\ui\view.ViewStateListener::viewContentsInitialized()
// 	 */
// 	public function viewContentsInitialized(View $view) {
// 		$this->contentsWriter = IoUtils::createSafeFileOutputStream($this->baseFilePath . ViewCacheStore::VIEW_CONTENTS_FILE_SUFFIX);
// 		$view->writeCachedContents($this);
// 		$this->contentsWriter->close();
// 	}
// 	/**
// 	 * 
// 	 * @param unknown_type $contents
// 	 */
// 	public function writeContents($contents) {
// 		$this->contentsWriter->write($contents);
// 		$this->writeCharacteristics();
// 	}
// 	/**
// 	 * 
// 	 * @param unknown_type $attributesObject
// 	 */
// 	public function writeAttributesObject($attributesObject) {
// 		if (!$this->contentsWriter->isOpen()) return;
// 		IoUtils::putContentsSafe($this->baseFilePath . ViewCacheStore::VIEW_ATTRIBUTES_FILE_SUFFIX, serialize($attributesObject));
// 	}
// 	/**
// 	 * 
// 	 */
// 	private function writeCharacteristics() {
// 		foreach ($this->characteristics as $charaName => $charaValue) {
// 			IoUtils::putContentsSafe($this->baseFilePath . '.' 
// 					. ViewCacheStore::buildCharacteristicsHash($charaName, $charaValue) .
// 					ViewCacheStore::VIEW_CHARACTERISTIC_FILE_SUFFIX, '');
// 		}
// 	}
// }

// class ViewCacheReader {
// 	private $contents;
// 	private $attributesObject;
// 	private $empty;
// 	/**
// 	 * 
// 	 * @param unknown_type $baseFilePath
// 	 * @throws OutdatedViewCacheException
// 	 */
// 	public function __construct($baseFilePath) {
// 		$contentsFileInputStream = IoUtils::createSafeFileInputStream($baseFilePath . ViewCacheStore::VIEW_CONTENTS_FILE_SUFFIX);
// 		$this->contents = $contentsFileInputStream->read();
		
// 		if (is_file($baseFilePath . ViewCacheStore::VIEW_ATTRIBUTES_FILE_SUFFIX)) {
// 			$this->attributesObject = @unserialize(IoUtils::getContentsSafe($baseFilePath . ViewCacheStore::VIEW_ATTRIBUTES_FILE_SUFFIX));
// 		}
		
// 		$contentsFileInputStream->close();
		
// 		if (!mb_strlen($this->contents) || $this->attributesObject === false) {
// 			throw new OutdatedViewCacheException('File "' . $baseFilePath . '"');
// 		} 
// 	}
// 	/**
// 	 * 
// 	 * @return boolean
// 	 */
// 	public function isEmpty() {
// 		return $this->empty;
// 	}
// 	/**
// 	 * 
// 	 * @return string
// 	 */
// 	public function readContents() {
// 		return $this->contents;
// 	}
// 	/**
// 	 * 
// 	 * @return mixed
// 	 */
// 	public function readAttributesObject() {
// 		return $this->attributesObject;
// 	}
// }

// class ViewCacheWriterIsClosedException extends UiException {
	
// }

// class OutdatedViewCacheException extends \Exception {
	
// }
