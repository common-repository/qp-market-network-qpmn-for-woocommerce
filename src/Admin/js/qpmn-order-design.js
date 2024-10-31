if (qpmn_admin_order_obj.items.length && jQuery('.qpmn-order-item-design-container').length) {
    const { __, _x, _n, sprintf } = wp.i18n;
    Vue.use(Vuex);

    const qpmn_order_mixin = {
        methods: {
            __: function (text) {
                console.log('__()');
                return __(text, 'qp-market-network');
            },
            previewBtn: function () {
                return this.__('Preview');
            },
            updateConfirmBtn: function () {
                return this.__('Do you really want to update?');
            },
            msgCustomizedDisabled: function () {
                return this.__('Personalization feature disabled.');
            },
            msgSyncedToQPMN: function () {
                return this.__('Order already synced to QPMN.');
            },
            init: function () {
                let url = new URL(window.location.href);
                let requestState = url.searchParams.get('state');
                if (this.$store.state.state != requestState) {
                    //determine which item need to update
                    return;
                }

                console.log(this.$store.state.state + ' - init');

                let templateThumbnail = url.searchParams.get('image_url');
                let templateId = url.searchParams.get('designtemplate');
                let designId = url.searchParams.get('designid');

                if (templateThumbnail) {
                    templateThumbnail = decodeURIComponent(templateThumbnail);
                    this.$store.commit('changeThumbnail', templateThumbnail);
                }

                //store created template
                if (designId) {
                    designId = designId.trim();
                    if (designId.length > 0) {
                        this.$store.commit('setIsEdited', designId != this.$store.state.designId);
                        this.$store.commit('changeDesignId', designId);
                    }
                }

                if (templateId) {
                    templateId = templateId.trim();
                    if (templateId.length > 0) {
                        this.$store.commit('changeDesignConfig', templateId);
                    }
                }
            },
        }
    };

    var qpmn_axios_instance = axios.create({
        baseURL: qpmn_admin_order_obj.ajax_url,
        headers: {
            'X-WP-Nonce': qpmn_admin_order_obj.nonce
        }
    });


    Vue.component('qpmn-builder-edit-btn', {
        mixins: [qpmn_order_mixin],
        template: '<button class="btn btn-primary" id="qpmn-builder-edit-btn" v-on:click.stop.prevent="openbuilder">{{btn}}</button>',
        computed: {
            btn: function () {
                return this.__('Edit');
            }
        },
        methods: {
            openbuilder: function (event) {
                //open a specific window to display qpmn builder
                //prepare builder url with fromurl and state 
                window.open(this.getEditUrl(), 'qpmn-order-item-builder');
            },
            getEditUrl: function () {
                let url = new URL(this.$store.state.builderUrl);
                url.searchParams.set('designtemplate', this.$store.state.designConfig);
                url.searchParams.set('designid', this.$store.state.designId);
                url.searchParams.set('state', this.$store.state.state);
                url.searchParams.set('fromurl', this.$store.state.orderPageUrl);
                return url.href;
            }
        }
    });

    Vue.component('qpmn-builder-update-btn', {
        mixins: [qpmn_order_mixin],
        template: '<button class="btn btn-danger" id="qpmn-builder-update-btn" v-on:click.stop.prevent="update">{{btn}}</button>',
        computed: {
            btn: function () {
                return this.__('Update');
            }
        },
        methods: {
            update: function (event) {
                if(confirm(this.updateConfirmBtn())) {
                    let data = {
                        'orderId': this.$store.state.orderId,
                        'itemId':       this.$store.state.itemId,
                        'designId':     this.$store.state.designId,
                        'designConfig': this.$store.state.designConfig,
                        'thumbnail':    this.$store.state.thumbnail,
                    };
                    this.$store.dispatch('updateDesign', data);
                }
            }
        }
    });

    const instanceTemplate = `
        <div v-if="isQPMNOrder">
            {{ this.msgSyncedToQPMN() }}
        </div>            
        <div v-else>
            <div v-if="this.$store.state.isCustomizedDesign" class='qpmn-order-design'>
                <div v-if="this.$store.state.showLoading" class="d-flex justify-content-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only"></span>
                    </div>
                </div>
                <div class="row" v-else>
                    <div class="col"><img width="150" :src='this.$store.state.thumbnail'/></div>
                    <div class="col" v-if="this.$store.state.isDesignUpdated">
                        <qpmn-builder-update-btn></qpmn-builder-update-btn>
                    </div>
                    <div class="col" v-else>
                        <qpmn-builder-edit-btn></qpmn-builder-edit-btn>
                    </div>
                </div>
            </div>
            <div v-else>
                {{ this.msgCustomizedDisabled() }}
            </div>
        </div>
        `;

    qpmn_admin_order_obj.items.forEach(function (item) {
        const qpmn_order_store = new Vuex.Store({
            state: {
                nonce: qpmn_admin_order_obj.nonce,
                showLoading: false,
                orderId: qpmn_admin_order_obj.orderId,
                QPMNOrderId: qpmn_admin_order_obj.QPMNOrderId,
                itemId: item.id,
                designId: item.designId,
                thumbnail: item.designThumbnail,
                designConfig: item.designConfig,
                isDesignUpdated: false,
                isCustomizedDesign: item.isCustomizedDesign,
                builderUrl: item.builderUrl,
                orderPageUrl: qpmn_admin_order_obj.orderPageUrl,
                state: item.state,
            },
            mutations: {
                updateLoading: function (state, payload) {
                    state.showLoading = payload;
                },
                changeThumbnail: function (state, payload) {
                    state.thumbnail = payload;
                },
                changeDesignId: function (state, payload) {
                    state.designId = payload;
                },
                changeDesignConfig: function (state, payload) {
                    state.designConfig = payload;
                },
                setIsEdited: function (state, payload) {
                    state.isDesignUpdated = payload;
                }
            },
            actions: {
                updateDesign: function(context, payload) {
                    context.commit('updateLoading', true);
                    qpmn_axios_instance.post('/wc/ordermeta', payload)
                    .then(function(resp){
                        console.log(resp);
                        alert(resp.data.message);
                        context.commit('setIsEdited', false);
                        context.commit('updateLoading', false);
                    })
                    .catch(function(error){
                        context.commit('updateLoading', false);
                        alert(error.data.message);
                        console.log(error);
                    });

                }
            }

        });

        let ele = '#qpmn-order-item-design-container-' + item.designId;
        new Vue({
            mixins: [qpmn_order_mixin],
            el: ele,
            template: instanceTemplate,
            store: qpmn_order_store,
            computed: {
                isQPMNOrder: function(){
                    let id = this.$store.state.QPMNOrderId;
                    return id && id.length > 0;
                }

            },
            mounted: function () {
                this.init();
            },
        });
    });
};