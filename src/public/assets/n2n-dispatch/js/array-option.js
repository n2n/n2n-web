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
(function() {
	if (!Array.prototype.indexOf) {
	    Array.prototype.indexOf = function(obj, start) {
	         for (var i = (start || 0), j = this.length; i < j; i++) {
	             if (this[i] === obj) { return i; }
	         }
	         return -1;
	    }
	}
	
	var N2nElement = function(elem) {
		if (typeof elem == "string") {
			this.elem = document.createElement(elem);
		} else {
			this.elem = elem;
		}
	};
	
	N2nElement.NODE_TYPE_ELEMENT = 1;
	N2nElement.NODE_NAMES_INLINE = new Array("A");
	
	N2nElement.prototype.classSplitPattern = /\s+/;
	
	N2nElement.prototype.addEvent = function(type, fn) {
		var _obj = this;
        if (this.elem.addEventListener) {
        	this.elem.addEventListener(type, fn, false);
        } else if (_obj.attachEvent) {
        	this.elem.attachEvent('on' + type, function() { 
        		return fn.apply(_obj.elem, [window.event]);
        	});
        }
        return this;
	};
	
	N2nElement.prototype.appendElement = function(n2nElem) {
		this.elem.appendChild(n2nElem.elem);
		return this;
	};
	
	N2nElement.prototype.appendTextNode = function(text) {
		this.elem.appendChild(document.createTextNode(text));
		return this;
	};
	
	N2nElement.prototype.appendAttribute = function(attributeName, attributeValue) {
		this.elem.setAttribute(attributeName, attributeValue)
		return this;
	};
	
	N2nElement.prototype.insertAfter = function(n2nElem) {
	    this.elem.parentNode.insertBefore(n2nElem.elem, this.elem.nextSibling);
	    return this;
	};
	
	N2nElement.prototype.getData = function(name) {
		return this.elem.getAttribute('data-' + name);
	};
	
	N2nElement.prototype.setData = function(name, data) {
		return this.elem.setAttribute('data-' + name, data);
	};
	
	N2nElement.prototype.createAttributeNode = function(attributeName) {
		var attribute = document.createAttribute(attributeName);
		//nodeValue needs to be set in IE
		attribute.nodeValue = true;
		this.elem.setAttributeNode(attribute);
		return this.elem.getAttributeNode(attributeName);
	};
	
	N2nElement.prototype.removeAttributeNode = function(attributeName) {
		if (this.elem.hasAttribute(attributeName)) {
			this.elem.removeAttributeNode(this.elem.getAttributeNode(attributeName));
		}
	};
	
	N2nElement.prototype.remove = function() {
		this.elem.parentNode.removeChild(this.elem);
	};
	
	N2nElement.prototype.getClassNames = function() {
		if (!this.elem.getAttribute("class")) return new Array();
		return this.elem.getAttribute("class").split(this.classSplitPattern);
	};
	
	N2nElement.prototype.isInline = function() {
		return (N2nElement.NODE_NAMES_INLINE.indexOf(this.elem.nodeName) != -1);
	};
	
	N2nElement.prototype.show = function() {
		if (this.isInline()) {
			this.elem.setAttribute("style", "display: inline;");
		} else {
			this.elem.setAttribute("style", "display: block;");
		}
	};
	
	N2nElement.prototype.hide = function() {
		this.elem.setAttribute("style", "display: none;");
	};
	
	N2nElement.prototype.addClass = function(className) {
		if (this.hasClass(className)) return;
		var classNames = this.getClassNames();
		classNames.push(className);
		this.elem.setAttribute("class", classNames.join(" "));
	};
	
	N2nElement.prototype.hasClass = function(className) {
		return (-1 != this.getClassNames().indexOf(className));
	};
	
	N2nElement.prototype.clone = function() {
		return new N2nElement(this.elem.cloneNode(true));
	};
	
	N2nElement.prototype.parentsWithClassName = function(className) {
		var currentParent = this.elem.parentNode;
		var n2nElemCurrentParent;
		while (true) {
			if (currentParent == null) return null;
			n2nElemCurrentParent = new N2nElement(currentParent);
			if (n2nElemCurrentParent.hasClass(className)) return n2nElemCurrentParent;
			currentParent = currentParent.parentNode;
		}
	};
	
	ArrayOptionOption = function(arrayOption, n2nElem, removable) {
		this.arrayOption = arrayOption;
		this.n2nElem = n2nElem;
		this.removable = removable || false;
		if (removable) {
			this.n2nElemObjectOptional = null;
			this.initialize();
		}
	};
	
	ArrayOptionOption.prototype.initialize = function() {
		var _obj = this;
		this.n2nElem.appendElement(new N2nElement("ul")
				.appendAttribute("class", "n2n-array-option-controls")
				.appendElement(new N2nElement("li")
						.appendElement(
								new N2nElement("a")
								.appendAttribute("class", "n2n-array-option-delete")
								.appendTextNode("remove")
								.addEvent("click", function(event) {
									event.preventDefault();
									_obj.arrayOption.removeElement(_obj);
								}).appendAttribute("href", "#")
						)
				)	
		).addClass("n2n-array-option-element");
		var inputElements = this.n2nElem.elem.getElementsByTagName("INPUT");
		var inputElementsLength = inputElements.length;
		for (var i = 0; i < inputElementsLength; i++) {
			var inputElement = inputElements.item(i);
			if (inputElement.getAttribute("type") == "hidden") {
				this.n2nElemObjectOptional = new N2nElement(inputElement);
				break;
			}
		}
	};
	
	ArrayOptionOption.prototype.show = function() {
		this.n2nElem.show();
		if (null !== this.n2nElemObjectOptional) {
			this.n2nElemObjectOptional.removeAttributeNode("disabled");
		}
	};
	
	ArrayOptionOption.prototype.append = function() {
		this.arrayOption.n2nOptionCollectionElem.appendElement(this.n2nElem);
		this.show();
	};
	
	ArrayOptionOption.prototype.hide = function() {
		this.n2nElem.hide();
		if (null !== this.n2nElemObjectOptional) {
			this.n2nElemObjectOptional.createAttributeNode("disabled");
		}
	};
	
	ArrayOptionOption.prototype.remove = function() {
		this.n2nElem.hide();
		this.n2nElem.remove();
	};
	
	
	var ArrayOption = function(n2nContainerElem) {
		this.n2nContainerElem = n2nContainerElem;
		var magCollectionElem = this.findOptionCollectionElem(n2nContainerElem.elem);
		if (null == magCollectionElem) return;
		this.n2nOptionCollectionElem =  new N2nElement(magCollectionElem);
		if (this.n2nOptionCollectionElem.getData("dynamic-array") === "") return;
		
		this.n2nElemA = new N2nElement("a")
			.appendTextNode("add")
			.appendAttribute("class", "n2n-array-option-add")
			.appendAttribute("href", "#");
		this.availableOptions = new Array();
		
		this.initialize();
	};
	
	ArrayOption.prototype.initialize = function() {
		var _obj = this;
		this.n2nElemA.addEvent("click", function(event) {
			_obj.addElement();
			event.preventDefault();
		});
		if (null != this.n2nOptionCollectionElem) {
			this.n2nOptionCollectionElem.insertAfter(this.n2nElemA);
			var childNode, arrayOptionOption;
			var count = 0;
			var numExisting = parseInt(this.n2nOptionCollectionElem.getData("num-existing"), 10);
			var minChildNodes = parseInt(this.n2nOptionCollectionElem.getData("min"), 10);
			
			var numShownChildNodes = numExisting;
			if ((null != minChildNodes) && (minChildNodes > numShownChildNodes)) {
				numShownChildNodes = minChildNodes;
			} 
			
			var arrayOptionOptions = new Array();
			for (var i = 0; i < this.n2nOptionCollectionElem.elem.childNodes.length; i++) {
				childNode = this.n2nOptionCollectionElem.elem.childNodes.item(i);
				if (childNode.nodeType != N2nElement.NODE_TYPE_ELEMENT) continue;
				count++;
				if (count <= minChildNodes) {
					arrayOptionOptions.push(new ArrayOptionOption(_obj, new N2nElement(childNode), false));
				} else {
					arrayOptionOptions.push(new ArrayOptionOption(_obj, new N2nElement(childNode), true));
				}
			}
			
			//we need to iterate over the arrayoptions again otherwise the childnodes disappear in the loop
			for (var i in arrayOptionOptions) {
				if (i >= numShownChildNodes) {
					this.removeElement(arrayOptionOptions[i], true);
				}
			}
		}
	};
	
	ArrayOption.prototype.addElement = function() {
		var arrayOptionOption = this.availableOptions.shift();
		arrayOptionOption.append();
		if (this.availableOptions.length == 0) {
			this.n2nElemA.hide();
		}
	};
	
	ArrayOption.prototype.removeElement = function(arrayOptionOption, prepend) {
		prepend = prepend || false;
		arrayOptionOption.remove();
		if (prepend) {
			this.availableOptions.push(arrayOptionOption);
		} else {
			this.availableOptions.unshift(arrayOptionOption);
			
		}
		if (this.availableOptions.length == 1) {
			this.n2nElemA.show();
		}
	}
	
	ArrayOption.prototype.initializeDomRemoval = function() {
		for (var i in this.availableOptions) {
			this.availableOptions[i].remove();
		}
		this.domRemoval = true;
	};
	
	ArrayOption.prototype.findOptionCollectionElem = function(containerElem) {
		var magCollectionElem, childElem;
		for (var i in containerElem.childNodes) {
			childElem = containerElem.childNodes[i];
			if ((null == childElem.childNodes) || (childElem.childNodes.length == 0) 
					|| (undefined == childElem.className)) continue;
			if (childElem.className.indexOf("n2n-option-collection-array") != -1 
					|| childElem.className.indexOf("n2n-option-array") != -1) {
				return childElem;
			}
			magCollectionElem = this.findOptionCollectionElem(childElem);
			if (null != magCollectionElem) return magCollectionElem;
			
		}
		return null;
	};
	
	var run = function() {
		var arrayOptionElems = document.querySelectorAll(".n2n-array-option");
		if (arrayOptionElems.length == 0) return;
		for (var i = arrayOptionElems.length; i--;) {
			var element = arrayOptionElems.item(i);
			if (element.nodeType == N2nElement.NODE_TYPE_ELEMENT) {
				var n2nElem = new N2nElement(element);
				if (n2nElem.getData('init-array-option')) continue;
				n2nElem.setData('init-array-option', 1);
				new ArrayOption(new N2nElement(element));
			}
		}
	}
	if (typeof n2n !== 'undefined' && typeof n2n.dispatch !== 'undefined') {
		n2n.dispatch.registerCallback(function() {
			run();
		});
	}
	run();
})();
window.test = {};
