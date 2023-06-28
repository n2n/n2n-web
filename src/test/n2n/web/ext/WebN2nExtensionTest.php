<?php

namespace n2n\web\ext;

use PHPUnit\Framework\TestCase;
use n2n\core\N2N;
use n2n\core\config\AppConfig;
use n2n\util\type\ArgUtils;
use n2n\core\config\build\CombinedConfigSource;
use n2n\config\source\impl\IniFileConfigSource;
use n2n\core\config\build\AppConfigFactory;
use n2n\util\io\fs\FsPath;
use n2n\core\cache\impl\NullAppCache;
use n2n\core\container\impl\AppN2nContext;
use n2n\core\container\TransactionManager;
use n2n\core\module\ModuleManager;
use n2n\core\VarStore;
use n2n\core\container\impl\PhpVars;
use n2n\context\LookupManager;
use n2n\context\config\SimpleLookupSession;
use n2n\util\cache\impl\EphemeralCacheStore;
use n2n\core\TypeNotFoundException;

class WebN2nExtensionTest extends TestCase {

	private function createAppConfig(): AppConfig {
		$iniFsPath = (new FsPath(__DIR__))->ext(['mock', 'ini', 'routing.app.ini']);
		$source = new CombinedConfigSource(new IniFileConfigSource($iniFsPath));

		$appConfigFactory = new AppConfigFactory(new FsPath('public'));
		return $appConfigFactory->create($source, N2N::STAGE_LIVE);
	}

	function testRouting() {
		$extension = new WebN2nExtension($appConfig = $this->createAppConfig(), new NullAppCache());


		$serverVars = ['REQUEST_URI' => '/holeradio', 'QUERY_STRING' => '', 'SCRIPT_NAME' => 'index.php',
				'SERVER_NAME' => 'holeradio', 'REQUEST_METHOD' => 'GET'];
		$getVars = [];
		$postVars = [];
		$filesVars = [];
		$extension->setUp($appN2nContext = new AppN2nContext(new TransactionManager(), new ModuleManager(), new NullAppCache(),
				new VarStore('var', null, null), $appConfig,
				new PhpVars($serverVars, $getVars, $postVars, $filesVars)));

		$appN2nContext->setLookupManager(new LookupManager(new SimpleLookupSession(), new EphemeralCacheStore(), $appN2nContext));


		$this->expectException(TypeNotFoundException::class);
		$appN2nContext->getHttp()->invokerControllers(false);

	}

}