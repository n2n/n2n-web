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

use n2n\reflection\property\PropertiesAnalyzer;
use n2n\reflection\annotation\Annotation;
use n2n\web\dispatch\property\ManagedProperty;
use n2n\web\dispatch\DispatchErrorException;
use n2n\reflection\annotation\AnnotationSet;

class SetupProcess {
	private $dispatchModel;
	private $propertiesAnalyzer;
	private $annotationSet;
	
	public function __construct(DispatchModel $dispatchModel, PropertiesAnalyzer $propertiesAnalyzer, 
			AnnotationSet $annotationSet) {
		$this->dispatchModel = $dispatchModel;
		$this->propertiesAnalyzer = $propertiesAnalyzer;
		$this->annotationSet = $annotationSet;
	}
	/**
	 * @return DispatchModel
	 */
	public function getDispatchModel() {
		return $this->dispatchModel;
	}
	/**
	 * @return PropertiesAnalyzer
	 */
	public function getPropertiesAnalyzer() {
		return $this->propertiesAnalyzer;
	}
	/**
	 * @return AnnotationSet
	 */
	public function getAnnotationSet() {
		return $this->annotationSet;
	}
	/**
	 * @param ManagedProperty $managedProperty
	 * @param Annotation $relatedAnnotation
	 * @throws ModelInitializationException
	 */
	public function provideManagedProperty(ManagedProperty $managedProperty, 
			Annotation $relatedAnnotation = null) {
		if (!$this->dispatchModel->containsPropertyName($managedProperty->getName())) {
			$this->dispatchModel->addProperty($managedProperty);
			return;
		}
		
		throw $this->createFailedException('Managed property for ' . $managedProperty->getName() 
				. ' already defined.', null, null, $relatedAnnotation);
	}
	
	public function failed($reason, \ReflectionMethod $method = null, 
			Annotation $causingAnnotation = null) {
		throw $this->createFailedException($reason, null, $method, $causingAnnotation);
	}
	
	public function failedE(\Exception $e, \ReflectionMethod $causingMethod = null, 
			Annotation $causingAnnotation = null) {
		if ($e instanceof \ErrorException) throw $e;
		throw $this->createFailedException($e->getMessage(), $e, $causingMethod, $causingAnnotation);
	}
	
	private function createFailedException($reason, \Exception $causingE = null, 
			\ReflectionMethod $causingMethod = null, Annotation $causingAnnotation = null) {
		$tps = array();
		if ($causingMethod !== null) {
			$tps[] = array('fileName' => $causingMethod->getFileName(),
					'line' => $causingMethod->getStartLine());
		}
		if ($causingAnnotation !== null) {
			$tps[] = array('fileName' => $causingAnnotation->getFileName(), 
					'line' => $causingAnnotation->getLine()); 
		}

		$message = 'Initialization of ' . $this->dispatchModel->getClass()->getName() 
				. ' failed. Reason: ' . $reason;
		if (0 == count($tps)) {
			throw new ModelInitializationException($message, null, $causingE);
		}
		
		$tp = array_shift($tps);
		$e = new DispatchErrorException($message, $tp['fileName'], $tp['line'], null, null, $causingE);
		foreach ($tps as $tp) {
			$e->addAdditionalError($tp['fileName'], $tp['line']);
		}
		throw $e;
	}
}
