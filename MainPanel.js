/**
 *
 */
Ext.namespace('GO.z-push');

GO.zpush.MainPanel = function(config) {

    if (!config) {
        config = {};
    }

    var centerPanel = new GO.zpush.DeviceGrid({
        region:'center',
        autoScroll:true,
        width:250,
        split:true
    });

    config.items = [
        centerPanel
    ];

    config.layout = 'border';

    GO.zpush.MainPanel.superclass.constructor.call(this, config);
};

/*
 * Extend the base class
 */
Ext.extend(GO.zpush.MainPanel, Ext.Panel, {

});

/*
 * This will add the module to the main tabpanel filled with all the modules
 */

GO.moduleManager.addModule('zpush', GO.zpush.MainPanel, {
    title : GO.zpush.lang.zpush,
    iconCls : 'go-tab-icon-zpush'
});


