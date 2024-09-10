<?php

namespace n2n\web\ext;

use PHPUnit\Framework\TestCase;
use n2n\core\N2N;
use n2n\core\config\build\CombinedConfigSource;
use n2n\config\source\impl\IniFileConfigSource;
use n2n\core\config\build\AppConfigFactory;
use n2n\util\io\fs\FsPath;
use n2n\core\module\ModuleManager;
use n2n\core\VarStore;
use n2n\core\container\impl\PhpVars;
use n2n\context\LookupManager;
use n2n\context\config\SimpleLookupSession;
use n2n\cache\impl\ephemeral\EphemeralCacheStore;
use n2n\core\TypeNotFoundException;
use n2n\core\N2nApplication;
use n2n\core\cache\impl\N2nCaches;

class WebN2nExtensionTest extends TestCase {

	private function createN2nApplication(): N2nApplication {
		$iniFsPath = (new FsPath(__DIR__))->ext(['mock', 'ini', 'routing.app.ini']);
		$source = new CombinedConfigSource(new IniFileConfigSource($iniFsPath));

		$publicFsPath = new FsPath('public');
		$appConfigFactory = new AppConfigFactory($publicFsPath);
		$appConfig = $appConfigFactory->create($source, N2N::STAGE_LIVE);

		return new N2nApplication(new VarStore('var', null, null), new ModuleManager(),
				N2nCaches::null(), $appConfig, $publicFsPath);
	}

	function testRouting() {
		$extension = new WebN2nExtension($this->createN2nApplication());

		$serverVars = ['REQUEST_URI' => '/holeradio', 'QUERY_STRING' => '', 'SCRIPT_NAME' => 'index.php',
				'SERVER_NAME' => 'holeradio', 'REQUEST_METHOD' => 'GET'];
		$getVars = [];
		$postVars = [];
		$filesVars = [];
		$extension->applyToN2nContext($appN2nContext = $this->createN2nApplication()
				->createN2nContext(phpVars: new PhpVars($serverVars, $getVars, $postVars, $filesVars)));

		$appN2nContext->setLookupManager(new LookupManager(new SimpleLookupSession(), new EphemeralCacheStore(), $appN2nContext));

		$this->expectException(TypeNotFoundException::class);
		$appN2nContext->getHttp()->invokerControllers(false);
	}

}