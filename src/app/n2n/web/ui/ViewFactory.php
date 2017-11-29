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
namespace n2n\web\ui;

use n2n\web\ui\view\View;
use n2n\core\N2N;
use n2n\core\TypeLoader;
use n2n\core\module\Module;
use n2n\reflection\ReflectionUtils;
use n2n\web\ui\view\ViewCacheControl;
use n2n\web\ui\view\ViewStateListener;
use n2n\core\container\N2nContext;
use n2n\core\module\impl\LazyModule;
use n2n\context\ThreadScoped;
use n2n\core\config\WebConfig;
use n2n\web\ui\view\ViewCacheStore;

class ViewFactory implements ThreadScoped {
	const SCRIPT_NAME_TYPE_SEPARATOR = '.';
	
	private $n2nContext;
	private $viewClassNames;
	private $viewClasses = array();
	private $viewCacheStore;
	private $viewCachingEnabled = true;
	private static $creationListeners = array();
	
	private function _init(N2nContext $n2nContext, WebConfig $webConfig, ViewCacheStore $viewCacheStore) {
		$this->n2nContext = $n2nContext;
		$this->viewClassNames = $webConfig->getViewClassNames();
		$this->viewCacheStore = $viewCacheStore;
		$this->viewCachingEnabled = $webConfig->isViewCachingEnabled();
	}
	
	public function isViewCachingEnabled(): bool {
		return $this->viewCachingEnabled;
	}
	
	public function setViewCachingEnabled(bool $viewCachingEnabled) {
		$this->viewCachingEnabled = $viewCachingEnabled;
	}
	
	public function createFromCache($viewName, ViewCacheControl $viewCacheControl, Module $module = null) {
		if (!$this->viewCachingEnabled) return null;
		
		$cacheItem = $this->viewCacheStore->get($viewName, $viewCacheControl->getCharacteristics());
		if ($cacheItem === null) return null;
			
		$view = $this->create($viewName, null, $module);
		try {
			$view->initializeFromCache($cacheItem->getData());
		} catch (\InvalidArgumentException $e) {
			$this->viewCacheStore->remove($cacheItem->getName(), $cacheItem->getCharacteristics());
			return null;
		} 
		return $view;
	}
	
// 	public function createFromViewNameExpression($viewNameExpression, Module $module, $props) {
// 		if (!mb_strlen(trim($viewNameExpression, '\\'))) {
// 			throw new \InvalidArgumentException('Invalid_view_name_expression: ' . $viewNameExpression);
// 		}
		
// 		if (StringUtils::startsWith('\\', $viewNameExpression)) {
// 			$viewName = $viewNameExpression;
// 			$module = N2N::getModuleOfTypeName($viewName);
// 		} else {
// 			$viewName = $module->getNamespace() . '\\' . $viewNameExpression;
// 		}

// 		return self::create($viewName, $module, $props);
// 	}
	/**
	 * 
	 * @param string $viewName
	 * @param mixed $params
	 * @param Module $module
	 * @return \n2n\web\ui\view\View
	 */
	public function create(string $viewName, array $params = null, Module $module = null) {
		return $this->createView(TypeLoader::getFilePathOfType($viewName, TypeLoader::SCRIPT_FILE_EXTENSION), 
				$viewName, $params, $module);
	}
// 	/**
// 	 * 
// 	 * @param unknown_type $scriptPath
// 	 * @param Module $module
// 	 * @param unknown_type $props
// 	 * @throws ViewNotFoundException
// 	 * @throws ViewErrorException
// 	 * @return View
// 	 */
// 	public function createFromScript($scriptPath, $params, Module $module = null) {
// 		if (!is_file($scriptPath)) {
// 			throw new ViewNotFoundException(SysTextUtils::get('n2n_error_view_not_found', array('scriptPath' => $scriptPath)));
// 		} else if (!is_readable($scriptPath)) {
// 			throw new ViewErrorException(SysTextUtils::get('n2n_error_view_cant_access_script', 
// 					array('scriptPath' => $scriptPath)), 0, E_USER_ERROR, scriptPath);
// 		}
		
// 		return $this->createView($scriptPath, TypeLoader::pathToTypeName($scriptPath), $module, $props);
// 	}
	/**
	 * 
	 * @param string $scriptPath
	 * @param array $params
	 * @param Module $module
	 * @throws InvalidViewNameException
	 * @return View
	 */
	private function createView($scriptPath, $viewName, array $params = null, Module $module = null) {
		$fileNameParts = explode(self::SCRIPT_NAME_TYPE_SEPARATOR, basename($scriptPath, TypeLoader::SCRIPT_FILE_EXTENSION));
		if (2 != sizeof($fileNameParts) || !mb_strlen($fileNameParts[0]) || !mb_strlen($fileNameParts[1])) {
			 throw new \InvalidArgumentException('Invalid script name: ' . $scriptPath . ' Pattern: [viewName]' 
			         . self::SCRIPT_NAME_TYPE_SEPARATOR . '[viewType]' . TypeLoader::SCRIPT_FILE_EXTENSION);
		}
		
		if ($module === null) {
			$module = $this->n2nContext->getModuleManager()->getModuleOfTypeName($viewName, false);
			if ($module === null) $module = new LazyModule(TypeLoader::namespaceOfTypeName($viewName));
		}
		
		
		$view = $this->getViewClassOfType($fileNameParts[1])
				->newInstance($scriptPath, $viewName, $module, $this->n2nContext);
		if ($params !== null) {
			$view->setParams($params);
		}
		foreach (self::$creationListeners as $listener) {
			$listener($view);
		}
		
		return $view;
	}
	
	public function cache(View $view, ViewCacheControl $viewCacheControl) {
		if (!$this->viewCachingEnabled) return;
		
		$listener = new CacheViewStateListener($this->viewCacheStore, $viewCacheControl);
		if ($view->isInitialized()) {
			$listener->viewContentsInitialized($view);
		}
		$view->registerStateListener($listener);
	}
	/**
	 * 
	 * @param string $type
	 * @throws ViewTypeNotAvailableException
	 * @return \ReflectionClass
	 */
	private function getViewClassOfType($type) {
		if (isset($this->viewClasses[$type])) {
			return $this->viewClasses[$type];
		}
		
		if (!isset($this->viewClassNames[$type])) {
			throw new ViewStuffFailedException('No view class defined for type: ' . $type);
		}
		
		$viewClass = ReflectionUtils::createReflectionClass($this->viewClassNames[$type]);
		if (!$viewClass->isSubclassOf('n2n\web\ui\view\View')) {
			throw new ViewStuffFailedException(
					'View class must extend n2n\web\ui\view\View: ' . $viewClass->getName());
		} 
		
		return $this->viewClasses[$type] = $viewClass;
	}
// 	/**
// 	 * 
// 	 * @return ViewCacheStore
// 	 */
// 	public static function getCacheStore() {
// 		if (self::$cacheManager) {
// 			self::$cacheManager = new ViewCacheStore(
// 					N2N::getVarStore()->requestDirFsPath(VarStore::CATEGORY_TMP, N2N::NS, self::VIEW_CACHE_DIR), 
// 					N2N::getAppConfig()->web()->isViewCachingEnabled(), N2N::getCurrentRequest());
// 		}
		
// 		return self::$cacheManager;
// 	}
	
// 	public static function registerCreationListener(\Closure $listener) {
// 		self::$creationListeners[] = $listener;
// 	}
}

class CacheViewStateListener implements ViewStateListener {
	private $cacheStore;
	private $viewCacheControl;
	
	public function __construct(ViewCacheStore $cacheStore, ViewCacheControl $viewCacheControl) {
		$this->cacheStore = $cacheStore;
		$this->viewCacheControl = $viewCacheControl;
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\ui\view\ViewStateListener::onViewContentsBuffering()
	 */
	public function onViewContentsBuffering(View $view) {
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\ui\view\ViewStateListener::viewContentsInitialized()
	 */
	public function viewContentsInitialized(View $view) {
		$this->cacheStore->store($view->getName(), $this->viewCacheControl->getCharacteristics(), 
				$view->toCacheData());
	}

	/* (non-PHPdoc)
	 * @see \n2n\web\ui\view\ViewStateListener::onPanelImport()
	 */
	public function onPanelImport(\n2n\web\ui\view\View $view, $panelName) {
	}

	
}

class ViewNotFoundException extends UiException {
	
}

class InvalidViewNameException extends UiException {
	
}

class ViewTypeNotAvailableException extends UiException {
	
}
