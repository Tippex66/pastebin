dojo.provide("tests._base.html");
if(dojo.isBrowser){
	doh.registerUrl("tests._base.html", dojo.moduleUrl("tests", "_base/html.html"), 15000);
	doh.registerUrl("tests._base.html_rtl", dojo.moduleUrl("tests", "_base/html_rtl.html"), 15000);
	doh.registerUrl("tests._base.html_quirks", dojo.moduleUrl("tests", "_base/html_quirks.html"), 15000);
	doh.registerUrl("tests._base.html_box", dojo.moduleUrl("tests", "_base/html_box.html"), 35000);
	doh.registerUrl("tests._base.html_box_quirks", dojo.moduleUrl("tests", "_base/html_box_quirks.html"), 35000);
}
