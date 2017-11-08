<?php
namespace n2n\web\http\payload\impl;

use n2n\io\fs\FsPath;

class FsPathPayload extends FilePayload {
	
	public function __construct(FsPath $fsPath, bool $attachment = false, string $attachmentName = null) {
		parent::__construct($fsPath->toFile(), $attachment, $attachmentName);
	}
}