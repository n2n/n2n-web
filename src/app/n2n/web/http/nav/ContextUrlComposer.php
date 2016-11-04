<?php
namespace n2n\web\http\nav;

use n2n\core\container\N2nContext;
use n2n\web\http\controller\ControllerContext;
use n2n\util\uri\Url;
use n2n\util\uri\Path;
use n2n\web\http\HttpContextNotAvailableException;

class ContextUrlComposer implements UrlComposer {
	private $toController;
	private $controllerContext;
	private $pathExts = array();
	private $queryExt;
	private $fragment;
	private $ssl;
	private $subsystem;

	public function __construct($toController) {
		$this->toController = (boolean) $toController;
	}

	/**
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public function context() {
		$this->toController = false;
		$this->controllerContext = null;
		return $this;
	}

	/**
	 * @param mixed $controllerContext
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public function controller($controllerContext = null) {
		$this->toController = true;
		$this->controllerContext = $controllerContext;
		return $this;
	}

	/**
	 * @param mixed ..$pathExts
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public function pathExt(...$pathPartExts) {
		$this->pathExts[] = $pathPartExts;
		return $this;
	}
	
	/**
	 * @param mixed ...$pathExts
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public function pathExtEnc(...$pathExts) {
		$this->pathExts = array_merge($this->pathExts, $pathExts);
		return $this;
	}

	/**
	 * @param mixed $query
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public function queryExt($queryExt) {
		$this->queryExt = $queryExt;
		return $this;
	}

	/**
	 * @param string $fragment
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public function fragment(string $fragment = null) {
		$this->fragment = $fragment;
		return $this;
	}

	/**
	 * @param bool $ssl
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public function ssl(bool $ssl = null) {
		$this->ssl = $ssl;
		return $this;
	}

	/**
	 * @param mixed $subsystem
	 * @return \n2n\web\http\nav\ContextUrlComposer
	 */
	public function subsystem($subsystem) {
		$this->subsystem = $subsystem;
		return $this;
	}
	/**
	 * @param View $view
	 * @return Url
	 */
	public function toUrl(N2nContext $n2nContext, ControllerContext $controllerContext = null, 
			string &$suggestedLabel = null): Url {
		$path = null;
		if ($this->controllerContext === null) {
			if ($this->toController) {
				if ($controllerContext === null) {
					throw new UnavailableMurlException(true, 'No ControllerContext known.');
				}
				$path = $controllerContext->getCmdContextPath();
			} else {
				$path = new Path(array());
			}
		} else if ($this->controllerContext instanceof ControllerContext) {
			$path = $this->controllerContext->getCmdContextPath();
		} else {
			$path = $controllerContext->getControllingPlan()->getByName($this->controllerContext)
					->getCmdContextPath();
		}

		try {
			return $n2nContext->getHttpContext()->buildContextUrl($this->ssl, $this->subsystem)
					->extR($path->ext($this->pathExts), $this->queryExt, $this->fragment);
		} catch (HttpContextNotAvailableException $e) {
			throw new UnavailableMurlException(null, null, $e);
		}
	}
}