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
;jQuery(document).ready(function($) {
	(function() {
		var jqElemsExceptionLinks = $(".exception-link");
		if (jqElemsExceptionLinks.length === 0) return;
		
		(function() {
			var jqElemBody = $("html, body"),
				jqElemWindow = $(window);
				
			var ExceptionLink = function(jqElem) {
				this.jqElem = jqElem;
				this.jqElemA = jqElem.children("a:first");
				this.jqElemException = $(this.jqElemA.attr("href"));
				this.jqElemADoc = this.jqElemA.next();
				this.jqElemIntro = $("#intro");
				
				(function(_obj) {
					var clicks = 0, timer;
					this.jqElemA.click(function(e) {
						e.preventDefault();
					});
					
					this.jqElemADoc.click(function(e) {
						e.stopPropagation();
					});
					
					this.jqElem.hover(function() {
						_obj.jqElemException.addClass("highlighted");
					}, function() {
						_obj.jqElemException.removeClass("highlighted");
					}).click(function() {
						if (_obj.jqElemADoc.length === 0) {
							_obj.scrollContent();
							_obj.toggleStackTraces()
							return;
						}
						
						clicks++;  //count clicks
						if(clicks === 1) {
							timer = setTimeout(function() {
								_obj.scrollContent();
								_obj.toggleStackTraces();
								clicks = 0;
							}, 200);
						} else {
							_obj.openExceptionDoc();
							clearTimeout(timer);
							clicks = 0;
						}
					}).dblclick(function(e) {
						e.preventDefault();
					});
				}).call(this, this);
			};
			
			ExceptionLink.prototype.toggleStackTraces = function() {
				var _obj = this;
				this.jqElemException.find(".stack-trace > h3").click().on("slideCompleted", function() {
					_obj.scrollContent();
					$(this).off("slideCompleted");
				}); 
			};
			
			ExceptionLink.prototype.openExceptionDoc = function() {
				if (this.jqElemADoc.length === 0) return;
				this.jqElemADoc[0].click();
			};
			
			ExceptionLink.prototype.scrollContent = function() {
				var scrollTo,
					exceptionHeight = this.jqElemException.outerHeight(true),
					windowHeight = jqElemWindow.outerHeight();
					exceptionOffset = this.jqElemException.offset().top,
					currentScrollTop = jqElemWindow.scrollTop(),
					introHeight = this.jqElemIntro.outerHeight();
				
				if (exceptionOffset >= (currentScrollTop + introHeight) && (exceptionOffset + exceptionHeight) < (currentScrollTop + windowHeight)) return;
				
				if (exceptionHeight > windowHeight || exceptionOffset < currentScrollTop) {
					scrollTo = exceptionOffset - introHeight;
				} else {
					scrollTo = exceptionOffset - (windowHeight - exceptionHeight); 
				}
				
				jqElemBody.stop(true, true).animate({
					"scrollTop": scrollTo
				}, 'fast');
			};
			
			jqElemsExceptionLinks.each(function() {
				new ExceptionLink($(this));
			});
		})();
		
		(function() {
			var jqElemsStackTrace = $(".stack-trace");
			if (jqElemsStackTrace.length === 0) return;
			
			(function() {
				var StackTrace = function(jqElem) {
					this.jqElemH3 = jqElem.children("h3:first");
					this.jqElemContent = this.jqElemH3.next().hide();
					this.closed = true;
					this.jqElemIcon = $("<i />", {"class": "fa fa-plus-circle"}).appendTo(this.jqElemH3);
					
					(function(_obj) {
						this.jqElemH3.click(function() {
							if (_obj.closed) {
								_obj.jqElemContent.stop(true, true).slideDown(75, function() {
									_obj.jqElemIcon.removeClass().addClass('fa fa-minus-circle');
									_obj.jqElemH3.trigger('slideCompleted');
								});
							} else {
								_obj.jqElemContent.stop(true, true).slideUp(75, function() {
									_obj.jqElemIcon.removeClass().addClass('fa fa-plus-circle');
									_obj.jqElemH3.trigger('slideCompleted');
								});
							}
							
							_obj.closed = !_obj.closed;
						});
					}).call(this, this);
				};
				
				StackTrace.prototype.open = function() {
					this.jqElemContent.stop(true, true).show()
					this.jqElemIcon.removeClass().addClass('fa fa-minus-circle');
				};
				
				var lastStackTrace = null;
				
				jqElemsStackTrace.each(function() {
					lastStackTrace = new StackTrace($(this));
				});
				
				if (null !== lastStackTrace) {
					lastStackTrace.open();
				}
			})();
		})();
	})();
});
