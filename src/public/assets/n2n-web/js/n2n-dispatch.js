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
var n2n;
(function (n2n) {
    var Dispatch = (function () {
        function Dispatch() {
            this.interestingTagNames = ['SCRIPT', 'META', 'LINK', 'STYLE'];
            this.callbacks = {};
        }
        Dispatch.prototype.registerCallback = function (callback) {
            var key = callback.toString();
            if (this.callbacks.hasOwnProperty(key) || typeof callback != 'function')
                return;
            this.callbacks[key] = callback;
        };
        Dispatch.prototype.analyze = function (response) {
            var elemBody;
            this.analyzeHead(response, document.getElementsByTagName('head').item(0));
            elemBody = document.getElementsByTagName('body').item(0);
            this.analyzeBodyStart(response, elemBody);
            this.analyzeBodyEnd(response, elemBody);
            if (response.hasOwnProperty('content')) {
                return response['content'];
            }
            return null;
        };
        Dispatch.prototype.update = function () {
            for (var i in this.callbacks) {
                this.callbacks[i]();
            }
        };
        Dispatch.prototype.includesElement = function (interestingElements, element) {
            var includes = false;
            if (!this.isInteresting(element))
                return includes;
            interestingElements.forEach(function (value) {
                if (includes || (value.nodeName !== element.nodeName) ||
                    !this.isInteresting(value))
                    return;
                includes = this.equals(element, value);
            }, this);
            return includes;
        };
        Dispatch.prototype.equals = function (element, otherElement) {
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
        };
        Dispatch.prototype.createDomElement = function (htmlCode) {
            var el = document.createElement('div');
            el.innerHTML = htmlCode;
            return el.childNodes.item(0);
        };
        Dispatch.prototype.isObject = function (obj) {
            return typeof obj === 'object';
        };
        Dispatch.prototype.isInteresting = function (element) {
            return -1 !== this.interestingTagNames.indexOf(element.nodeName);
        };
        Dispatch.prototype.analyzeHead = function (response, elemHead) {
            var interestingElements = [], i;
            if (!(response.hasOwnProperty(Dispatch.TARGET_HEAD)
                && this.isObject(response[Dispatch.TARGET_HEAD])))
                return;
            for (i = 0; i < elemHead.childNodes.length; i++) {
                interestingElements.push(elemHead.childNodes.item(i));
            }
            for (i in response[Dispatch.TARGET_HEAD]) {
                var value = response[Dispatch.TARGET_HEAD][i];
                var domElement = this.createDomElement(value);
                if (this.includesElement(interestingElements, domElement))
                    return;
                this.insert(domElement, elemHead.childNodes.item(elemHead.childNodes.length - 1));
            }
            ;
        };
        Dispatch.prototype.insert = function (elem, beforeElement) {
            switch (elem.nodeName) {
                case 'SCRIPT':
                    var newElem = document.createElement("script");
                    //hack that the script gets executed
                    if (null !== elem.getAttribute("src")) {
                        newElem.src = elem.getAttribute("src");
                    }
                    else {
                        newElem.innerHTML = elem.innerHTML;
                    }
                    newElem.src = elem.getAttribute("src");
                    elem = newElem;
                    break;
            }
            beforeElement.parentNode.insertBefore(newElem, beforeElement);
        };
        Dispatch.prototype.analyzeBodyStart = function (response, elemBody) {
            var interestingElements = [], i, lastElement, element, value, domElement;
            if (!(response.hasOwnProperty(Dispatch.TARGET_BODY_START)
                && this.isObject(response[Dispatch.TARGET_BODY_START])))
                return;
            for (i = 0; i < elemBody.childNodes.length; i++) {
                element = elemBody.childNodes.item(i);
                if (element.nodeType !== Dispatch.NODE_TYPE_ELEMENT)
                    continue;
                if (!this.isInteresting(element))
                    break;
                lastElement = element;
                interestingElements.push(lastElement);
            }
            for (i in response[Dispatch.TARGET_BODY_START]) {
                value = response[Dispatch.TARGET_BODY_START][i];
                domElement = this.createDomElement(value);
                if (this.includesElement(interestingElements, domElement))
                    return;
                this.insert(domElement, lastElement);
                lastElement = domElement;
            }
        };
        Dispatch.prototype.analyzeBodyEnd = function (response, elemBody) {
            var interestingElements = [], i, element, domElement, value;
            if (!(response.hasOwnProperty(Dispatch.TARGET_BODY_END)
                && this.isObject(response[Dispatch.TARGET_BODY_END])))
                return;
            for (i = elemBody.childNodes.length - 1; i >= 0; i--) {
                element = elemBody.childNodes.item(i);
                if (element.nodeType !== Dispatch.NODE_TYPE_ELEMENT)
                    continue;
                if (!this.isInteresting(element))
                    break;
                interestingElements.push(element);
            }
            for (i in response[Dispatch.TARGET_BODY_END]) {
                value = response[Dispatch.TARGET_BODY_END][i];
                domElement = this.createDomElement(value);
                if (this.includesElement(interestingElements, domElement))
                    return;
                this.insert(domElement, elemBody.childNodes.item(elemBody.childNodes.length - 1));
            }
        };
        Dispatch.TARGET_HEAD = 'head';
        Dispatch.TARGET_BODY_START = 'bodyStart';
        Dispatch.TARGET_BODY_END = 'bodyEnd';
        Dispatch.NODE_TYPE_ELEMENT = 1;
        return Dispatch;
    }());
    n2n.dispatch = new Dispatch();
})(n2n || (n2n = {}));
