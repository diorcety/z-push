/**
 *
 */

GO.zpush.MainPanel = function(config){
	
	if(!config)
	{
		config = {};
	}

	config.html='Z-Push';
	
	GO.zpush.MainPanel.superclass.constructor.call(this, config);	
};

/*
 * Extend the base class
 */
Ext.extend(GO.zpush.MainPanel, Ext.Panel,{

});

/*
 * This will add the module to the main tabpanel filled with all the modules
 */
 
GO.moduleManager.addModule('zpush', GO.zpush.MainPanel, {
	title : GO.zpush.lang.zpush,
	iconCls : 'go-tab-icon-zpush'
});


