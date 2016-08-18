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
module n2n {
	class Dispatch {
		private static TARGET_HEAD = 'head';
		private static TARGET_BODY_START = 'bodyStart';
		private static TARGET_BODY_END = 'bodyEnd';
		private static NODE_TYPE_ELEMENT = 1;
		
		private interestingTagNames = ['SCRIPT', 'META', 'LINK', 'STYLE'];
		private callbacks = {};
		
		public registerCallback(callback: () => void) {
			var key = callback.toString();
			if (this.callbacks.hasOwnProperty(key) || typeof callback != 'function') return;
			this.callbacks[key] = callback;
		}
		
		public analyze(response: Object) {
			var elemBody;
			this.analyzeHead(response, document.getElementsByTagName('head').item(0));
			elemBody = document.getElementsByTagName('body').item(0);
			this.analyzeBodyStart(response, elemBody);
			this.analyzeBodyEnd(response, elemBody);
			if (response.hasOwnProperty('content')) {
				return response['content'];
			}
			return null;
		}
		
		public update() {
			for (var i in this.callbacks) {
				this.callbacks[i]();
			}
		}
		
		private includesElement(interestingElements: Array<HTMLElement>, element: HTMLElement): boolean {
			var includes = false;
			if (!this.isInteresting(element)) return includes;
			interestingElements.forEach(function(value: Element) {
				if (includes || (value.nodeName !== element.nodeName) || 
						!this.isInteresting(value)) return;
				includes = this.equals(element, value);
			}, this);
			return includes;
		}
		
		private equals(element: HTMLElement, otherElement: HTMLElement) {
			switch (element.nodeName) {
				case "SCRIPT":
					return element.getAttribute("src") === otherElement.getAttribute("src") 
							&& element.innerHTML.trim() === otherElement.innerHTML.trim();
				case "META":
					return element.getAttribute("name") === otherElement.getAttribute("name");
				case "LINK":
					return element.getAttribute("src") === otherElement.getAttribute("src");
				case "STYLE":
					return element.innerHTML.trim() === otherElement.innerHTML.trim();
			}
			throw new Error('Invalid Element Nodename:' + element.nodeName);
		}
		
		private createDomElement(htmlCode: string) {
			var el = document.createElement('div');
			el.innerHTML = htmlCode;
			return <HTMLElement> el.childNodes.item(0);
		}
		
		private isObject(obj) {
			return typeof obj === 'object';
		}
		
		private isInteresting(element: Node) {
			return -1 !== this.interestingTagNames.indexOf(element.nodeName);
		}
		
		private analyzeHead(response: Object, elemHead: HTMLElement) {
			var interestingElements = [], i;
			if (!(response.hasOwnProperty(Dispatch.TARGET_HEAD) 
					&& this.isObject(response[Dispatch.TARGET_HEAD]))) return;
			for (i = 0; i < elemHead.childNodes.length; i++) {
				interestingElements.push(elemHead.childNodes.item(i));
			}
			
			for (i in response[Dispatch.TARGET_HEAD]) {
				var value = response[Dispatch.TARGET_HEAD][i];				
				var domElement = this.createDomElement(value);
				if (this.includesElement(interestingElements, domElement)) return;
				this.insert(domElement, <HTMLElement> elemHead.childNodes.item(elemHead.childNodes.length - 1));
			};
		}
		
		private insert(elem: HTMLElement, beforeElement: HTMLElement) {
			switch (elem.nodeName) {
				case 'SCRIPT':
					var newElem = document.createElement("script");
					//hack that the script gets executed
					 if (null !== elem.getAttribute("src")) {
                    	newElem.src = elem.getAttribute("src");
                    } else {
                    	newElem.innerHTML = elem.innerHTML;
                    }
					elem = newElem;
					break;
			}
			beforeElement.parentNode.insertBefore(newElem, beforeElement); 
		}
		
		private analyzeBodyStart(response: Object, elemBody: HTMLElement) {
			var interestingElements = [], i, lastElement, element, value, domElement;
			if (!(response.hasOwnProperty(Dispatch.TARGET_BODY_START) 
					&& this.isObject(response[Dispatch.TARGET_BODY_START]))) return;
			for (i = 0; i < elemBody.childNodes.length; i++) {
				element = elemBody.childNodes.item(i);
				if (element.nodeType !== Dispatch.NODE_TYPE_ELEMENT) continue;
				if (!this.isInteresting(element)) break;
				lastElement = element;
				interestingElements.push(lastElement);
			}
			
			for (i in response[Dispatch.TARGET_BODY_START]) {
				value = response[Dispatch.TARGET_BODY_START][i];	
				domElement = this.createDomElement(value);
				if (this.includesElement(interestingElements, domElement)) return;
				this.insert(domElement, lastElement);
				lastElement = domElement;
			}
		}
		
		private analyzeBodyEnd(response: Object, elemBody: HTMLElement) {
			var interestingElements = [], i, element, domElement, value;
			if (!(response.hasOwnProperty(Dispatch.TARGET_BODY_END) 
					&& this.isObject(response[Dispatch.TARGET_BODY_END]))) return
			for (i = elemBody.childNodes.length - 1; i >= 0; i--) {
				element = elemBody.childNodes.item(i);
				if (element.nodeType !== Dispatch.NODE_TYPE_ELEMENT) continue;	
				if (!this.isInteresting(element)) break;
				interestingElements.push(element);
			}
			for (i in response[Dispatch.TARGET_BODY_END]) {
				value = response[Dispatch.TARGET_BODY_END][i];
				domElement = this.createDomElement(value);
				if (this.includesElement(interestingElements, domElement)) return;
				this.insert(domElement, <HTMLElement> elemBody.childNodes.item(elemBody.childNodes.length - 1));
			}
		}
	}
	export var dispatch = new Dispatch();
}
