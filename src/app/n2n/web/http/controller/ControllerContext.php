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
namespace n2n\web\http\controller;

use n2n\util\uri\Path;
use n2n\util\ex\IllegalStateException;

class ControllerContext {
	private $cmdPath = array();
	private $cmdContextPath = array();
	private $controller;
	private $name;
	private $params = array();
	private $controllingPlan;
	private $moduleNamespace;
	/**
	 * @param array $cmds
	 * @param array $contextCmds
	 * @param Controller $controller
	 */
	public function __construct(Path $cmdPath, Path $cmdContextPath, Controller $controller = null) {
		$this->cmdPath = $cmdPath;
		$this->cmdContextPath = $cmdContextPath;
		if ($controller !== null) {
			$this->controller = $controller;
			$this->name = get_class($controller);
		}
	}
	
	public function setControllingPlan(ControllingPlan $controllingPlan) {
		$this->controllingPlan = $controllingPlan;
	}
	/**
	 * @throws IllegalStateException
	 * @return ControllingPlan
	 */
	public function getControllingPlan() {
		if ($this->controllingPlan === null) {
			throw new IllegalStateException('No ControllingPlan assigned to ControllerContext.');
		}
		
		return $this->controllingPlan;
	}
	
	public function setCmdPath(Path $cmdPath) {
		$this->cmdPath = $cmdPath;
	}
	/**
	 * @return Path
	 */
	public function getCmdPath() {
		return $this->cmdPath;
	}
	/**
	 * @param Path $cmdContextPath
	 */
	public function setCmdContextPath(Path $cmdContextPath) {
		$this->cmdContextPath = $cmdContextPath;
	}
	/**
	 * @return Path
	 */
	public function getCmdContextPath() {
		return $this->cmdContextPath;
	}
	
	public function hasController(): bool {
		return $this->controller !== null;
	}
	/**
	 * @param Controller $controller
	 */
	public function setController(Controller $controller) {
		$this->controller = $controller;
	}
	/**
	 * @return Controller
	 */
	public function getController(): Controller {
		if ($this->controller === null) {
			throw new IllegalStateException('No Controller assigned to ControllerContext');
		}
		return $this->controller;
	}
	/**
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}
	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
	/**
	 * @param array $params
	 */
	public function setParams(array $params) {
		$this->params = $params;
	}
	/**
	 * @return array
	 */
	public function getParams() {
		return $this->params;
	}
	/**
	 * @return \n2n\util\uri\Path
	 */
	public function toRootCmdPath() {
		return $this->cmdContextPath->ext($this->cmdPath);
	}
	
	public function setModuleNamespace(string $moduleNamespace = null) {
		$this->moduleNamespace = $moduleNamespace;
	}
	
	/**
	 * @return string
	 */
	public function getModuleNamespace() {
		return $this->moduleNamespace;
	}
	
	/**
	 * 
	 */
	public function execute(): bool {
		return $this->getController()->execute($this);	
	}
	
	/**
	 * @return \n2n\web\http\controller\ControllerContext
	 */
	public function copy() {
		return new ControllerContext($this->cmdPath, $this->cmdContextPath);
	}
}
