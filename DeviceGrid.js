// Creates a namespace to be used for scoping variables and classes
Ext.namespace('GO.z-push');

/*
 * This is the constructor of our PresidentsGrid
 */
GO.zpush.DeviceGrid = function(config) {

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
            task: 'devices'
        },
        root: 'results',
        id: 'id',
        totalProperty:'total',
        fields: [
            {name: 'id', type: 'string'},
            {name: 'device', type: 'string'},
            {name: 'agent', type: 'string'},
            {name: 'first_sync', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'last_sync', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'status', type: 'integer'}
        ]
    });

    /*
     * ColumnModel used by our DeviceGrid
     */
    var DeviceModel = new Ext.grid.ColumnModel(
            [
                {
                    header: GO.zpush.lang.deviceGrid.columns.id,
                    readOnly: true,
                    dataIndex: 'id',
                    renderer: function(value, cell) {
                        cell.css = "readonlycell";
                        return value;
                    },
                    width: 120
                },
                {
                    header: GO.zpush.lang.deviceGrid.columns.device,
                    readOnly: true,
                    dataIndex: 'device',
                    renderer: function(value, cell) {
                        cell.css = "readonlycell";
                        return value;
                    },
                    width: 120
                },
                {
                    header: GO.zpush.lang.deviceGrid.columns.agent,
                    readOnly: true,
                    dataIndex: 'agent',
                    renderer: function(value, cell) {
                        cell.css = "readonlycell";
                        return value;
                    },
                    width: 120
                },
                {
                    header: GO.zpush.lang.deviceGrid.columns.first_sync,
                    readOnly: true,
                    dataIndex: 'first_sync',
                    renderer: function(value, cell) {
                        cell.css = "readonlycell";
                        return value;
                    },
                    width: 160
                },
                {
                    header: GO.zpush.lang.deviceGrid.columns.last_sync,
                    readOnly: true,
                    dataIndex: 'last_sync',
                    renderer: function(value, cell) {
                        cell.css = "readonlycell";
                        return value;
                    },
                    width: 160
                },
                {
                    header: GO.zpush.lang.deviceGrid.columns.status,
                    readOnly: true,
                    dataIndex: 'status',
                    renderer: function(value, cell) {
                        cell.css = "readonlycell";
                        return value;
                    },
                    width: 160,
                    renderer: function(value, metaData, record, rowIndex, colIndex, store) {
                        switch (value) {
                            case 1:
                                return GO.zpush.lang.deviceGrid.status.ok;
                                break;
                            case 2:
                                return GO.zpush.lang.deviceGrid.status.pending;
                                break;
                            case 3:
                                return GO.zpush.lang.deviceGrid.status.wiped;
                                break;
                            case 0:
                            default:
                                return GO.zpush.lang.deviceGrid.status.unknown;
                        }
                    }
                }
            ]
    );
    DeviceModel.defaultSortable = true;
    config.cm = DeviceModel;

    config.view = new Ext.grid.GridView({
        emptyText: GO.lang['strNoItems']
    });

    config.sm = new Ext.grid.RowSelectionModel({
        singleSelect: true
    });
    config.sm.on('selectionchange', function(sm) {
        if (sm.getCount()) {
            this.getTopToolbar().get('remove').enable();
            this.getTopToolbar().get('wipe').enable();
        } else {
            this.getTopToolbar().get('remove').disable();
            this.getTopToolbar().get('wipe').disable();
        }
    }, this);

    config.loadMask = true;

    config.tbar = [
        {
            itemId: 'refresh',
            xtype: 'button',
            text: GO.zpush.lang.deviceGrid.buttons.refresh,
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
            itemId: 'remove',
            xtype: 'button',
            text: GO.zpush.lang.deviceGrid.buttons.remove,
            iconCls: 'btn-delete',
            disabled: true,
            scope: this,
            handler: function(btn) {
                GO.deleteItems({
                    url:GO.settings.modules['z-push'].url + 'action.php',
                    params: {
                        task: 'delete_device',
                        id: this.getSelectionModel().getSelected()['id']
                    },
                    count:1,
                    callback:function(responseParams) {
                        if (responseParams.success) {
                            this.store.load();
                        }
                    },
                    scope:this
                }
                );
            }
        },
        {
            itemId: 'wipe',
            xtype :'button',
            text: GO.zpush.lang.deviceGrid.buttons.wipe,
            iconCls :'btn-dismiss',
            disabled:true,
            scope: this,
            handler: function(btn) {
                Ext.Ajax.request({
                    scope:this,
                    url: GO.settings.modules['z-push'].url + 'action.php',
                    params: {
                        task: 'wipe_device',
                        id: this.getSelectionModel().getSelected()['id']
                    },
                    callback: function(options, success, response) {
                        var responseParams = Ext.decode(response.responseText);
                        if (responseParams.success) {
                            this.store.load();
                        }
                    }
                });
            }
        }
    ];

    /*
     * explicitly call the superclass constructor
     */
    GO.zpush.DeviceGrid.superclass.constructor.call(this, config);

};

/*
 * Extend the base class
 */
Ext.extend(GO.zpush.DeviceGrid, GO.grid.GridPanel, {

    loaded : false,

    afterRender : function() {
        GO.zpush.DeviceGrid.superclass.afterRender.call(this);

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


	