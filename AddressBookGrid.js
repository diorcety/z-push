// Creates a namespace to be used for scoping variables and classes
Ext.namespace('GO.z-push');

/*
 * This is the constructor of our PresidentsGrid
 */
GO.zpush.AddressBookGrid = function(config) {

    if (!config) {
        config = {};
    }

    config.autoScroll = true;

    config.store = new GO.data.JsonStore({
        /*
         * Here we store our remotely-loaded JSON data from json.php?task=devices
         */
        url: GO.settings.modules['z-push'].url + 'json.php',
        baseParams: {
            task: 'addressbooks'
        },
        autoSave : false,
        writer: new Ext.data.JsonWriter({
            encode: true,
            writeAllFields: true,
            listful: true
        }),
        root: 'results',
        id: 'id',
        totalProperty:'total',
        fields: ['id','name','synchronize','default']
    });

    var synchronizeColumn = new GO.grid.CheckColumn({
        header: GO.zpush.lang.addressBookGrid.columns.synchronize,
        dataIndex: 'synchronize',
        width: 120
    });
    var defaultColumn = new GO.grid.RadioColumn({
        header: GO.zpush.lang.addressBookGrid.columns['default'],
        dataIndex: 'default',
        width: 120
    });
    /*
     * ColumnModel used by our DeviceGrid
     */
    var ElementModel = new Ext.grid.ColumnModel(
            [
                {
                    header: GO.zpush.lang.addressBookGrid.columns.name,
                    readOnly: true,
                    dataIndex: 'name',
                    renderer: function(value, cell) {
                        cell.css = "readonlycell";
                        return value;
                    },
                    width: 120
                },
                synchronizeColumn,
                defaultColumn
            ]
    );
    ElementModel.defaultSortable = true;
    config.cm = ElementModel;

    config.view = new Ext.grid.GridView({
        emptyText: GO.lang['strNoItems']
    });

    config.plugins = [synchronizeColumn, defaultColumn];
    config.loadMask = true;

    config.tbar = [
        {
            itemId: 'refresh',
            xtype: 'button',
            text: GO.zpush.lang.addressBookGrid.buttons.refresh,
            iconCls: 'btn-refresh',
            scope: this,
            handler: function(btn) {
                this.store.load();
            }
        },
        {
            xtype: 'tbseparator'
        },
        {
            itemId: 'save',
            xtype: 'button',
            text: GO.zpush.lang.addressBookGrid.buttons.save,
            iconCls: 'btn-save',
            scope: this,
            handler: function(btn) {
                this.store.save();
            }
        }
    ];


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
    }

});


	