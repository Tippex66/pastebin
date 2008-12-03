dojo.provide("paste.main");

(function() {
    dojo.require("spindle.main");

    dojo.require("paste._base");
    dojo.require("paste.highlight.php");
    dojo.require("paste.TabHandler");
    dojo.require("dijit.Dialog");
    dojo.require("dijit.form.Button");
    dojo.require("dijit.form.FilteringSelect");
    dojo.require("dijit.form.Form");
    dojo.require("dijit.form.SimpleTextarea");
    dojo.require("dijit.form.ValidationTextBox");
    dojo.require("dijit.layout.TabContainer");
    dojo.require("dojo.back");
    dojo.require("dojox.data.QueryReadStore");
    dojo.require("dojox.dtl.Context");
    dojo.require("dojox.grid.DataGrid");
    dojo.require("dojox.highlight.languages.html");
    dojo.require("dojox.highlight.languages.python");
    dojo.require("dojox.highlight.languages._www");
    dojo.require("dojox.highlight.languages.xml");
    dojo.require("dojox.rpc.Service");
    dojo.require("dojox.rpc.JsonRPC");

    dojo.addOnLoad(function() {
        // Progressive enhancement of app
        paste.upgrade(); 

        // Retrieve base URL and create paste.tabs object
        paste.baseUrl = spindle.baseUrl;
        paste.tabs    = new paste.TabHandler(paste.baseUrl);

        // Connect loading of new-paste tab to prepare new-paste form
        dojo.connect(dijit.byId("new-paste"), "onLoad", paste, "prepareNewPasteForm");

        // setup back button handling
        dojo.back.setInitialState({
            handle: dojo.hitch(paste.tabs, "urlChangeHandler"),
        });

        // set the tab based on any URL at load-time
        paste.tabs.urlChangeHandler();
    
        // update the URL hash each time the tab changes
        dojo.connect(dijit.byId("pastebin"), "selectChild", paste.tabs, "urlUpdateHandler");
        dojo.connect(dijit.byId("new-paste").controlButton, "onClick", dijit.byId("new-paste"), "refresh");
        dojo.connect(dijit.byId("active").controlButton, "onClick", dijit.byId("active"), "refresh");
    });
})();
