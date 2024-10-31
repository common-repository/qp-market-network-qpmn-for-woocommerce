const { __, _x, _n, sprintf } = wp.i18n;

const qpmn_axios_instance = axios.create({
    baseURL: qpmn_builder_obj.ajax_url,
    headers: {
        'X-WP-Nonce': qpmn_builder_obj.nonce
    }
});
const qpmn_personalize_product_mixin = {
    methods: {
        __: function (text) {
            console.log('__()');
            return __(text, 'qp-market-network');
        }
    }
};
const qpmn_personalize_product_store = new Vuex.Store({
    state: {
        showLoading: true,
        iframeSrc: '',
        designId: '',
        designConfig: '',
        postId: '',
        thumbnail: '',
        siteUrl: '',
        cartItemKey:'',
        isCustomizationDisabled: false,
        isEditableCartItem: false,
        isDesignUpdated: false,
        nonce: qpmn_builder_obj.nonce,
        originhash: qpmn_builder_obj.originhash
    },
    mutations: {
        changeDesignId: function (state, payload) {
            state.designId = payload;
        },
        isEditableCartItem: function(state, payload){
            state.isEditableCartItem = payload;
        },
        isDesignUpdated: function(state, payload) {
            state.isDesignUpdated = payload;
        },
        changeDesignConfig: function (state, payload) {
            state.designConfig = payload;
        },
        changeThumbnail: function (state, payload) {
            if (!payload || payload.length === 0) {
                return false;
            }
            state.thumbnail = payload;
            let imgUrl = payload;
            var existsELe = jQuery('.woocommerce-product-gallery__image.qpmn-personalize-thumbnail');

            if (existsELe.length) {
                //exists found
                existsELe.remove();
            }
            var targetDom = jQuery('.woocommerce-product-gallery__wrapper');
            var img = '<img src="' + payload + '" class="" alt="" title="" data-caption="" data-src="' + imgUrl + '"' +
                ' data-large_image_width="510" data-large_image_height="510" data-large_image="' + imgUrl + '" srcset="' + imgUrl + '">'
            var slide = '<div data-thumb="' + imgUrl + '" data-thumb-alt="text" class="woocommerce-product-gallery__image qpmn-personalize-thumbnail"><a href="' + imgUrl + '">' + img + '</a></div>';
            jQuery('.woocommerce-product-gallery__image--placeholder').hide();
            targetDom.prepend(slide);
        },
        changeShowLoading: function (state, payload) {
            state.showLoading = payload;
        },
        changeiFrameSrc: function (state, payload) {
            var newIframeSrc = payload || state.iframeSrc;
            var siteUrl = state.siteUrl;
            var newURL = new URL(newIframeSrc, siteUrl);

            //reset query string
            newURL.searchParams.set('state', state.originhash);
            newURL.searchParams.set('fromurl', window.location.href);

            state.iframeSrc = newURL.href;
        },
    },
    actions: {
        displayAddToCart: function(context, display) {

            if (display) {
                jQuery('.single_add_to_cart_button').show();
            } else {
                jQuery('.single_add_to_cart_button').hide();
            }
        },
        updateCart: function(context) {
            context.commit('changeShowLoading', true);
            let form_data = new FormData;
            form_data.append('action', 'update_cart_item_design');
            form_data.append('_ajax_nonce', context.state.nonce);
            //get cart item key
            //pass form with cart item key
            form_data.append('cart_item_key', context.state.cartItemKey);
            form_data.append('design_id', context.state.designId);
            form_data.append('design_config', context.state.designConfig);
            form_data.append('design_thumbnail', context.state.thumbnail);

            qpmn_axios_instance.post('', form_data).then(response => {
                context.commit('changeShowLoading', false);
                //reset update btn
                context.commit('isDesignUpdated', false);
            }).catch(function(error) {
                context.commit('changeShowLoading', false);
            });
        },
        init: function (context, payload) {
            let isDesignUpdated = false;
            let isCustomizationDisabled = jQuery('.product form.cart input[name="is_customization_disabled"]');
            let eleProxyPost = jQuery('.product form.cart input[name="proxypost"]');
            var eleIframeSrc = jQuery('.product form.cart input[name="qp_iframe_src"]');
            let eleDesignId = jQuery('.product form.cart input[name="qp_design_id"]');
            let eleDesignConfig = jQuery('.product form.cart input[name="qp_design_config"]');
            let eleThumbnail = jQuery('.product form.cart input[name="qp_design_thumbnail"]');
            let eleUrl = jQuery('.product form.cart input[name="site_url"]');

            //assign is customization flag
            context.state.isCustomizationDisabled = isCustomizationDisabled == 1;
            //trigger ui handle

            //get data form query string  
            const params = new URLSearchParams(window.location.search);
            let statusCode = params.get('statuscode') ?? null;
            let state = params.get('state') ?? null;
            let image = params.get('image_url') ?? null;
            let cartItemKey = params.get('cart_item_key') ?? null;

            //use form data first
            let designId = eleDesignId.val() ?? null;
            let designConfig = eleDesignConfig.val() ?? null;
            let thumbnail = eleThumbnail.val() ?? null;

            let tmpDesignId = params.get('designid') ?? null;
            //same value with two response data from builder
            let tmpDesignJson = params.get('json') ?? null;
            let tmpDesignTemplate = params.get('designtemplate') ?? null;

            if (tmpDesignId && tmpDesignId.length > 0) {
                //new design id found
                if (context.state.originhash != state) {
                    //origin check failed
                    //redirect to normal product page
                    window.location.href = window.location.origin + window.location.pathname;
                } else {
                    //replace existing id
                    if (tmpDesignId !== designId) {
                        //edited
                        isDesignUpdated = true;
                    } 
                    designId = tmpDesignId;
                }
            }
            if (image && image.length > 0) {
                //thumbnail found
                thumbnail = image;
            }

            //same value with two response data from builder
            if (tmpDesignJson && tmpDesignJson.length > 0) {
                designConfig = tmpDesignJson;
            }

            //same value with two response data from builder
            if (tmpDesignTemplate && tmpDesignTemplate.length > 0) {
                designConfig = tmpDesignTemplate;
            }


            //assign data
            eleDesignId.val(designId);
            eleDesignConfig.val(designConfig)
            eleThumbnail.val(thumbnail);


            context.state.postId = eleProxyPost.val();
            context.state.thumbnail = eleThumbnail.val();
            context.state.siteUrl = eleUrl.val();

            //update
            context.commit('changeDesignId', designId);
            context.commit('changeDesignConfig', designConfig);
            context.commit('changeThumbnail', context.state.thumbnail);

            if (designId && designConfig.length > 0) {
                //design found
                context.commit('isEditableCartItem', true);
            }

            if (cartItemKey && cartItemKey.length > 0) {
                context.state.cartItemKey = cartItemKey;
                //edit design in cart item page only
                context.commit('isDesignUpdated', isDesignUpdated);
                context.dispatch('displayAddToCart', false);
            } else {
                context.dispatch('displayAddToCart', !(!designId || designId === "" || isNaN(designId)));
            }

            //last step - prepare iframe src
            context.commit('changeiFrameSrc', eleIframeSrc.val());
            context.commit('changeShowLoading', false);
        },
    }
});

if (jQuery('#qpmn-personalize-product').length) {

    //function: control and determine buttons display
    //provide init data 
    //determine produjct is configurable
    const qpmn_personalize_product = new Vue({
        mixins: [qpmn_personalize_product_mixin],
        el: '#qpmn-personalize-product',
        store: qpmn_personalize_product_store,
        template: `
    <div class="qpmn-public qpmn-bootstrap" id='qpmn-personalize-product'>
        <div v-if="showLoading" class="btn">
            <div class="spinner-border" role="status">
                <span class="sr-only">...</span>
            </div>
        </div>
        <div v-if="!showLoading" class="qpmn-personalize-product-container">
            <qpmn-builder-custom-btn v-if="!designed" ></qpmn-builder-custom-btn>
            <div v-if="isEdited">
                <qpmn-builder-update-btn></qpmn-builder-update-btn>
            </div>
            <div v-else>
                <qpmn-builder-edit-btn  v-if="editable"></qpmn-builder-edit-btn>
            </div>
        </div>
    </div>
    `,
        computed: {
            designed: {
                get() {
                    return qpmn_personalize_product_store.state.designId.length > 0;
                }
            },
            editable: {
                get() {
                    return qpmn_personalize_product_store.state.isEditableCartItem;
                }
            },
            isEdited: {
                get() {
                    return qpmn_personalize_product_store.state.isDesignUpdated;
                }
            },
            showLoading: {
                set(val) {
                    qpmn_personalize_product_store.commit('changeShowLoading', val);
                },
                get() {
                    return qpmn_personalize_product_store.state.showLoading;
                }
            },
        },
        mounted: function () {
            this.$nextTick(function () {
                qpmn_personalize_product_store.dispatch('init');
            });
        }
    });
}

Vue.component('qpmn-builder-custom-btn', {
    mixins: [qpmn_personalize_product_mixin],
    template: '<button v-if="!isDisabled()" id="qpmn-builder-custom-btn" v-on:click.stop.prevent="openbuilder">{{customize}}</button>',
    computed: {
        customize: function () {
            return this.__('Customize');
        }
    },
    methods: {
        isDisabled: function(){
            return qpmn_personalize_product_store.state.isCustomizationDisabled;
        },
        openbuilder: function (event) {
            //open a specific window to display qpmn builder
            //prepare builder url with fromurl and state 
            window.open(qpmn_personalize_product_store.state.iframeSrc, 'qpmn-builder');
        }
    }
});

Vue.component('qpmn-builder-edit-btn', {
    mixins: [qpmn_personalize_product_mixin],
    template: '<button id="qpmn-builder-edit-btn" v-on:click.stop.prevent="openbuilder">{{btn}}</button>',
    computed: {
        btn: function () {
            return this.__('Edit');
        }
    },
    methods: {
        openbuilder: function (event) {
            //open a specific window to display qpmn builder
            //prepare builder url with fromurl and state 
            window.open(this.getEditUrl(), 'qpmn-builder');
        },
        getEditUrl: function() {
            let url = new URL(qpmn_personalize_product_store.state.iframeSrc);
            let json = qpmn_personalize_product_store.state.designConfig;
            url.searchParams.set('designtemplate', json);
            return url.href;
        }
    }
});

Vue.component('qpmn-builder-update-btn', {
    mixins: [qpmn_personalize_product_mixin],
    template: '<button id="qpmn-builder-update-btn" v-on:click.stop.prevent="update">{{btn}}</button>',
    computed: {
        btn: function () {
            return this.__('Update');
        }
    },
    methods: {
        update: function (event) {
            qpmn_personalize_product_store.dispatch('updateCart');
        }
    }
});