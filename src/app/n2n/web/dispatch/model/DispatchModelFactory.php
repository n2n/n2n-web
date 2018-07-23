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
namespace n2n\web\dispatch\model;

use n2n\reflection\ReflectionUtils;
use n2n\reflection\property\PropertiesAnalyzer;
use n2n\reflection\ReflectionContext;
use n2n\reflection\ReflectionException;
use n2n\reflection\annotation\Annotation;
use n2n\reflection\property\AccessProxy;
use n2n\web\dispatch\DispatchErrorException;
use n2n\reflection\property\InvalidPropertyAccessMethodException;
use n2n\impl\web\dispatch\property\ScalarProperty;

class DispatchModelFactory {
	private $managedPropertyProviderClassNames;
	private $managedPropertyProviders = null;
	
	public function __construct(array $managedPropertyProviderClassNames) {
		$this->managedPropertyProviderClassNames = $managedPropertyProviderClassNames;
	}
	/**
	 * @throws ModelInitializationException
	 * @return \n2n\web\dispatch\property\ManagedPropertyProvider[]
	 */
	private function getManagedPropertyProviders() {
		if ($this->managedPropertyProviders !== null) {
			return $this->managedPropertyProviders;
		}
		
		$this->managedPropertyProviders = array();
		foreach ($this->managedPropertyProviderClassNames as $factoryClassName) {
			$factoryClass = ReflectionUtils::createReflectionClass($factoryClassName);
			if (!$factoryClass->isSubclassOf('n2n\web\dispatch\property\ManagedPropertyProvider')) {
				throw new ModelInitializationException('ManagedPropertyProvider must implement ' 
						. 'interface n2n\web\dispatch\property\ManagedPropertyProvider: ' 
						. $factoryClass->getName());
			}	

			$this->managedPropertyProviders[] = $factoryClass->newInstance();
		}
		
		return $this->managedPropertyProviders;
	}
	/**
	 * @param \ReflectionClass $class
	 * @return \n2n\web\dispatch\model\DispatchModel
	 */
	public function create(\ReflectionClass $class) { 
		$dispatchModel = new DispatchModel($class);
		
		do {
			$this->assignAnnoDispProperties($class, $dispatchModel);
		} while (false !== ($class = $class->getParentClass()));
		
		return $dispatchModel;
	}
	/**
	 * @param \ReflectionClass $class
	 * @param DispatchModel $dispatchModel
	 */
	private function assignAnnoDispProperties(\ReflectionClass $class, DispatchModel $dispatchModel) {
		$annotationSet = ReflectionContext::getAnnotationSet($class);
		$propertiesAnalyzer = new PropertiesAnalyzer($class);
		
		$setupProcess = new SetupProcess($dispatchModel, $propertiesAnalyzer, $annotationSet);
		foreach ($this->getManagedPropertyProviders() as $propertyProvider) {
			$propertyProvider->setupModel($setupProcess);
		}
		
		$propertyAccessProxies = null;
		if (null !== ($annoDispProperties = $annotationSet
				->getClassAnnotation('n2n\web\dispatch\annotation\AnnoDispProperties'))) {
			foreach ($annoDispProperties->getNames() as $name) {
				try {
					$propertyAccessProxies[$name] = $propertiesAnalyzer->analyzeProperty($name);
				} catch (InvalidPropertyAccessMethodException $e) {
					throw new DispatchErrorException('Invalid property access method for property: ' 
									. $class->getName() . '::$' . $name, 
							$e->getMethod()->getFileName(), $e->getMethod()->getStartLine(), null, null, $e);
				} catch (ReflectionException $e) {
					throw $this->createDispatchErrorException($e, $annoDispProperties);
				}
			}
		} else {
			$propertyAccessProxies = $propertiesAnalyzer->analyzeProperties();
		}
		
		foreach ($propertyAccessProxies as $propertyName => $propertyAccessProxy) {
			if ($dispatchModel->containsPropertyName($propertyName)) {
				continue;
			}
			
			$this->assignManagedProperty($propertyAccessProxy, $setupProcess);	
		}
	}
	
	private function assignManagedProperty(AccessProxy $propertyAccessProxy, 
			SetupProcess $setupProcess) {
		$propertyName = $propertyAccessProxy->getPropertyName();
		$dispatchModel = $setupProcess->getDispatchModel();
		
		foreach ($this->getManagedPropertyProviders() as $propertyProvider) {
			$propertyProvider->setupPropertyIfSuitable($propertyAccessProxy, $setupProcess);
			
			if ($dispatchModel->containsPropertyName($propertyName)) {
				return;
			}
		}
			
		if (!$dispatchModel->containsPropertyName($propertyName)) {
			$this->assignDefaultManagedProperty($propertyAccessProxy, $setupProcess);
		}
	}
	
	private function assignDefaultManagedProperty(AccessProxy $propertyAccessProxy, 
			SetupProcess $setupProcess) {
		$arrayLike = false;
		if (null !== ($constraint = $propertyAccessProxy->getConstraint())) {
			$arrayLike = $constraint->isArrayLike();
		}
		$setupProcess->provideManagedProperty(new ScalarProperty($propertyAccessProxy, $arrayLike));
	}
	
	private function createDispatchErrorException(ReflectionException $e, Annotation $anno) {
		return new DispatchErrorException('Invalid use of annotation ' . get_class($anno), $anno->getFileName(), $anno->getLine(), null, null, $e);
	}
}
