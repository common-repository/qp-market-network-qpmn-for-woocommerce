<?php

use QPMN\Partner\Qpmn_i18n;

?>
<div class="wrap qpmn-admin qpmn-bootstrap">
    <form method="POST" action="">
        <div class="container">
            <div id="app">
                <alert :show-alert="showAlert" :alert-message="alertMessage" :alert-state="alertState"></alert>
                <step-navigation :steps="steps">
                </step-navigation>

                <div v-if="showLoading" class="d-flex justify-content-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only"><?php Qpmn_i18n::_e('Loading') ?>...</span>
                    </div>
                </div>
                <div v-if="!showLoading">
                    <div v-show="isCurrentStep(1)">
                        <div v-if="!secretLoggedIn">
                        </div>
                        <div v-else>
                            <div v-if="partnerLoggedIn">
                                <p><?php Qpmn_i18n::_e('Logged in QPMN as') ?> {{ partnerName }}</p>
                                <a class="button" @click="logout"><?php Qpmn_i18n::_e('Logout') ?></a>
                            </div>
                        </div>
                        <div v-if="!partnerLoggedIn">
                            <div v-if="showPartnerLoginURL">
                                <div>
                                    <a class="btn btn-success" @click="loginQPMN"><?php Qpmn_i18n::_e('Connect to QPMN') ?></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-show="isCurrentStep(2)">
                        <div class="form-group">
                            <div v-if="products.length">
                                <label for="select"><?php Qpmn_i18n::_e('Select a default Product') ?></label>
                                <div class="d-flex flex-wrap">
                                    <div v-for="product in products">
                                        <product-card-item :product='product' roduct-card-item>
                                    </div>
                                </div>
                                <create-product></create-product>
                            </div>
                            <div v-else>please connect to QPMN to get product list</div>


                        </div>
                    </div>

                    <div v-show="isCurrentStep(3)">
                        <div class="form-group">
                            <fieldset>
                                <legend><?php Qpmn_i18n::_e('Order Update Schedule') ?></legend>
                                <div v-if="nextSchedule"><?php Qpmn_i18n::_e('Next scheduled') ?> {{ nextSchedule }}</div>
                                <div v-for="(option, key) in scheduleOptions" class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="updateSchedule" v-model="schedule" :value="key" :id="key">
                                    <label class="form-check-label" :for="key">{{ option }}</label>
                                </div>
                            </fieldset>
                        </div>
                        <div class="form-group">
                            <fieldset>
                                <legend><?php Qpmn_i18n::_e('Debug mode') ?></legend>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="debug" v-model="debug" value="true" id="debug-true">
                                    <label class="form-check-label" for="debug-true"> <?php Qpmn_i18n::_e('Enable') ?> </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="debug" v-model="debug" value="false" id="debug-false">
                                    <label class="form-check-label" for="debug-false"> <?php Qpmn_i18n::_e('Disable') ?> </label>
                                </div>
                                <div v-if="debug == 'true'">
                                    <div><?php Qpmn_i18n::_e('Click') ?> <a href="<?php echo admin_url("admin.php?page=qpmn_options_logs"); ?>"><?php Qpmn_i18n::_e('here'); ?></a> <?php Qpmn_i18n::_e('to view debug logs') ?>.</div>
                                </div>
                            </fieldset>
                        </div>
                        <a class="button mt-4" @click="updateSettings"><?php Qpmn_i18n::_e('Update Settings') ?></a>
                    </div>

                    <!-- <step v-for="step in steps" :currentstep="currentstep" :key="step.id" :step="step" :stepcount="steps.length" @step-change="stepChanged"> -->
                    <step v-for="step in steps" :key="step.id" :step="step" :stepcount="steps.length">
                    </step>
                </div>

            </div>
        </div>
    </form>
</div>
<script type="x-template" id="product-card-item-template">
    <div class="p-2">
        <img v-bind:class="[
                {
                    'rounded-circle': this.isSelected(), 
                    'border-5': this.isSelected(), 
                    'rounded': !this.isSelected()
                }
            ]" class="m-1 img-thumbnail border" width="100px" height="auto" 
            v-show="this.thumbnail.length" 
            :src="this.thumbnail" 
            v-on:click="changeProduct"
        />
        <p class="text-truncate" style="max-width: 150px;"> {{ this.name }}</p>
    </div>
</script>

<script type="x-template" id="create-product-template">
    <div v-show="isSelected(productDetail.id)" >
        <div v-if="isTemplate()" class="row mb-3">
            <div class="input-group">
                <div v-if="isTemplateCreated()" class="text-center">
                    <img style="max-width: 500px;" class="img-fluid rounded mx-auto d-block" alt="product template" :src="templateThumbnail"></img>
                </div>
                <a v-else @click="createTemplate" class="btn btn-primary">Create your own template</a>
            </div">
        </div>
        <div class="row">
            <div class="col-lg-6 col-md-12">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="product-name-addon"><?php Qpmn_i18n::_e('Product Name') ?></span>
                    </div>
                    <input type="text" v-model="productName" class="form-control" placeholder="<?php Qpmn_i18n::_e('Product Name') ?>" aria-label="product name" aria-describedby="product-name-addon">
                </div>
            </div>
        </div>
        <div class="row">
            <div v-if="productDetail.categories !== null"  class="col-6">
                <legend class="col-form-label col-sm-2 pt-0"><?php Qpmn_i18n::_e('Category') ?></legend>
                <template v-for="(category, index) in productDetail.categories">
                    <div class="form-check">
                        <input 
                            autocomplete="off" 
                            style="margin-top: 0.25em;" 
                            class="" 
                            type="checkbox" 
                            name="categories[]" 
                            :id="'cat-' + index" 
                            :value="category"
                            v-model="checkedCategories"
                        />
                        <label class="form-check-label" :for="'cat-' + index">{{category}}</label>
                    </div>
                </template>
            </div>
            <div v-if="productDetail.tags !== null"  class="col-sm-4">
                <legend class="col-form-label col-sm-2 pt-0"><?php Qpmn_i18n::_e('Tags') ?></legend>
                <div>
                    <div v-for="(tag, index) in productDetail.tags" class="form-check">
                        <input 
                            autocomplete="off" style="margin-top: 0.25em;" 
                            class="" type="checkbox" 
                            name="tags[]" 
                            :id="'tag-' + index" 
                            :value="tag"
                            v-model="checkedTags"
                            >
                        <label class="form-check-label" :for="'tag-' + index">{{ tag }}</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="input-group mb-3">
                <a v-if="productDetail.key!=0" class="btn btn-success button mt-2" @click="createProduct"><?php Qpmn_i18n::_e('Create Product') ?></a>
                <div
                    id="disable-customization-container"
                    v-if="isDesignIdFound()"
                >
                    <input
                        autocomplete="off"
                        style=""
                        type="checkbox"
                        id="checkbox-disable-customization"
                        v-model="disableCustomization"
                    />
                    <label class="h4" for="checkbox-disable-customization">Disable Customization</label>                        
                </div>
            </div>
        </div>
    </div>
</script>

<script type="x-template" id="step-navigation-template">
    <ol class="step-indicator">
        <li v-for="step in steps" is="step-navigation-step" :key="step.id" :step="step" :currentstep="currentstep">
        </li>
    </ol>
</script>

<script type="x-template" id="step-navigation-step-template">
    <li :class="indicatorclass" v-on:click="goto(step.id)" >
        <div class="step"><i :class="step.icon_class"></i></div>
        <div class="caption hidden-xs hidden-sm"><?php Qpmn_i18n::_e('Step') ?> <span v-text="step.id"></span>: <span v-text="step.title"></span></div>
    </li>
</script>

<script type="x-template" id="step-template">
    <div class="step-wrapper" :class="stepWrapperClass">
        <button type="button" class="btn btn-primary" @click="lastStep" :disabled="firststep"><?php Qpmn_i18n::_e('Back') ?></button>
        <button type="button" class="btn btn-primary" @click="nextStep" :disabled="laststep"><?php Qpmn_i18n::_e('Next') ?></button>
    </div>
</script>
<script type="x-template" id="alert-template">
    <div v-show="showAlert" class="alert alert-success" :class="classObj" role="alert">
        {{ alertMessage }}
    </div>
</script>

<script>
    Vue.component("step-navigation-step", {
        template: "#step-navigation-step-template",

        props: ["step"],

        computed: {
            currentstep() {
                return store.state.currentstep;
            },
            indicatorclass() {
                return {
                    active: this.step.id == this.currentstep,
                    complete: this.currentstep > this.step.id
                };
            }
        },

        methods: {
            goto(step) {
                store.commit('gotoStep', step);
            }
        }
    });

    Vue.component("alert", {
        template: "#alert-template",
        props: ['showAlert', 'alertMessage', 'alertState'],
        computed: {
            classObj: function() {
                return {
                    'alert-success': this.alertState === 'success',
                    'alert-danger': this.alertState === 'danger'
                };
            }
        }
    });

    Vue.component("step-navigation", {
        template: "#step-navigation-template",

        //props: ["steps", "currentstep"],
        props: ["steps"],
        computed: {
            currentstep() {
                return store.state.currentstep;
            },
        }
    });

    Vue.component("step", {
        template: "#step-template",

        props: ["step", "stepcount"],

        computed: {
            currentstep() {
                return store.state.currentstep;
            },
            active() {
                return this.step.id == this.currentstep;
            },

            firststep() {
                return this.currentstep == 1;
            },
            laststep() {
                return this.currentstep == this.stepcount;
            },
            stepWrapperClass() {
                return {
                    active: this.active
                };
            }
        },

        methods: {
            nextStep() {
                store.commit('gotoStep', this.currentstep + 1);
            },

            lastStep() {
                store.commit('gotoStep', this.currentstep - 1);
            },
        }
    });

    Vue.component("create-product", {
        template: "#create-product-template",
        computed: {
            productDetail: {
                set(val) {
                    store.commit('setSelectedProductDetail', val);
                },
                get() {
                    return store.state.selectedProductDetail;
                }
            },
            templateThumbnail: {
                set(val) {
                    store.commit('changeTemplateThumbnail', val);
                },
                get() {
                    return store.state.templateThumbnail;
                }
            },
            productName: {
                set(val) {
                    store.commit('changeProductName', val);
                },
                get() {
                    return store.state.productName;
                }
            },
            checkedCategories: {
                set(val) {
                    store.commit('updateCheckedCategories', val);
                },
                get() {
                    return store.state.checkedCategories;
                }
            },
            checkedTags: {
                set(val) {
                    store.commit('updateCheckedTags', val);
                },
                get() {
                    return store.state.checkedTags;
                }
            },
            productTemplate: {
                set(val) {
                    store.commit('updateCreatedProductTemplate', val);
                },
                get() {
                    return store.state.createdProductTemplate;
                }
            },
            productDesignId: {
                set(val) {
                    store.commit('updateCreatedProductDesignId', val);
                },
                get() {
                    return store.state.createdProductDesignId;
                }
            },
            disableCustomization: {
                set(val) {
                    store.commit('updateDisableCustomization', val);
                },
                get() {
                    return store.state.disableCustomization;
                }
            }

        },
        methods: {
            isSelected: function(product) {
                return store.state.selectedProduct != "" && store.state.selectedProduct == this.productDetail.id;
            },
            isDesignIdFound: function() {
                let designId = store.state.createdProductDesignId;
                return designId && designId.length > 0;
            },
            isTemplate: function() {
                return this.productDetail.template_url.trim().length > 0;
            },
            isTemplateCreated: function() {
                return this.productTemplate.length > 0;
            },
            createTemplate: function() {
                let url = new URL(store.state.pageUrl);
                //prepare template params
                url.searchParams.set('step', 2);
                url.searchParams.set('state', store.state.nonce);
                url.searchParams.set('productk', store.state.selectedProduct);

                builder = new URL(this.productDetail.template_url);
                builder.searchParams.set('state', store.state.nonce);
                builder.searchParams.set('fromurl', url.href);

                window.document.location = builder;
            },
            editTemplate: function() {
                let url = new URL(window.location.href);
                //prepare template params
                url.searchParams.set('step', 2);
                url.searchParams.set('state', store.state.nonce);
                url.searchParams.set('productk', store.state.selectedProduct);

                builder = new URL(this.productDetail.template_url);
                builder.searchParams.set('state', store.state.nonce);
                builder.searchParams.set('fromurl', url.href);

                window.document.location = builder.href;
            },
            createProduct: function() {
                store.dispatch('createProduct', {
                    id: this.productDetail.id,
                    builder: this.productDetail.builder_url,
                    images: this.productDetail.images,
                    description: this.productDetail.description,
                    shortDescription: this.productDetail.short_description,
                    productName: this.productName,
                    categories: this.checkedCategories,
                    tags: this.checkedTags,
                    templateThumbnail: this.templateThumbnail,
                    template: this.productTemplate,
                    designId: this.productDesignId,
                    disableCustomization: this.disableCustomization
                });
            },
        },
        created: function() {}

    });

    Vue.component("product-card-item", {
        template: "#product-card-item-template",
        props: ['product'],
        data() {
            return {
                name: this.product.name,
                thumbnail: this.product.thumbnail,
                id: this.product.id,
                activeClass: 'rounded-circle',
                inactiveClass: 'rounded'
            };

        },
        computed: {
            productDetail: function() {
                return store.state.selectedProductDetail;
            },
        },
        methods: {
            isSelected: function() {
                return store.state.selectedProduct != "" && store.state.selectedProduct == this.id;
            },
            changeProduct: function() {
                if (store.state.selectedProduct != this.id) {
                    if (store.state.createdProductTemplate.length > 0) {
                        let url = new URL(store.state.pageUrl);
                        //prepare template params
                        url.searchParams.set('step', 2);
                        url.searchParams.set('state', store.state.nonce);
                        url.searchParams.set('productk', this.id);
                        //redirect to new product page
                        window.location.href = url.href;
                        return false;
                    }

                    store.commit('changeProduct', this.id);
                    store.commit('changeProductName', this.name);
                }
            }
        }
    });

    var instance = axios.create({
        baseURL: qpmn_admin_obj.ajax_url,
        headers: {
            'X-WP-Nonce': qpmn_admin_obj.nonce
        }
    });
    var mixin = {
        methods: {}
    };

    Vue.use(Vuex);

    const store = new Vuex.Store({
        state: {
            pageUrl: qpmn_admin_obj.page_url,
            currentstep: 0,
            selectedProduct: 0,
            selectedProductDetail: {
                'id': '',
                'name': '',
                'description': '',
                'short_description': '',
                'images': [],
                'categories': [],
                'tags': [],
                'updated_at': '',
                'template_url': '',
                'builder_url': ''
            },
            nonce: qpmn_admin_obj.nonce,
            //disable secret logged in 
            secretLoggedIn: true,
            partnerLoggedIn: qpmn_admin_obj.partner_loggedin,
            partnerLoginUrl: qpmn_admin_obj.partner_login_url,
            partnerName: qpmn_admin_obj.partner_name,
            checkedCategories: [],
            checkedTags: [],
            debug: qpmn_admin_obj.config.config.debug,
            schedule: qpmn_admin_obj.config.config.schedule,
            nextSchedule: qpmn_admin_obj.config.nextSchedule,
            showAlert: false,
            showAlertState: 'success',
            alertMessage: '',
            showLoading: false,
            createdProductTemplate: '',
            createdProductDesignId: '',
            disableCustomization: false,
            productName: '',
            templateThumbnail: '',
            products: [],
            productDetailList: {}
        },
        mutations: {
            setSelectedProductDetail: function(state, payload) {
                state.selectedProductDetail = payload;
                store.commit('changeProductName', payload.name);
            },
            setProductDetailList: function(state, payload) {
                state.productDetailList = payload;
            },
            setProducts: function(state, payload) {
                state.products = payload;
            },
            changeTemplateThumbnail: function(state, payload) {
                state.templateThumbnail = payload;
            },
            changeProductName: function(state, payload) {
                state.productName = payload;
            },
            changeShowLoading: function(state, payload) {
                state.showLoading = payload;
            },
            gotoStep(state, step) {
                if (state.currentstep != step) {
                    // let currentURL = new URL(window.location.href);
                    // currentURL.searchParams.set('step', step);
                    // window.history.pushState({}, '', currentURL);
                    state.currentstep = step;
                }
            },
            logout: function(state, payload) {
                state.secretLoggedIn = true;
                state.partnerLoggedIn = payload.partner_verified;
                state.partnerLoginUrl = payload.partner_login_url;
            },
            changeProduct(state, productKey) {
                store.commit('clearChecked');
                state.selectedProduct = productKey;
                //find and assign product detail
                if (state.productDetailList.hasOwnProperty(productKey)) {
                    store.commit('setSelectedProductDetail', state.productDetailList[productKey]);
                } else {
                    //get product detail from api
                    store.dispatch('getProductDetail');
                }
            },
            clearChecked(state) {
                state.checkedCategories = [];
                state.checkedTags = [];
                state.createdProductTemplate = '';
                state.templateThumbnail = '';
            },
            changeAlert(state, payload) {

                state.showAlert = payload.show;
                state.showAlertState = payload.state;
                state.alertMessage = payload.msg;
            },
            clearAlert(state, payload) {
                state.showAlert = false;
                state.showAlertState = 'success';
                state.alertMessage = '';
            },
            changeShowAlert(state, show) {
                state.showAlert = show;
            },
            changeShowAlertState(state, s) {
                state.showAlertState = s;
            },
            changeAlertMsg(state, msg) {
                state.alertMessage = msg;
            },
            updateCheckedCategories(state, categories) {
                state.checkedCategories = categories;
            },
            updateCheckedTags(state, tags) {
                state.checkedTags = tags;
            },
            updateCreatedProductTemplate(state, template) {
                state.createdProductTemplate = template;
            },
            updateCreatedProductDesignId(state, designId) {
                state.createdProductDesignId = designId;
            },
            updateDisableCustomization(state, disableCustomization) {
                state.disableCustomization = disableCustomization;
            },
            setSchedule(state, schedule) {
                state.schedule = schedule;
            },
            setDebug(state, debug) {
                state.debug = debug;
            },
            updateNextSchedule(state, schedule) {
                state.nextSchedule = schedule;
            }
        },
        actions: {
            logout: function(context) {
                context.commit('changeShowLoading', true);
                instance.get('qpmn/account/logout')
                    .then(response => {
                        if (!response.data.partner_verified) {
                            context.commit('logout', response.data);
                        }
                        context.commit('clearAlert');
                        context.commit('changeShowLoading', false);

                    }).catch(function(error) {
                        context.commit('changeAlert', {
                            'show': true,
                            'state': 'danger',
                            'msg': error.response.data.message
                        });
                        context.commit('changeShowLoading', false);
                    }).then(function() {
                        context.commit('changeShowLoading', false);
                    });
            },
            createProduct: function(context, payload) {
                context.commit('changeShowLoading', true);
                context.commit('clearAlert');
                instance.post('wc/product/', payload)
                    .then(response => {
                        context.commit('clearAlert');
                        context.commit('changeAlert', {
                            'show': true,
                            'state': 'success',
                            'msg': 'Successful!'
                        });
                        context.commit('changeShowLoading', false);
                    }).catch(function(error) {
                        var errorMsg = error.message;
                        var data = error.response.data || null;
                        if (data) {
                            errorMsg = data.message;
                        }
                        context.commit('changeAlert', {
                            'show': true,
                            'state': 'danger',
                            'msg': error.response.data.message
                        });
                        context.commit('changeShowLoading', false);
                    }).then(function() {
                        context.commit('changeShowLoading', false);
                    });
            },
            getProducts: function(context, payload) {
                context.commit('changeShowLoading', true);
                context.commit('clearAlert');
                instance.get('qpmn/product')
                    .then(response => {
                        console.log(response);
                        if (response.data) {
                            context.commit('setProducts', response.data.data);
                        }
                        context.commit('clearAlert');
                        context.commit('changeAlert', {
                            'show': true,
                            'state': 'success',
                            'msg': 'Successful!'
                        });
                        context.commit('changeShowLoading', false);
                    }).catch(function(error) {
                        var errorMsg = error.message;
                        var data = error.response.data || null;
                        if (data) {
                            errorMsg = data.message;
                        }
                        context.commit('changeAlert', {
                            'show': true,
                            'state': 'danger',
                            'msg': error.response.data.message
                        });
                        context.commit('changeShowLoading', false);
                    }).then(function() {
                        context.commit('changeShowLoading', false);
                    });
            },
            getProductDetail: function(context, payload) {
                context.commit('changeShowLoading', true);
                context.commit('clearAlert');
                instance.get('qpmn/product/' + context.state.selectedProduct)
                    .then(response => {
                        console.log(response);
                        if (response.data) {
                            context.commit('setSelectedProductDetail', response.data.data);
                            let productDetailList = context.state.productDetailList;
                            //update product detail list
                            productDetailList[response.data.data.id] = response.data.data;
                            context.commit('setProductDetailList', productDetailList);
                        }
                        context.commit('clearAlert');
                        context.commit('changeAlert', {
                            'show': true,
                            'state': 'success',
                            'msg': 'Successful!'
                        });
                        context.commit('changeShowLoading', false);
                    }).catch(function(error) {
                        var errorMsg = error.message;
                        var data = error.response.data || null;
                        if (data) {
                            errorMsg = data.message;
                        }
                        context.commit('changeAlert', {
                            'show': true,
                            'state': 'danger',
                            'msg': error.response.data.message
                        });
                        context.commit('changeShowLoading', false);
                    }).then(function() {
                        context.commit('changeShowLoading', false);
                    });
            },
            updateSettings: function(context, payload) {
                context.commit('changeShowLoading', true);
                instance.put('wc/setting/', payload)
                    .then(response => {
                        if (response.data.hasOwnProperty('nextSchedule')) {
                            context.commit('updateNextSchedule', response.data.nextSchedule);
                        }
                        if (response.data.debug) {
                            context.commit('setDebug', response.data.debug);
                        }
                        if (response.data.schedule) {
                            context.commit('setSchedule', response.data.schedule);
                        }

                        context.commit('clearAlert');
                        context.commit('changeShowLoading', false);
                    }).catch(function(error) {
                        context.commit('changeAlert', {
                            'show': true,
                            'state': 'danger',
                            'msg': error.response.data.message
                        });
                        context.commit('changeShowLoading', false);
                    }).then(function() {
                        context.commit('changeShowLoading', false);
                    });
            }
        }

    });

    new Vue({
        el: "#app",
        mixins: [mixin],
        store: store,
        data: {
            qppp: qpmn_admin_obj,
            partnerName: store.state.partnerName,
            // products: qpmn_admin_obj.products,
            // productDetails: qpmn_admin_obj.productDetails,
            scheduleOptions: qpmn_admin_obj.config.scheduleOptions,
            // category: '',
            // tag: '',
            // productName: '',
            steps: [{
                    id: 1,
                    title: '<?php Qpmn_i18n::_e("Account") ?>',
                    icon_class: "fa fa-user-circle-o"
                },
                {
                    id: 2,
                    title: '<?php Qpmn_i18n::_e("Product") ?>',
                    icon_class: "fa fa-shopping-bag"
                },
                {
                    id: 3,
                    title: '<?php Qpmn_i18n::_e("Settings") ?>',
                    icon_class: "fa fa-cogs"
                }
            ]
        },
        computed: {
            productDetails: {
                set(val) {
                    store.commit('setProductDetailList', val);
                },
                get() {
                    return store.state.productDetailList;
                }
            },
            products: {
                set(val) {
                    store.commit('setProducts', val);
                },
                get() {
                    return store.state.products;
                }
            },
            showLoading: {
                set(val) {
                    store.commit('changeShowLoading', val);
                },
                get() {
                    return store.state.showLoading;
                }
            },
            showAlert: {
                set(val) {
                    store.commit('changeShowAlert', val);
                },
                get() {
                    return store.state.showAlert;
                }
            },
            alertState: {
                set(val) {
                    store.commit('changeShowAlertState', val);
                },
                get() {
                    return store.state.showAlertState;
                }
            },
            alertMessage: {
                set(val) {
                    store.commit('changeAlertMsg', val);
                },
                get() {
                    return store.state.alertMessage;
                }
            },
            selectedProduct: {
                set(val) {
                    store.commit('changeProduct', val);
                },
                get() {
                    return store.state.selectedProduct;
                }
            },
            schedule: {
                set(val) {
                    store.commit('setSchedule', val);
                },
                get() {
                    return store.state.schedule;
                }
            },
            nextSchedule: {
                set(val) {
                    store.commit('updateNextSchedule', val);
                },
                get() {
                    return store.state.nextSchedule;
                }
            },
            debug: {
                set(val) {
                    store.commit('setDebug', val);
                },
                get() {
                    return store.state.debug;
                }
            },
            currentstep: function() {
                return store.state.currentstep;
            },
            secretLoggedIn: function() {
                return store.state.secretLoggedIn;
            },
            partnerLoggedIn: function() {
                return store.state.partnerLoggedIn;
            },
            showPartnerLoginURL: function() {
                //partner not verified and login url found
                return store.state.secretLoggedIn &&
                    store.state.partnerLoggedIn == false &&
                    store.state.partnerLoginUrl.length > 0;
            },
        },
        methods: {
            init: function() {
                let url = new URL(window.location.href);
                let step = url.searchParams.get('step');
                let productKey = url.searchParams.get('productk');
                let templateThumbnail = url.searchParams.get('image_url');
                let templateId = url.searchParams.get('designtemplate');
                let designId = url.searchParams.get('designid');

                //which step
                initStep = parseInt(step);
                if (!initStep) {
                    initStep = 1;
                }

                if (store.state.partnerLoggedIn) {
                    store.dispatch('getProducts');
                }
                store.commit('gotoStep', initStep);

                //step 2 = create a productd
                if (productKey) {
                    //select product
                    store.commit('changeProduct', productKey);
                } else {
                    //select default product - first product
                }

                if (templateThumbnail) {
                    templateThumbnail = decodeURIComponent(templateThumbnail);
                    store.commit('changeTemplateThumbnail', templateThumbnail);
                }

                //store created template
                if (templateId) {
                    templateId = templateId.trim();
                    if (templateId.length > 0) {
                        store.commit('updateCreatedProductTemplate', templateId);
                    }
                }
                if (designId) {
                    designId = designId.trim();
                    if (designId.length > 0) {
                        store.commit('updateCreatedProductDesignId', designId);
                    }
                }
            },
            isCurrentStep: function(step) {
                return store.state.currentstep === step;
            },
            stepChanged: function(step) {
                store.commit('gotoStep', step);
            },
            loginQPMN: function() {
                window.location.href = store.state.partnerLoginUrl;
            },
            logout: function() {
                store.dispatch('logout');
            },
            updateSettings: function() {
                store.dispatch('updateSettings', {
                    schedule: this.schedule,
                    debug: this.debug
                });
            },
            selectedProductOnChange: function(event) {
                store.commit('changeProduct', event.target.value);
            },
            confirmLeaving: function(event) {
                if (store.state.currentstep == 2 && store.state.createdProductTemplate.length > 0) {
                    //warning when you are in create product page and product template created
                    //most of browser doesnt allow cusotm message in the current day
                    return event.returnValue = true;
                }
            }
        },
        created: function() {
            window.addEventListener('beforeunload', this.confirmLeaving);
        },
        mounted: function() {
            this.init();
        },
    });
</script>

<style>
    @import 'https://fonts.googleapis.com/css?family=Roboto';

    body {
        padding: 0;
        margin: 0;
        background-color: #fff;
        font-family: "Roboto", sans-serif;
    }

    .step-wrapper {
        padding: 20px 0;
        display: none;
    }

    .step-wrapper.active {
        display: block;
    }

    .step-indicator {
        border-collapse: separate;
        display: table;
        margin-left: 0px;
        position: relative;
        table-layout: fixed;
        text-align: center;
        vertical-align: middle;
        padding-left: 0;
        padding-top: 20px;
    }

    .step-indicator li {
        display: table-cell;
        position: relative;
        float: none;
        padding: 0;
        width: 1%;
    }

    .step-indicator li:after {
        background-color: #ccc;
        content: "";
        display: block;
        height: 1px;
        position: absolute;
        width: 100%;
        top: 32px;
    }

    .step-indicator li:after {
        left: 50%;
    }

    .step-indicator li:last-child:after {
        display: none;
    }

    .step-indicator li.active .step {
        border-color: #4183D7;
        color: #4183D7;
    }

    .step-indicator li.active .caption {
        color: #4183D7;
    }

    .step-indicator li.complete:after {
        background-color: #87D37C;
    }

    .step-indicator li.complete .step {
        border-color: #87D37C;
        color: #87D37C;
    }

    .step-indicator li.complete .caption {
        color: #87D37C;
    }

    .step-indicator .step {
        background-color: #fff;
        border-radius: 50%;
        border: 1px solid #ccc;
        color: #ccc;
        font-size: 24px;
        height: 64px;
        line-height: 64px;
        margin: 0 auto;
        position: relative;
        width: 64px;
        z-index: 1;
    }

    .step-indicator .step:hover {
        cursor: pointer;
    }

    .step-indicator .caption {
        color: #ccc;
        padding: 11px 16px;
    }
    #disable-customization-container {
        margin-top: 1em;
        margin-left: 1em;
    }
    #disable-customization-container > label{
        margin: auto;
    }
</style>