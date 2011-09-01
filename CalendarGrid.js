// Creates a namespace to be used for scoping variables and classes
Ext.namespace('GO.z-push');

/*
 * This is the constructor of our PresidentsGrid
 */
GO.zpush.CalendarGrid = function(config) {

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
            task: 'calendars'
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
        fields: [
            {name: 'id', type: 'integer'},
            {name: 'name', type: 'string'},
            {name: 'synchronize', type: 'boolean'},
            {name: 'default', type: 'boolean'}
        ]
    });

    var synchronizeColumn = new GO.grid.CheckColumn({
        header: GO.zpush.lang.calendarGrid.columns.synchronize,
        dataIndex: 'synchronize',
        disabled_field: 'default',
        width: 120,
        sortable: false,
        menuDisabled: true,
        renderer : function(v, p, record) {
            p.css += ' x-grid3-check-col-td';

            var disabled = record.get(this.disabled_field);

            var on;
            if (disabled) {
                on = '-on x-item-disabled';
            } else {
                if (!GO.util.empty(v)) {
                    on = '-on';
                }  else {
                    on = '';
                }
            }

            return '<div class="x-grid3-check-col' + on + ' x-grid3-cc-' + this.id + '">&#160;</div>';
        }
    });
    var defaultColumn = new GO.grid.RadioColumn({
        header: GO.zpush.lang.calendarGrid.columns['default'],
        dataIndex: 'default',
        width: 120,
        sortable: false,
        menuDisabled: true
    });
    /*
     * ColumnModel used by our DeviceGrid
     */
    var CalendarModel = new Ext.grid.ColumnModel(
            [
                {
                    header: GO.zpush.lang.calendarGrid.columns.name,
                    readOnly: true,
                    dataIndex: 'name',
                    renderer: function(value, cell) {
                        cell.css = "readonlycell";
                        return value;
                    },
                    width: 120,
                    sortable: true
                },
                synchronizeColumn,
                defaultColumn
            ]
    );
    CalendarModel.defaultSortable = true;
    config.cm = CalendarModel;

    config.view = new Ext.grid.GridView({
        emptyText: GO.lang['strNoItems']
    });

    config.plugins = [synchronizeColumn, defaultColumn];
    config.loadMask = true;

    config.tbar = [
        {
            itemId: 'refresh',
            xtype: 'button',
            text: GO.zpush.lang.calendarGrid.buttons.refresh,
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
            text: GO.zpush.lang.calendarGrid.buttons.save,
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
    GO.zpush.CalendarGrid.superclass.constructor.call(this, config);

};

/*
 * Extend the base class
 */
Ext.extend(GO.zpush.CalendarGrid, GO.grid.GridPanel, {

    loaded : false,

    afterRender : function() {
        GO.zpush.CalendarGrid.superclass.afterRender.call(this);

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


	