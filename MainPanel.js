/**
 *
 */
Ext.namespace('GO.z-push');

GO.zpush.MainPanel = function(config) {

    if (!config) {
        config = {};
    }

    config.items = [
        new GO.zpush.AddressBookGrid({
            fieldLabel:GO.zpush.lang.addressBookGrid.title,
            height: 200
        }),
        new GO.zpush.DeviceGrid({
            fieldLabel:GO.zpush.lang.deviceGrid.title,
            height: 200
        })
    ];
    config.padding = 10;
    config.labelWidth = 200;

    GO.zpush.MainPanel.superclass.constructor.call(this, config);
};

/*
 * Extend the base class
 */
Ext.extend(GO.zpush.MainPanel, Ext.form.FormPanel, {

});

/*
 * This will add the module to the main tabpanel filled with all the modules
 */

GO.moduleManager.addModule('zpush', GO.zpush.MainPanel, {
    title : GO.zpush.lang.zpush,
    iconCls : 'go-tab-icon-zpush'
});


