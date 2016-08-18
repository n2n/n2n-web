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
namespace n2n\dispatch\property\impl;

use n2n\dispatch\property\ManagedPropertyProvider;
use n2n\dispatch\model\SetupProcess;
use n2n\reflection\property\PropertiesAnalyzer;
use n2n\reflection\property\InvalidPropertyAccessMethodException;
use n2n\reflection\ReflectionException;
use n2n\reflection\property\AccessProxy;
use n2n\reflection\property\TypeConstraint;
use n2n\reflection\property\ConstraintsConflictException;
use n2n\dispatch\annotation\AnnoDispDateTime;
use n2n\dispatch\annotation\AnnoDispDateTimeArray;
use n2n\dispatch\annotation\AnnoIcuFormat;

class CommonManagedPropertyProvider implements ManagedPropertyProvider {
	
	public function __construct() {
	}
	
	public function setupModel(SetupProcess $setupProcess) {
		$annotation = null;
		try {
			$this->provideScalarProperties($setupProcess, $annotation);
			$this->provideDateTimeProperties($setupProcess, $annotation);
			$this->provideFileProperties($setupProcess, $annotation);
			$this->provideObjectProperties($setupProcess, $annotation);
		} catch (InvalidPropertyAccessMethodException $e) {
 			$setupProcess->failedE($e, $e->getMethod(), $annotation);
		} catch (ConstraintsConflictException $e) {
			$setupProcess->failedE($e, $e->getCausingMethod(), $annotation);
		} catch (ReflectionException $e) {
			$setupProcess->failedE($e, null, $annotation);
		}
	}

	private function provideScalarProperties(SetupProcess $setupProcess, &$annotation) {
		$propertyAnalyzer = $setupProcess->getPropertiesAnalyzer();
		$annotationSet = $setupProcess->getAnnotationSet();

		$annotations = $annotationSet->getPropertyAnnotationsByName(
				'n2n\dispatch\annotation\AnnoDispScalar');
		foreach ($annotations as $propertyName => $annotation) {
			$setupProcess->provideManagedProperty(new ScalarProperty(
							$propertyAnalyzer->analyzeProperty($propertyName), false), 
					$annotation);
		}
		
		$annotations = $annotationSet->getMethodAnnotationsByName(
				'n2n\dispatch\annotation\AnnoDispScalar');
		foreach ($annotations as $methodName => $annotation) {			
			$setupProcess->provideManagedProperty(new ScalarProperty(
							$propertyAnalyzer->analyzeProperty(
									PropertiesAnalyzer::parsePropertyName($methodName)), true),
					$annotation);
		}
		
		$annotations = $annotationSet->getPropertyAnnotationsByName(
				'n2n\dispatch\annotation\AnnoDispScalarArray');
		foreach ($annotations as $propertyName => $annotation) {
			$setupProcess->provideManagedProperty(new ScalarProperty(
							$propertyAnalyzer->analyzeProperty($propertyName), true), 
					$annotation);
		}
		
		$annotations = $annotationSet->getMethodAnnotationsByName(
				'n2n\dispatch\annotation\AnnoDispScalarArray');
		foreach ($annotations as $methodName => $annotation) {
			$setupProcess->provideManagedProperty(new ScalarProperty(
							$propertyAnalyzer->analyzeProperty(
									PropertiesAnalyzer::parsePropertyName($methodName)), 
							true),
					$annotation);
		}
	}

	private function provideDateTimeProperties(SetupProcess $setupProcess, &$annotation) {
		$propertyAnalyzer = $setupProcess->getPropertiesAnalyzer();
		$annotationSet = $setupProcess->getAnnotationSet();

		$annotations = $annotationSet->getPropertyAnnotationsByName(
				'n2n\dispatch\annotation\AnnoDispDateTime');
		foreach ($annotations as $propertyName => $annotation) {
			$setupProcess->provideManagedProperty(self::createDateTimeProperty(
							$propertyAnalyzer->analyzeProperty($propertyName),
							$annotation, $annotationSet->getPropertyAnnotation($propertyName, 
									'n2n\dispatch\annotation\AnnoIcuFormat')), 
					$annotation);
		}
		
		$annotations = $annotationSet->getMethodAnnotationsByName(
				'n2n\dispatch\annotation\AnnoDispDateTime');
		foreach ($annotations as $methodName => $annotation) {			
			$setupProcess->provideManagedProperty(self::createDateTimeProperty(
							$propertyAnalyzer->analyzeProperty(
									PropertiesAnalyzer::parsePropertyName($methodName)),
							$annotation, $annotationSet->getPropertyAnnotation($propertyName, 
									'n2n\dispatch\annotation\AnnoIcuFormat')), 
					$annotation);
		}
		
		$annotations = $annotationSet->getMethodAnnotationsByName(
				'n2n\dispatch\annotation\AnnoDispDateTimeArray');
		foreach ($annotations as $propertyName => $annotation) {
			$setupProcess->provideManagedProperty(self::createDateTimeProperty(
							$propertyAnalyzer->analyzeProperty($propertyName),
							$annotation, $annotationSet->getPropertyAnnotation($propertyName, 
									'n2n\dispatch\annotation\AnnoIcuFormat')), 
					$annotation);
		}
		
		$annotations = $annotationSet->getMethodAnnotationsByName(
				'n2n\dispatch\annotation\AnnoDispDateTimeArray');
		foreach ($annotations as $methodName => $annotation) {
			$setupProcess->provideManagedProperty(self::createDateTimeProperty(
							$propertyAnalyzer->analyzeProperty(
									PropertiesAnalyzer::parsePropertyName($methodName)),
							$annotation, $annotationSet->getMethodAnnotation($methodName, 
									'n2n\dispatch\annotation\AnnoIcuFormat')), 
					$annotation);
		}
	}
	
	private function provideFileProperties(SetupProcess $setupProcess, &$annotation) {
		$propertyAnalyzer = $setupProcess->getPropertiesAnalyzer();
		$annotationSet = $setupProcess->getAnnotationSet();
		
		$annotations = $annotationSet->getPropertyAnnotationsByName(
				'n2n\dispatch\annotation\AnnoDispFile');
		foreach ($annotations as $propertyName => $annotation) {
			$setupProcess->provideManagedProperty(new FileProperty(
							$propertyAnalyzer->analyzeProperty($propertyName), false), 
					$annotation);
		}
		
		$annotations = $annotationSet->getMethodAnnotationsByName(
				'n2n\dispatch\annotation\AnnoDispFile');
		foreach ($annotations as $methodName => $annotation) {			
			$setupProcess->provideManagedProperty(new FileProperty(
							$propertyAnalyzer->analyzeProperty(
									PropertiesAnalyzer::parsePropertyName($methodName)),
							false),
					$annotation);
		}
		
		$annotations = $annotationSet->getMethodAnnotationsByName(
				'n2n\dispatch\annotation\AnnoDispFileArray');
		foreach ($annotations as $propertyName => $annotation) {
			$setupProcess->provideManagedProperty(new FileProperty(
							$propertyAnalyzer->analyzeProperty($propertyName), 
							true, $annotation->useArrayObject()),
					$annotation);
		}
		
		$annotations = $annotationSet->getMethodAnnotationsByName(
				'n2n\dispatch\annotation\AnnoDispFileArray');
		foreach ($annotations as $methodName => $annotation) {
			$setupProcess->provideManagedProperty(new FileProperty(
							$propertyAnalyzer->analyzeProperty(
									PropertiesAnalyzer::parsePropertyName($methodName)), 
							true, $annotation->useArrayObject()),
					$annotation);
		}
	}
	
	private function provideObjectProperties(SetupProcess $setupProcess, &$annotation) {
		$propertyAnalyzer = $setupProcess->getPropertiesAnalyzer();
		$annotationSet = $setupProcess->getAnnotationSet();
		
		$annotations = $annotationSet->getPropertyAnnotationsByName(
				'n2n\dispatch\annotation\AnnoDispObject');
		foreach ($annotations as $propertyName => $annotation) {
			$objectProperty = new ObjectProperty($propertyAnalyzer->analyzeProperty($propertyName), false);
			$objectProperty->setCreator($annotation->getCreator());
			$setupProcess->provideManagedProperty($objectProperty, $annotation);
		}
		
		$annotations = $annotationSet->getMethodAnnotationsByName(
				'n2n\dispatch\annotation\AnnoDispObject');
		foreach ($annotations as $methodName => $annotation) {			
			$objectProperty = new ObjectProperty($propertyAnalyzer->analyzeProperty(
					PropertiesAnalyzer::parsePropertyName($methodName)), false);
			$objectProperty->setCreator($annotation->getCreator());
			$setupProcess->provideManagedProperty($objectProperty, $annotation);
		}
		
		$annotations = $annotationSet->getPropertyAnnotationsByName(
				'n2n\dispatch\annotation\AnnoDispObjectArray');
		foreach ($annotations as $propertyName => $annotation) {
			$objectProperty = new ObjectProperty($propertyAnalyzer->analyzeProperty($propertyName), 
					true, $annotation->useArrayObject());
			$objectProperty->setCreator($annotation->getCreator());
			$setupProcess->provideManagedProperty($objectProperty, $annotation);
		}
		
		$annotations = $annotationSet->getMethodAnnotationsByName(
				'n2n\dispatch\annotation\AnnoDispObjectArray');
		foreach ($annotations as $methodName => $annotation) {
			$objectProperty = new ObjectProperty(
					$propertyAnalyzer->analyzeProperty(
							PropertiesAnalyzer::parsePropertyName($methodName)),
					true, $annotation->useArrayObject());
			$objectProperty->setCreator($annotation->getCreator());
			$setupProcess->provideManagedProperty($objectProperty, $annotation);
		}
	}
	
	public function setupPropertyIfSuitable(AccessProxy $propertyAccessProxy, SetupProcess $setupProcess) {
		$constraint = $propertyAccessProxy->getConstraint();
		
		if (null === $constraint->getTypeName()) {
			return;
		}
		
		try {
			switch($constraint->getTypeName()) {
				case 'DateTime':
					$setupProcess->provideManagedProperty(
							new DateTimeProperty($propertyAccessProxy, false));
					return;
				case 'n2n\io\managed\File':
					$setupProcess->provideManagedProperty(
							new FileProperty($propertyAccessProxy, false));
					return;
				case 'array':
					$setupProcess->provideManagedProperty(new ScalarProperty($propertyAccessProxy,
							true, false));
					return;
				case 'ArrayObject':
					$setupProcess->provideManagedProperty(new ScalarProperty($propertyAccessProxy, 
							true, true));
					return;
				default:					
					if (is_subclass_of($constraint->getTypeName(), 'n2n\dispatch\Dispatchable')) {
						$setupProcess->provideManagedProperty(
								new ObjectProperty($propertyAccessProxy, false));
						return;
					}
			}
			
			$setupProcess->provideManagedProperty(new ScalarProperty($propertyAccessProxy, false));
		} catch (ConstraintsConflictException $e) {
			$setupProcess->failedE($e, $e->getCausingMethod());
		}
	}
	
	public static function restrictConstraints(AccessProxy $accessProxy, $type, $array, &$useArrayObject) {
		$allowsNull = true;
		if (null !== ($baseConstraints = $accessProxy->getConstraint())) {
			$allowsNull = $baseConstraints->allowsNull();
		}
		
		$typeConstraints = null;
		if (!$array) {
			$typeConstraints = TypeConstraint::createSimple($type, $allowsNull);
			$useArrayObject = null;
		} else {
			if ($useArrayObject === null) {
				$useArrayObject = $accessProxy->getConstraint()->getTypeName() == 'ArrayObject';
			}
			$typeConstraints = TypeConstraint::createArrayLike($useArrayObject ? 'ArrayObject' : 'array',
					$allowsNull, TypeConstraint::createSimple($type));
		}

		$accessProxy->setConstraint($typeConstraints);
		$accessProxy->setNullReturnAllowed(true);
	}
			
	private static function createDateTimeProperty(AccessProxy $accessProxy, 
			AnnoDispDateTime $dtAnno, AnnoIcuFormat $icufAnno = null) {
		$dateTimeProperty = new DateTimeProperty($accessProxy, false);
		if ($dtAnno !== null) {
			$dateTimeProperty->setDateStyle($dtAnno->getDateStyle());
			$dateTimeProperty->setTimeStyle($dtAnno->getTimeStyle());
		}
		if ($icufAnno !== null) {
			$dateTimeProperty->setIcuPattern($icufAnno->getPattern());
		}
		return $dateTimeProperty;
	}
	
	private static function createDateTimeArrayProperty(AccessProxy $accessProxy, 
			AnnoDispDateTimeArray $dtaAnno, AnnoIcuFormat $icufAnno = null) {
		
		$dateTimeProperty = null;
		if ($dtaAnno === null) {
			$dateTimeProperty = new DateTimeProperty($accessProxy, true);
		} else {
			$dateTimeProperty = new DateTimeProperty($accessProxy, true, $dtaAnno->useArrayObject());
			$dateTimeProperty->setDateStyle($dtAnno->getDateStyle());
			$dateTimeProperty->setTimeStyle($dtAnno->getTimeStyle());
		}
		if ($icufAnno !== null) {
			$dateTimeProperty->setIcuFormat($icufAnno->getPattern());
		}
		return $dateTimeProperty;
	}
}
