// Creates a namespace to be used for scoping variables and classes
Ext.namespace('GO.z-push');

/*
 * This is the constructor of our PresidentsGrid
 */
GO.zpush.AddressBookGrid = function(config) {

    if (!config) {
        config = {};
    }

    config.title = GO.zpush.lang.addressbookGrid.title;
    config.layout = 'fit';
    config.autoScroll = true;
    config.split = true;
    config.store = new GO.data.JsonStore({
        /*
         * Here we store our remotely-loaded JSON data from json.php?task=presidents
         */
        url: GO.settings.modules['z-push'].url + 'json.php',
        baseParams: {
            task: 'addressbooks'
        },
        root: 'results',
        id: 'id',
        totalProperty:'total',
        fields: ['id','device','agent','first_sync','last_sync']
    });

    /*
     * ColumnModel used by our PresidentsGrid
     */
    var AddressBookModel = new Ext.grid.ColumnModel(
            [
                {
                    header: GO.zpush.lang.addressbookGrid.columns.id,
                    readOnly: true,
                    dataIndex: 'id',
                    renderer: function(value, cell) {
                        cell.css = "readonlycell";
                        return value;
                    },
                    width: 50
                },
                {
                    header: GO.zpush.lang.addressbookGrid.columns.device,
                    readOnly: true,
                    dataIndex: 'device',
                    renderer: function(value, cell) {
                        cell.css = "readonlycell";
                        return value;
                    },
                    width: 120
                },
                {
                    header: GO.zpush.lang.addressbookGrid.columns.agent,
                    readOnly: true,
                    dataIndex: 'agent',
                    renderer: function(value, cell) {
                        cell.css = "readonlycell";
                        return value;
                    },
                    width: 120
                },
                {
                    header: GO.zpush.lang.addressbookGrid.columns.first_sync,
                    readOnly: true,
                    dataIndex: 'first_sync',
                    renderer: function(value, cell) {
                        cell.css = "readonlycell";
                        return value;
                    },
                    width: 160
                },
                {
                    header: GO.zpush.lang.addressbookGrid.columns.last_sync,
                    readOnly: true,
                    dataIndex: 'last_sync',
                    renderer: function(value, cell) {
                        cell.css = "readonlycell";
                        return value;
                    },
                    width: 160
                }
            ]
    );
    AddressBookModel.defaultSortable = true;
    config.cm = AddressBookModel;

    config.view = new Ext.grid.GridView({
        emptyText: GO.lang['strNoItems']
    });

    config.sm = new Ext.grid.RowSelectionModel();
    config.loadMask = true;

    /*
     * explicitly call the superclass constructor
     */
    GO.zpush.AddressBookGrid.superclass.constructor.call(this, config);

};

/*
 * Extend the base class
 */
Ext.extend(GO.zpush.AddressBookGrid, GO.grid.GridPanel, {

    loaded : false,

    afterRender : function() {
        GO.zpush.AddressBookGrid.superclass.afterRender.call(this);

        if (this.isVisible()) {
            this.onGridShow();
        }
    },

    onGridShow : function() {
        if (!this.loaded && this.rendered) {
            this.store.load();
            this.loaded = true;
        }
    },

});


	