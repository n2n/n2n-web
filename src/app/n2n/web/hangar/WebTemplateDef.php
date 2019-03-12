<?php
namespace n2n\web\hangar;

use hangar\api\HangarTemplateDef;
use phpbob\representation\PhpClass;
use phpbob\representation\PhpTypeDef;
use n2n\reflection\ObjectAdapter;
use n2n\web\dispatch\mag\MagDispatchable;
use n2n\impl\web\dispatch\mag\model\MagForm;
use n2n\web\dispatch\mag\MagCollection;
use n2n\impl\web\dispatch\mag\model\BoolMag;
use n2n\web\http\orm\ResponseCacheClearer;
use n2n\persistence\orm\annotation\AnnoEntityListeners;
use hangar\api\Huo;

class WebTemplateDef implements HangarTemplateDef {
	const PROP_NAME_APPLY_OBJECT_ADAPTER = 'applyObjectAdapter';
	const PROP_NAME_ADD_RESPONSE_CACHE_CLEARER = 'addResponseCacheClearer';
	const PROP_NAME_ABSTRACT = 'abstract';
	
	public function getName(): string {
		return 'Common';
	}
	
	public function applyTemplate(Huo $huo, PhpClass $phpClass, MagDispatchable $magDispatchable = null) {
		$phpClass->createPhpProperty('id');
		$phpClass->createPhpGetterAndSetter('id', new PhpTypeDef('int'));
		
		if ($magDispatchable->getPropertyValue(self::PROP_NAME_APPLY_OBJECT_ADAPTER)) {
			$phpClass->setSuperClassTypeDef(PhpTypeDef::fromTypeName(ObjectAdapter::class));
		}
		
		self::applyResponseCacheClearerValue($phpClass, $magDispatchable);
		self::applyAbstractValue($phpClass, $magDispatchable);
	}
	
	public function createMagDispatchable(): ?MagDispatchable {
		$magCollection = new MagCollection();
		
		$magCollection->addMag(self::PROP_NAME_APPLY_OBJECT_ADAPTER, 
				new BoolMag('Apply object adapter', true));
		self::addResponseCacheClearerMag($magCollection);
		self::addAbstractMag($magCollection);
		
		return new MagForm($magCollection);
	}
	
	public static function addAbstractMag(MagCollection $magCollection) {
		return $magCollection->addMag(self::PROP_NAME_ABSTRACT,
				new BoolMag('Abstract', false));
	}
	
	public static function applyAbstractValue(PhpClass $phpClass, MagDispatchable $magDispatchable = null) {
		if (null === $magDispatchable || !$magDispatchable->getPropertyValue(self::PROP_NAME_ABSTRACT)) return;
		
		$phpClass->setAbstract(true);
	}
	
	public static function addResponseCacheClearerMag(MagCollection $magCollection) {
		return $magCollection->addMag(self::PROP_NAME_ADD_RESPONSE_CACHE_CLEARER,
				new BoolMag('Add response cache clearer', true));
	}
	
	public static function applyResponseCacheClearerValue(PhpClass $phpClass, MagDispatchable $magDispatchable = null) {
		if (null === $magDispatchable || !$magDispatchable->getPropertyValue(self::PROP_NAME_ADD_RESPONSE_CACHE_CLEARER)) return;
		
		$phpClass->createPhpUse(ResponseCacheClearer::class);
		$phpClass->getPhpAnnotationSet()->getOrCreatePhpClassAnnoCollection()
				->createPhpAnno(AnnoEntityListeners::class, 'AnnoEntityListeners')
				->createPhpAnnoParam('ResponseCacheClearer::getClass()');
	}
}